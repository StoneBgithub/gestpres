<?php
require "db_connect.php";

// Démarrer la session uniquement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
$agent_conn = $_SESSION['user_id'] ?? null;
if (!$agent_conn) {
    http_response_code(401);
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit();
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as nouvelles_actions FROM journal_actions WHERE est_vue = 0");
    $ro = $stmt->fetch(PDO::FETCH_ASSOC);
    $nbnouvelles = $ro['nouvelles_actions'] ?? 0;
    echo json_encode(['nbnouvelles' => $nbnouvelles]);
} catch (PDOException $e) {
    error_log("Erreur dans get_notifications.php : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>