<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$bureau_id = $_GET['bureau_id'] ?? null;
if (!$bureau_id) {
  echo json_encode([]);
  exit;
}

$stmt = $pdo->prepare("SELECT id, nom, prenom FROM agent WHERE bureau_id = ?");
$stmt->execute([$bureau_id]);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($agents);
?>