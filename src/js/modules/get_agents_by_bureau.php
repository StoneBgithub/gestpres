<?php
require_once 'connexion.php'; // ou ton fichier de connexion à la BDD

header('Content-Type: application/json');

$bureau_id = $_GET['bureau_id'] ?? null;

if (!$bureau_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, nom, prenom FROM agent WHERE bureau_id = :bureau_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['bureau_id' => $bureau_id]);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($agents);
?>