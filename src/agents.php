<?php
header('Content-Type: application/json');

$pdo = new PDO('mysql:host=localhost;dbname=gestion_presence', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$sql = "SELECT a.id, a.nom, a.prenom, a.bureau_id, b.service_id
        FROM agent a
        JOIN bureau b ON a.bureau_id = b.id
        WHERE 1=1";
$params = [];

if (isset($_GET['service_id']) && is_numeric($_GET['service_id'])) {
    $sql .= " AND b.service_id = :service_id";
    $params['service_id'] = (int) $_GET['service_id'];
}

if (isset($_GET['bureau_id']) && is_numeric($_GET['bureau_id'])) {
    $sql .= " AND a.bureau_id = :bureau_id";
    $params['bureau_id'] = (int) $_GET['bureau_id'];
}

$sql .= " ORDER BY a.nom, a.prenom";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($agents);