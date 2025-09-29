<?php
session_start();
require_once 'db_connect.php';
require_once __DIR__ . '/dompdf-3.1.0/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Vérifier si l'utilisateur est connecté
$agent_conn = $_SESSION['user_id'] ?? null;
if (!$agent_conn) {
    header("Location: login.php"); // Rediriger vers la page de connexion
    exit();
}

// Récupérer le nom du responsable connecté
$responsable = '—'; // Valeur par défaut
try {
    $stmt = $pdo->prepare("SELECT CONCAT(nom, ' ', prenom) AS nom_prenom FROM agent WHERE id = :agent_id");
    $stmt->execute(['agent_id' => $agent_conn]);
    $res_nom = $stmt->fetchColumn();
    if ($res_nom) {
        $responsable = $res_nom;
    }
} catch (PDOException $e) {
    error_log("Erreur récupération nom responsable : " . $e->getMessage());
}

// Requête pour récupérer les absences longues
$sql = "
   SELECT a.id AS agent_id,
          CONCAT(a.nom,' ',a.prenom) as nom_prenom,
          a.telephone as telephone,
          b.libele AS bureau,
          s.libele AS service,
          MAX(p.date) AS derniere_presence,
          DATEDIFF(CURDATE(), MAX(p.date)) AS nb_jours_absence_estimee 
   FROM agent a
   LEFT JOIN presence p ON a.id = p.agent_id 
   LEFT JOIN absence ab ON a.id = ab.agent_id 
        AND DATEDIFF(ab.date_fin, ab.date_debut) + 1 >= 15 
        AND ab.date_fin >= CURDATE() - INTERVAL 30 DAY 
   LEFT JOIN bureau b ON a.bureau_id = b.id 
   LEFT JOIN service s ON b.service_id = s.id
   WHERE ab.id IS NULL 
   GROUP BY a.id, a.nom, a.prenom, a.telephone, b.libele, s.libele 
   HAVING MAX(p.date) IS NULL OR MAX(p.date) < CURDATE()
   ORDER BY nb_jours_absence_estimee DESC, a.nom ASC
";
$agents_absences = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// -------------------
// Génération du HTML professionnel
// -------------------
$current_date = date('d/m/Y à H:i');
$total_agents = count($agents_absences);

// Calcul des statistiques
$stats = [
    'absence_critique' => 0,  // > 30 jours
    'absence_longue' => 0,    // 15-30 jours
    'absence_moyenne' => 0    // 7-14 jours
];

foreach ($agents_absences as $agent) {
    $jours = $agent['nb_jours_absence_estimee'];
    if ($jours > 30) $stats['absence_critique']++;
    elseif ($jours >= 15) $stats['absence_longue']++;
    elseif ($jours >= 7) $stats['absence_moyenne']++;
}

$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport des Agents en Longue Absence</title>
<style>
@page { margin: 20mm; }

body { 
font-family: "Segoe UI", "DejaVu Sans", Arial, sans-serif; 
font-size: 11px; 
line-height: 1.4; 
color: #333;
 margin: 0; 
 padding: 0;
  background: #fff;
   padding-bottom: 50px;
   }

.header { 
border-bottom: 1px solid gray;
 padding-bottom: 15px; 
 margin-bottom: 25px;
  position: relative;

  }
.header-top { 
display: flex; 
justify-content: space-between; 
align-items: flex-start;
 margin-bottom: 10px;
  }

.logo-section {
 display: flex;
 align-items: center; 
 }

.flag-stripes { 
width: 8px;
 height: 60px; margin-right: 15px; 
 display: flex; 
 flex-direction: column;
  border-radius: 2px; 
  overflow: hidden; 
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
   }

.stripe { 
flex: 1;
 }

.stripe.green {
 background: #228B22; 
 }

.stripe.yellow { 
background: #FFD700; 
}

.stripe.red {
 background: #DC143C; 
 }

.ministry-info { 
flex: 1; 
}

.ministry-title {
 font-size: 12px; 
font-weight: bold; 
color: black; 
margin: 0; 
text-transform: uppercase;
 letter-spacing: 0.5px;
  }

.ministry-subtitle { 
text-transform: uppercase; 
font-size: 12px; 
font-weight: bold;
 color: black; 
 letter-spacing: 0.5px; 
 margin: 0;
  }

.national-motto { 
font-size: 12px; 
font-weight: bold; 
color: black; 
font-style: italic; 
text-align: right;
 }

.report-info {
 background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 15px;
   border-radius: 8px;
    margin-bottom: 25px; 
    }

.report-title {
 font-size: 16px; 
font-weight: bold; 
color: black; 
margin: 0 0 10px 0;
 text-align: center; 
 text-transform: uppercase; 
 letter-spacing: 1px; 
 }

.report-meta { 
display: flex; 
text-align: center;
 justify-content: space-between; 
 font-size: 10px;
  color: #666;
   margin-top: 10px; 
   }

.stats-grid { 
display: flex;
 flex-direction: row; 
 justify-content: space-between; 
 gap: 10px;
  margin: 20px 0; 
  }

.stat-card {
 background: white;
 padding: 12px; 
 border-radius: 8px; 
 text-align: center;
  flex: 1; 
  min-width: 100px; 
  border: 1px solid #e9ecef;
   box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

.stat-card.critical {
 border-left: 4px solid #dc3545; 
 }

.stat-card.warning {
 border-left: 4px solid #ffc107;
  }

.stat-card.moderate {
 border-left: 4px solid #fd7e14;
  }

.stat-card.total {
 border-left: 4px solid #007bff; 
 }

.stat-number { 
font-size: 24px;
 font-weight: bold;
  margin-bottom: 5px;
   }

.stat-number.critical {
 color: #dc3545
 ; }

.stat-number.warning {
 color: #ffc107; 
 }

.stat-number.moderate {
 color: #fd7e14; 
 }

.stat-number.total { 
color: #007bff; 
}

.stat-label { 
font-size: 10px;
 color: #666; 
 text-transform: uppercase;
  font-weight: bold; 
  }

.data-section { 
margin-bottom: 30px; 
}

.section-title { font-size: 16px;
 font-weight: bold; 
 color: black; 
 margin: 20px 0 15px 0;
  padding-bottom: 5px;
  border-bottom: 2px solid #e9ecef; 
  }

table { 
width: 100%; 
border-collapse: collapse;
 margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
  border-radius: 8px; 
  overflow: hidden;
   }

thead { 
background: linear-gradient(135deg, #008000 0%, #006400 100%); 
color: white; 
}

th {
 padding: 12px 8px; 
 text-align: center; 
 font-weight: bold;
  font-size: 10px; 
  text-transform: uppercase;
   color:gray; 
   letter-spacing: 0.5px;
    border: none; 
    }

td { padding: 10px 8px;
 text-align: center;
  border: none;
   border-bottom: 1px solid #e9ecef;
    }

tbody tr:nth-child(even) { background-color: #f8f9fa; }

tbody tr:hover { background-color: #e3f2fd; }

.badge-critique { 
background: #dc3545;
 color: white;
  padding: 4px 8px; 
  border-radius: 12px;
   font-weight: bold;
    font-size: 9px; 
    }

.badge-longue { 
background: #ffc107;
 color: #212529;
  padding: 4px 8px; 
  border-radius: 12px;
   font-weight: bold; 
   font-size: 9px; 
   }

.badge-moyenne { 
background: #fd7e14;
 color: white; 
padding: 4px 8px; 
border-radius: 12px; 
font-weight: bold; 
font-size: 9px;
 }

.badge-courte {
 background: #20c997; 
color: white; padding: 4px 8px;
 border-radius: 12px; font-weight: bold; 
 font-size: 9px;
  }

.footer {
    position: fixed;
    bottom: 0; /* en bas de la page */
    left: 0;
    right: 0;
    text-align: center;
    font-size: 9px;
    color: #666;
    border-top: 1px solid #e9ecef;
    padding: 10px 0;
}

.no-data { 
text-align: center; 
padding: 40px;
 color: #666; 
 background: #f8f9fa; 
 border-radius: 8px;
  margin: 20px 0; }

.alert-box { 
background: #fff3cd; 
border: 1px solid #ffeaa7;
 border-radius: 8px; 
 padding: 15px;
  margin: 20px 0;
   color: #856404;
    }

.alert-title { 
font-weight: bold;
 margin-bottom: 5px;
  }
@media print { body { -webkit-print-color-adjust: exact; } .data-section { page-break-inside: avoid; } }
</style>
</head>
<body>

<div class="header">
    <div class="header-top">
        <div class="logo-section">
            <div class="flag-stripes">
                <div class="stripe green"></div>
                <div class="stripe yellow"></div>
                <div class="stripe red"></div>
            </div>
            <div class="ministry-info">
                <h1 class="ministry-title">Ministère de la Fonction Publique</h1>
                <p class="ministry-subtitle">du Travail Social et de la Sécurité Sociale</p>
            </div>
        </div>
        <div class="national-motto">Unité-Travail-Progrès</div>
    </div>
</div>

<div class="report-info">
    <h2 class="report-title"> Rapport des Agents en Longue Absence</h2>
    <div class="report-meta">
        <span>Généré le : ' . $current_date . '</span><br>
        <span>Agents concernés : ' . $total_agents . '</span>
    </div>
</div>';

// Alerte si absences critiques
if ($stats['absence_critique'] > 0) {
    $html .= '
    <div class="alert-box">
        <div class="alert-title"> Attention !</div>
        <p>' . $stats['absence_critique'] . ' agent(s) en absence critique (plus de 30 jours). Action immédiate requise.</p>
    </div>';
}

// Table des absences
if (!empty($agents_absences)) {
    $html .= '<div class="data-section">
        <h3 class="section-title">📋 Liste des Agents en Absence</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Agent</th>
                    <th>Service</th>
                    <th>Bureau</th>
                    <th>Dernière Présence</th>
                    <th>Durée d\'Absence</th>
                </tr>
            </thead>
            <tbody>';

    $i = 1;
    foreach ($agents_absences as $agent) {
        $jours = $agent['nb_jours_absence_estimee'];
        if ($jours > 30) { $badge_class = 'badge-critique'; $badge_text = $jours . ' jours '; }
        elseif ($jours >= 15) { $badge_class = 'badge-longue'; $badge_text = $jours . ' jours '; }
        elseif ($jours >= 7) { $badge_class = 'badge-moyenne'; $badge_text = $jours . ' jours '; }
        else { $badge_class = 'badge-courte'; $badge_text = $jours . ' jours'; }

        $derniere_presence = $agent['derniere_presence'] ? date('d/m/Y', strtotime($agent['derniere_presence'])) : 'Jamais présent';

        $html .= '<tr>
            <td><strong>' . $i . '</strong></td>
            <td><strong>' . htmlspecialchars($agent['nom_prenom']) . '</strong></td>
            <td>' . htmlspecialchars($agent['service'] ?: '—') . '</td>
            <td>' . htmlspecialchars($agent['bureau'] ?: '—') . '</td>
            <td>' . $derniere_presence . '</td>
            <td><span class="' . $badge_class . '">' . $badge_text . '</span></td>
        </tr>';
        $i++;
    }

    $html .= '</tbody></table></div>';
} else {
    $html .= '<div class="no-data">
        <h3>✅ Excellente nouvelle !</h3>
        <p>Aucun agent n\'est actuellement en situation d\'absence prolongée.</p>
        <p><em>Tous les agents respectent leur obligation de présence.</em></p>
    </div>';
}

// Pied de page avec responsable
$html .= '<div class="footer">
    Document confidentiel - Ministère de la Fonction Publique, du Travail Social et de la Sécurité Sociale<br>
    Établi par : ' . htmlspecialchars($responsable) . '
</div>

</body>
</html>';

// -------------------
// Configuration et génération PDF
// -------------------
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nom du fichier avec timestamp
$filename = "Rapport_Absences_Longues_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
?>
