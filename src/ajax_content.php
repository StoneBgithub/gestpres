
<?php
header('Content-Type: application/json');
require_once "db_connect.php";

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$agent_conn = $_SESSION['user_id'] ?? null;
if (!$agent_conn) {
    echo json_encode(['success' => false, 'errors' => ["Utilisateur non connecté."]]);
    exit;
}

$messages = ['success' => [], 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_id = trim($_POST['role_id'] ?? '');
    $agent_id = trim($_POST['agent_id'] ?? '');
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

    // Validation
    if (empty($role_id)) $messages['errors'][] = "Le rôle est requis.";
    if (empty($agent_id)) $messages['errors'][] = "L'agent est requis.";
    if (empty($mot_de_passe)) $messages['errors'][] = "Le mot de passe est requis.";

    if (empty($messages['errors'])) {
        try {
            $stmtCheck = $pdo->prepare("SELECT id FROM login WHERE agent_id = :agent_id");
            $stmtCheck->execute([':agent_id' => $agent_id]);

            if ($stmtCheck->fetch()) {
                $messages['errors'][] = "Un compte existe déjà pour cet agent.";
            } else {
                $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

                $stmtInsert = $pdo->prepare("
                    INSERT INTO login (agent_id, mot_de_passe, date_creation, derniere_connexion, statut, role_id, etat)
                    VALUES (:agent_id, :mot_de_passe, NOW(), NOW(), :statut, :role_id, :etat)
                ");
                $stmtInsert->execute([
                    ':agent_id' => $agent_id,
                    ':mot_de_passe' => $mot_de_passe_hash,
                    ':statut' => 'actif',
                    ':role_id' => $role_id,
                    ':etat' => 'déconnecté',
                ]);

                // Journalisation
                $donnees = json_encode([
                    'agent_id' => $agent_id,
                    'statut' => 'actif',
                    'role_id' => $role_id,
                    'etat' => 'déconnecté',
                ], JSON_UNESCAPED_UNICODE);

                $stmtLog = $pdo->prepare("
                    INSERT INTO journal_actions (ag_id, action_type, donnees, date_action)
                    VALUES (:ag_id, :action_type, :donnees, :date_action)
                ");
                $stmtLog->execute([
                    ':ag_id' => $agent_conn,
                    ':action_type' => 'ajouter',
                    ':donnees' => $donnees,
                    ':date_action' => date('Y-m-d H:i:s'),
                ]);

                $messages['success'][] = "Compte utilisateur enregistré avec succès.";
            }
        } catch (PDOException $e) {
            error_log("Erreur SQL : " . $e->getMessage());
            $messages['errors'][] = "Erreur lors de l'enregistrement du compte utilisateur.";
        }
    }

    echo json_encode($messages);
    exit;
}
?>