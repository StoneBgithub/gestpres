<?php
require "db_connect.php";
// Démarrer la session uniquement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer le paramètre de recherche
$searchs = $_GET['searchs'] ?? '';

// Vérifier si l'utilisateur est connecté
$agent_conn = $_SESSION['user_id'] ?? null;
$messages = ['success' => [], 'errors' => []];
if (!$agent_conn) {
    header("Location: login.php");
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT 
            j.id as id,
            j.date_action as date_action,
            r.libelle as responsable,
            a.nom as nom,
            a.prenom as prenom,
            a.photo as photo,
            concat(a.nom, ' ' , a.prenom) as nom_prenom,
            j.action_type as action_type,
            j.donnees as details
        FROM journal_actions j
        JOIN login l on j.ag_id=l.id
        JOIN agent a ON l.agent_id=a.id
        JOIN role r ON l.role_id=r.id
        ORDER BY j.date_action DESC;
    ");
    
    $action = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 catch (PDOException $e) {
    error_log("Erreur dans fetch_historique.php : " . $e->getMessage());
    echo "<tr><td colspan='3'>Erreur lors du chargement de l'historique : " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

try {
    $sql21 = "SELECT libelle FROM role ";
    $stmt21 = $pdo->query($sql21);
    $role= $stmt21->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans agent_content.php (récupération services) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des services.";
}


try {
    $sql22 = "SELECT DISTINCT action_type FROM  journal_actions ";
    $stmt22 = $pdo->query($sql22);
    $action_type= $stmt22->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans historique_content.php (récupération services) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des services.";
}


try {
    $sql = "SELECT a.id, a.nom, a.prenom, CONCAT(a.prenom, ' ', a.nom) AS nom_prenom, a.matricule, a.email, a.telephone, a.photo, a.bureau_id, b.libele AS libele_bureau, s.libele AS libele_service 
            FROM agent a 
            JOIN bureau b ON a.bureau_id = b.id 
            JOIN service s ON b.service_id = s.id 
            WHERE a.telephone LIKE :searchs OR CONCAT(a.prenom, ' ', a.nom) LIKE :searchs";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['searchs' => "%$searchs%"]);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans historique_content.php (récupération agents) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des agents.";
    $agents = []; // Pour éviter des erreurs plus tard si $agents est utilisé
}



?>
<?php
// Stocker les données dans un élément invisible pour le JS
echo '<script id="actionData" type="application/json">' . json_encode($action, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="roleData" type="application/json">' . json_encode($role, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="agentData" type="application/json">' . json_encode($agents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="actiontypeData" type="application/json">' . json_encode($action_type, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';


?>



<!-- Filtres et recherche -->
<div class="bg-gradient-to-r from-indigo-50 to-blue-50 p-4 sm:p-6 rounded-xl shadow-sm mb-6 transition-all hover:shadow-md">
    <div class="flex items-center mb-4">
        <i class="fas fa-filter text-indigo-600 mr-2"></i>
        <h2 class="text-base sm:text-lg font-semibold text-gray-700">Recherche et filtres</h2>
    </div>
    <form action="#" method="get" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <input type="hidden" name="page" value="historique_content">
        <div class="relative">
            <label for="searchs" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Recherche par nom/prénom</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-searchs text-gray-400"></i>
                </div>
                <input type="text" value="<?= htmlspecialchars($searchs, ENT_QUOTES, 'UTF-8') ?>" name="searchs" id="searchs"
                    placeholder="Rechercher un agent..."
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>
        </div>
        <div>
            <label for="filter_actions" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par actions</label>
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
            <label for="filter_roles" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par role</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-door-open text-gray-400"></i>
                </div>
                <select name="roles" id="filter_roles"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les roles</option>
                     <?php foreach ($role as $role): ?>
                    <option value="<?= htmlspecialchars($role['libelle'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($role['libelle'], ENT_QUOTES, 'UTF-8') ?></option>
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
                <th scope="col" class="px-4 py-3  text-left">AGENT</th>
                <th scope="col" class="px-4 py-3  text-left ">role</th>
                <th scope="col" class="px-4 py-3  text-left">action</th>
                <th scope="col" class="px-4 py-3 text-left ">date</th>
                <th scope="col" class="px-4 py-3 text-right">detail </th>
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
                                 onerror="this.parentNode.innerHTML = '<div class=\'w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center\'><span class=\'text-blue-600 font-medium text-xs\'>' + getInitials('<?= htmlspecialchars($agent['nom_prenom'], ENT_QUOTES, 'UTF-8') ?>') + '</span></div>'">
                            <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-600 font-medium text-xs">
                                    <?php echo strtoupper(substr($action['prenom'], 0, 1) . substr($action['nom'], 0, 1)); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($action['nom_prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 "><?= htmlspecialchars($action['responsable'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 "><?= htmlspecialchars($action['action_type'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($action['date_action'], ENT_QUOTES, 'UTF-8') ?></td>
               <td class="px-4 py-3 whitespace-nowrap text-right">
    <button class="detail-btn bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500" 
            data-id="<?= $action['id'] ?>" title="detail">
        <i class="fas fa-eye mr-1"></i>
       <span> Voir détails</span>
    </button>
</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<div id="detailmodal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">

    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4 p-6 relative">
        <button onclick="closeModal('modal-<?= $action['id'] ?>')" 
            class="absolute top-2 right-3 text-gray-600 hover:text-red-500 text-xl font-bold">
            &times;
        </button>

         <h3 id="detailTitle" class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-eye mr-1"></i>
       <span> Voir détails</span>
            </h3>
        <div class="text-sm text-gray-800 space-y-2">
            <?php
            $donnees = json_decode($action['details'], true);
            if (is_array($donnees)) {
                foreach ($donnees as $champ => $valeur) {
                    $champ_formate = ucfirst(str_replace('_', ' ', $champ));
                    echo "<div><span class='font-semibold'>" . htmlspecialchars($champ_formate) . ":</span> " . htmlspecialchars($valeur) . "</div>";
                }
            } else {
                echo "<div class='text-red-600'>(Données non lisibles)</div>";
            }
            ?>
        </div>
    </div>
</div>

<!-- Modale pour messages de succès/erreur -->
<div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50" 
     data-messages="<?php echo htmlspecialchars(json_encode($messages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
         id="messageModalContent">
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-info-circle mr-2 <?php echo !empty($messages['errors']) ? 'text-red-500' : 'text-green-600'; ?>"></i>
                <span><?php echo !empty($messages['errors']) ? 'Erreur' : 'Succès'; ?></span>
            </h3>
            <button class="close-modal text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 sm:p-6">
            <?php if (!empty($messages['success'])): ?>
                <?php foreach ($messages['success'] as $msg): ?>
                    <p class="text-green-600 font-semibold text-sm sm:text-base mb-2">✅ <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($messages['errors'])): ?>
                <?php foreach ($messages['errors'] as $error): ?>
                    <p class="text-red-600 font-semibold text-sm sm:text-base mb-2">❌ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
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


