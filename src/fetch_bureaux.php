<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$service_id = $_GET['service_id'] ?? null;
if (!$service_id) {
  echo json_encode([]);
  exit;
}

$stmt = $pdo->prepare("SELECT id, libele FROM bureau WHERE service_id = ?");
$stmt->execute([$service_id]);
$bureaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($bureaux);
?>