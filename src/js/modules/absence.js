import { eventBus } from "../config.js";

// Variables globales pour les données
let absences = [];
let agents = [];
let typesAbsence = [];
let statuts = [];
let bureaux = [];
let roleUtilisateur = "secretaire"; // À récupérer dynamiquement si nécessaire

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

export function init() {
  console.log("Initialisation du module de gestion des absences");
  loadAbsencesData();
  loadAgentsData();
  loadBureauxData();
  loadStatutsData();
  loadTypesAbsenceData();
  loadUserRole();
  initModals();
  setupFiltersAbsences();
  setupListeners();
  checkAndShowMessageModal();
}

// [Fonctions de chargement des données - inchangées]
function loadAbsencesData() {
  const absencesDataElement = document.getElementById("AbsencesDatas");

  if (absencesDataElement) {
    try {
      absences = JSON.parse(absencesDataElement.textContent).map((absence, index) => {
        if (!absence.id) {
          absence.id = absence.absence_id || absence._id || null;
          console.warn(`Absence à l'index ${index} n'a pas d'ID défini`, absence);
        }

        absence.id = String(absence.id);
        return absence;
      });

      console.log(`${absences.length} absences chargées`);
    } catch (e) {
      console.error("Erreur lors du parsing des données d'absences :", e);
      absences = [];
      alert("Erreur lors du chargement des données des absences.");
    }
  } else {
    console.warn("Élément AbsencesDatas non trouvé");
  }
}

function loadAgentsData() {
  const agentsDataElement = document.getElementById("AgentsDatas");
  if (agentsDataElement) {
    try {
      agents = JSON.parse(agentsDataElement.textContent);
      console.log(`${agents.length} agents chargés`);
      console.log("Structure des agents:", agents[0]); // Debug
    } catch (e) {
      console.error("Erreur lors du parsing des données agents:", e);
      agents = [];
    }
  }
}

/**
 * Charge les données des bureaux.
 */
function loadBureauxData() {
  const bureauxDataElement = document.getElementById("bureauxDatas");
  if (bureauxDataElement) {
    try {
      bureaux = JSON.parse(bureauxDataElement.textContent);
      console.log(`${bureaux.length} bureaux chargés`);
      console.log("Structure des bureaux:", bureaux[0]); // Debug
    } catch (e) {
      console.error("Erreur lors du parsing des données bureaux:", e);
      bureaux = [];
    }
  }
}

function loadTypesAbsenceData() {
  const typesDataElement = document.getElementById("typesAbsencesDatas");

  if (typesDataElement) {
    try {
      typesAbsence = JSON.parse(typesDataElement.textContent);
      console.log(`${typesAbsence.length} types d'absences chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des types d'absence :", e);
      typesAbsence = [];
    }
  } else {
    console.warn("Élément typesAbsencesDatas non trouvé");
  }
}

function loadStatutsData() {
  const statutsDataElement = document.getElementById("statutsAbsencesDatas");

  if (statutsDataElement) {
    try {
      statuts = JSON.parse(statutsDataElement.textContent);
      console.log(`${statuts.length} statuts chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des statuts :", e);
      statuts = [];
    }
  } else {
    console.warn("Élément statutsAbsencesDatas non trouvé");
  }
}

function loadUserRole() {
  const roleElement = document.getElementById("roleUtilisateur");
  if (roleElement) {
    try {
      roleUtilisateur = JSON.parse(roleElement.textContent) || "secretaire";
      console.log(`Rôle utilisateur chargé : ${roleUtilisateur}`);
    } catch (e) {
      console.error("Erreur lors du parsing du rôle utilisateur:", e);
      roleUtilisateur = "secretaire";
    }
  } else {
    console.warn("Élément roleUtilisateur non trouvé, utilisation du rôle par défaut");
  }
}

eventBus.subscribe("comptes:externalUpdate", (data) => {
  console.log("Mise à jour externe des comptes reçue", data);
  loadAbsencesData();
  loadAgentsData();
  refreshDisplay();
});

function getTypeAbsenceLibelle(typeId) {
  if (!typeId) return 'Non défini';
  const type = typesAbsence.find(t => String(t.id) === String(typeId));
  return type ? type.libelle : 'Non défini';
}

function getStatutLibelle(statutInput) {
  if (!statutInput) return 'Inconnu';
  
  if (typeof statutInput === 'string') {
    return statutInput.toLowerCase().trim();
  }
  
  const statut = statuts.find(s => String(s.id) === String(statutInput));
  return statut ? statut.libelle.toLowerCase().trim() : 'Inconnu';
}

function checkAndShowMessageModal() {
  const messageModal = document.getElementById("messageAbsences");
  if (messageModal && messageModal.dataset.messages) {
    try {
      const messages = JSON.parse(messageModal.dataset.messages);
      if (
        (messages.success && messages.success.length > 0) ||
        (messages.errors && messages.errors.length > 0)
      ) {
        showModal("messageAbsences");
      }
    } catch (e) {
      console.error("Erreur lors du parsing de data-messages:", e);
    }
  }
}

function initModals() {
  // Gestion des boutons de fermeture
  document.querySelectorAll(".close-modals").forEach((btn) => {
    btn.addEventListener("click", function () {
      const modal = this.closest("#absenceModal, #deleteAbsenceModal, #messageAbsences, #authorizeAbsenceModal, #rejectAbsenceModal");
      if (modal) {
        closeModal(modal.id);
      }
    });
  });
  
  // Gestion des clics en dehors des modaux
  document
    .querySelectorAll("#absenceModal, #deleteAbsenceModal, #messageAbsences, #authorizeAbsenceModal, #rejectAbsenceModal")
    .forEach((modal) => {
      modal.addEventListener("click", function (e) {
        if (e.target === this) {
          closeModal(this.id);
        }
      });
    });

  // AJOUT: Créer le modal de rejet s'il n'existe pas
  createRejectModalIfNotExists();
}

function createRejectModalIfNotExists() {
  if (!document.getElementById("rejectAbsenceModal")) {
    const rejectModalHTML = `
      <div id="rejectAbsenceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md" id="rejectAbsenceModalContent">
          <h3 class="text-lg font-semibold text-red-600 mb-4">
            <i class="fas fa-times-circle mr-2"></i> Rejeter cette absence ?
          </h3>
          <p class="text-gray-700 mb-6">Confirmez-vous le rejet de cette absence ?</p>
          <div class="flex justify-end gap-2">
            <button class="close-modals px-4 py-2 bg-gray-300 rounded">Annuler</button>
            <a id="confirmRejectAbsenceBtn" href="#" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
              <i class="fas fa-times mr-1"></i> Rejeter
            </a>
          </div>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', rejectModalHTML);
  }
}

function showModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) {
    console.error(`Modal ${modalId} introuvable`);
    return;
  }

  modal.classList.remove("hidden");
  const modalContent = modal.querySelector("[id$='Content'], .bg-white");
  if (modalContent) {
    setTimeout(() => {
      modalContent.classList.remove("scale-95", "opacity-0");
      modalContent.classList.add("scale-100", "opacity-100");
    }, 10);
  }
}

// FONCTION AJOUTÉE : Met à jour les options des agents selon le bureau sélectionné
function updateModalAgentsOptions(selectedBureau) {
  const agentSelect = document.getElementById("filter_agents");
  if (!agentSelect) {
    console.error("Element filter_agents non trouvé");
    return;
  }

  console.log("Mise à jour des agents pour le bureau:", selectedBureau);
  console.log("Agents disponibles:", agents);

  // Réinitialiser les options
  agentSelect.innerHTML = '<option value="">Choisir un agent</option>';

  if (!selectedBureau) {
    agentSelect.disabled = true;
    return;
  }

  // Filtrer les agents par bureau (utilise 'bureau' au lieu de 'libele_bureau')
  const agentsDuBureau = agents.filter(agent => {
    // Vérifier plusieurs possibilités selon la structure des données
    return agent.bureau === selectedBureau || 
           agent.libele_bureau === selectedBureau ||
           agent.bureau_libelle === selectedBureau;
  });

  console.log("Agents filtrés pour le bureau:", agentsDuBureau);

  if (agentsDuBureau.length === 0) {
    agentSelect.innerHTML = '<option value="">Aucun agent trouvé pour ce bureau</option>';
    agentSelect.disabled = true;
    return;
  }

  // Ajouter les agents du bureau sélectionné
  agentsDuBureau.forEach(agent => {
    const option = document.createElement('option');
    option.value = agent.id;
    option.textContent = agent.nom_prenom || `${agent.prenom || ''} ${agent.nom || ''}`.trim();
    agentSelect.appendChild(option);
  });

  agentSelect.disabled = false;
}

function setupListeners() {
  document.body.addEventListener("click", function (e) {
    const target = e.target;

    // Bouton "Ajouter une absence"
    if (target.matches(".add-absence-btns") || target.closest(".add-absence-btns")) {
      const btn = target.matches(".add-absence-btns") ? target : target.closest(".add-absence-btns");
      addAbsence();
      return;
    }

    // Bouton "Modifier une absence"
    if (target.matches(".edit-absence-btn") || target.closest(".edit-absence-btn")) {
      const btn = target.matches(".edit-absence-btn") ? target : target.closest(".edit-absence-btn");
      const absenceId = btn.getAttribute("data-id");
      editAbsence(absenceId);
      return;
    }

    // Bouton "Supprimer une absence"
    if (target.matches(".delete-absence-btn") || target.closest(".delete-absence-btn")) {
      const btn = target.matches(".delete-absence-btn") ? target : target.closest(".delete-absence-btn");
      const absenceId = btn.getAttribute("data-id");
      confirmDeleteAbsence(absenceId);
      return;
    }

    // Bouton "Autoriser / Valider une absence"
    if (
      target.matches(".validate-absence-btn") || target.closest(".validate-absence-btn") ||
      target.matches(".authorize-absence-btn") || target.closest(".authorize-absence-btn")
    ) {
      const btn = target.closest(".validate-absence-btn, .authorize-absence-btn");
      const absenceId = btn.getAttribute("data-id");
      confirmAuthorizeAbsence(absenceId);
      return;
    }

    // Bouton "Rejeter une absence"
    if (target.matches(".reject-absence-btn") || target.closest(".reject-absence-btn")) {
      const btn = target.matches(".reject-absence-btn") ? target : target.closest(".reject-absence-btn");
      const absenceId = btn.getAttribute("data-id");
      confirmRejectAbsence(absenceId);
      return;
    }
  });

  const modalBureauSelect = document.getElementById("filter_bureaux");
  if (modalBureauSelect) {
    console.log("Event listener ajouté pour filter_bureaux");
    modalBureauSelect.addEventListener("change", function () {
      console.log("Changement de bureau détecté:", this.value);
      updateModalAgentsOptions(this.value);

      const agentSelect = document.getElementById("filter_agents");
      if (!agentSelect) return;

      if (!this.value) {
        // Si aucun bureau sélectionné, désactive le sélecteur agent
        agentSelect.disabled = true;
        agentSelect.innerHTML = '<option value="">Choisir un agent</option>';
      } else {
        // Active le sélecteur agent
        agentSelect.disabled = false;
      }
    });
  } else {
    console.error("Element filter_bureaux non trouvé lors du setup des listeners");
  }
}

/**
 * Ouvre le modal pour ajouter un compte.
 */
export function addAbsence() {
  const modal = document.getElementById("absenceModal");
  if (!modal) return;

  document.getElementById("absenceTitle").innerHTML =
    '<i class="fas fa-user-plus mr-2 text-indigo-600"></i><span>Ajouter une nouvelle absence</span>';

  const form = document.getElementById("absenceForm");
  if (form) {
    form.reset();

    // Définir l'action pour ajout
    document.getElementById("actions").value = "add";
    document.getElementById("absence_id").value = "";

    // Réinitialiser et désactiver le select agent
    const agentSelect = document.getElementById("filter_agents");
    if (agentSelect) {
      agentSelect.disabled = true;
      agentSelect.innerHTML = '<option value="">Choisir un agent</option>';
    }

    // Réinitialiser le select bureau
    const bureauSelect = document.getElementById("filter_bureaux");
    if (bureauSelect) {
      bureauSelect.value = "";
    }

    // Réinitialiser le motif
    const motifSelect = document.getElementById("filter_types");
    if (motifSelect) {
      motifSelect.value = "";
    }

    // Réinitialiser les dates et description (le reset du form le fait déjà, mais ici par sécurité)
    const dateDebutInput = document.getElementById("filter_date_debut");
    const dateFinInput = document.getElementById("filter_date_fin");
    const descriptionInput = document.getElementById("description");
    
    if (dateDebutInput) dateDebutInput.value = "";
    if (dateFinInput) dateFinInput.value = "";
    if (descriptionInput) descriptionInput.value = "";
  }

  showModal("absenceModal");
}

export function editAbsence(absenceId) {
  const modal = document.getElementById("absenceModal");
  if (!modal) return;

  document.getElementById("absenceTitle").innerHTML =
    '<i class="fas fa-user-edit mr-2 text-indigo-600"></i><span>Modifier une absence</span>';

  const absenceIdStr = String(absenceId);
  const absence = absences.find((a) => String(a.id) === absenceIdStr);
  if (!absence) {
    alert("Absence non trouvée.");
    return;
  }

  const agent = agents.find((a) => String(a.id) === String(absence.agent_id));
  if (!agent) {
    alert("Agent introuvable.");
    return;
  }

  const form = document.getElementById("absenceForm");
  if (!form) return;

  // Préparer les champs à remplir
  const fields = {
    actions: "update",
    absence_id: absence.id || "",
    filter_date_debut: absence.date_debut || "",
    filter_date_fin: absence.date_fin || "",
    description: absence.description || "",
    filter_types: absence.motif || "",
  };

  for (const [id, value] of Object.entries(fields)) {
    const field = document.getElementById(id);
    if (field) {
      field.value = value;
    }
  }

  const bureauSelect = document.getElementById("filter_bureaux");
  const agentSelect = document.getElementById("filter_agents");

  const bureauAgent =
    agent.bureau ||
    agent.libelle_bureau ||
    agent.bureau_libelle ||
    agent.bureau_id;

  if (bureauAgent && bureauSelect) {
    bureauSelect.value = bureauAgent;

    // Met à jour la liste des agents selon le bureau
    updateModalAgentsOptions(bureauAgent);

    // Sélectionne l'agent une fois les options mises à jour
    setTimeout(() => {
      if (agentSelect) {
        agentSelect.disabled = false;
        agentSelect.value = agent.id;
      }
    }, 100);
  }

  showModal("absenceModal");
}


export function confirmDeleteAbsence(absenceId) {
  console.log("confirmDeleteAbsence appelée avec ID:", absenceId);
  const modal = document.getElementById("deleteAbsenceModal");
  if (!modal) {
    console.error("Modal deleteAbsenceModal introuvable");
    return;
  }

  const absenceIdStr = String(absenceId);
  const absence = absences.find((a) => String(a.id) === absenceIdStr);
  if (!absence) {
    alert("Absence non trouvée pour suppression.");
    return;
  }

  const confirmDeleteBtn = document.getElementById("confirmDeleteAbsenceBtn");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.href = `?page=absence_content&action=delete&id=${absenceId}`;
  }

  showModal("deleteAbsenceModal");
  eventBus.publish("absences:deleteRequested", { absenceId });
}

export function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  const modalContent = modal.querySelector("[id$='Content'], .bg-white");
  if (modalContent) {
    modalContent.classList.remove("scale-100", "opacity-100");
    modalContent.classList.add("scale-95", "opacity-0");
    setTimeout(() => modal.classList.add("hidden"), 300);
  } else {
    modal.classList.add("hidden");
  }

  eventBus.publish("modal:closed", { modalId });
}

export function confirmAuthorizeAbsence(absenceId) {
  console.log("confirmAuthorizeAbsence appelée avec ID:", absenceId);
  const modal = document.getElementById("authorizeAbsenceModal");
  if (!modal) {
    console.error("Modal authorizeAbsenceModal introuvable");
    return;
  }

  const absence = absences.find((a) => String(a.id) === String(absenceId));
  if (!absence) {
    alert("Absence introuvable pour autorisation.");
    return;
  }

  const confirmBtn = document.getElementById("confirmAuthorizeAbsenceBtn");
  if (confirmBtn) {
    confirmBtn.href = `autoriser_absence.php?absence_id=${absenceId}`;
  }

  showModal("authorizeAbsenceModal");
  eventBus.publish("absences:authorizeRequested", { absenceId });
}

export function confirmRejectAbsence(absenceId) {
  console.log("confirmRejectAbsence appelée avec ID:", absenceId);
  const modal = document.getElementById("rejectAbsenceModal");
  if (!modal) {
    console.error("Modal rejectAbsenceModal introuvable");
    return;
  }

  const absence = absences.find((a) => String(a.id) === String(absenceId));
  if (!absence) {
    alert("Absence introuvable pour rejet.");
    return;
  }

  const confirmBtn = document.getElementById("confirmRejectAbsenceBtn");
  if (confirmBtn) {
    confirmBtn.href = `refuser_absence.php?absence_id=${absenceId}`;
  }

  showModal("rejectAbsenceModal");
  eventBus.publish("absences:rejectRequested", { absenceId });
}

// CORRECTION 4: Amélioration de la génération des boutons avec les bonnes classes
function generateActionButtons(absence, role) {
  let buttons = "";
  const statutLibelle = getStatutLibelle(absence.statut).toLowerCase();

  if (role === "secretaire") {
    if (statutLibelle === "autoriser") {
      buttons += `
        <form action="generer_autorisation.php" method="post" target="_blank" class="inline-block">
          <input type="hidden" name="absence_id" value="${absence.id}">
          <button type="submit" class="text-green-600 hover:text-green-800 text-sm" title="Imprimer l'autorisation">
            <i class="fas fa-print mr-1"></i> 
          </button>
        </form>
      `;
    } else {
      buttons += `
        <button class="edit-absence-btn text-blue-600 hover:text-blue-900 text-sm" data-id="${absence.id}" title="Modifier">
          <i class="fas fa-edit mr-1"></i>
        </button>
        <button class="delete-absence-btn text-red-600 hover:text-red-900 text-sm" data-id="${absence.id}" title="Supprimer">
          <i class="fas fa-trash mr-1"></i> 
        </button>
      `;
    }
  } 
  else if (role === "chef de service" || role === "directrice") {
    if (statutLibelle === "en attente") {
      buttons += `
        <button class="validate-absence-btn text-green-600 hover:text-green-800 text-sm" data-id="${absence.id}" title="Autoriser">
          <i class="fas fa-check-circle mr-1"></i> 
        </button>
        <button class="reject-absence-btn text-red-600 hover:text-red-800 text-sm" data-id="${absence.id}" title="Rejeter">
          <i class="fas fa-times-circle mr-1"></i>
        </button>
      `;
    } else if (statutLibelle === "autoriser") {
      buttons += `
        <span class="text-green-600 text-sm italic">
           Déjà autorisé
        </span>
      `;
    } else if (statutLibelle === "rejeter") {
      buttons += `
        <span class="text-red-600 text-sm italic">
          <i class="fas fa-times-circle mr-1"></i> Déjà rejeté
        </span>
      `;
    }
  }

  return buttons;
}

function setupFiltersAbsences() {
  const searchInput = document.getElementById("search");
  const typesSelect = document.getElementById("filter_types");
  const statutsSelect = document.getElementById("filter_statuts");
  const absencesCardsContainer = document.getElementById("absencesCards");
  const absencesTableBody = document.querySelector("#absencesTable tbody");

  if (!searchInput || !typesSelect || !statutsSelect || !absencesCardsContainer || !absencesTableBody) {
    console.warn("Éléments nécessaires pour les filtres d'absences non trouvés");
    return;
  }

  searchInput.addEventListener("input", filterAndDisplayAbsences);
  typesSelect.addEventListener("change", filterAndDisplayAbsences);
  statutsSelect.addEventListener("change", filterAndDisplayAbsences);

  function filterAndDisplayAbsences() {
    const searchQuery = (searchInput.value || "").trim().toLowerCase().replace(/[<>]/g, "");
    const typeFilter = typesSelect.value;
    const statutFilter = statutsSelect.value;

    console.log("Filtres appliqués:", { searchQuery, typeFilter, statutFilter });

    const filteredAbsences = absences.filter(absence => {
      const nomPrenom = absence.nom_prenom ? absence.nom_prenom.toLowerCase() : "";
      const matchesSearch = searchQuery === "" || nomPrenom.includes(searchQuery);

      // CORRECTION 1: Comparer avec le libellé du motif au lieu de l'ID
      let matchesType = true;
      if (typeFilter !== "") {
        const typeLibelle = getTypeAbsenceLibelle(typeFilter);
        matchesType = absence.motif && absence.motif.toLowerCase() === typeLibelle.toLowerCase();
      }

      // CORRECTION 2: Comparer avec le libellé du statut au lieu de l'ID
      let matchesStatut = true;
      if (statutFilter !== "") {
        const statutLibelle = statuts.find(s => String(s.id) === String(statutFilter));
        if (statutLibelle) {
          const absenceStatutLibelle = getStatutLibelle(absence.statut);
          matchesStatut = absenceStatutLibelle.toLowerCase() === statutLibelle.libelle.toLowerCase();
        }
      }

      console.log("Filtrage absence:", {
        nom: absence.nom_prenom,
        motif: absence.motif,
        statut: absence.statut,
        matchesSearch,
        matchesType,
        matchesStatut
      });

      return matchesSearch && matchesType && matchesStatut;
    });

    console.log(`${filteredAbsences.length} absences trouvées après filtrage`);

    // Mise à jour des cartes (mobile)
    absencesCardsContainer.innerHTML = filteredAbsences.length === 0 
      ? `<div class="col-span-full flex flex-col items-center justify-center p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl shadow-sm">
           <i class="fas fa-search text-4xl text-indigo-500 mb-4"></i>
           <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucune absence trouvée</h3>
           <p class="text-sm text-gray-600">Essayez une autre recherche ou un autre filtre.</p>
         </div>`
      : filteredAbsences.map(absence => `
          <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
            <div class="p-4">
              <div class="flex items-center mb-4">
                <div class="h-12 w-12 rounded-full flex items-center justify-center mr-3 border-2 shadow-sm">
                  ${absence.photo && absence.photo !== "NULL" && absence.photo !== ""
                    ? `<img src="${absence.photo}" alt="Photo de ${absence.nom_prenom || "Agent"}" class="rounded-full object-cover w-12 h-12" onerror="this.parentNode.innerHTML = '${getInitialsCircle(absence.nom_prenom || "").replace(/'/g, "\\'")}'"/>`
                    : getInitialsCircle(absence.nom_prenom || "")
                  }
                </div>
                <div>
                  <h3 class="font-semibold text-base sm:text-lg text-gray-800">${absence.nom_prenom || "Nom inconnu"}</h3>
                </div>
              </div>
              <div class="text-sm text-gray-600 space-y-1 mb-4">
                <div><i class="fas fa-traffic-light mr-2"></i><strong>Statut :</strong> 
                  ${getStatutDisplayIcon(getStatutLibelle(absence.statut))}
                </div>
                <div><i class="fas fa-calendar-alt mr-2"></i><strong>Début :</strong> ${absence.debut || "Non défini"}</div>
                <div><i class="fas fa-calendar-check mr-2"></i><strong>Fin :</strong> ${absence.fin || "Non défini"}</div>
                <div><i class="fas fa-suitcase-rolling mr-2"></i><strong>Type :</strong> ${absence.motif || "Non défini"}</div>
                <div><i class="fas fa-file-alt mr-2"></i><strong>Justificatif :</strong> ${absence.justificatif || "Non défini"}</div>
              </div>
              <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
                ${generateActionButtons(absence, roleUtilisateur)}
              </div>
            </div>
          </div>
        `).join("");

    // Mise à jour du tableau (desktop)
    if (filteredAbsences.length === 0) {
      absencesTableBody.innerHTML = `
        <tr>
          <td colspan="7" class="px-4 py-6 text-center">
            <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl shadow-sm">
              <i class="fas fa-search text-4xl text-indigo-500 mb-4"></i>
              <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucune absence trouvée</h3>
              <p class="text-sm text-gray-600">Essayez une autre recherche ou un autre filtre.</p>
            </div>
          </td>
        </tr>
      `;
    } else {
      absencesTableBody.innerHTML = filteredAbsences.map(absence => `
        <tr class="hover:bg-gray-50 transition-colors">
          <td class="px-4 py-3 whitespace-nowrap">
            <div class="flex items-center">
              <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                ${absence.photo && absence.photo !== "NULL" && absence.photo !== ""
                  ? `<img src="${absence.photo}" alt="Photo de ${absence.nom_prenom || "Agent"}" class="rounded-full object-cover w-10 h-10" onerror="this.parentNode.innerHTML = '${getInitialsCircle(absence.nom_prenom || "").replace(/'/g, "\\'")}'"/>`
                  : getInitialsCircle(absence.nom_prenom || "")
                }
              </div>
              <div>
                <div class="text-sm font-medium text-gray-900">${absence.nom_prenom || "Nom inconnu"}</div>
              </div>
            </div>
          </td>
          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${absence.debut || "Non défini"}</td>
          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${absence.fin || "Non défini"}</td>
          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${absence.motif || "Non défini"}</td>
          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${absence.justificatif || "Non défini"}</td>
          <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
            ${getStatutDisplayIcon(getStatutLibelle(absence.statut))}
          </td>
          <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
            <div class="flex space-x-2 justify-end">
              ${generateActionButtons(absence, roleUtilisateur)}
            </div>
          </td>
        </tr>
      `).join("");
    }
  }

  // Appel initial pour afficher toutes les absences
  filterAndDisplayAbsences();
}

  
/**
 * Fonction utilitaire pour afficher l'icône du statut
 */
function getStatutDisplayIcon(statutLibelle) {
  const statut = statutLibelle.toLowerCase();
  if (statut === 'autoriser') {
    return '<span class="text-green-600">✔️</span>';
  } else if (statut === 'rejeter') {
    return '<span class="text-red-600">❌</span>';
  } else if (statut === 'en attente') {
    return '<span class="text-yellow-600">⏳</span>';
  } 
}


