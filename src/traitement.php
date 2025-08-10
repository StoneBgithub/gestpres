<?php
session_start();
require_once "db_connect.php";

header('Content-Type: application/json');

$response = ['success' => false, 'messages' => ['success' => [], 'errors' => []]];
$agent_conn = $_SESSION['user_id'] ?? null;

if (!$agent_conn) {
    $response['messages']['errors'][] = "Non autorisé";
    echo json_encode($response);
    exit;
}

// Récupérer et valider
$role = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
$agent_nom_prenom = filter_input(INPUT_POST, 'agent_id', FILTER_SANITIZE_STRING);
$mot_de_passe = filter_input(INPUT_POST, 'mot_de_passe', FILTER_SANITIZE_STRING);

if (!$role) {
    $response['messages']['errors'][] = "Le champ rôle est requis.";
}
if (!$agent_nom_prenom) {
    $response['messages']['errors'][] = "Le champ agent est requis.";
}
if (!$mot_de_passe) {
    $response['messages']['errors'][] = "Le mot de passe est requis.";
} elseif (strlen($mot_de_passe) > 10) {
    $response['messages']['errors'][] = "Le mot de passe ne doit pas dépasser 10 caractères.";
}

if (count($response['messages']['errors']) > 0) {
    echo json_encode($response);
    exit;
}

try {
    // Récupérer l'ID de l'agent via nom et prénom concaténés
    $stmt = $pdo->prepare("SELECT id FROM agent WHERE CONCAT(nom, ' ', prenom) = :nom_prenom");
    $stmt->execute([':nom_prenom' => $agent_nom_prenom]);
    $agentData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agentData) {
        $response['messages']['errors'][] = "Agent non trouvé.";
        echo json_encode($response);
        exit;
    }

    $agent_id_real = $agentData['id'];

    // Vérifier si l'agent a déjà un compte
    $checkStmt = $pdo->prepare("SELECT id FROM login WHERE agent_id = :agent_id");
    $checkStmt->execute([':agent_id' => $agent_id_real]);
    if ($checkStmt->fetch()) {
        $response['messages']['errors'][] = "Cet agent a déjà un compte utilisateur.";
    }

    // Créer le compte
    $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
    $stmtInsert = $pdo->prepare("
        INSERT INTO login (agent_id, mot_de_passe, date_creation, derniere_connexion, statut, role_id, etat)
        VALUES (:agent_id, :mot_de_passe, NOW(), NOW(), :statut, :role_id, :etat)
    ");
    $stmtInsert->execute([
        ':agent_id' => $agent_id_real,
        ':mot_de_passe' => $mot_de_passe_hash,
        ':statut' => 'actif',
        ':role_id' => $role,
        ':etat' => 'déconnecté'
    ]);

    $response['success'] = true;
    $response['messages']['success'][] = "Compte utilisateur enregistré avec succès.";

} catch (PDOException $e) {
    $response['messages']['errors'][] = "Erreur de base de données : " . $e->getMessage();
}

echo json_encode($response);
exit;
?>
