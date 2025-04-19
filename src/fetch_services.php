<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    $stmt = $pdo->query("SELECT id, libele FROM service");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($services);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des services']);
}
?>