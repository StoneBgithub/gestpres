<?php
require_once 'db_connect.php';
require_once __DIR__ . '/dompdf-3.1.0/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// -------------------
// Récupération des filtres POST
// -------------------
$filters = $_POST ?: [];
$date = $filters['date'] ?? '';
$service = $filters['service'] ?? 'all';
$bureau = $filters['bureau'] ?? 'all';
$employee = $filters['employee'] ?? 'all';

// -------------------
// Construire conditions SQL
// -------------------
$conditions = [];
$params = [];

if (!empty($date)) { $conditions[] = "p.date = :date"; $params[':date'] = $date; }
if ($service !== 'all') { $conditions[] = "s.libele = :service"; $params[':service'] = $service; }
if ($bureau !== 'all') { $conditions[] = "b.libele = :bureau"; $params[':bureau'] = $bureau; }
if ($employee !== 'all') { $conditions[] = "CONCAT(a.nom, ' ', a.prenom) = :employee"; $params[':employee'] = $employee; }

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// -------------------
// Requête SQL pour récupérer arrivée et départ par agent et par date
// -------------------
$sql = "
SELECT 
    p.date,
    CONCAT(a.nom,' ',a.prenom) AS nom_prenom,
    s.libele AS service,
    b.libele AS bureau,
    MAX(CASE WHEN p.type='arrivée' THEN p.heure END) AS heure_arrivee,
    MAX(CASE WHEN p.type='depart' THEN p.heure END) AS heure_depart,
    CASE
        WHEN MAX(CASE WHEN p.type='arrivée' THEN p.heure END) > '09:00:00' THEN 'Retard'
        WHEN MAX(CASE WHEN p.type='depart' THEN p.heure END) < '17:00:00' THEN 'Départ anticipé'
        ELSE 'À l\'heure'
    END AS statut
FROM presence p
JOIN agent a ON p.agent_id = a.id
JOIN bureau b ON a.bureau_id = b.id
JOIN service s ON b.service_id = s.id
$where_clause
GROUP BY p.date, a.id
ORDER BY p.date DESC, a.nom ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$results) $results = [];

// -------------------
// Calcul résumé par agent
// -------------------
$summary = [];
foreach ($results as $row) {
    $agent = $row['nom_prenom'];
    if (!isset($summary[$agent])) {
        $summary[$agent] = ['ponctuel'=>0, 'retard'=>0, 'depart_ante'=>0];
    }
    if ($row['statut'] === 'À l\'heure') $summary[$agent]['ponctuel']++;
    if ($row['statut'] === 'Retard') $summary[$agent]['retard']++;
    if ($row['statut'] === 'Départ anticipé') $summary[$agent]['depart_ante']++;
}

// -------------------
// Génération du HTML professionnel
// -------------------
$current_date = date('d/m/Y à H:i');
$filtered_info = '';
if (!empty($date)) $filtered_info .= "Date : " . date('d/m/Y', strtotime($date)) . " | ";
if ($service !== 'all') $filtered_info .= "Service : $service | ";
if ($bureau !== 'all') $filtered_info .= "Bureau : $bureau | ";
if ($employee !== 'all') $filtered_info .= "Employé : $employee | ";
$filtered_info = rtrim($filtered_info, ' | ');

$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport des Présences</title>
<style>
@page {
    margin: 20mm;
    @top-center {
        content: "Rapport des Présences - Page " counter(page);
        font-family: "Segoe UI", Arial, sans-serif;
        font-size: 10px;
        color: #666;
    }
}

body {
    font-family: "Segoe UI", "DejaVu Sans", Arial, sans-serif;
    font-size: 11px;
    line-height: 1.4;
    color: #333;
    margin: 0;
    padding: 0;
    background: #fff;
}

/* En-tête professionnel */
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
    height: 60px;
    margin-right: 15px;
    display: flex;
    flex-direction: column;
    border-radius: 2px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stripe {
    flex: 1;
}

.stripe.green { background: #228B22; }
.stripe.yellow { background: #FFD700; }
.stripe.red { background: #DC143C; }

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
    text-align:center;
    justify-content: space-between;
    font-size: 10px;
    color: #666;
    margin-top: 10px;
}

.filters-info {
    font-size: 10px;
    color: #555;
    font-style: italic;
}

/* Tableaux professionnels */
.data-section {
    margin-bottom: 30px;
}

.section-title {
    font-size: 16px;
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

td {
    padding: 10px 8px;
    text-align: center;
    border: none;
    border-bottom: 1px solid #e9ecef;
}

tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}

tbody tr:hover {
    background-color: #e3f2fd;
}

/* Statuts avec couleurs */
.status-ponctuel {
    background: #d4edda;
    color: #008000;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 9px;
}

.status-retard {
    background: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 9px;
}

.status-depart-anticipe {
    background: #f8d7da;
    color: #721c24;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 9px;
}

/* Statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #008000;
}

.stat-label {
    font-size: 10px;
    color: #666;
    text-transform: uppercase;
    margin-top: 5px;
}

/* Pied de page */
.footer {
    position: fixed;
    bottom: 15mm;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 9px;
    color: #666;
    border-top: 1px solid #e9ecef;
    padding-top: 10px;
}

/* Responsive pour impression */
@media print {
    body { -webkit-print-color-adjust: exact; }
    .data-section { page-break-inside: avoid; }
}
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
        <div class="national-motto">
            Unité-Travail-Progrès
        </div>
    </div>
</div>

<div class="report-info">
    <h2 class="report-title">Rapport des Présences</h2>
    <div class="report-meta">
        <span>Généré le : ' . $current_date . '</span>
        <span>Nombre d\'enregistrements : ' . count($results) . '</span>
    </div>
    ' . (!empty($filtered_info) ? '<div class="filters-info">Filtres appliqués : ' . $filtered_info . '</div>' : '') . '
</div>';

if (!empty($results)) {
    $html .= '
    <div class="data-section">
        <h3 class="section-title">📊 Détail des Présences</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Agent</th>
                    <th>Service</th>
                    <th>Bureau</th>
                    <th>Arrivée</th>
                    <th>Départ</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($results as $row) {
        $status_class = '';
        switch ($row['statut']) {
            case 'À l\'heure':
                $status_class = 'status-ponctuel';
                break;
            case 'Retard':
                $status_class = 'status-retard';
                break;
            case 'Départ anticipé':
                $status_class = 'status-depart-anticipe';
                break;
        }

        $html .= '<tr>
            <td><strong>' . date('d/m/Y', strtotime($row['date'])) . '</strong></td>
            <td>' . htmlspecialchars($row['nom_prenom']) . '</td>
            <td>' . htmlspecialchars($row['service']) . '</td>
            <td>' . htmlspecialchars($row['bureau']) . '</td>
            <td>' . ($row['heure_arrivee'] ? '<strong>' . date('H:i', strtotime($row['heure_arrivee'])) . '</strong>' : '—') . '</td>
            <td>' . ($row['heure_depart'] ? '<strong>' . date('H:i', strtotime($row['heure_depart'])) . '</strong>' : '—') . '</td>
            <td><span class="' . $status_class . '">' . htmlspecialchars($row['statut']) . '</span></td>
        </tr>';
    }

    $html .= '</tbody></table></div>';

    // Résumé par agent
    if (!empty($summary)) {
        $html .= '
        <div class="data-section">
            <h3 class="section-title">📈 Résumé par Agent</h3>
            <table>
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Présences Ponctuelles</th>
                        <th>Retards</th>
                        <th>Départs Anticipés</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($summary as $agent => $data) {
            $total = $data['ponctuel'] + $data['retard'] + $data['depart_ante'];
            $html .= '<tr>
                <td><strong>' . htmlspecialchars($agent) . '</strong></td>
                <td><span class="status-ponctuel">' . $data['ponctuel'] . '</span></td>
                <td><span class="status-retard">' . $data['retard'] . '</span></td>
                <td><span class="status-depart-anticipe">' . $data['depart_ante'] . '</span></td>
                <td><strong>' . $total . '</strong></td>
            </tr>';
        }

        $html .= '</tbody></table></div>';
    }
} else {
    $html .= '
    <div class="data-section" style="text-align: center; padding: 40px; color: #666;">
        <h3>Aucune donnée trouvée</h3>
        <p>Aucun enregistrement ne correspond aux critères de recherche spécifiés.</p>
    </div>';
}

$html .= '
<div class="footer">
    Document confidentiel - Ministère de la Fonction Publique, du Travail Social et de la Sécurité Sociale
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
$filename = "Rapport_Presences_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
?>