<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$bureaux2 = $_GET['bureau_id'] ?? null;
if (!$bureaux2) {
  echo json_encode([]);
  exit;
}

$stmt = $pdo->prepare("SELECT id, nom, prenom FROM agent WHERE bureau_id = ?");
$stmt->execute([$bureaux2]);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($agents);
?>