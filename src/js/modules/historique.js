// Variables globales
let actionData = [];
let roleData = [];
let agentData = [];
let actionTypeData = [];
let filteredData = [];

// Variables pour la gestion des filtres
let currentFilters = {
    search: '',
    actions: '',
    roles: ''
};

// Fonction d'initialisation principale
function initHistoriqueActions() {
    console.log('Initialisation de l\'historique des actions...');
    
    // Charger les données
    loadDataFromScripts();
    
    // Configurer les événements
    setupEventListeners();
    
    // Initialiser l'affichage
    applyFilters();
    
    console.log('Historique des actions initialisé');
}

// Fonction pour charger les données depuis les scripts JSON
function loadDataFromScripts() {
    try {
        // Récupérer les données depuis les scripts JSON intégrés
        const actionScript = document.getElementById('actionData');
        const roleScript = document.getElementById('roleData');
        const agentScript = document.getElementById('agentData');
        const actionTypeScript = document.getElementById('actiontypeData');

        if (actionScript && actionScript.textContent) {
            actionData = JSON.parse(actionScript.textContent);
        }
        if (roleScript && roleScript.textContent) {
            roleData = JSON.parse(roleScript.textContent);
        }
        if (agentScript && agentScript.textContent) {
            agentData = JSON.parse(agentScript.textContent);
        }
        if (actionTypeScript && actionTypeScript.textContent) {
            actionTypeData = JSON.parse(actionTypeScript.textContent);
        }

        // Initialiser les données filtrées
        filteredData = [...actionData];
        
        console.log('Données chargées:', {
            actions: actionData.length,
            roles: roleData.length,
            agents: agentData.length,
            actionTypes: actionTypeData.length
        });
        
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
        actionData = [];
        filteredData = [];
    }
}

// Configuration des écouteurs d'événements
function setupEventListeners() {
    // Écouteur pour la recherche
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            currentFilters.search = e.target.value.toLowerCase();
            applyFilters();
        });
    }

    // Écouteurs pour les filtres
    const filterActions = document.getElementById('filter_actions');
    if (filterActions) {
        filterActions.addEventListener('change', function(e) {
            currentFilters.actions = e.target.value;
            applyFilters();
        });
    }

    const filterRoles = document.getElementById('filter_roles');
    if (filterRoles) {
        filterRoles.addEventListener('change', function(e) {
            currentFilters.roles = e.target.value;
            applyFilters();
        });
    }

    // Écouteur pour fermer le modal
    const modal = document.getElementById('detailmodal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeDetailModal();
            }
        });
    }

    // Écouteur pour la touche Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDetailModal();
        }
    });
}

// Fonction pour appliquer les filtres
function applyFilters() {
    if (!Array.isArray(actionData)) {
        console.error('actionData n\'est pas un tableau');
        return;
    }

    filteredData = actionData.filter(action => {
        // Filtre par recherche (nom/prénom)
        const matchesSearch = !currentFilters.search || 
            (action.nom_prenom && action.nom_prenom.toLowerCase().includes(currentFilters.search));

        // Filtre par type d'action
        const matchesAction = !currentFilters.actions || 
            action.action === currentFilters.actions;

        // Filtre par rôle
        const matchesRole = !currentFilters.roles || 
            action.responsable === currentFilters.roles;

        return matchesSearch && matchesAction && matchesRole;
    });

    console.log('Filtres appliqués:', currentFilters, 'Résultats:', filteredData.length);
    updateTable();
}

// Fonction pour mettre à jour le tableau
function updateTable() {
    const tableBody = document.querySelector('#actionTable tbody');
    if (!tableBody) {
        console.error('Corps du tableau non trouvé');
        return;
    }

    // Vider le tableau
    tableBody.innerHTML = '';

    // Vérifier s'il y a des données
    if (filteredData.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                        <p class="text-lg font-medium">Aucune action trouvée</p>
                        <p class="text-sm">Essayez de modifier vos critères de recherche</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    // Créer les lignes du tableau
    filteredData.forEach(action => {
        const row = createTableRow(action);
        if (row) {
            tableBody.appendChild(row);
        }
    });

    // Reconfigurer les boutons de détails
    setupDetailButtons();
}

// Fonction pour créer une ligne du tableau
function createTableRow(action) {
    if (!action) return null;

    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-50 transition-colors';

    // Générer les initiales
    const initials = getInitials(action.nom_prenom || '');
    
    // Créer le contenu de la photo/avatar
    let photoContent = '';
    if (action.photo && action.photo.trim() !== '') {
        photoContent = `
            <img src="${escapeHtml(action.photo)}" 
                 alt="${escapeHtml(action.nom_prenom || '')}" 
                 class="w-10 h-10 rounded-full object-cover"
                 onerror="this.outerHTML='<div class=\\'w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center\\'><span class=\\'text-blue-600 font-medium text-xs\\'>${initials}</span></div>'">
        `;
    } else {
        photoContent = `
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="text-blue-600 font-medium text-xs">${initials}</span>
            </div>
        `;
    }

    row.innerHTML = `
        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center align-middle">
            <div class="flex items-center">
                <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                    ${photoContent}
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(action.nom_prenom || '')}</div>
                </div>
            </div>
        </td>
        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${escapeHtml(action.responsable || '')}</td>
        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${escapeHtml(action.action || '')}</td>
        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${escapeHtml(formatDate(action.date_action))}</td>
        <td class="px-4 py-3 whitespace-nowrap text-right">
            <button class="detail-btn bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500" 
                    data-id="${action.id}" 
                    title="Voir détails">
                <i class="fas fa-eye mr-1"></i>
                Voir détails
            </button>
        </td>
    `;

    return row;
}

// Fonction pour configurer les boutons de détails
function setupDetailButtons() {
    const detailButtons = document.querySelectorAll('.detail-btn');
    detailButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            const actionId = this.getAttribute('data-id');
            const action = actionData.find(a => a.id == actionId);
            
            if (action) {
                openDetailModal(action);
            } else {
                console.error('Action non trouvée:', actionId);
            }
        };
    });
}

// Fonction pour ouvrir le modal de détails
function openDetailModal(action) {
    const modal = document.getElementById('detailmodal');
    if (!modal) {
        console.error('Modal non trouvé');
        return;
    }

    // Mettre à jour le contenu du modal
    const modalContent = modal.querySelector('.bg-white');
    if (modalContent) {
        modalContent.innerHTML = `
            <button onclick="closeDetailModal()" 
                class="absolute top-2 right-3 text-gray-600 hover:text-red-500 text-xl font-bold">
                &times;
            </button>
            <h2 class="text-lg font-semibold mb-4">Détails de l'action</h2>
            <div class="text-sm text-gray-800 space-y-2">
                <div><span class="font-semibold">Agent:</span> ${escapeHtml(action.nom_prenom || '')}</div>
                <div><span class="font-semibold">Rôle:</span> ${escapeHtml(action.responsable || '')}</div>
                <div><span class="font-semibold">Action:</span> ${escapeHtml(action.action || '')}</div>
                <div><span class="font-semibold">Date:</span> ${escapeHtml(formatDate(action.date_action))}</div>
                <div class="mt-4">
                    <span class="font-semibold">Détails:</span>
                    <div class="mt-2 p-3 bg-gray-50 rounded-lg">
                        ${formatActionDetails(action.details)}
                    </div>
                </div>
            </div>
        `;
    }

    // Afficher le modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Fonction pour fermer le modal
function closeDetailModal() {
    const modal = document.getElementById('detailmodal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Fonction pour formater les détails de l'action
function formatActionDetails(details) {
    if (!details || details.trim() === '') {
        return '<div class="text-gray-500 italic">Aucun détail disponible</div>';
    }

    try {
        const donnees = JSON.parse(details);
        if (typeof donnees === 'object' && donnees !== null) {
            let html = '';
            for (const [champ, valeur] of Object.entries(donnees)) {
                const champFormate = champ.charAt(0).toUpperCase() + champ.slice(1).replace(/_/g, ' ');
                html += `<div><span class="font-semibold">${escapeHtml(champFormate)}:</span> ${escapeHtml(String(valeur))}</div>`;
            }
            return html || '<div class="text-gray-500 italic">Aucun détail disponible</div>';
        }
    } catch (error) {
        console.log('Détails non JSON, affichage brut');
    }

    return `<div>${escapeHtml(String(details))}</div>`;
}

// Fonction pour obtenir les initiales
function getInitials(nomPrenom) {
    if (!nomPrenom || nomPrenom.trim() === '') return 'NN';
    const parts = nomPrenom.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }
    return nomPrenom.charAt(0).toUpperCase();
}

// Fonction pour échapper le HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// Fonction pour formater la date
function formatDate(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return dateString;
    }
}

// Fonction pour réinitialiser les filtres
function resetFilters() {
    currentFilters = {
        search: '',
        actions: '',
        roles: ''
    };

    // Réinitialiser les champs du formulaire
    const searchInput = document.getElementById('search');
    const filterActions = document.getElementById('filter_actions');
    const filterRoles = document.getElementById('filter_roles');

    if (searchInput) searchInput.value = '';
    if (filterActions) filterActions.value = '';
    if (filterRoles) filterRoles.value = '';

    // Réappliquer les filtres
    applyFilters();
}

// Exposer les fonctions globalement
window.closeDetailModal = closeDetailModal;
window.resetFilters = resetFilters;

// Initialiser quand le DOM est chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHistoriqueActions);
} else {
    initHistoriqueActions();
}