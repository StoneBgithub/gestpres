<?php
require_once 'db_connect.php';

// Récupérer les filtres depuis la requête POST
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

// Définir la plage horaire
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

// Construire les conditions dynamiques
$conditions = [];
$params = [];
if (!empty($date)) {
    $conditions[] = "p.date = :date";
    $params[':date'] = $date;
}
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

// Requête pour récupérer les présences
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

// Définir les en-têtes HTTP pour le téléchargement
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Liste_Presences_' . date('Ymd_His') . '.csv"');

// Ajouter l'en-tête BOM pour UTF-8 (compatibilité Excel)
echo "\xEF\xBB\xBF"; // BOM pour UTF-8

// Créer un flux de sortie CSV
$output = fopen('php://output', 'w');

// Écrire les en-têtes du CSV
$headers = ['Date', 'Nom Prénom', 'Service', 'Bureau', 'Heure', 'Type', 'Statut'];
fputcsv($output, $headers, ';');

// Écrire les données
foreach ($results as $row) {
    $line = [
        date('d/m/Y', strtotime($row['date'])),
        $row['nom_prenom'],
        $row['service'],
        $row['bureau'],
        date('H:i', strtotime($row['heure'])),
        $row['type'],
        $row['statut']
    ];
    fputcsv($output, $line, ';');
}

// Fermer le flux
fclose($output);
exit;