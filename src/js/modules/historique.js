let actionData = [];
let roleData = [];
let agentData = [];
let actionTypeData = [];
let filteredData = [];

let currentFilters = {
  search: "",
  actions: "",
  roles: "",
};

function initHistoriqueActions() {
  console.log("Initialisation de l'historique des actions...");

  // Réinitialiser les données pour éviter les problèmes après navigation
  actionData = [];
  roleData = [];
  agentData = [];
  actionTypeData = [];
  filteredData = [];
  currentFilters = { search: "", actions: "", roles: "" };

  loadDataFromScripts();
  setupEventListeners();
  applyFilters();

  console.log("Historique des actions initialisé");
}

function loadDataFromScripts() {
  try {
    const actionScript = document.getElementById("actionData");
    if (!actionScript) throw new Error("Element #actionData introuvable");
    actionData = JSON.parse(actionScript.textContent);
    if (!Array.isArray(actionData))
      throw new Error("actionData n'est pas un tableau");

    const roleScript = document.getElementById("roleData");
    if (!roleScript) throw new Error("Element #roleData introuvable");
    roleData = JSON.parse(roleScript.textContent);
    if (!Array.isArray(roleData))
      throw new Error("roleData n'est pas un tableau");

    const agentScript = document.getElementById("agentData");
    if (!agentScript) throw new Error("Element #agentData introuvable");
    agentData = JSON.parse(agentScript.textContent);
    if (!Array.isArray(agentData))
      throw new Error("agentData n'est pas un tableau");

    const actionTypeScript = document.getElementById("actiontypeData");
    if (!actionTypeScript)
      throw new Error("Element #actiontypeData introuvable");
    actionTypeData = JSON.parse(actionTypeScript.textContent);
    if (!Array.isArray(actionTypeData))
      throw new Error("actionTypeData n'est pas un tableau");

    filteredData = [...actionData];

    console.log("Données chargées:", {
      actions: actionData.length,
      roles: roleData.length,
      agents: agentData.length,
      actionTypes: actionTypeData.length,
    });
  } catch (error) {
    console.error("Erreur lors du chargement des données:", error);
    actionData = [];
    filteredData = [];
    displayErrorMessage(
      "Erreur lors du chargement des données. Veuillez recharger la page."
    );
  }
}

function validateFilters() {
  // Validation du filtre de recherche
  currentFilters.search = currentFilters.search.trim();
  if (currentFilters.search.length > 100) {
    console.warn("La recherche est trop longue, tronquée à 100 caractères");
    currentFilters.search = currentFilters.search.slice(0, 100);
    const searchInput = document.getElementById("search");
    if (searchInput) searchInput.value = currentFilters.search;
    displayErrorMessage("La recherche est limitée à 100 caractères.");
  }

  // Validation du filtre d'action
  if (
    currentFilters.actions &&
    !actionTypeData.some((type) => type.action_type === currentFilters.actions)
  ) {
    console.warn(
      `Valeur invalide pour filter_actions: ${currentFilters.actions}`
    );
    currentFilters.actions = "";
    const filterActions = document.getElementById("filter_actions");
    if (filterActions) filterActions.value = "";
    displayErrorMessage("Action sélectionnée invalide, réinitialisée.");
  }

  // Validation du filtre de rôle
  if (
    currentFilters.roles &&
    !roleData.some((role) => role.libelle === currentFilters.roles)
  ) {
    console.warn(`Valeur invalide pour filter_roles: ${currentFilters.roles}`);
    currentFilters.roles = "";
    const filterRoles = document.getElementById("filter_roles");
    if (filterRoles) filterRoles.value = "";
    displayErrorMessage("Rôle sélectionné invalide, réinitialisé.");
  }
}

function displayErrorMessage(message) {
  const tableBody = document.querySelector("#actionTable tbody");
  if (tableBody) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" class="px-4 py-8 text-center text-red-500">
          <div class="flex flex-col items-center">
            <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
            <p class="text-lg font-medium">Erreur</p>
            <p class="text-sm">${escapeHtml(message)}</p>
          </div>
        </td>
      </tr>
    `;
  }
}

function setupEventListeners() {
  const searchInput = document.getElementById("search");
  let debounceTimeout;
  if (searchInput) {
    searchInput.value = currentFilters.search; // S'assurer que l'input reflète currentFilters
    searchInput.addEventListener("input", function (e) {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => {
        currentFilters.search = e.target.value.toLowerCase();
        applyFilters();
      }, 300);
    });
  } else {
    console.error("Champ de recherche #search introuvable");
    displayErrorMessage(
      "Champ de recherche introuvable. Veuillez recharger la page."
    );
  }

  const filterActions = document.getElementById("filter_actions");
  if (filterActions) {
    filterActions.value = currentFilters.actions; // S'assurer que le select reflète currentFilters
    filterActions.addEventListener("change", function (e) {
      currentFilters.actions = e.target.value;
      applyFilters();
    });
  } else {
    console.error("Champ filter_actions introuvable");
    displayErrorMessage(
      "Filtre des actions introuvable. Veuillez recharger la page."
    );
  }

  const filterRoles = document.getElementById("filter_roles");
  if (filterRoles) {
    filterRoles.value = currentFilters.roles; // S'assurer que le select reflète currentFilters
    filterRoles.addEventListener("change", function (e) {
      currentFilters.roles = e.target.value;
      applyFilters();
    });
  } else {
    console.error("Champ filter_roles introuvable");
    displayErrorMessage(
      "Filtre des rôles introuvable. Veuillez recharger la page."
    );
  }

  const modal = document.getElementById("detailmodal");
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeDetailModal();
      }
    });
  }

  const closeButton = document.querySelector(".close-modal");
  if (closeButton) {
    closeButton.addEventListener("click", function (e) {
      e.preventDefault();
      console.log("Clic sur le bouton de fermeture");
      closeDetailModal();
    });
  }

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeDetailModal();
    }
  });

  // Écouteur pour le bouton de réinitialisation
  const resetButton = document.querySelector(
    'a[href="?page=historique_content"]'
  );
  if (resetButton) {
    resetButton.addEventListener("click", function (e) {
      e.preventDefault();
      resetFilters();
      window.location.href = "?page=historique_content"; // Recharger la page sans paramètres
    });
  }
}

function applyFilters() {
  if (!Array.isArray(actionData)) {
    console.error("actionData n'est pas un tableau");
    displayErrorMessage("Erreur de données. Veuillez recharger la page.");
    return;
  }

  validateFilters();

  try {
    filteredData = actionData.filter((action) => {
      const matchesSearch =
        !currentFilters.search ||
        (action.nom_prenom &&
          action.nom_prenom.toLowerCase().includes(currentFilters.search));
      const matchesAction =
        !currentFilters.actions || action.action === currentFilters.actions;
      const matchesRole =
        !currentFilters.roles || action.responsable === currentFilters.roles;

      return matchesSearch && matchesAction && matchesRole;
    });

    console.log(
      "Filtres appliqués:",
      currentFilters,
      "Résultats:",
      filteredData.length
    );
    updateTable();
  } catch (error) {
    console.error("Erreur lors de l'application des filtres:", error);
    displayErrorMessage(
      "Erreur lors du filtrage. Veuillez vérifier vos critères."
    );
  }
}

function updateTable() {
  const tableBody = document.querySelector("#actionTable tbody");
  if (!tableBody) {
    console.error("Corps du tableau non trouvé");
    displayErrorMessage(
      "Tableau des actions introuvable. Veuillez recharger la page."
    );
    return;
  }

  tableBody.innerHTML = "";

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

  filteredData.forEach((action) => {
    const row = createTableRow(action);
    if (row) {
      tableBody.appendChild(row);
    }
  });

  setupDetailButtons();
}

function createTableRow(action) {
  if (!action) return null;

  const row = document.createElement("tr");
  row.className = "hover:bg-gray-50 transition-colors";

  const initials = getInitials(action.nom_prenom || "");

  let photoContent = "";
  if (action.photo && action.photo.trim() !== "") {
    photoContent = `
      <img src="${escapeHtml(action.photo)}" 
           alt="${escapeHtml(action.nom_prenom || "")}" 
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
          <div class="text-sm font-medium text-gray-900">${escapeHtml(
            action.nom_prenom || ""
          )}</div>
        </div>
      </div>
    </td>
    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${escapeHtml(
      action.responsable || ""
    )}</td>
    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${escapeHtml(
      action.action || ""
    )}</td>
    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${escapeHtml(
      formatDate(action.date_action)
    )}</td>
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

function setupDetailButtons() {
  const detailButtons = document.querySelectorAll(".detail-btn");
  console.log("Boutons de détails trouvés:", detailButtons.length);
  detailButtons.forEach((button) => {
    button.onclick = null; // Supprimer les anciens écouteurs pour éviter les doublons
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const actionId = this.getAttribute("data-id");
      const action = actionData.find((a) => a.id == actionId);

      if (action) {
        console.log("Ouverture du modal pour action:", actionId);
        openDetailModal(action);
      } else {
        console.error("Action non trouvée:", actionId);
        displayErrorMessage("Action non trouvée. Veuillez recharger la page.");
      }
    });
  });
}

function openDetailModal(action) {
  const modal = document.getElementById("detailmodal");
  const modalContent = modal.querySelector(".modal-content");
  const modalContentInner = modal.querySelector("#modalContent");

  if (!modal || !modalContent || !modalContentInner) {
    console.error("Erreur: Éléments du modal non trouvés", {
      modal,
      modalContent,
      modalContentInner,
    });
    displayErrorMessage(
      "Erreur d'affichage des détails. Veuillez recharger la page."
    );
    return;
  }

  modalContentInner.innerHTML = `
    <div><span class="font-semibold">Effectuée par:</span> ${escapeHtml(
      action.nom_prenom || ""
    )}</div>
    <div><span class="font-semibold">Rôle:</span> ${escapeHtml(
      action.responsable || ""
    )}</div>
    <div><span class="font-semibold">Action:</span> ${escapeHtml(
      action.action || ""
    )}</div>
    <div><span class="font-semibold">Date:</span> ${escapeHtml(
      formatDate(action.date_action)
    )}</div>
    <div class="action-details-section">
      <span class="font-semibold">Détails:</span>
      <div class="mt-2">
        ${formatActionDetails(action)}
      </div>
    </div>
  `;

  console.log("Affichage du modal");
  modal.classList.remove("hidden");
  setTimeout(() => {
    modalContent.classList.add("show");
    console.log("Classe show ajoutée au modal-content");
  }, 10);
  document.body.style.overflow = "hidden";
}

function closeDetailModal() {
  const modal = document.getElementById("detailmodal");
  const modalContent = modal.querySelector(".modal-content");
  if (modal && modalContent) {
    console.log("Fermeture du modal");
    modalContent.classList.remove("show");
    setTimeout(() => {
      modal.classList.add("hidden");
      document.body.style.overflow = "auto";
      console.log("Modal fermé et overflow rétabli");
    }, 300);
  } else {
    console.error("Erreur: Éléments du modal non trouvés pour la fermeture", {
      modal,
      modalContent,
    });
  }
}

function formatActionDetails(action) {
  if (!action.details || action.details.trim() === "") {
    return '<div class="text-gray-500 italic">Aucun détail disponible</div>';
  }

  try {
    const donnees = JSON.parse(action.details);
    if (typeof donnees === "object" && donnees !== null) {
      let html = "";
      const bureauLabel = escapeHtml(donnees.bureau || "N/A");

      if (action.action === "modifier" && donnees.changes) {
        html += `<div><span class="font-semibold">Agent modifié:</span> ${escapeHtml(
          donnees.prenom || ""
        )} ${escapeHtml(donnees.nom || "")}</div>`;
        html += `<div><span class="font-semibold">Bureau:</span> ${bureauLabel}</div>`;
        if (Object.keys(donnees.changes).length > 0) {
          html += `<table class="changes-table">`;
          html += `<thead><tr><th>Champ</th><th>Ancienne valeur</th><th>Nouvelle valeur</th></tr></thead>`;
          html += `<tbody>`;
          for (const [champ, valeurs] of Object.entries(donnees.changes)) {
            let champFormate =
              champ.charAt(0).toUpperCase() + champ.slice(1).replace(/_/g, " ");
            let oldValue = valeurs.old ?? "N/A";
            let newValue = valeurs.new ?? "N/A";
            if (champ === "bureau") {
              champFormate = "Bureau";
              oldValue = escapeHtml(oldValue);
              newValue = escapeHtml(newValue);
            }
            html += `
              <tr>
                <td>${escapeHtml(champFormate)}</td>
                <td class="old-value">${escapeHtml(String(oldValue))}</td>
                <td class="new-value">${escapeHtml(String(newValue))}</td>
              </tr>`;
          }
          html += `</tbody></table>`;
        } else {
          html += `<div class="text-gray-500 italic">Aucune modification de champs</div>`;
        }
      } else {
        html += `<div><span class="font-semibold">Agent:</span> ${escapeHtml(
          donnees.prenom || ""
        )} ${escapeHtml(donnees.nom || "")}</div>`;
        html += `<div><span class="font-semibold">Bureau:</span> ${bureauLabel}</div>`;
        for (const [champ, valeur] of Object.entries(donnees)) {
          if (
            champ !== "nom" &&
            champ !== "prenom" &&
            champ !== "bureau" &&
            champ !== "changes"
          ) {
            const champFormate =
              champ.charAt(0).toUpperCase() + champ.slice(1).replace(/_/g, " ");
            html += `<div><span class="font-semibold">${escapeHtml(
              champFormate
            )}:</span> ${escapeHtml(String(valeur))}</div>`;
          }
        }
      }
      return (
        html ||
        '<div class="text-gray-500 italic">Aucun détail disponible</div>'
      );
    }
  } catch (error) {
    console.log("Détails non JSON, affichage brut:", error);
  }

  return `<div>${escapeHtml(String(action.details))}</div>`;
}

function getInitials(nomPrenom) {
  if (!nomPrenom || nomPrenom.trim() === "") return "NN";
  const parts = nomPrenom.trim().split(" ");
  if (parts.length >= 2) {
    return (
      parts[0].charAt(0) + parts[parts.length - 1].charAt(0)
    ).toUpperCase();
  }
  return nomPrenom.charAt(0).toUpperCase();
}

function escapeHtml(text) {
  if (text === null || text === undefined) return "";
  const div = document.createElement("div");
  div.textContent = String(text);
  return div.innerHTML;
}

function formatDate(dateString) {
  if (!dateString) return "";
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString("fr-FR", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch (error) {
    return dateString;
  }
}

function resetFilters() {
  currentFilters = {
    search: "",
    actions: "",
    roles: "",
  };

  const searchInput = document.getElementById("search");
  const filterActions = document.getElementById("filter_actions");
  const filterRoles = document.getElementById("filter_roles");

  if (searchInput) searchInput.value = "";
  if (filterActions) filterActions.value = "";
  if (filterRoles) filterRoles.value = "";

  applyFilters();
}

window.closeDetailModal = closeDetailModal;
window.resetFilters = resetFilters;

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initHistoriqueActions);
} else {
  initHistoriqueActions();
}