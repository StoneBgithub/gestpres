<?php
$agents = [
    ['id' => 1, 'nom' => 'Dubois', 'prenom' => 'Martin', 'service' => 'Informatique', 'bureau' => 'A101', 'telephone' => '01 23 45 67 89', 'initiales' => 'DM', 'couleur' => 'blue'],
    ['id' => 2, 'nom' => 'Sanchez', 'prenom' => 'Laura', 'service' => 'Ressources Humaines', 'bureau' => 'B205', 'telephone' => '01 34 56 78 90', 'initiales' => 'SL', 'couleur' => 'red'],
    ['id' => 3, 'nom' => 'Moreau', 'prenom' => 'Thomas', 'service' => 'Comptabilité', 'bureau' => 'C310', 'telephone' => '01 45 67 89 12', 'initiales' => 'MT', 'couleur' => 'green'],
    ['id' => 4, 'nom' => 'Lefebvre', 'prenom' => 'Claire', 'service' => 'Marketing', 'bureau' => 'D415', 'telephone' => '01 56 78 91 23', 'initiales' => 'LC', 'couleur' => 'purple'],
    ['id' => 5, 'nom' => 'Bernard', 'prenom' => 'Jacques', 'service' => 'Direction', 'bureau' => 'E520', 'telephone' => '01 67 89 12 34', 'initiales' => 'BJ', 'couleur' => 'yellow']
];

// Stocker les données dans un élément invisible pour le JS
echo '<script id="agentsData" type="application/json">' . json_encode($agents) . '</script>';
?>

<!-- Filtres et recherche -->
<div class="bg-gradient-to-r from-indigo-50 to-blue-50 p-5 rounded-xl shadow-sm mb-6 transition-all hover:shadow-md">
    <div class="flex items-center mb-4">
        <i class="fas fa-filter text-indigo-600 mr-2"></i>
        <h2 class="text-lg font-semibold text-gray-700">Recherche et filtres</h2>
    </div>
    <form action="#" method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <input type="hidden" name="page" value="agents_content">
        
        <div class="relative">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche par nom/prénom</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" name="search" id="search" placeholder="Rechercher un agent..." 
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>
        </div>
        
        <div>
            <label for="filter_service" class="block text-sm font-medium text-gray-700 mb-1">Filtrer par service</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-building text-gray-400"></i>
                </div>
                <select name="filter_service" id="filter_service" 
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les services</option>
                    <option value="Informatique">Informatique</option>
                    <option value="Ressources Humaines">Ressources Humaines</option>
                    <option value="Comptabilité">Comptabilité</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Direction">Direction</option>
                </select>
            </div>
        </div>
        
        <div>
            <label for="filter_bureau" class="block text-sm font-medium text-gray-700 mb-1">Filtrer par bureau</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-door-open text-gray-400"></i>
                </div>
                <select name="filter_bureau" id="filter_bureau" 
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les bureaux</option>
                    <option value="A101">A101</option>
                    <option value="B205">B205</option>
                    <option value="C310">C310</option>
                    <option value="D415">D415</option>
                    <option value="E520">E520</option>
                </select>
            </div>
        </div>
        
        <div class="flex items-end space-x-2">
            <a href="?page=agents_content" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                <i class="fas fa-redo-alt"></i>
            </a>
            <button type="button" class="add-agent-btn px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i> Ajouter un agent
            </button>
        </div>
    </form>
</div>

<!-- Affichage des agents - Vue carte -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:hidden gap-4 mb-6" id="agentsCards">
    <?php foreach ($agents as $index => $agent): 
        $delay = ($index * 0.1);
    ?>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 transform hover:-translate-y-1" style="animation-delay: <?= $delay ?>s;">
        <div class="p-4">
            <div class="flex items-center mb-4">
                <div class="h-14 w-14 rounded-full bg-<?= $agent['couleur'] ?>-100 flex items-center justify-center mr-3 border-2 border-<?= $agent['couleur'] ?>-300 shadow-sm">
                    <span class="text-<?= $agent['couleur'] ?>-600 font-bold text-xl"><?= $agent['initiales'] ?></span>
                </div>
                <div>
                    <h3 class="font-semibold text-lg text-gray-800"><?= $agent['prenom'] ?> <?= $agent['nom'] ?></h3>
                    <div class="flex items-center text-gray-600 text-sm">
                        <i class="fas <?= $serviceIcons[$agent['service']] ?? 'fa-briefcase' ?> mr-1"></i>
                        <span><?= $agent['service'] ?></span>
                    </div>
                </div>
            </div>
            <div class="space-y-2 mb-4">
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-door-open w-5 text-center mr-2"></i>
                    <span><?= $agent['bureau'] ?></span>
                </div>
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-phone-alt w-5 text-center mr-2"></i>
                    <span><?= $agent['telephone'] ?></span>
                </div>
            </div>
            <div class="flex justify-between pt-3 border-t border-gray-100">
                <button class="edit-agent-btn px-3 py-1 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" data-id="<?= $agent['id'] ?>">
                    <i class="fas fa-edit mr-1"></i> Modifier
                </button>
                <button class="qr-agent-btn px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors" data-id="<?= $agent['id'] ?>">
                    <i class="fas fa-qrcode mr-1"></i> QR Code
                </button>
                <button class="delete-agent-btn px-3 py-1 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" data-id="<?= $agent['id'] ?>">
                    <i class="fas fa-trash mr-1"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Affichage des agents - Vue tableau -->
<div class="hidden lg:block overflow-hidden rounded-xl shadow-sm bg-white" id="agentsTable">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 text-gray-700 text-sm uppercase font-semibold">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bureau</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($agents as $agent): ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="h-10 w-10 rounded-full bg-<?= $agent['couleur'] ?>-100 flex items-center justify-center mr-3 border border-<?= $agent['couleur'] ?>-300">
                            <span class="text-<?= $agent['couleur'] ?>-600 font-bold"><?= $agent['initiales'] ?></span>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900"><?= $agent['prenom'] ?> <?= $agent['nom'] ?></div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <i class="fas <?= $serviceIcons[$agent['service']] ?? 'fa-briefcase' ?> text-gray-400 mr-2"></i>
                        <span class="text-sm text-gray-900"><?= $agent['service'] ?></span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $agent['bureau'] ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $agent['telephone'] ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex space-x-2 justify-end">
                        <button class="edit-agent-btn text-blue-600 hover:text-blue-900 transition-colors" data-id="<?= $agent['id'] ?>" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="qr-agent-btn text-green-600 hover:text-green-900 transition-colors" data-id="<?= $agent['id'] ?>" title="Générer QR Code">
                            <i class="fas fa-qrcode"></i>
                        </button>
                        <button class="delete-agent-btn text-red-600 hover:text-red-900 transition-colors" data-id="<?= $agent['id'] ?>" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modales -->
<div id="agentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl transform transition-all duration-300 scale-95 opacity-0" id="agentModalContent">
        <div class="border-b px-6 py-4 flex justify-between items-center">
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-user-plus mr-2 text-indigo-600"></i>
                <span>Ajouter un nouvel agent</span>
            </h3>
            <button class="close-modal text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="agentForm" action="?page=agents_content&action=save" method="post" class="p-6">
            <input type="hidden" id="agent_id" name="agent_id" value="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="nom" id="nom" required
                               class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>
                </div>
                <!-- Ajoutez ici les autres champs si nécessaire -->
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="deleteModalContent">
        <div class="border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>
                <span>Confirmer la suppression</span>
            </h3>
            <button class="close-modal text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <p class="text-gray-700 mb-6">Êtes-vous sûr de vouloir supprimer cet agent ? Cette action est irréversible.</p>
            <div class="flex justify-end space-x-3">
                <button type="button" class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <a id="confirmDeleteBtn" href="#" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Supprimer
                </a>
            </div>
        </div>
    </div>
</div>

<div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="qrModalContent">
        <div class="border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-qrcode mr-2 text-green-600"></i>
                <span>QR Code de l'agent</span>
            </h3>
            <button class="close-modal text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="flex justify-center mb-4">
                <div id="qrCodeContainer" class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm"></div>
            </div>
            <div class="text-center mb-6">
                <p id="qrAgentName" class="text-lg font-medium text-gray-800"></p>
                <p id="qrAgentInfo" class="text-sm text-gray-600"></p>
            </div>
            <div class="flex justify-center space-x-3">
                <button type="button" class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Fermer
                </button>
                <button type="button" id="downloadQRBtn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-download mr-2"></i> Télécharger
                </button>
            </div>
        </div>
    </div>
</div>