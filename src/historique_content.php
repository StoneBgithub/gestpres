<?php
require "db_connect.php";
// Démarrer la session uniquement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer le paramètre de recherche
$search = $_GET['search'] ?? '';
$filter_actions = $_GET['actions'] ?? '';
$filter_roles = $_GET['roles'] ?? '';

// Vérifier si l'utilisateur est connecté
$agent_conn = $_SESSION['user_id'] ?? null;
$messages = ['success' => [], 'errors' => []];
if (!$agent_conn) {
    header("Location: login.php");
    exit();
}

// Mettre à jour est_vue à 1 pour toutes les actions non vues
try {
    $stmt_update = $pdo->prepare("UPDATE journal_actions SET est_vue = 1 WHERE est_vue = 0");
    $stmt_update->execute();
} catch (PDOException $e) {
    error_log("Erreur lors de la mise à jour de est_vue dans historique_content.php : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la mise à jour des notifications.";
}

$format = isset($_GET['format']) && $_GET['format'] === 'json' ? 'json' : 'html';

if ($format === 'json') {
    try {
        $query = "
            SELECT 
                j.id as id,
                j.date_action as date_action,
                r.libelle as responsable,
                a.nom as nom,
                a.prenom as prenom,
                a.photo as photo,
                CONCAT(a.prenom, ' ', a.nom) as nom_prenom,
                j.action_type as action,
                j.donnees as details
            FROM journal_actions j
            JOIN login l on j.ag_id=l.id
            JOIN agent a ON l.agent_id=a.id
            JOIN role r ON l.role_id=r.id
            WHERE 1=1
        ";
        $params = [];
        if ($search) {
            $query .= " AND (CONCAT(a.prenom, ' ', a.nom) LIKE :search)";
            $params['search'] = "%$search%";
        }
        if ($filter_actions) {
            $query .= " AND j.action_type = :action_type";
            $params['action_type'] = $filter_actions;
        }
        if ($filter_roles) {
            $query .= " AND r.libelle = :role";
            $params['role'] = $filter_roles;
        }
        $query .= " ORDER BY j.date_action DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $action = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql21 = "SELECT libelle FROM role";
        $stmt21 = $pdo->query($sql21);
        $role = $stmt21->fetchAll(PDO::FETCH_ASSOC);

        $sql22 = "SELECT DISTINCT action_type FROM journal_actions";
        $stmt22 = $pdo->query($sql22);
        $action_type = $stmt22->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT a.id, CONCAT(a.prenom, ' ', a.nom) AS nom_prenom FROM agent";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $ag = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'actions' => $action,
            'roles' => $role,
            'actionTypes' => $action_type,
            'agents' => $ag
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        exit;
    } catch (PDOException $e) {
        error_log("Erreur dans historique_content.php : " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Erreur serveur: ' . htmlspecialchars($e->getMessage())]);
        exit;
    }
}

try {
    $query = "
        SELECT 
            j.id as id,
            j.date_action as date_action,
            r.libelle as responsable,
            a.nom as nom,
            a.prenom as prenom,
            a.photo as photo,
            CONCAT(a.prenom, ' ', a.nom) as nom_prenom,
            j.action_type as action,
            j.donnees as details
        FROM journal_actions j
        JOIN login l on j.ag_id=l.id
        JOIN agent a ON l.agent_id=a.id
        JOIN role r ON l.role_id=r.id
        WHERE 1=1
    ";
    $params = [];
    if ($search) {
        $query .= " AND (CONCAT(a.prenom, ' ', a.nom) LIKE :search)";
        $params['search'] = "%$search%";
    }
    if ($filter_actions) {
        $query .= " AND j.action_type = :action_type";
        $params['action_type'] = $filter_actions;
    }
    if ($filter_roles) {
        $query .= " AND r.libelle = :role";
        $params['role'] = $filter_roles;
    }
    $query .= " ORDER BY j.date_action DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $action = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans historique_content.php : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors du chargement de l'historique : " . htmlspecialchars($e->getMessage());
}

try {
    $sql21 = "SELECT libelle FROM role";
    $stmt21 = $pdo->query($sql21);
    $role = $stmt21->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans historique_content.php (récupération rôles) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des rôles.";
}

try {
    $sql22 = "SELECT DISTINCT action_type FROM journal_actions";
    $stmt22 = $pdo->query($sql22);
    $action_type = $stmt22->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans historique_content.php (récupération types d'actions) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des types d'actions.";
}

try {
    $sql = "SELECT a.id, CONCAT(a.prenom, ' ', a.nom) AS nom_prenom, a.matricule, a.email, a.telephone, a.photo, a.bureau_id, b.libele AS libele_bureau, s.libele AS libele_service 
            FROM agent a 
            JOIN bureau b ON a.bureau_id = b.id 
            JOIN service s ON b.service_id = s.id 
            WHERE a.telephone LIKE :search OR CONCAT(a.prenom, ' ', a.nom) LIKE :search";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
    $ag = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans historique_content.php (récupération agents) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des agents.";
}

// Marquer toutes les actions comme vues
try {
    $stmt = $pdo->prepare("UPDATE journal_actions SET est_vue = 1 WHERE est_vue = 0");
    $stmt->execute();
    echo "<script>document.dispatchEvent(new CustomEvent('notifications:updated'));</script>";
} catch (PDOException $e) {
    error_log("Erreur dans historique_content.php (mise à jour est_vue) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la mise à jour des notifications.";
}

// Stocker les données dans un élément invisible pour le JS
echo '<script id="actionData" type="application/json">' . json_encode($action, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="roleData" type="application/json">' . json_encode($role, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="agentData" type="application/json">' . json_encode($ag, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="actiontypeData" type="application/json">' . json_encode($action_type, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
?>

<!-- Affichage des erreurs -->
<?php if (!empty($messages['errors'])): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
    <p class="font-bold">Erreur</p>
    <?php foreach ($messages['errors'] as $error): ?>
    <p><?php echo htmlspecialchars($error); ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.modal-content {
    transform: scale(0.95);
    opacity: 0;
    transition: all 0.3s ease-in-out;
}

.modal-content.show {
    transform: scale(1);
    opacity: 1;
}

#modalContent {
    max-height: 60vh;
    overflow-y: auto;
    padding-right: 0.5rem;
}

#modalContent::-webkit-scrollbar {
    width: 8px;
}

#modalContent::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#modalContent::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

#modalContent::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.changes-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow-x: auto;
    display: block;
}

.changes-table th,
.changes-table td {
    padding: 0.75rem;
    text-align: left;
    min-width: 120px;
    white-space: normal;
    word-break: break-word;
}

.changes-table th {
    background-color: #f3f4f6;
    font-weight: 600;
    color: #1f2937;
    border-bottom: 1px solid #e5e7eb;
}

.changes-table td {
    border-bottom: 1px solid #e5e7eb;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.changes-table tr:last-child td {
    border-bottom: none;
}

.changes-table tr:nth-child(even) {
    background-color: #f9fafb;
}

.changes-table .old-value {
    color: #6b7280;
}

.changes-table .new-value {
    color: #059669;
}

.action-details-section {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}
</style>

<!-- Filtres et recherche -->
<div
    class="bg-gradient-to-r from-indigo-50 to-blue-50 p-4 sm:p-6 rounded-xl shadow-sm mb-6 transition-all hover:shadow-md">
    <div class="flex items-center mb-4">
        <i class="fas fa-filter text-indigo-600 mr-2"></i>
        <h2 class="text-base sm:text-lg font-semibold text-gray-700">Recherche et filtres</h2>
    </div>
    <form action="#" method="get" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <input type="hidden" name="page" value="historique_content">
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
            <label for="filter_actions" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par
                actions</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-building text-gray-400"></i>
                </div>
                <select name="actions" id="filter_actions"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Toutes les actions</option>
                    <?php foreach ($action_type as $action_type): ?>
                    <option value="<?= htmlspecialchars($action_type['action_type'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($action_type['action_type'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label for="filter_roles" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par
                rôle</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-door-open text-gray-400"></i>
                </div>
                <select name="roles" id="filter_roles"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les rôles</option>
                    <?php foreach ($role as $role): ?>
                    <option value="<?= htmlspecialchars($role['libelle'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($role['libelle'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex items-end">
            <a href="?page=historique_content"
                class="px-3 py-2 text-sm bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                <i class="fas fa-redo-alt"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tableau des actions -->
<div class="hidden lg:block overflow-x-auto rounded-xl shadow-sm bg-white" id="actionTable">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-semibold">
            <tr>
                <th scope="col" class="px-4 py-3 text-left">AGENT</th>
                <th scope="col" class="px-4 py-3 text-left">RÔLE</th>
                <th scope="col" class="px-4 py-3 text-left">ACTION</th>
                <th scope="col" class="px-4 py-3 text-left">DATE</th>
                <th scope="col" class="px-4 py-3 text-right">DÉTAIL</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($action as $action): ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center align-middle">
                    <div class="flex items-center">
                        <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                            <?php if (!empty($action['photo']) && file_exists($action['photo'])): ?>
                            <img src="<?= htmlspecialchars($action['photo'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($action['nom_prenom'], ENT_QUOTES, 'UTF-8') ?>"
                                class="rounded-full object-cover"
                                onerror="this.parentNode.innerHTML = '<div class=\'w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center\'><span class=\'text-blue-600 font-medium text-xs\'>' + getInitials('<?= htmlspecialchars($action['nom_prenom'], ENT_QUOTES, 'UTF-8') ?>') + '</span></div>'">
                            <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-600 font-medium text-xs">
                                    <?php echo strtoupper(substr($action['prenom'], 0, 1) . substr($action['nom'], 0, 1)); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($action['nom_prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    <?= htmlspecialchars($action['responsable'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    <?= htmlspecialchars($action['action'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    <?= htmlspecialchars($action['date_action'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <button
                        class="detail-btn bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                        data-id="<?= $action['id'] ?>" title="Voir détails">
                        <i class="fas fa-eye mr-1"></i>
                        Voir détails
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal pour les détails -->
<div id="detailmodal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div
        class="bg-white rounded-lg shadow-2xl w-full max-w-md sm:max-w-lg p-4 sm:p-6 transform transition-all duration-300 modal-content">
        <div class="border-b px-4 py-3 flex justify-between items-center bg-gradient-to-r from-indigo-50 to-blue-50">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-info-circle mr-2 text-indigo-600"></i>
                <span>Détails de l'action</span>
            </h3>
            <button class="close-modal text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 sm:p-6 text-sm text-gray-800 space-y-2" id="modalContent">
            <!-- Contenu généré dynamiquement par JS -->
        </div>
    </div>
</div>

<!-- Modal pour afficher les messages -->
<div id="messageModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50"
    data-messages='<?= json_encode($messages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>'>
    <div id="messageModalContent"
        class="bg-white rounded-xl shadow-lg p-4 sm:p-6 w-full max-w-md transform transition-all scale-95 opacity-0">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <i class="fas fa-info-circle mr-2 text-indigo-600"></i>
            <span>Message</span>
        </h3>
        <div class="p-4 sm:p-6">
            <?php foreach ($messages['success'] as $msg): ?>
            <p class="text-green-600 font-semibold text-sm sm:text-base mb-2">✅ <?= htmlspecialchars($msg) ?></p>
            <?php endforeach; ?>
            <?php foreach ($messages['errors'] as $msg): ?>
            <p class="text-red-600 font-semibold text-sm sm:text-base mb-2">❌ <?= htmlspecialchars($msg) ?></p>
            <?php endforeach; ?>
        </div>
        <div class="flex justify-end mt-4">
            <button type="button"
                class="close-modal px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                <i class="fas fa-times mr-2"></i> Fermer
            </button>
        </div>
    </div>
</div>

<script src="js/historique.js"></script>