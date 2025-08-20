<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session uniquement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données
require_once "db_connect.php";

// Gestion centralisée des erreurs pour les requêtes AJAX
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Erreur PHP [$errno] : $errstr dans $errfile à la ligne $errline");
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'messages' => ['errors' => ["Erreur serveur : $errstr"]]
        ]);
        exit();
    }
});

set_exception_handler(function($exception) {
    error_log("Exception non gérée : " . $exception->getMessage());
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'messages' => ['errors' => ["Erreur serveur : " . htmlspecialchars($exception->getMessage())]]
        ]);
        exit();
    }
});

// Vérifier si l'utilisateur est connecté
$agent_conn = $_SESSION['user_id'] ?? null;
$messages = ['success' => [], 'errors' => []];

if (!$agent_conn) {
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'messages' => ['errors' => ["Session expirée. Veuillez vous reconnecter."]]
        ]);
        exit();
    } else {
        header("Location: login.php");
        exit();
    }
}

// Vérifier si l'utilisateur connecté a un login.id valide
try {
    $stmt = $pdo->prepare("SELECT id FROM login WHERE agent_id = :agent_id");
    $stmt->execute(['agent_id' => $agent_conn]);
    $login_id = $stmt->fetchColumn();
    if (!$login_id) {
        $messages['errors'][] = "Erreur : Aucun compte de connexion trouvé pour l'utilisateur connecté.";
    }
} catch (PDOException $e) {
    error_log("Erreur dans compte_content.php (vérification login) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la vérification du compte.";
}

function formatPrenoms($prenoms, $maxPrenoms = 2) {
    if (empty($prenoms)) return "";
    $prenomList = array_filter(explode(' ', trim($prenoms)));
    if (count($prenomList) <= $maxPrenoms) {
        return implode(' ', $prenomList);
    }
    $displayedPrenoms = array_slice($prenomList, 0, $maxPrenoms);
    $abbreviatedPrenoms = array_map(function($p) { return $p[0] . '.'; }, array_slice($prenomList, $maxPrenoms));
    return implode(' ', array_merge($displayedPrenoms, $abbreviatedPrenoms));
}

// TRAITEMENT AJAX POUR L'AJOUT OU LA MISE À JOUR DE COMPTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['add', 'update'])) {
    // S'assurer que la réponse sera en JSON
    header('Content-Type: application/json');
    
    error_log("Début du traitement de l'action '" . $_POST['action'] . "' : " . print_r($_POST, true));
    
    $action = $_POST['action'];
    $agent_id = isset($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;
    $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : null;
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

    // Vérification des champs obligatoires
    if (empty($role_id)) {
        $messages['errors'][] = "Le champ <strong>rôle</strong> est requis.";
    }
    if (empty($agent_id)) {
        $messages['errors'][] = "Le champ <strong>agent</strong> est requis.";
    }
    if ($action === 'add' && empty($mot_de_passe)) {
        $messages['errors'][] = "Le champ <strong>mot de passe</strong> est requis pour l'ajout.";
    }

    // Validation des données
    if (empty($messages['errors'])) {
        try {
            // Vérifier si le rôle existe
            $stmtRole = $pdo->prepare("SELECT id, libelle FROM role WHERE id = :role_id");
            $stmtRole->execute([':role_id' => $role_id]);
            $roleData = $stmtRole->fetch(PDO::FETCH_ASSOC);
            if (!$roleData) {
                $messages['errors'][] = "Le rôle sélectionné n'existe pas.";
            }

            // Vérifier si l'agent existe et a un email valide
            $stmtAgent = $pdo->prepare("SELECT id, email, nom, prenom, bureau_id FROM agent WHERE id = :agent_id");
            $stmtAgent->execute([':agent_id' => $agent_id]);
            $agentData = $stmtAgent->fetch(PDO::FETCH_ASSOC);
            if (!$agentData) {
                $messages['errors'][] = "Agent non trouvé pour l'ID : " . htmlspecialchars($agent_id);
            } elseif (empty($agentData['email'])) {
                $messages['errors'][] = "L'agent sélectionné n'a pas d'adresse email valide.";
            }

            if (empty($messages['errors'])) {
                $agent_id_real = $agentData['id'];
                if ($action === 'add') {
                    // Vérifier si un compte existe déjà pour cet agent
                    $stmtCheck = $pdo->prepare("SELECT id FROM login WHERE agent_id = :agent_id");
                    $stmtCheck->execute([':agent_id' => $agent_id_real]);
                    if ($stmtCheck->fetch()) {
                        $messages['errors'][] = "Un compte existe déjà pour cet agent.";
                    } else {
                        // Ajout d'un nouveau compte
                        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                        $stmtInsert = $pdo->prepare("
                            INSERT INTO login (agent_id, mot_de_passe, date_creation, derniere_connexion, statut, role_id, etat)
                            VALUES (:agent_id, :mot_de_passe, NOW(), NOW(), :statut, :role_id, :etat)
                        ");
                        $stmtInsert->execute([
                            ':agent_id' => $agent_id_real,
                            ':mot_de_passe' => $mot_de_passe_hash,
                            ':statut' => 'activé',
                            ':role_id' => $role_id,
                            ':etat' => 'déconnecté'
                        ]);
                        $messages['success'][] = "Compte utilisateur enregistré avec succès.";

                        // Journalisation
                        if ($login_id) {
                            $donnees = json_encode([
                                'agent_id' => $agent_id_real,
                                'email' => $agentData['email'],
                                'nom' => $agentData['nom'],
                                'prenom' => $agentData['prenom'],
                                'statut' => 'activé',
                                'role_id' => $role_id,
                                'role' => $roleData['libelle'],
                                'etat' => 'déconnecté'
                            ], JSON_UNESCAPED_UNICODE);
                            $stmtLog = $pdo->prepare("
                                INSERT INTO journal_actions (ag_id, action_type, donnees, date_action)
                                VALUES (:ag_id, :action_type, :donnees, :date_action)
                            ");
                            $stmtLog->execute([
                                ':ag_id' => $login_id,
                                ':action_type' => 'ajouter_compte',
                                ':donnees' => $donnees,
                                ':date_action' => date('Y-m-d H:i:s')
                            ]);
                        }
                    }
                } elseif ($action === 'update') {
                    // Récupérer l'état actuel du compte
                    $stmt = $pdo->prepare("
                        SELECT id, agent_id, role_id, mot_de_passe, statut, etat
                        FROM login
                        WHERE agent_id = :agent_id
                    ");
                    $stmt->execute(['agent_id' => $agent_id_real]);
                    $current_compte = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$current_compte) {
                        $messages['errors'][] = "Erreur : Compte introuvable.";
                    } else {
                        // Normaliser les valeurs pour la comparaison
                        $current_role_id = (string)($current_compte['role_id'] ?? '');
                        $current_statut = (string)($current_compte['statut'] ?? '');
                        $current_etat = (string)($current_compte['etat'] ?? '');
                        $current_mot_de_passe = (string)($current_compte['mot_de_passe'] ?? '');

                        $new_role_id = (string)$role_id;
                        $new_statut = (string)($current_compte['statut'] ?? 'activé');
                        $new_etat = (string)($current_compte['etat'] ?? 'déconnecté');
                        $new_mot_de_passe = $mot_de_passe ? password_hash($mot_de_passe, PASSWORD_DEFAULT) : $current_mot_de_passe;

                        // Comparer les nouvelles valeurs avec les anciennes
                        $changes = [];
                        if ($new_role_id !== $current_role_id) {
                            $stmt_old_role = $pdo->prepare("SELECT libelle FROM role WHERE id = :id");
                            $stmt_old_role->execute(['id' => $current_role_id]);
                            $old_role_libele = $stmt_old_role->fetchColumn() ?: 'N/A';
                            $changes['role'] = ['old' => $old_role_libele, 'new' => $roleData['libelle']];
                        }
                        if ($mot_de_passe && $new_mot_de_passe !== $current_mot_de_passe) {
                            $changes['mot_de_passe'] = ['old' => '******', 'new' => '******'];
                        }

                        error_log("compte_content.php (update) - Changes: " . json_encode($changes));

                        if (empty($changes)) {
                            $messages['success'][] = "Aucune donnée modifiée.";
                        } else {
                            // Mise à jour du compte
                            $sql = "UPDATE login SET role_id = :role_id";
                            if ($mot_de_passe) {
                                $sql .= ", mot_de_passe = :mot_de_passe";
                            }
                            $sql .= " WHERE agent_id = :agent_id";

                            $params = [
                                ':agent_id' => $agent_id_real,
                                ':role_id' => $role_id
                            ];
                            if ($mot_de_passe) {
                                $params[':mot_de_passe'] = $new_mot_de_passe;
                            }

                            $stmtUpdate = $pdo->prepare($sql);
                            $stmtUpdate->execute($params);
                            $messages['success'][] = "Compte utilisateur mis à jour avec succès.";

                            // Journalisation
                            if ($login_id) {
                                $donnees = json_encode([
                                    'agent_id' => $agent_id_real,
                                    'email' => $agentData['email'],
                                    'nom' => $agentData['nom'],
                                    'prenom' => $agentData['prenom'],
                                    'statut' => $new_statut,
                                    'role_id' => $role_id,
                                    'role' => $roleData['libelle'],
                                    'etat' => $new_etat,
                                    'changes' => $changes
                                ], JSON_UNESCAPED_UNICODE);
                                $stmtLog = $pdo->prepare("
                                    INSERT INTO journal_actions (ag_id, action_type, donnees, date_action)
                                    VALUES (:ag_id, :action_type, :donnees, :date_action)
                                ");
                                $stmtLog->execute([
                                    ':ag_id' => $login_id,
                                    ':action_type' => 'modifier_compte',
                                    ':donnees' => $donnees,
                                    ':date_action' => date('Y-m-d H:i:s')
                                ]);
                            }
                        }
                    }
                }
            }
        } catch (PDOException $e) {
    error_log("Exception PDO dans compte_content.php ($action) : " . $e->getMessage());
    error_log("Trace de l'erreur : " . $e->getTraceAsString());
    $messages['errors'][] = "Erreur serveur : Impossible de traiter la requête. Détails : " . htmlspecialchars($e->getMessage());
}
    }

    // Réponse JSON finale
    echo json_encode(['success' => empty($messages['errors']), 'messages' => $messages]);
    exit();
}

// TRAITEMENT AJAX POUR LA SUPPRESSION DE COMPTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    // S'assurer que la réponse sera en JSON
    header('Content-Type: application/json');
    
    $id = (int)$_POST['id'];
    try {
        $stmtCheck = $pdo->prepare("SELECT agent_id FROM login WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $login = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if ($login) {
            $stmtDelete = $pdo->prepare("DELETE FROM login WHERE id = :id");
            $stmtDelete->execute([':id' => $id]);
            $messages['success'][] = "Compte supprimé avec succès.";
            if ($login_id) {
                $donnees = json_encode(['agent_id' => $login['agent_id']], JSON_UNESCAPED_UNICODE);
                $stmtLog = $pdo->prepare("
                    INSERT INTO journal_actions (ag_id, action_type, donnees, date_action)
                    VALUES (:ag_id, :action_type, :donnees, :date_action)
                ");
                $stmtLog->execute([
                    ':ag_id' => $login_id,
                    ':action_type' => 'supprimer_compte',
                    ':donnees' => $donnees,
                    ':date_action' => date('Y-m-d H:i:s')
                ]);
            }
        } else {
            $messages['errors'][] = "Compte non trouvé pour l'ID : " . htmlspecialchars($id);
        }
    } catch (PDOException $e) {
        error_log("Exception PDO lors de la suppression : " . $e->getMessage());
        $messages['errors'][] = "Erreur serveur lors de la suppression.";
    }

    // Réponse JSON finale
    echo json_encode(['success' => empty($messages['errors']), 'messages' => $messages]);
    exit();
}

// Si c'est une requête AJAX mais sans action valide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'messages' => ['errors' => ["Action non reconnue : " . htmlspecialchars($_POST['action'])]]
    ]);
    exit();
}

// Récupérer le paramètre de recherche
$search = $_GET['search'] ?? '';

// Récupération des données pour l'affichage
try {
    $stmt = $pdo->query("
        SELECT l.id as id, l.agent_id as agent_id, a.nom as nom, a.prenom as prenom, 
               CONCAT(a.nom, ' ', a.prenom) as nom_prenom, a.photo as photo, 
               r.libelle as role, r.id as role_id, l.derniere_connexion as connexion, 
               l.statut as statut, l.etat as etat, b.libele as bureau
        FROM login l 
        JOIN agent a ON l.agent_id = a.id 
        JOIN role r ON l.role_id = r.id 
        JOIN bureau b ON a.bureau_id = b.id
        ORDER BY l.derniere_connexion DESC
    ");
    $comptes_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Données comptes récupérées : " . json_encode($comptes_data, JSON_UNESCAPED_UNICODE));
} catch (PDOException $e) {
    error_log("Erreur dans compte_content.php (récupération comptes) : " . $e->getMessage());
    $comptes_data = [];
}

// Récupérer les agents sans compte
try {
    $sql = "SELECT a.id, a.bureau_id, CONCAT(a.nom, ' ', a.prenom) AS nom_prenom, a.email, b.libele AS bureau
            FROM agent a
            JOIN bureau b ON a.bureau_id = b.id
            LEFT JOIN login l ON a.id = l.agent_id
            WHERE l.agent_id IS NULL";
    $stmt = $pdo->query($sql);
    $agents_bureau = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans compte_content.php (récupération agents bureau) : " . $e->getMessage());
    $agents_bureau = [];
}

// Récupérer les rôles
try {
    $sql = "SELECT id, libelle FROM role";
    $stmt = $pdo->query($sql);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans compte_content.php (récupération rôles) : " . $e->getMessage());
    $roles = [];
}

// Récupérer les statuts
try {
    $sql = "SELECT DISTINCT statut FROM login";
    $stmt = $pdo->query($sql);
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans compte_content.php (récupération statuts) : " . $e->getMessage());
    $statuts = [];
}

// Récupérer les bureaux
try {
    $sql = "SELECT libele FROM bureau";
    $stmt = $pdo->query($sql);
    $bureaux2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans compte_content.php (récupération bureaux) : " . $e->getMessage());
    $bureaux2 = [];
}

// Stocker les données dans des éléments invisibles pour le JS
echo '<script id="comptesData" type="application/json">' . json_encode($comptes_data, JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="bureauxData" type="application/json">' . json_encode($bureaux2, JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="statutData" type="application/json">' . json_encode($statuts, JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="roleData" type="application/json">' . json_encode($roles, JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="agentsData" type="application/json">' . json_encode($agents_bureau, JSON_UNESCAPED_UNICODE) . '</script>';
?>

<!-- Filtres et recherche -->
<div
    class="bg-gradient-to-r from-indigo-50 to-blue-50 p-4 sm:p-6 rounded-xl shadow-sm mb-6 transition-all hover:shadow-md">
    <div class="flex items-center mb-4">
        <i class="fas fa-filter text-indigo-600 mr-2"></i>
        <h2 class="text-base sm:text-lg font-semibold text-gray-700">Recherche et filtres</h2>
    </div>
    <form action="./compte_content.php" method="get" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <input type="hidden" name="page" value="compte_content">
        <div class="relative">
            <label for="search" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Recherche par
                nom/prénom</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" name="search"
                    id="search" placeholder="Rechercher un agent..."
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>
        </div>
        <div>
            <label for="filter_role" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par
                rôle</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-user-tag text-gray-400"></i>
                </div>
                <select name="filter_role" id="filter_role"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les rôles</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars($role['libelle'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($role['libelle'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label for="filter_statut" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par
                statut</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-toggle-on text-gray-400"></i>
                </div>
                <select name="filter_statut" id="filter_statut"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les statuts</option>
                    <?php foreach ($statuts as $statut): ?>
                    <option value="<?= htmlspecialchars($statut['statut'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($statut['statut'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex items-end space-x-2">
            <a href="?page=compte_content"
                class="px-3 py-2 text-sm bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                <i class="fas fa-redo-alt"></i>
            </a>
            <button type="button"
                class="add-compte-btn px-3 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i> Ajouter un utilisateur
            </button>
        </div>
    </form>
</div>

<!-- Tableau des comptes -->
<div class="overflow-x-auto rounded-xl shadow-sm bg-white" id="compteTable">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-semibold">
            <tr>
                <th scope="col" class="px-4 py-3 text-left">Agent</th>
                <th scope="col" class="px-4 py-3 text-left">Rôle</th>
                <th scope="col" class="px-4 py-3 text-left">Bureau</th>
                <th scope="col" class="px-4 py-3 text-left">Dernière connexion</th>
                <th scope="col" class="px-4 py-3 text-left">Statut</th>
                <th scope="col" class="px-4 py-3 text-left">État</th>
                <th scope="col" class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200" id="compteTableBody">
            <?php foreach ($comptes_data as $compte): ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center align-middle">
                    <div class="flex items-center">
                        <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                            <?php if (!empty($compte['photo']) && file_exists($compte['photo'])): ?>
                            <img src="<?= htmlspecialchars($compte['photo'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($compte['nom_prenom'], ENT_QUOTES, 'UTF-8') ?>"
                                class="rounded-full object-cover"
                                onerror="this.parentNode.innerHTML = '<div class=\'w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center\'><span class=\'text-blue-600 font-medium text-xs\'>' + getInitials('<?= htmlspecialchars($compte['nom_prenom'], ENT_QUOTES, 'UTF-8') ?>') + '</span></div>'">
                            <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-600 font-medium text-xs">
                                    <?php echo strtoupper(substr($compte['prenom'], 0, 1) . substr($compte['nom'], 0, 1)); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($compte['nom_prenom'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <?= htmlspecialchars($compte['role'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    <?= htmlspecialchars($compte['bureau'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    <?php echo $compte['connexion'] ? (new DateTime($compte['connexion']))->format('d/m/Y H:i') : 'Jamais'; ?>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    <span
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $compte['statut'] === 'activé' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= htmlspecialchars($compte['statut'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    <?= htmlspecialchars($compte['etat'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-right align-middle">
                    <button class="edit-compte-btn text-blue-600 hover:text-blue-900 transition-colors mr-2"
                        data-id="<?= $compte['id'] ?>" data-agent-id="<?= $compte['agent_id'] ?>" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="delete-compte-btn text-red-600 hover:text-red-900 transition-colors"
                        data-id="<?= $compte['id'] ?>" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal pour ajouter/modifier un compte -->
<div id="compteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md sm:max-w-lg md:max-w-4xl p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
        id="compteModalContent">
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 id="modalTitle" class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-user-plus mr-2 text-indigo-600"></i>
                <span>Ajouter un nouvel utilisateur</span>
            </h3>
            <button class="close-modals text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <form id="compteForm" action="?page=compte_content" method="post"
            class="p-6 flex flex-col justify-between h-full space-y-8">
            <input type="hidden" id="agent_idss" name="agent_id" value="">
            <input type="hidden" id="formAction" name="action" value="add">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="mb-4">
                    <label for="filter_bureau" class="block text-sm font-medium text-gray-700 mb-2">Bureau</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-building text-gray-400"></i>
                        </div>
                        <select name="bureau_id" id="filter_bureau"
                            class="w-full pl-10 pr-3 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">Choisir un bureau</option>
                            <?php foreach ($bureaux2 as $b): ?>
                            <option value="<?= htmlspecialchars($b['libele'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($b['libele'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="filter_agent" class="block text-sm font-medium text-gray-700 mb-2">Agent</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <select id="filter_agent" name="agent_id" required
                            class="w-full pl-10 pr-3 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">Choisir un agent</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="mb-4">
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Rôle</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user-tag text-gray-400"></i>
                        </div>
                        <select name="role_id" id="role" required
                            class="w-full pl-10 pr-3 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">Choisir un rôle</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($role['libelle'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="mot_de_passe" class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="mot_de_passe" id="mot_de_passe" maxlength="10"
                            class="w-full pl-10 pr-10 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                            placeholder="Entrez le mot de passe">
                        <button type="button" id="togglePassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 transition">
                            <i id="eyeIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="pt-6 flex justify-end space-x-6">
                <button type="button"
                    class="close-modal px-5 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <button type="submit"
                    class="px-5 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pour confirmer la suppression -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
        id="deleteModalContent">
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>
                <span>Confirmer la suppression</span>
            </h3>
            <button class="close-modals text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 sm:p-6">
            <p class="text-gray-700 text-sm sm:text-base mb-6">Êtes-vous sûr de vouloir supprimer cet utilisateur ?
                Cette action est irréversible.</p>
            <div class="flex justify-end space-x-3">
                <button type="button"
                    class="close-modal px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <button id="confirmDeleteBtn"
                    class="px-3 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les messages -->
<div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50"
    data-messages='<?php echo json_encode($messages, JSON_UNESCAPED_UNICODE); ?>'>
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
        id="messageModalContent">
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i
                    class="fas fa-info-circle mr-2 <?php echo !empty($messages['errors']) ? 'text-red-500' : 'text-green-600'; ?>"></i>
                <span><?php echo !empty($messages['errors']) ? 'Erreur' : 'Succès'; ?></span>
            </h3>
            <button class="close-modal text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 sm:p-6">
            <?php if (!empty($messages['success'])): ?>
            <?php foreach ($messages['success'] as $msg): ?>
            <p class="text-green-600 font-semibold text-sm sm:text-base mb-2">✅
                <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($messages['errors'])): ?>
            <?php foreach ($messages['errors'] as $error): ?>
            <p class="text-red-500 font-semibold text-sm sm:text-base mb-2">❌
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
            <?php endif; ?>
            <div class="flex justify-end mt-4">
                <button type="button"
                    class="close-modal px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>