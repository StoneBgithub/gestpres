<?php
require_once 'db_connect.php';

$service = $_GET['service'] ?? '';
if ($service && $service !== 'all') {
    $stmt = $pdo->prepare("SELECT id, libele FROM bureau WHERE service_id = (SELECT id FROM service WHERE libele = :service)");
    $stmt->execute([':service' => $service]);
    $bureaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($bureaux);
} else {
    echo json_encode([]);
}
?>