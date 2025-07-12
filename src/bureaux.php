<?php
header('Content-Type: application/json');

if (!isset($_GET['service_id'])) {
    echo json_encode([]);
    exit;
}

$service_id = (int) $_GET['service_id'];

$pdo = new PDO('mysql:host=localhost;dbname=gestion_presence', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$stmt = $pdo->prepare("SELECT id, libele AS name FROM bureau WHERE service_id = :service_id ORDER BY libele");
$stmt->execute(['service_id' => $service_id]);
$bureaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($bureaux);