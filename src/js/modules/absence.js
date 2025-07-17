// Module de gestion des absences (absences.js)
import { eventBus } from "../config.js";

let absences = [];
let agents = [];
let typesAbsence = [];
let statuts = [];


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

function drawResponsiveText(ctx, text, maxWidth, initialFontSize, x, y, fontFamily = "Arial", color = "#f2f0e9", bold = false) {
  let fontSize = initialFontSize;
  ctx.textAlign = "center";
  ctx.textBaseline = "top";
  ctx.fillStyle = color;

  // Réduire la taille de la police si le texte dépasse maxWidth
  do {
    ctx.font = `${bold ? "bold " : ""}${fontSize}pt ${fontFamily}`;
    const textWidth = ctx.measureText(text).width;
    if (textWidth <= maxWidth) break;
    fontSize -= 1;
  } while (fontSize > 10); // Taille minimale

  ctx.fillText(text, x, y);
}






export function init() {
  console.log("Initialisation du module des absences");
  loadAbsencesData();
  loadAgentsData();
  loadStatutsAbsenceData()
  loadTypesAbsenceData();
  setupFilters();
  setupListeners();
}

// Charger les données des comptes depuis l'élément script
function loadAbsencesData() {
  const absencesDataElement = document.getElementById("AbsencesDatas");
  if (absencesDataElement) {
    try {
      absences = JSON.parse(absencesDataElement.textContent).map((absence, index) => {
        if (!absence.id) {
          absence.id = absence.agent_id || absence._id || null;
          console.warn(`Compte à l'index ${index} n'a pas d'ID défini`, absence);
        }
        absence.id = String(absence.id);
        return absence;
      });
      console.log(`${absences.length} comptes chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données absences:", e);
      absences = [];
      alert("Erreur lors du chargement des données des absences.");
    }
  } else {
    console.warn("Élément absencesData non trouvé");
  }
}

function loadAgentsData() {
  const element = document.getElementById("agentsDatas");
  if (element) {
    try {
      agents = JSON.parse(element.textContent);
    } catch (e) {
      console.error("Erreur de chargement des agents", e);
      agents = [];
    }
  }
}

function loadTypesAbsenceData() {
  const element = document.getElementById("typesAbsenceDatas");
  if (element) {
    try {
      typesAbsence = JSON.parse(element.textContent);
    } catch (e) {
      console.error("Erreur de chargement des types d'absence", e);
      typesAbsence = [];
    }
  }
}

function loadStatutsAbsenceData() {
  const element = document.getElementById("statutsAbsencesDatas");
  if (element) {
    try {
      statutsAbsence = JSON.parse(element.textContent);
    } catch (e) {
      console.error("Erreur de chargement des types d'absence", e);
      statutsAbsence = [];
    }
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
        "#absenceModal, #deleteAbsenceModal,#messageModal"
      );
      if (modal) {
        closeModal(modal.id);
      }
    });
  });

  document
    .querySelectorAll("#absenceModal,#deleteAbsenceModal, #messageModal")
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
  document.body.addEventListener("click", function (e) {
    const target = e.target;

    // Bouton "Ajouter un utilisateur"
    if (target.matches(".add-absence-btn")) {
      addCompte();
    }

    // Boutons d'édition
    if (
      target.matches(".edit-absence-btn") ||
      target.closest(".edit-absence-btn")
    ) {
      const btn = target.matches(".edit-absence-btn")
        ? target
        : target.closest(".edit-absence-btn");
      const bsenceId = btn.getAttribute("data-id");
      editCompte(compteId);
    }

    // Boutons de suppression
    if (
      target.matches(".delete-absence-btn") ||
      target.closest(".delete-absence-btn")
    ) {
      const btn = target.matches(".delete-absence-btn")
        ? target
        : target.closest(".delete-absence-btn");
      const absenceId = btn.getAttribute("data-id");
      confirmDelete(absenceId);
    }
  });
}

// Gestion du changement de bureau pour mettre à jour la liste des agents DANS LE MODAL
  const modalBureauSelect = document.getElementById("filter_bureau");
  if (modalBureauSelect) {
    modalBureauSelect.addEventListener("change", function () {
      updateModalAgentsOptions(this.value);
    });
  }



function updateModalAgentsOptions(bureauLibelle) {
  const agentSelect = document.getElementById("filter_agent");
  if (!agentSelect) return;

  // Réinitialiser les options
  agentSelect.innerHTML = '<option value="">Choisir un agent</option>';
  
  if (bureauLibelle) {
    console.log("Bureau sélectionné:", bureauLibelle);
    console.log("Agents disponibles:", agents);
    
    // Filtrer les agents par bureau (en utilisant le libellé du bureau)
    const agentsDuBureau = agents.filter(agent => agent.bureau === bureauLibelle);
    console.log("Agents du bureau:", agentsDuBureau);
    
    agentsDuBureau.forEach(agent => {
      const option = document.createElement("option");
      option.value = agent.nom_prenom; // Utiliser nom_prenom comme valeur
      option.textContent = agent.nom_prenom;
      agentSelect.appendChild(option);
    });
    
    // Activer le select des agents
    agentSelect.disabled = false;
  } else {
    // Désactiver le select des agents
    agentSelect.disabled = true;
  }
}

// Fonction pour ouvrir le modal d'ajout de compte
export function addCompte() {
  const modal = document.getElementById("compteModal");
  if (!modal) return;
  
  document.getElementById("modalTitle").innerHTML =
    '<i class="fas fa-user-plus mr-2 text-indigo-600"></i><span>Ajouter un nouvel utilisateur</span>';
  
  const form = document.getElementById("compteForm");
  if (form) {
    form.reset();
    document.getElementById("agent_idss").value = "";
    document.getElementById("actions").value = "add";
    
    // Réinitialiser les selects
    const agentSelect = document.getElementById("filter_agent");
    const bureauSelect = document.getElementById("filter_bureau");
    
    if (agentSelect) {
      agentSelect.disabled = true;
      agentSelect.innerHTML = '<option value="">Choisir un agent</option>';
    }
    
    if (bureauSelect) {
      bureauSelect.value = "";
    }
  }

  // Réinitialiser le champ mot de passe
  const passwordInput = document.getElementById("mot_de_passe");
  const eyeIcon = document.getElementById("eyeIcon");
  if (passwordInput) {
    passwordInput.type = "password";
    passwordInput.value = "";
    passwordInput.placeholder = "Mot de passe";
  }
  if (eyeIcon) {
    eyeIcon.classList.remove("fa-eye-slash");
    eyeIcon.classList.add("fa-eye");
  }
  
  // Réinitialiser le toggle du mot de passe
  passwordToggleInitialized = false;
  setupPasswordToggle();
  
  showModal("compteModal");
}

// Fonction pour ouvrir le modal d'édition
export function edit(AbsenceId) {
  const modal = document.getElementById("absenceModal");
  if (!modal) return;
  
  document.getElementById("absenceTitle").innerHTML =
    '<i class="fas fa-user-edit mr-2 text-indigo-600"></i><span>Modifier une nouvelle absence </span>';

  const absenceIdStr = String(absenceId);
  const absence = absences.find((c) => abs.id === absenceIdStr);
  if (!absence) {
    alert("Utilisateur non trouvé.");
    return;
  }

  const form = document.getElementById("absenceForm");
  if (!form) return;

  // Trouver l'agent associé à ce compte
  const agent = agents.find(a => a.nom_prenom === compte.nom_prenom);
  
  const fields = {
    agent_idss: absence.id,
    actions: "update",
    agent: absence.agent_id || "",
    date_debut: absence.date_debut|| "",
    date_fin: absence.date_fin|| "",
    type: absence.id_type_absence || "",
    justificatif: absence.justificatif || "",
    statut: absence.id_statut || "",
    description: absence.description || "",
  };

  for (const [id, value] of Object.entries(fields)) {
    const field = document.getElementById(id);
    if (field) {
      field.value = value || "";
    }
  }


  // Pré-sélectionner le bureau et l'agent
  const bureauSelect = document.getElementById("filter_bureau");
  const agentSelect = document.getElementById("filter_agent");
  
  if (agent && agent.bureau && bureauSelect) {
    bureauSelect.value = agent.bureau;
    // Mettre à jour les agents selon le bureau sélectionné
    updateModalAgentsOptions(agent.bureau);
    
    // Sélectionner l'agent associé après un court délai
    setTimeout(() => {
      if (agentSelect) {
        agentSelect.value = agent.nom_prenom;
      }
    }, 100);
  }

  // Réinitialiser le toggle du mot de passe
  passwordToggleInitialized = false;
  setupPasswordToggle();

  showModal("absenceModal");
}

// Fonction pour ouvrir le modal de suppression
export function confirmDelete(absenceId) {
  const modal = document.getElementById("absenceModal");
  if (!modal) return;

  const absenceIdStr = String(absenceId);
  const absence = absences.find((abs) => abs.id === absenceIdStr);
  if (!absence) {
    alert("agent non trouvé pour suppression.");
    return;
  }

  const confirmDeleteAbsenceBtn = document.getElementById("confirmDeleteAbsenceBtn");
  if (confirmDeleteAbsenceBtn) {
    confirmDeleteAbsenceBtn.href = `?page=compte_content&action=delete&id=${compteId}`;
  }

  showModal("deleteAbsenceModal");
  eventBus.publish("comptes:deleteRequested", { compteId });
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
  console.log("Mise à jour externe des comptes reçue", data);
  // Recharger les données et rafraîchir l'affichage
  loadAbsencesData();
  refreshDisplay();
});

// Fonction pour rafraîchir l'affichage
function refreshDisplay() {
  const event = new Event('input');
  const searchInput = document.getElementById("search");
  if (searchInput) {
    searchInput.dispatchEvent(event);
  }
}
function setupFilters() {
  const searchInput = document.getElementById("search_absence");
  const typesSelect = document.getElementById("filter_types");
  const statutsSelect = document.getElementById("filter_statuts");
   const absencesCardsContainer = document.getElementById("absencesCards");
  const absencestableBody = document.querySelector("#absencesTable tbody");

 if (
    !searchInput ||
    !typesSelect ||
    !statutsSelect ||
    !absencesCardsContainer ||
    !absencestableBody
  ) {
    console.warn("Éléments nécessaires pour les filtres non trouvés");
    return;
  }

// Écouteurs pour les filtres
  searchInput.addEventListener("input", filterAndDisplayAbsences);
  typesSelect.addEventListener("change", filterAndDisplayAbsences);
  statutsSelect.addEventListener("change", filterAndDisplayAbsences);
 // Affichage initial
  filterAndDisplayAbsences();

  function filterAndDisplayAbsences() {
    const searchQuery = searchInput.value.trim().toLowerCase();
    const typesFilter = typesSelect.value;
    const statutsFilter = statutsSelect.value;

    const filteredAbsences = absences.filter((absence) => {
      const nomPrenom = absence.nom_prenom ? absence.nom_prenom.toLowerCase() : "";
      const matchesSearch = nomPrenom.includes(searchQuery);
      const matchesType = typesFilter === "" || type.role === typesFilter;
      const matchesStatut = statutsFilter === "" || absence.statuts === statutsFilter;

      return matchesSearch && matchesType && matchesStatut;
    });

  absencesCardsContainer.innerHTML = filteredAbsences
      .map(
        (absence) => `
        <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
        <div class="p-4">
            <!-- Agent Info -->
            <div class="flex items-center mb-4">
                <div class="h-12 w-12 rounded-full flex items-center justify-center mr-3 border-2 shadow-sm">
                ${
                  absence.photo && absence.photo !== "NULL"
                    ? `<img src="${absence.photo}" alt="Photo de ${
                        absence.nom_prenom || "Agent"
                      }" class="rounded-full object-cover" onerror="this.parentNode.innerHTML = getInitialsCircle('${
                        absence.nom_prenom || ""
                      }')">`
                    : getInitialsCircle(a.nom_prenom || "")
                }
              </div>
              <div>
               <h3 class="font-semibold text-base sm:text-lg text-gray-800">${
                  absence.nom_prenom || "Nom inconnu"
                }</h3>
            <div class="flex items-center text-gray-600 text-xs sm:text-sm">
  <i class="fas fa-traffic-light mr-2"></i>
  ${
    absence.statut?.toLowerCase() === "autorisé"
      ? '<span class="text-green-600">✔️ Autorisé</span>'
      : absence.statut?.toLowerCase() === "rejeté"
      ? '<span class="text-red-600">❌ Rejeté</span>'
      : absence.statut?.toLowerCase() === "en attente"
      ? '<span class="text-gray-600">⏳ En attente</span>'
      : '<span class="text-gray-500">Inconnu</span>'
  }
            </div>
            <div class="flex items-center text-gray-600">
                <i class="fas fa-calendar-alt mr-2"></i>
                <span>${absence.debut || "Non défini"}</span>
              </div>
              <div class="flex items-center text-gray-600">
                <i class="fas fa-calendar-check mr-2"></i>
                <span>${absence.fin || "Non défini"}</span>
              </div>
              <div class="flex items-center text-gray-600">
                <i class="fas fa-suitcase-rolling mr-2"></i>
                <span>${absence.motif || "Non défini"}</span>
              </div>
              <div class="flex items-center text-gray-600">
                <i class="fas fa-file-alt mr-2"></i>
                <span>${absence.justificatif || "Non défini"}</span>
              </div>
      `
      )
      .join("");
    absencesCardsContainer.classList.remove(
      "grid",
      "grid-cols-1",
      "sm:grid-cols-2",
      "lg:grid-cols-3",
      "gap-4",
      "mb-6",
      "lg:hidden"
    );
    agentsCardsContainer.classList.add(
      "grid",
      "grid-cols-1",
      "sm:grid-cols-2",
      "lg:grid-cols-3",
      "gap-4",
      "mb-6",
      "lg:hidden"
    );

    if (filteredAbsences.length === 0) {
      absencesTableBody.innerHTML = `
        <tr>
          <td colspan="5" class="px-4 py-6 text-center">
            <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl shadow-sm animate-fade-in">
              <i class="fas fa-search text-4xl text-indigo-500 mb-4 animate-pulse"></i>
              <h3 class="text-lg font-semibold text-gray-800 mb-2">Oups, aucun agent trouvé !</h3>
              <p class="text-sm text-gray-600">Essayez une autre recherche ou un autre filtre.</p>
            </div>
          </td>
        </tr>
      `;
    }
    if (filteredAbsences.length === 0) {
      absencesTableBody.innerHTML = `
        <tr>
          <td colspan="5" class="px-4 py-6 text-center">
            <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl shadow-sm animate-fade-in">
              <i class="fas fa-search text-4xl text-indigo-500 mb-4 animate-pulse"></i>
              <h3 class="text-lg font-semibold text-gray-800 mb-2">Oups, aucun agent trouvé !</h3>
              <p class="text-sm text-gray-600">Essayez une autre recherche ou un autre filtre.</p>
            </div>
          </td>
        </tr>
      `;
    } else {
      absencesTableBody.innerHTML = filteredAbsences
        .map(
          (absence) => `
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 whitespace-nowrap">
              <div class="flex items-center">
                <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                  ${
                     absence.photo && absence.photo !== "NULL"
                    ? `<img src="${absence.photo}" alt="Photo de ${
                        absence.nom_prenom || "Agent"
                      }" class="rounded-full object-cover" onerror="this.parentNode.innerHTML = getInitialsCircle('${
                        absence.nom_prenom || ""
                      }')">`
                    : getInitialsCircle(a.nom_prenom || "")
                  }
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-900"> ${
    absence.statut?.toLowerCase() === "autorisé"
      ? '<span class="text-green-600">✔️ Autorisé</span>'
      : absence.statut?.toLowerCase() === "rejeté"
      ? '<span class="text-red-600">❌ Rejeté</span>'
      : absence.statut?.toLowerCase() === "en attente"
      ? '<span class="text-gray-600">⏳ En attente</span>'
      : '<span class="text-gray-500">Inconnu</span>'
  }</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${absence.debut || "Non défini"}</td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${absence.fin || "Non défini"}</td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${absence.motif || "Non défini"}</td>
             <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${absence.motif || "Non défini"}</td>
            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex space-x-2 justify-end">
                <button class="edit-agent-btn text-blue-600 hover:text-blue-900 transition-colors" data-id="${
                  agent.id
                }" title="Modifier">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="qr-agent-btn text-green-600 hover:text-green-900 transition-colors" data-id="${
                  agent.id
                }" title="Générer Badge">
                  <i class="fas fa-id-card"></i>
                </button>
                <button class="delete-agent-btn text-red-600 hover:text-red-900 transition-colors" data-id="${
                  agent.id
                }" title="Supprimer">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        `
        )
        .join("");
    }

    const tableContainer = agentsTableBody.closest("#agentsTable");
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