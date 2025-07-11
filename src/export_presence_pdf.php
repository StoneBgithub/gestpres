<?php
require_once 'db_connect.php';
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Préparation des données filtrées (comme dans ton CSV original)
$filters = $_POST ?: [];
$date = $filters['date'] ?? '';
$time_range = $filters['time_range'] ?? 'all';
$status = $filters['status'] ?? 'all';
$service = $filters['service'] ?? 'all';
$bureau = $filters['bureau'] ?? 'all';
$employee = $filters['employee'] ?? 'all';
$custom_start = $filters['custom_start'] ?? '';
$custom_end = $filters['custom_end'] ?? '';
$type = $filters['type'] ?? 'all';

// Plage horaire
$start_time = '00:00:00';
$end_time = '23:59:59';
if ($time_range === 'morning') {
    $start_time = '08:00:00';
    $end_time = '12:00:00';
} elseif ($time_range === 'afternoon') {
    $start_time = '12:00:00';
    $end_time = '18:00:00';
} elseif ($time_range === 'custom' && $custom_start && $custom_end) {
    $start_time = $custom_start;
    $end_time = $custom_end;
}

// Conditions SQL dynamiques
$conditions = [];
$params = [];
if (!empty($date)) $conditions[] = "p.date = :date" and $params[':date'] = $date;
if ($time_range !== 'all') {
    $conditions[] = "p.heure BETWEEN :start_time AND :end_time";
    $params[':start_time'] = $start_time;
    $params[':end_time'] = $end_time;
}
if ($status !== 'all') {
    if ($status === 'on-time') {
        $conditions[] = "((p.type = 'arrivée' AND p.heure <= '09:00:00') OR (p.type = 'depart' AND p.heure >= '17:00:00'))";
    } elseif ($status === 'late') {
        $conditions[] = "p.type = 'arrivée' AND p.heure > '09:00:00'";
    } elseif ($status === 'early') {
        $conditions[] = "p.type = 'depart' AND p.heure < '17:00:00'";
    }
}
if ($service !== 'all') {
    $conditions[] = "s.libele = :service";
    $params[':service'] = $service;
}
if ($bureau !== 'all') {
    $conditions[] = "b.libele = :bureau";
    $params[':bureau'] = $bureau;
}
if ($employee !== 'all') {
    $conditions[] = "CONCAT(a.nom, ' ', a.prenom) = :employee";
    $params[':employee'] = $employee;
}
if ($type !== 'all') {
    $conditions[] = "p.type = :type";
    $params[':type'] = $type;
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "
    SELECT p.date, 
           CONCAT(a.nom, ' ', a.prenom) AS nom_prenom,
           s.libele AS service,
           b.libele AS bureau,
           p.heure,
           p.type,
           CASE 
               WHEN p.type = 'arrivée' AND p.heure > '09:00:00' THEN 'Retard'
               WHEN p.type = 'depart' AND p.heure < '17:00:00' THEN 'Départ anticipé'
               ELSE 'À l''heure'
           END AS statut
    FROM presence p
    JOIN agent a ON p.agent_id = a.id
    JOIN bureau b ON a.bureau_id = b.id
    JOIN service s ON b.service_id = s.id
    $where_clause
    ORDER BY p.date DESC, p.heure DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$logoPath = realpath('photos/');
if (!$logoPath) {
    die('Logo introuvable : vérifie le nom et le dossier assets/');
}

// Construction du HTML
$html = '
<style>
    body { font-family: DejaVu Sans, sans-serif; 
    font-size: 11px;
     margin : 0; 
     padding : 0;
     }
    .logo { width: 100px;}
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid black;
        padding: 5px;
        text-align: center;
    }
    th {
        background-color: green;
    }
        .bordure-gauche{
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: 15px;
        display: flex;
        flex-direction: column;
        z-index: 100;
        }
       .ligne-verte {
    background-color: green;
    flex: 1;
}
.ligne-jaune {
    background-color: yellow;
    flex: 1;
}
.ligne-rouge {
    background-color: red;
    flex: 1;
}
       
</style>
     <div class="bordure-gauche">
     <div class="ligne-verte"></div>
     <div class="ligne-jaune"></div>
     <div class="ligne-rouge"></div>
     </div>
    
     <img src="file://' . $logoPath . '" class="logo"><br>
    
    <h2>Liste des présences</h2>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Nom Prénom</th>
            <th>Service</th>
            <th>Bureau</th>
            <th>Heure</th>
            <th>Type</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>';

foreach ($results as $row) {
    $html .= '<tr>
        <td>' . date('d/m/Y', strtotime($row['date'])) . '</td>
        <td>' . htmlspecialchars($row['nom_prenom']) . '</td>
        <td>' . htmlspecialchars($row['service']) . '</td>
        <td>' . htmlspecialchars($row['bureau']) . '</td>
        <td>' . date('H:i', strtotime($row['heure'])) . '</td>
        <td>' . $row['type'] . '</td>
        <td>' . $row['statut'] . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// Création du PDF
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('Liste_Presences_' . date('Ymd_His') . '.pdf', ["Attachment" => true]);
exit;
