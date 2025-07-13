<?php
require "db_connect.php";

// Démarrer la session uniquement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Récupérer les paramètres de recherche et filtres
$search = $_GET['search'] ?? '';
$filter_actions = $_GET['actions'] ?? '';
$filter_roles = $_GET['roles'] ?? '';

try {
    $query = "
        SELECT 
            j.id as id,
            j.date_action as date_action,
            r.libelle as responsable,
            a.nom as nom,
            a.prenom as prenom,
            a.photo as photo,
            concat(a.nom, ' ' , a.prenom) as nom_prenom,
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
        $query .= " AND (a.nom LIKE :search OR a.prenom LIKE :search OR concat(a.nom, ' ', a.prenom) LIKE :search)";
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
    $messages['errors'][] = "Erreur lors du chargement de l'historique.";
}

try {
    $sql21 = "SELECT libelle FROM role ";
    $stmt21 = $pdo->query($sql21);
    $role = $stmt21->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans historique_content.php (récupération rôles) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des rôles.";
}

try {
    $sql22 = "SELECT DISTINCT action_type FROM journal_actions ";
    $stmt22 = $pdo->query($sql22);
    $action_type = $stmt22->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans historique_content.php (récupération types d'action) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des types d'action.";
}

$sql = "SELECT a.id, a.nom, a.prenom, CONCAT(a.prenom, ' ', a.nom) AS nom_prenom, a.matricule, a.email, a.telephone, a.photo, a.bureau_id, b.libele AS libele_bureau, s.libele AS libele_service 
        FROM agent a 
        JOIN bureau b ON a.bureau_id = b.id 
        JOIN service s ON b.service_id = s.id 
        WHERE a.telephone LIKE :search OR CONCAT(a.prenom, ' ', a.nom) LIKE :search";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
    $ag = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans historique_content.php (récupération agents) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des agents.";
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

<!-- Filtres et recherche -->
<div
    class="bg-gradient-to-r from-indigo-50 to-blue-50 p-4 sm:p-6 rounded-xl shadow-sm mb-6 transition-all hover:shadow-md">
    <div class="flex items-center mb-4">
        <i class="fas fa-filter text-indigo-600 mr-2"></i>
        <h2 class="text-base sm:text-lg font-semibold text-gray-700">Recherche et filtres</h2>
    </div>
    <form action="#" method="get" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" id="filter-form">
        <input type="hidden" name="page" value="historique_content">
        <div class="relative">
            <label for="search" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Recherche par
                nom/prénom</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" name="search"
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
                    <?php foreach ($action_type as $type): ?>
                    <option value="<?php echo htmlspecialchars($type['action_type'], ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo ($filter_actions === $type['action_type']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['action_type'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
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
                    <?php foreach ($role as $r): ?>
                    <option value="<?php echo htmlspecialchars($r['libelle'], ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo ($filter_roles === $r['libelle']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['libelle'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
</div>

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
            <?php foreach ($action as $act): ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center align-middle">
                    <div class="flex items-center">
                        <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                            <?php if (!empty($act['photo']) && file_exists($act['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($act['photo'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($act['nom_prenom'], ENT_QUOTES, 'UTF-8'); ?>"
                                class="rounded-full object-cover"
                                onerror="this.parentNode.innerHTML = '<div class=\'w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center\'><span class=\'text-blue-600 font-medium text-xs\'>' + getInitials('<?php echo htmlspecialchars($act['nom_prenom'], ENT_QUOTES, 'UTF-8'); ?>') + '</span></div>'">
                            <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-600 font-medium text-xs">
                                    <?php echo strtoupper(substr($act['prenom'], 0, 1) . substr($act['nom'], 0, 1)); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($act['nom_prenom'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    <?php echo htmlspecialchars($act['responsable'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    <?php echo htmlspecialchars($act['action'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    <?php echo date('d M Y, H:i', strtotime($act['date_action'])); ?>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <button
                        class="detail-btn bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                        data-id="<?php echo $act['id']; ?>" title="Voir les détails">
                        <i class="fas fa-eye mr-1"></i>
                        Voir détails
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="detailmodal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4 p-6 relative">
        <button onclick="closeModal()"
            class="absolute top-3 right-4 text-gray-500 hover:text-red-500 text-2xl font-bold transition duration-200 ease-in-out"
            aria-label="Fermer le modal">
            ×
        </button>
        <h2 class="text-lg font-semibold mb-4 text-blue-700">Détails de l'action</h2>
        <div id="modal-content" class="text-sm text-gray-800 space-y-2">
            <!-- Contenu chargé dynamiquement via JavaScript -->
        </div>
    </div>
</div>

<script>
function getInitials(name) {
    return name.split(' ').map(word => word.charAt(0).toUpperCase()).join('');
}

function closeModal() {
    document.getElementById('detailmodal').classList.add('hidden');
}

document.querySelectorAll('.detail-btn').forEach(button => {
    button.addEventListener('click', () => {
        const actionId = button.getAttribute('data-id');
        const actions = JSON.parse(document.getElementById('actionData').textContent);
        const action = actions.find(a => a.id == actionId);

        if (action) {
            const modalContent = document.getElementById('modal-content');
            modalContent.innerHTML = '';
            try {
                const details = JSON.parse(action.details);
                for (const [key, value] of Object.entries(details)) {
                    const formattedKey = key.charAt(0).toUpperCase() + key.slice(1).replace('_', ' ');
                    const div = document.createElement('div');
                    div.innerHTML =
                        `<span class="font-semibold">${formattedKey}:</span> ${value || 'N/A'}`;
                    modalContent.appendChild(div);
                }
            } catch (e) {
                modalContent.innerHTML = '<div class="text-red-600">Données non lisibles</div>';
            }
            document.getElementById('detailmodal').classList.remove('hidden');
        }
    });
});

// Notifier que les notifications ont été marquées comme vues
window.dispatchEvent(new CustomEvent('notifications:updated'));
</script>