import { eventBus } from "../config.js";
console.log("✅ JS chargé !");
// Liste des données et variables globales
let actions = [];
let agents = [];
let roles = [];
let actionTypes = [];
let detailModalInitialized = false;

export function init() {
  loadActionsData();
  loadAgentsData();
  loadRolesData();
  loadActionTypesData();
  setupListeners();
  setupFilters();
  initModals();
  setupDetailModal();
  checkAndShowMessageModal()
}

// Fonction pour générer le cercle d'initiales
function getInitialsCircle(name) {
  if (!name)
    return '<div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center"><span class="text-blue-600 font-medium text-xs">NA</span></div>';
  const initials = name
    .split(" ")
    .map((word) => word[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
  return `<div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center"><span class="text-blue-600 font-medium text-xs">${initials}</span></div>`;
}

// Charger les données des actions depuis l'élément script
function loadActionsData() {
  const actionsDataElement = document.getElementById("actionData");
  if (actionsDataElement) {
    try {
      const rawData = actionsDataElement.textContent;
      actions = JSON.parse(rawData).map((action, index) => {
        if (!action.id) {
          console.warn(`Action à l'index ${index} n'a pas d'ID défini`, action);
        }
        action.id = String(action.id);
        return action;
      });
      console.log(`${actions.length} actions chargées`, actions);
    } catch (e) {
      console.error("Erreur lors du parsing des données actions:", e);
      actions = [];
    }
  } else {
    console.warn("Élément actionData non trouvé");
    actions = [];
  }
}


// Charger les données des rôles
function loadRolesData() {
  const roleDataElement = document.getElementById("roleData");
  if (roleDataElement) {
    try {
      roles = JSON.parse(roleDataElement.textContent);
      console.log(`${roles.length} rôles chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données rôles:", e);
      roles = [];
    }
  } else {
    roles = [];
  }
}

// Charger les données des agents
function loadAgentsData() {
  const agentsDataElement = document.getElementById("agentData");
  if (agentsDataElement) {
    try {
      agents = JSON.parse(agentsDataElement.textContent);
      console.log(`${agents.length} agents chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données agents:", e);
      agents = [];
    }
  } else {
    agents = [];
  }
}
// Charger les données des types d'actions
function loadActionTypesData() {
  const actionTypesDataElement = document.getElementById("actiontypeData");
  if (actionTypesDataElement) {
    try {
      actionTypes = JSON.parse(actionTypesDataElement.textContent);
      console.log(`${actionTypes.length} types d'actions chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données types d'actions:", e);
      actionTypes = [];
    }
  } else {
    actionTypes = [];
  }
}



// Vérifier et afficher messageModal si des messages sont présents
function checkAndShowMessageModal() {
  const messageModal = document.getElementById("messageModal");
  if (messageModal && messageModal.dataset.messages) {
    try {
      const messages = JSON.parse(messageModal.dataset.messages);
      if (
        (messages.success && messages.success.length > 0) ||
        (messages.errors && messages.errors.length > 0)
      ) {
        showModal("messageModal");
      }
    } catch (e) {
      console.error("Erreur lors du parsing de data-messages:", e);
    }
  }
}
// Initialiser les modales
function initModals() {
  document.querySelectorAll(".close-modal").forEach((btn) => {
    btn.addEventListener("click", function () {
      const modal = this.closest(
        " #messageModal"
      );
      if (modal) {
        closeModal(modal.id);
      }
    });
  });

  document
    .querySelectorAll("#messageModal")
    .forEach((modal) => {
      modal.addEventListener("click", function (e) {
        if (e.target === this) {
          closeModal(this.id);
        }
      });
    });
}




// Configurer les écouteurs d'événements
function setupListeners() {
  // Utiliser la délégation d'événements pour les boutons dynamiques
  document.addEventListener("click", function (e) {
    const target = e.target;

    // Boutons de détails
    if (target.matches(".detail-btn") || target.closest(".detail-btn")) {
      e.preventDefault();
      const btn = target.matches(".detail-btn") ? target : target.closest(".detail-btn");
      const actionId = btn.getAttribute("data-id");
      console.log("Bouton cliqué, ID:", actionId);
      showActionDetails(actionId);
    }

    // Boutons de fermeture des modales
    if (target.matches(".close-modal") || target.closest(".close-modal")) {
      e.preventDefault();
      const modal = target.closest("[id*='modal']");
      if (modal) {
        window.closeModal(modal.id);
      }
    }
  });

  // Fermer modal en cliquant à l'extérieur
  document.addEventListener("click", function(e) {
    const modal = document.getElementById("detailmodal");
    if (modal && e.target === modal) {
      window.closeModal("detailmodal");
    }
  });
}

// Fonction pour afficher les détails d'une action
function showActionDetails(actionId) {
  console.log("showActionDetails appelé avec ID:", actionId);
  console.log("Actions disponibles:", actions);
  
  const modal = document.getElementById("detailmodal");
  if (!modal) {
    console.error("Modal detailmodal non trouvé");
    return;
  }

  const actionIdStr = String(actionId);
  const action = actions.find((a) => String(a.id) === actionIdStr);
  
  console.log("Action trouvée:", action);
  
  if (!action) {
    alert("Action non trouvée. ID recherché: " + actionIdStr);
    return;
  }

  // Mettre à jour le contenu du modal
  updateModalContent(action);
  showModal("detailmodal");
}



// Afficher un modal avec animation
export function showModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  modal.classList.remove("hidden");
  const modalContent = document.getElementById(`${modalId}Content`);
  if (modalContent) {
    setTimeout(() => {
      modalContent.classList.remove("scale-95", "opacity-0");
      modalContent.classList.add("scale-100", "opacity-100");
    }, 10);
  }
}




// Fermer un modal
export function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  const modalContent = document.getElementById(`${modalId}Content`);
  if (modalContent) {
    modalContent.classList.remove("scale-100", "opacity-100");
    modalContent.classList.add("scale-95", "opacity-0");
    setTimeout(() => modal.classList.add("hidden"), 300);
  } else {
    modal.classList.add("hidden");
  }

  eventBus.publish("modal:closed", { modalId });
}


eventBus.subscribe("comptes:externalUpdate", (data) => {
  console.log("Mise à jur externe des comptes reçue", data);
  // Recharger les données et rafraîchir l'affichage
  loadActionsData();
  refreshDisplay();
});

// Fonction pour rafraîchir l'affichage
function refreshDisplay() {
  const event = new Event('input');
  const searchInput = document.getElementById("searchs");
  if (searchInput) {
    searchInput.dispatchEvent(event);
  }
}
function setupFilters() {
  const searchsInput = document.getElementById("searchs");
  const rolesSelect = document.getElementById("filter_roles");
  const actionTypesSelect= document.getElementById("filter_actions");
  const actionTableBody = document.querySelector("#actionTable tbody");

  if (!searchsInput || !rolesSelect || !actionTypesSelect || !actionTableBody) {
    console.warn("Éléments nécessaires pour les filtres non trouvés");
    return;
  }

  // Écouteurs pour les filtres
  searchsInput.addEventListener("input", filterAndDisplayActions);
  rolesSelect.addEventListener("change", filterAndDisplayActions);
  actionTypesSelect.addEventListener("change", filterAndDisplayActions);

  // Affichage initial
  filterAndDisplayActions();

  function filterAndDisplayActions() {
    const searchsQuery = searchsInput.value.trim().toLowerCase();
    const rolesFilter = rolesSelect.value;
    const actionTypesFilter = actionTypesSelect.value;

    const filteredAction = actions.filter((action) => {
      const nomPrenom = action.nom_prenom ? action.nom_prenom.toLowerCase() : "";
      const matchesSearch = nomPrenom.includes(searchsQuery);
      const matchesRole = rolesFilter === "" || action.role === rolesFilter;
      const matchesActionType= actionTypesFilter === "" || action.actionType === actionTypesFilter;

      return matchesSearch && matchesRole && matchesActionType;
    });

    // Mettre à jour le tableau
    if (filteredActions.length === 0) {
      actionTableBody.innerHTML = `
        <tr>
          <td colspan="6" class="px-4 py-6 text-center">
            <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl shadow-sm animate-fade-in">
              <i class="fas fa-search text-4xl text-indigo-500 mb-4 animate-pulse"></i>
              <h3 class="text-lg font-semibold text-gray-800 mb-2">Oups, aucun utilisateur trouvé !</h3>
              <p class="text-sm text-gray-600">Essayez une autre recherche ou un autre filtre.</p>
            </div>
          </td>
        </tr>
      `;
    } else {
      actionsCardsContainer.innerHTML = filteredActions
      .map(
        (action) => `
        <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
          <div class="p-4">
            <div class="flex items-center mb-4">
              <div class="h-12 w-12 rounded-full flex items-center justify-center mr-3 border-2 shadow-sm">
                ${
                  action.photo && action.photo !== "NULL"
                    ? `<img src="${action.photo}" alt="Photo de ${
                        action.nom_prenom || "Agent"
                      }" class="rounded-full object-cover" onerror="this.parentNode.innerHTML = getInitialsCircle('${
                        action.nom_prenom || ""
                      }')">`
                    : getInitialsCircle(action.nom_prenom || "")
                }
              </div>
              <div>
                <h3 class="font-semibold text-base sm:text-lg text-gray-800">${
                  action.nom_prenom || "Nom inconnu"
                }</h3>
                <div class="flex items-center text-gray-600 text-xs sm:text-sm">
                  <i class="fas fa-briefcase mr-1"></i>
                  <span>${action.libele_role || "Non défini"}</span>
                </div>
              </div>
            </div>
            <div class="space-y-2 mb-4 text-xs sm:text-sm">
              <div class="flex items-center text-gray-600">
                <i class="fas fa-door-open w-4 text-center mr-2"></i>
                <span>${action.libele_action || "Non défini"}</span>
              </div>
              <div class="flex items-center text-gray-600">
                <i class="fas fa-phone-alt w-4 text-center mr-2"></i>
                <span>${action.date || "Non défini"}</span>
              </div>
            </div>
            <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
 <button class="detail-btn bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500" 
            data-id="<?= $action['id'] ?>" title="detail">
        <i class="fas fa-eye mr-1"></i>
       <span> Voir détails</span>
    </button>
            </div>
          </div>
        </div>
      `
      )
      .join("");
    }

    const tableContainer = agentsTableBody.closest("#actionTable");
    tableContainer.classList.remove(
      "hidden",
      "lg:block",
      "overflow-x-auto",
      "rounded-xl",
      "shadow-sm",
      "bg-white"
    );
    tableContainer.classList.add(
      "hidden",
      "lg:block",
      "overflow-x-auto",
      "rounded-xl",
      "shadow-sm",
      "bg-white"
    );
  }
}