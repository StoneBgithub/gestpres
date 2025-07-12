<?php
// Démarrer la session uniquement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données
require_once "db_connect.php";

// Vérifier si l'utilisateur est connecté
$agent_conn = $_SESSION['user_id'] ?? null;
$messages = ['success' => [], 'errors' => []];

if (!$agent_conn) {
    // Si c'est une requête AJAX, retourner JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Non autorisé']);
        exit();
    }
    header("Location: login.php");
    exit();
}

// TRAITEMENT AJAX POUR L'AJOUT/MODIFICATION DE COMPTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'add')) {
    $role = $_POST['role_id'] ?? null;
    $agent_id = $_POST['agent_id'] ?? null;
    $mot_de_passe = $_POST['mot_de_passe'] ?? null;

    // Validation des champs
    if (empty($role)) $messages['errors'][] = "Le champ <strong>role</strong> est requis.";
    if (empty($agent_id)) $messages['errors'][] = "Le champ <strong>Agent</strong> est requis.";
    if (empty($mot_de_passe)) $messages['errors'][] = "Le champ <strong>Mot de passe</strong> est requis.";

    if (empty($messages['errors'])) {
        try {
            // Récupérer l'ID de l'agent basé sur son nom_prenom
            $stmtAgent = $pdo->prepare("SELECT id FROM agent WHERE CONCAT(nom, ' ', prenom) = :nom_prenom");
            $stmtAgent->execute([':nom_prenom' => $agent_id]);
            $agentData = $stmtAgent->fetch();
            
            if (!$agentData) {
                $messages['errors'][] = "Agent non trouvé.";
            } else {
                $agent_id_real = $agentData['id'];
                
                $stmtCheck = $pdo->prepare("SELECT id FROM login WHERE agent_id = :agent_id");
                $stmtCheck->execute([':agent_id' => $agent_id_real]);
                if ($stmtCheck->fetch()) {
                    $messages['errors'][] = "Un compte existe déjà pour cet agent.";
                } else {
                    // Hasher le mot de passe pour la sécurité
                    $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                    
                    $stmtInsert = $pdo->prepare("INSERT INTO login (agent_id, mot_de_passe, date_creation, derniere_connexion, statut, role_id, etat)
                    VALUES (:agent_id, :mot_de_passe, NOW(), NOW(), :statut, :role_id, :etat)");
                    $stmtInsert->execute([
                        ':agent_id' => $agent_id_real,
                        ':mot_de_passe' => $mot_de_passe_hash,
                        ':statut' => 'actif',
                        ':role_id' => $role,
                        ':etat' => 'déconnecté'
                    ]);

                    $messages['success'][] = "Compte utilisateur enregistré avec succès.";

                    // Journalisation
                    if ($agent_conn) {
                        $donnees = json_encode([
                            'agent_id' => $agent_id_real,
                            'statut' => 'actif',
                            'role_id' => $role,
                            'etat' => 'déconnecté'
                        ], JSON_UNESCAPED_UNICODE);

                        $stmtLog = $pdo->prepare("INSERT INTO journal_actions (ag_id, action_type, donnees, date_action)
                                                  VALUES (:ag_id, :action_type, :donnees, :date_action)");
                        $stmtLog->execute([
                            ':ag_id' => $agent_conn,
                            ':action_type' => 'ajouter_compte',
                            ':donnees' => $donnees,
                            ':date_action' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du compte utilisateur : " . $e->getMessage());
            $messages['errors'][] = "Erreur lors de l'enregistrement du compte utilisateur.";
        }
    }

    // Si c'est une requête AJAX, retourner JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => empty($messages['errors']),
            'messages' => $messages
        ]);
        exit();
    }
}

// Récupérer le paramètre de recherche
$search = $_GET['search'] ?? '';

// Récupération des données pour l'affichage
try {
    $stmt = $pdo->query("SELECT l.id as id, a.nom as nom, a.prenom as prenom, concat(a.nom,' ',a.prenom) as nom_prenom, a.photo as photo, r.libelle as role, l.derniere_connexion as connexion, l.statut as statut, l.etat as etat FROM login l JOIN agent a ON l.agent_id=a.id JOIN role r ON l.role_id=r.id ORDER BY l.derniere_connexion DESC");
    $comptes_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans compte_content.php : " . $e->getMessage());
    echo "<tr><td colspan='3'>Erreur lors de la récupération des comptes utilisateurs: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
    $comptes_data = [];
}

$sql = "SELECT a.id, a.nom, a.prenom, CONCAT(a.prenom, ' ', a.nom) AS nom_prenom, a.matricule, a.email, a.telephone, a.photo, a.bureau_id, b.libele AS libele_bureau, s.libele AS libele_service FROM agent a JOIN bureau b ON a.bureau_id = b.id JOIN service s ON b.service_id = s.id WHERE a.telephone LIKE :search OR CONCAT(a.prenom, ' ', a.nom) LIKE :search";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans agent_content.php (récupération agents) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des agents.";
}

try {
    $sql21 = "SELECT id, libelle FROM role";
    $stmt21 = $pdo->query($sql21);
    $roles = $stmt21->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans agent_content.php (récupération rôles) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des rôles.";
}

try {
    $sql22 = "SELECT DISTINCT statut FROM login ";
    $stmt22 = $pdo->query($sql22);
    $statuts = $stmt22->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans agent_content.php (récupération statuts) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des statuts.";
}

try {
    $sql2 = "SELECT libele FROM bureau";
    $stmt2 = $pdo->query($sql2);
    $bureaux2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans agent_content.php (récupération bureaux) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des bureaux.";
}

try {
    $sql24 = "SELECT a.id, a.bureau_id, concat(a.nom,' ',a.prenom) AS nom_prenom, b.libele AS bureau FROM agent a JOIN bureau b ON a.bureau_id = b.id";
    $stmt24 = $pdo->query($sql24);
    $agents_bureau = $stmt24->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans agent_content.php (récupération agents bureau) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des agents.";
}


?>
<?php
// Stocker les données dans un élément invisible pour le JS
echo '<script id="comptesData" type="application/json">' . json_encode($comptes_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="bureauxData" type="application/json">' . json_encode($bureaux2, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="statutData" type="application/json">' . json_encode($statuts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="roleData" type="application/json">' . json_encode($roles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="agentsData" type="application/json">' . json_encode($agents_bureau, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
?>

<!-- Filtres et recherche -->
<div class="bg-gradient-to-r from-indigo-50 to-blue-50 p-4 sm:p-6 rounded-xl shadow-sm mb-6 transition-all hover:shadow-md">
    <div class="flex items-center mb-4">
        <i class="fas fa-filter text-indigo-600 mr-2"></i>
        <h2 class="text-base sm:text-lg font-semibold text-gray-700">Recherche et filtres</h2>
    </div>
    <form action="#" method="get" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <input type="hidden" name="page" value="compte_content">
        <div class="relative">
            <label for="search" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Recherche par nom/prénom</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" value="" name="search" id="search"
                    placeholder="Rechercher un agent..."
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>
        </div>
        <div>
            <label for="filter_role" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par rôle</label>
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
            <label for="filter_statut" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par statut</label>
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

<div class="hidden lg:block overflow-x-auto rounded-xl shadow-sm bg-white" id="compteTable">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-semibold">
            <tr>
                <th scope="col" class="px-4 py-3 text-left">Agent</th>
                <th scope="col" class="px-4 py-3 text-left">Rôle</th>
                <th scope="col" class="px-4 py-3 text-left">Dernière connexion</th>
                <th scope="col" class="px-4 py-3 text-left">Statut</th>
                <th scope="col" class="px-4 py-3 text-left">État</th>
                <th scope="col" class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
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
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($compte['nom_prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($compte['role'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($compte['connexion'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($compte['statut'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($compte['etat'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-right align-middle">
                    <button class="edit-compte-btn text-blue-600 hover:text-blue-900 transition-colors mr-2"
                            data-id="<?= $compte['id'] ?>" title="Modifier">
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
        <!-- Header modal -->
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 id="modalTitle" class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-user-plus mr-2 text-indigo-600"></i>
                <span>Ajouter un nouvel utilisateur</span>
            </h3>
            <button class="close-modals text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Formulaire -->
        <form id="compteForm" action="?page=compte_content" method="post" 
    class="p-6 flex flex-col justify-between h-full space-y-8"> <!-- plus d'espace vertical -->

    <!-- Champs cachés -->
    <input type="hidden" id="agent_idss" name="agent_id" value="">
    <input type="hidden" id="actions" name="action" value="add">

    <!-- Bureau + Agent -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <!-- Bureau -->
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

        <!-- Agent -->
        <div class="mb-4">
            <label for="filter_agent" class="block text-sm font-medium text-gray-700 mb-2">Agent</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-user text-gray-400"></i>
                </div>
                <select disabled id="filter_agent" name="agent_id" required
                    class="w-full pl-10 pr-3 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Choisir un agent</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Rôle + Mot de passe -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <!-- Rôle -->
        <div class="mb-4">
            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Filtrer par rôle</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-user-tag text-gray-400"></i>
                </div>
                <select name="role_id" id="role"
                    class="w-full pl-10 pr-3 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les rôles</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars($role['id'], ENT_QUOTES, 'UTF-8') ?>">
    <?= htmlspecialchars($role['libelle'], ENT_QUOTES, 'UTF-8') ?>
</option>

                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Mot de passe -->
        <div class="mb-4">
            <label for="mot_de_passe" class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-lock text-gray-400"></i>
                </div>
                <input type="password" name="mot_de_passe" id="mot_de_passe" maxlength="10"
                    class="w-full pl-10 pr-10 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                    placeholder="Entrez le mot de passe" required>
                <button type="button" id="togglePassword"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 transition">
                    <i id="eyeIcon" class="fas fa-eye"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Boutons -->
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
</div>
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
            <p class="text-gray-700 text-sm sm:text-base mb-6">Êtes-vous sûr de vouloir supprimer cet agent ? Cette
                action est irréversible.</p>
            <div class="flex justify-end space-x-3">
                <button type="button"
                    class="close-modal px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <a id="confirmDeleteBtn" href="#"
                    class="px-3 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Supprimer
                </a>
            </div>
        </div>
    </div>
</div>