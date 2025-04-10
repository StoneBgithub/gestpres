<?php
require_once 'db_connect.php';

$bureau = $_GET['bureau'] ?? '';
if ($bureau && $bureau !== 'all') {
    $stmt = $pdo->prepare("SELECT id, CONCAT(nom, ' ', prenom) AS nom_prenom FROM agent WHERE bureau_id = (SELECT id FROM bureau WHERE libele = :bureau)");
    $stmt->execute([':bureau' => $bureau]);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($agents);
} else {
    echo json_encode([]);
}
?>