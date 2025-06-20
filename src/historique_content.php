<?php
require "db_connect.php";

try {
    $stmt = $pdo->query("
        SELECT 
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
        ORDER BY j.date_action DESC;
    ");
    
    $action = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 catch (PDOException $e) {
    error_log("Erreur dans fetch_historique.php : " . $e->getMessage());
    echo "<tr><td colspan='3'>Erreur lors du chargement de l'historique : " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
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
            <label for="filter_service" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par actions</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-building text-gray-400"></i>
                </div>
                <select name="filter_actions" id="filter_service"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Toutes les actions</option>
                </select>
            </div>
        </div>

        <div>
            <label for="filter_role" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par role</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-door-open text-gray-400"></i>
                </div>
                <select name="filter_date" id="filter_role"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les roles</option>
                </select>
            </div>
        </div>
        <div>
            <label for="filter_date" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par date</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-door-open text-gray-400"></i>
                </div>
                <select name="filter_date" id="filter_date"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Toutes les dates</option>
                </select>
            </div>
        </div>
    
    </form>
</div> 






<div class="hidden lg:block overflow-x-auto rounded-xl shadow-sm bg-white" id="compteTable">
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
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 "><?= htmlspecialchars($action['action'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($action['date_action'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-right align-middle">
    <button onclick="openModal('modal-<?= $row['id'] ?>')" 
        class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 transition">
        Voir détails
    </button>
</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php /*
<div id="modal-<?= $action['id'] ?>" 
     class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">

    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4 p-6 relative">
        <button onclick="closeModal('modal-<?= $action['id'] ?>')" 
            class="absolute top-2 right-3 text-gray-600 hover:text-red-500 text-xl font-bold">
            &times;
        </button>

        <h2 class="text-lg font-semibold mb-4">Détails de l'action</h2>
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
*/ ?>

