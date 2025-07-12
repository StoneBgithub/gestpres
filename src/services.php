<?php
header('Content-Type: application/json');
$pdo = new PDO('mysql:host=localhost;dbname=gestion_presence', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$stmt = $pdo->query("SELECT id, libele AS name FROM service ORDER BY libele");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($services);