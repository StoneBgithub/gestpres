import { eventBus } from "../config.js";

// Données globales
let absences = [];
let agents = [];
let bureaux = [];
let typesAbsences = [];
let statutsAbsences = [];
let roleUtilisateur = '';

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

// Fonction pour générer l'affichage du justificatif avec icône
function generateJustificatifDisplay(justificatif) {
  if (!justificatif || justificatif === '' || justificatif === 'NULL') {
    return '<span class="text-gray-400 italic">Aucun</span>';
  }
  
  const ext = justificatif.split('.').pop().toLowerCase();
  const url = justificatif;
  
  if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
    return `<a href="${url}" target="_blank" title="Voir l'image" class="text-blue-600 hover:opacity-75 text-xl">
      <i class="fas fa-eye"></i>
    </a>`;
  } else if (ext === 'pdf') {
    return `<a href="${url}" target="_blank" title="Voir le PDF" class="text-red-600 hover:opacity-75 text-xl">
      <i class="fas fa-eye"></i>
    </a>`;
  } else {
    return `<a href="${url}" target="_blank" title="Télécharger le fichier" class="text-gray-600 hover:opacity-75 text-xl">
      <i class="fas fa-eye"></i>
    </a>`;
  }
}

// Fonction d'initialisation principale
export function init() {
  console.log("Initialisation du module de gestion des absences");
  loadAbsencesData();
  loadAgentsData();
  loadBureauxData();
  loadTypesAbsencesData();
  loadStatutsAbsencesData();
  loadRoleUtilisateur();
  initModals();
  setupListeners();
  setupFilters();
  checkAndShowMessageModal();
}

// Charger les données des absences
function loadAbsencesData() {
  const absencesDataElement = document.getElementById("AbsencesDatas");
  if (absencesDataElement) {
    try {
      absences = JSON.parse(absencesDataElement.textContent).map((absence, index) => {
        if (!absence.id) {
          absence.id = absence._id || `temp-id-${index}`;
          console.warn(`Absence à l'index ${index} n'a pas d'ID défini`, absence);
        }
        absence.id = String(absence.id);
        return absence;
      });
      console.log(`${absences.length} absences chargées`);
    } catch (e) {
      console.error("Erreur lors du parsing des données absences:", e);
      absences = [];
    }
  }
}

// Charger les données des agents
function loadAgentsData() {
  const agentsDataElement = document.getElementById("AgentsDatas");
  if (agentsDataElement) {
    try {
      agents = JSON.parse(agentsDataElement.textContent);
      console.log(`${agents.length} agents chargés:`, agents);
      
      // Vérification de la structure des données
      if (agents.length > 0) {
        console.log("Structure du premier agent:", Object.keys(agents[0]));
        console.log("Exemple agent:", agents[0]);
      }
    } catch (e) {
      console.error("Erreur lors du parsing des données agents:", e);
      console.error("Contenu de l'élément:", agentsDataElement.textContent);
      agents = [];
    }
  } else {
    console.error("Élément AgentsDatas non trouvé dans le DOM");
  }
}

// Charger les données des bureaux
function loadBureauxData() {
  const bureauxDataElement = document.getElementById("bureauxDatas");
  if (bureauxDataElement) {
    try {
      bureaux = JSON.parse(bureauxDataElement.textContent);
      console.log(`${bureaux.length} bureaux chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données bureaux:", e);
      bureaux = [];
    }
  }
}

// Charger les types d'absences
function loadTypesAbsencesData() {
  const typesDataElement = document.getElementById("typesAbsencesDatas");
  if (typesDataElement) {
    try {
      typesAbsences = JSON.parse(typesDataElement.textContent);
      console.log(`${typesAbsences.length} types d'absences chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des types d'absences:", e);
      typesAbsences = [];
    }
  }
}

// Charger les statuts d'absences
function loadStatutsAbsencesData() {
  const statutsDataElement = document.getElementById("statutsAbsencesDatas");
  if (statutsDataElement) {
    try {
      statutsAbsences = JSON.parse(statutsDataElement.textContent);
      console.log(`${statutsAbsences.length} statuts d'absences chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des statuts d'absences:", e);
      statutsAbsences = [];
    }
  }
}

// Charger le rôle de l'utilisateur
function loadRoleUtilisateur() {
  const roleElement = document.getElementById("roleUtilisateur");
  if (roleElement) {
    try {
      roleUtilisateur = JSON.parse(roleElement.textContent);
      console.log("Rôle utilisateur:", roleUtilisateur);
    } catch (e) {
      console.error("Erreur lors du parsing du rôle utilisateur:", e);
      roleUtilisateur = '';
    }
  }
}

// Vérifier et afficher messageModal si des messages sont présents
function checkAndShowMessageModal() {
  const messageModal = document.getElementById("messageAbsences");
  if (messageModal && messageModal.dataset.messages) {
    try {
      const messages = JSON.parse(messageModal.dataset.messages);
      if (
        (messages.success && messages.success.length > 0) ||
        (messages.errors && messages.errors.length > 0)
      ) {
        console.log("Messages détectés:", messages);
        showModal("messageAbsences");
      }
    } catch (e) {
      console.error("Erreur lors du parsing de data-messages:", e);
    }
  }
}

// Initialiser les modales
function initModals() {
  document.querySelectorAll(".close-modal, .close-modals").forEach(btn => {
    btn.addEventListener("click", function() {
      const modal = this.closest("#absenceModal, #deleteAbsenceModal, #authorizeAbsenceModal, #rejectAbsenceModal, #messageAbsences");
      if (modal) {
        closeModal(modal.id);
      }
    });
  });

  // Fermer les modales en cliquant à l'extérieur
  document.querySelectorAll("#absenceModal, #deleteAbsenceModal, #authorizeAbsenceModal, #rejectAbsenceModal, #messageAbsences").forEach(modal => {
    modal.addEventListener("click", function(e) {
      if (e.target === this) {
        closeModal(this.id);
      }
    });
  });

  // Fermeture avec Échap
  document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
      closeModal("absenceModal");
      closeModal("deleteAbsenceModal");
      closeModal("authorizeAbsenceModal");
      closeModal("rejectAbsenceModal");
      closeModal("messageAbsences");
    }
  });
}

// Configurer les écouteurs d'événements
function setupListeners() {
  document.body.addEventListener("click", function(e) {
    const target = e.target;

    // Bouton "Nouvelle absence"
    if (target.matches(".add-absence-btns") || target.closest(".add-absence-btns")) {
      e.preventDefault();
      addAbsence();
    }

    // Boutons d'édition
    if (target.matches(".edit-absence-btn") || target.closest(".edit-absence-btn")) {
      e.preventDefault();
      const btn = target.matches(".edit-absence-btn") ? target : target.closest(".edit-absence-btn");
      const absenceId = btn.getAttribute("data-id");
      editAbsence(absenceId);
    }

    // Boutons de suppression
    if (target.matches(".delete-absence-btn") || target.closest(".delete-absence-btn")) {
      e.preventDefault();
      const btn = target.matches(".delete-absence-btn") ? target : target.closest(".delete-absence-btn");
      const absenceId = btn.getAttribute("data-id");
      confirmDeleteAbsence(absenceId);
    }

    // Boutons de validation
    if (target.matches(".validate-absence-btn") || target.closest(".validate-absence-btn")) {
      e.preventDefault();
      const btn = target.matches(".validate-absence-btn") ? target : target.closest(".validate-absence-btn");
      const absenceId = btn.getAttribute("data-id");
      confirmAuthorizeAbsence(absenceId);
    }

    // Boutons de rejet
    if (target.matches(".reject-absence-btn") || target.closest(".reject-absence-btn")) {
      e.preventDefault();
      const btn = target.matches(".reject-absence-btn") ? target : target.closest(".reject-absence-btn");
      const absenceId = btn.getAttribute("data-id");
      confirmRejectAbsence(absenceId);
    }
  });

  // Gestion du sélecteur de bureau pour mettre à jour les agents
  const modalBureauSelect = document.getElementById("filter_bureaux");
  if (modalBureauSelect) {
    modalBureauSelect.addEventListener("change", function() {
      updateModalAgentsOptions(this.value);
    });
  }

  // Soumission du formulaire d'absence
  const absenceForm = document.getElementById("absenceForm");
  if (absenceForm) {
    absenceForm.addEventListener("submit", function (e) {
      e.preventDefault();
      console.log("=== DÉBUT SOUMISSION FORMULAIRE ===");

      const bureauSelect = document.getElementById("filter_bureaux");
      const agentSelect = document.getElementById("filter_agents");
      const typeSelect = document.getElementById("filter_types");
      const dateDebut = document.getElementById("filter_date_debut");
      const dateFin = document.getElementById("filter_date_fin");
      const justificatif = document.getElementById("justificatif");
      const description = document.getElementById("description");
      const action = document.getElementById("formActions").value;
      const absenceId = document.getElementById("absence_id").value;

      // Validation améliorée avec logs détaillés
      let errors = [];
      
      console.log("Valeurs des champs:");
      console.log("- Bureau:", bureauSelect ? bureauSelect.value : "ÉLÉMENT MANQUANT");
      console.log("- Agent:", agentSelect ? agentSelect.value : "ÉLÉMENT MANQUANT");
      console.log("- Type:", typeSelect ? typeSelect.value : "ÉLÉMENT MANQUANT");
      console.log("- Date début:", dateDebut ? dateDebut.value : "ÉLÉMENT MANQUANT");
      console.log("- Date fin:", dateFin ? dateFin.value : "ÉLÉMENT MANQUANT");
      console.log("- Action:", action);
      console.log("- ID absence:", absenceId);

      // Vérifications avec messages spécifiques
      if (!bureauSelect) {
        errors.push("Élément 'bureau' non trouvé dans le DOM.");
      } else if (!bureauSelect.value || bureauSelect.value.trim() === "") {
        errors.push("Veuillez sélectionner un bureau.");
      }

      if (!agentSelect) {
        errors.push("Élément 'agent' non trouvé dans le DOM.");
      } else if (!agentSelect.value || agentSelect.value.trim() === "") {
        errors.push("Veuillez sélectionner un agent.");
      }

      if (!typeSelect) {
        errors.push("Élément 'type d'absence' non trouvé dans le DOM.");
      } else if (!typeSelect.value || typeSelect.value.trim() === "") {
        errors.push("Veuillez sélectionner un motif d'absence.");
      }

      if (!dateDebut) {
        errors.push("Élément 'date de début' non trouvé dans le DOM.");
      } else if (!dateDebut.value || dateDebut.value.trim() === "") {
        errors.push("Veuillez saisir la date de début.");
      }

      if (!dateFin) {
        errors.push("Élément 'date de fin' non trouvé dans le DOM.");
      } else if (!dateFin.value || dateFin.value.trim() === "") {
        errors.push("Veuillez saisir la date de fin.");
      }

      // Validation des dates
      if (dateDebut && dateFin && dateDebut.value && dateFin.value) {
        const debut = new Date(dateDebut.value);
        const fin = new Date(dateFin.value);
        if (debut > fin) {
          errors.push("La date de début ne peut pas être postérieure à la date de fin.");
        }
      }

      if (errors.length > 0) {
        console.error("Erreurs de validation:", errors);
        showMessageModal("Erreur de validation", errors, "error");
        return false;
      }

      // Préparation des données avec vérifications
      const formData = new FormData();
      
      formData.append("action", action);
      if (absenceId && absenceId.trim() !== "") {
        formData.append("absence_id", absenceId);
      }
      formData.append("agent_id", agentSelect.value);
      formData.append("motif", typeSelect.value);
      formData.append("date_debut", dateDebut.value);
      formData.append("date_fin", dateFin.value);
      formData.append("description", description ? description.value : "");
      
      // Gestion du fichier
      if (justificatif && justificatif.files && justificatif.files.length > 0) {
        console.log("Fichier détecté:", justificatif.files[0].name, justificatif.files[0].size, "bytes");
        formData.append("justificatif", justificatif.files[0]);
      } else {
        console.log("Aucun fichier sélectionné");
      }

      // Debug: afficher le contenu du FormData
      console.log("Contenu FormData:");
      for (let [key, value] of formData.entries()) {
        console.log(`- ${key}:`, value);
      }

      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Traitement...';
      }

      // Requête AJAX améliorée
      fetch("?page=absence_content", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
      .then((response) => {
        console.log("=== RÉPONSE SERVEUR ===");
        console.log("Status:", response.status);
        console.log("Headers:", Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
          throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
        }

        return response.text();
      })
      .then((responseText) => {
        console.log("Réponse brute du serveur:");
        console.log(responseText);

        let data;
        try {
          data = JSON.parse(responseText);
          console.log("Données parsées:", data);
        } catch (e) {
          console.warn("Impossible de parser en JSON, traitement en tant que HTML");
          
          if (responseText.includes("Fatal error") || responseText.includes("Parse error")) {
            throw new Error("Erreur PHP détectée dans la réponse");
          }
          
          data = { success: true, reload: true, message: "Opération réussie (redirection)" };
        }

        if (submitButton) {
          submitButton.disabled = false;
          submitButton.innerHTML = '<i class="fas fa-save mr-2"></i> Enregistrer';
        }

        closeModal("absenceModal");

        if (data.success || data.reload) {
          const successMessages = data.messages?.success || 
                                 data.message ? [data.message] : 
                                 ["Absence enregistrée avec succès."];
          
          showMessageModal("Succès", successMessages, "success");
          
          setTimeout(() => {
            console.log("Rechargement de la page...");
            window.location.reload();
          }, 1500);
        } else {
          const errorMessages = data.messages?.errors || 
                               data.error ? [data.error] :
                               ["Une erreur est survenue lors de l'enregistrement."];
          
          console.error("Erreurs serveur:", errorMessages);
          showMessageModal("Erreur", errorMessages, "error");
        }
      })
      .catch((error) => {
        console.error("=== ERREUR REQUÊTE ===");
        console.error("Type:", error.name);
        console.error("Message:", error.message);
        console.error("Stack:", error.stack);

        if (submitButton) {
          submitButton.disabled = false;
          submitButton.innerHTML = '<i class="fas fa-save mr-2"></i> Enregistrer';
        }

        showMessageModal(
          "Erreur technique",
          ["Erreur lors de l'envoi : " + error.message],
          "error"
        );
      });

      console.log("=== FIN SOUMISSION FORMULAIRE ===");
    });
  }
}

// Mettre à jour les options des agents selon le bureau
function updateModalAgentsOptions(bureauLibele) {
  const agentSelect = document.getElementById("filter_agents");
  if (!agentSelect) {
    console.error("Élément filter_agents non trouvé");
    return;
  }

  console.log("Mise à jour agents pour bureau:", bureauLibele);
  console.log("Agents disponibles:", agents);

  agentSelect.innerHTML = '<option value="">Choisir un agent</option>';
  agentSelect.disabled = true;

  if (bureauLibele && bureauLibele.trim() !== "") {
    const agentsDuBureau = agents.filter(agent => {
      const bureauAgent = agent.bureau || agent.libele || agent.libele_bureau || "";
      return bureauAgent.toLowerCase() === bureauLibele.toLowerCase();
    });
    
    console.log(`Agents trouvés pour le bureau "${bureauLibele}":`, agentsDuBureau);

    if (agentsDuBureau.length > 0) {
      agentsDuBureau.forEach(agent => {
        const option = document.createElement("option");
        option.value = agent.id;
        option.textContent = agent.nom_prenom || `${agent.nom || ''} ${agent.prenom || ''}`.trim();
        agentSelect.appendChild(option);
        console.log("Agent ajouté:", option.textContent, "ID:", option.value);
      });
      agentSelect.disabled = false;
    } else {
      const option = document.createElement("option");
      option.value = "";
      option.textContent = "Aucun agent disponible pour ce bureau";
      agentSelect.appendChild(option);
      console.warn("Aucun agent trouvé pour ce bureau");
    }
  }
}

// Ouvrir le modal d'ajout d'absence
export function addAbsence() {
  console.log("=== OUVERTURE MODAL AJOUT ABSENCE ===");
  
  const modal = document.getElementById("absenceModal");
  if (!modal) {
    console.error("Modal absenceModal non trouvé");
    return;
  }

  const elementsToCheck = [
    "absenceTitle",
    "absenceForm", 
    "absence_id",
    "formActions",
    "filter_bureaux",
    "filter_agents",
    "filter_types",
    "filter_date_debut", 
    "filter_date_fin",
    "justificatif",
    "description"
  ];

  elementsToCheck.forEach(id => {
    const element = document.getElementById(id);
    console.log(`Élément ${id}:`, element ? "✓ Trouvé" : "✗ MANQUANT");
  });

  const titleElement = document.getElementById("absenceTitle");
  if (titleElement) {
    titleElement.innerHTML =
      '<i class="fas fa-calendar-plus mr-2 text-indigo-600"></i><span>Ajouter une nouvelle absence</span>';
  }

  const form = document.getElementById("absenceForm");
  if (form) {
    form.reset();
    
    const absenceIdField = document.getElementById("absence_id");
    const actionField = document.getElementById("formActions");
    
    if (absenceIdField) absenceIdField.value = "";
    if (actionField) actionField.value = "add";

    const bureauSelect = document.getElementById("filter_bureaux");
    const agentSelect = document.getElementById("filter_agents");
    const typeSelect = document.getElementById("filter_types");
    const dateDebut = document.getElementById("filter_date_debut");
    const dateFin = document.getElementById("filter_date_fin");
    const justificatif = document.getElementById("justificatif");
    const description = document.getElementById("description");
    
    if (bureauSelect) {
      bureauSelect.value = "";
      bureauSelect.disabled = false;
    }
    
    if (agentSelect) {
      agentSelect.disabled = true;
      agentSelect.innerHTML = '<option value="">Choisir un agent</option>';
    }
    
    if (typeSelect) {
      typeSelect.value = "";
      typeSelect.disabled = false;
    }
    
    if (dateDebut) dateDebut.value = "";
    if (dateFin) dateFin.value = "";
    if (justificatif) justificatif.value = "";
    if (description) description.value = "";
  }

  showModal("absenceModal");
  console.log("Modal d'ajout ouvert");
}

// Ouvre le modal d'édition pour une absence
export function editAbsence(absenceId) {
  const modal = document.getElementById("absenceModal");
  if (!modal) return;

  const titleElement = document.getElementById("absenceTitle");
  if (titleElement) {
    titleElement.innerHTML =
      '<i class="fas fa-calendar-edit mr-2 text-indigo-600"></i><span>Modifier une absence</span>';
  }

  const absenceIdStr = String(absenceId);
  const absence = absences.find((a) => a.id === absenceIdStr);
  if (!absence) {
    showMessageModal("Erreur", ["Absence non trouvée."], "error");
    return;
  }

  const form = document.getElementById("absenceForm");
  if (!form) return;

  form.reset();

  document.getElementById("absence_id").value = absence.id || "";
  document.getElementById("formActions").value = "update";

  const bureauSelect = document.getElementById("filter_bureaux");
  const agentSelect = document.getElementById("filter_agents");

  if (bureauSelect && agentSelect) {
    const agent = agents.find(a => a.id == absence.agent_id);
    if (agent) {
      bureauSelect.value = agent.bureau || "";
      updateModalAgentsOptions(agent.bureau || "");
      setTimeout(() => {
        agentSelect.value = absence.agent_id;
      }, 100);
    }
  }

  const typeSelect = document.getElementById("filter_types");
  if (typeSelect) {
    const typeAbsence = typesAbsences.find(t => t.libelle === absence.motif);
    if (typeAbsence) {
      typeSelect.value = typeAbsence.id;
    }
  }

  const dateDebut = document.getElementById("filter_date_debut");
  const dateFin = document.getElementById("filter_date_fin");
  if (dateDebut) {
    dateDebut.value = absence.debut || absence.date_debut || "";
  }
  if (dateFin) {
    dateFin.value = absence.fin || absence.date_fin || "";
  }

  const justificatif = document.getElementById("justificatif");
  if (justificatif) {
    justificatif.value = "";
  }

  const description = document.getElementById("description");
  if (description) {
    description.value = absence.description || "";
  }

  showModal("absenceModal");
}

// Confirmer suppression d'absence
export function confirmDeleteAbsence(absenceId) {
  const modal = document.getElementById("deleteAbsenceModal");
  if (!modal) return;

  const confirmDeleteBtn = document.getElementById("confirmDeleteAbsenceBtn");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.href = `?page=absence_content&action=delete&id=${absenceId}`;
  }

  showModal("deleteAbsenceModal");
}

// Confirmer autorisation d'absence
export function confirmAuthorizeAbsence(absenceId) {
  const modal = document.getElementById("authorizeAbsenceModal");
  if (!modal) return;

  const confirmAuthorizeBtn = document.getElementById("confirmAuthorizeAbsenceBtn");
  if (confirmAuthorizeBtn) {
    confirmAuthorizeBtn.onclick = function(e) {
      e.preventDefault();
      performAbsenceAction(absenceId, "validate");
    };
  }

  showModal("authorizeAbsenceModal");
}

// Confirmer rejet d'absence
export function confirmRejectAbsence(absenceId) {
  const modal = document.getElementById("rejectAbsenceModal");
  if (!modal) return;

  const confirmRejectBtn = document.getElementById("confirmRejectAbsenceBtn");
  const reasonInput = document.getElementById("rejectReason");

  if (reasonInput) {
    reasonInput.value = "";
    reasonInput.focus();
  }

  if (confirmRejectBtn) {
    confirmRejectBtn.disabled = true;
    confirmRejectBtn.classList.add("opacity-50", "cursor-not-allowed");
    confirmRejectBtn.classList.remove("hover:bg-red-700", "focus:ring-2", "focus:ring-red-500");
  }

  if (reasonInput && confirmRejectBtn) {
    reasonInput.addEventListener("input", function() {
      const motif = this.value.trim();
      
      if (motif.length >= 10) {
        confirmRejectBtn.disabled = false;
        confirmRejectBtn.classList.remove("opacity-50", "cursor-not-allowed");
        confirmRejectBtn.classList.add("hover:bg-red-700", "focus:ring-2", "focus:ring-red-500");
      } else {
        confirmRejectBtn.disabled = true;
        confirmRejectBtn.classList.add("opacity-50", "cursor-not-allowed");
        confirmRejectBtn.classList.remove("hover:bg-red-700", "focus:ring-2", "focus:ring-red-500");
      }
    });

    confirmRejectBtn.onclick = function(e) {
      e.preventDefault();
      
      const motif = reasonInput.value.trim();
      
      if (motif.length < 10) {
        alert("Le motif du rejet doit contenir au moins 10 caractères.");
        reasonInput.focus();
        return;
      }

      this.disabled = true;
      this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Traitement...';

      performAbsenceAction(absenceId, "reject", motif);
    };
  }

  showModal("rejectAbsenceModal");
}

// Exécuter une action sur une absence (autoriser/rejeter)
function performAbsenceAction(absenceId, action, reason = null) {
  const formData = new FormData();
  formData.append("action", action);
  formData.append("absence_id", absenceId);

  if (action === "reject" && reason) {
    formData.append("motif_rejet", reason);
  }

  console.log(`${action} absence ID:`, absenceId, reason ? ` | Motif: ${reason}` : "");

  fetch("?page=absence_content", {
    method: "POST",
    body: formData,
    credentials: "same-origin",
    headers: {
      "X-Requested-With": "XMLHttpRequest"
    }
  })
    .then(response => {
      console.log("Statut de réponse action:", response.status);

      if (!response.ok) {
        throw new Error(`Erreur HTTP ! Statut : ${response.status}`);
      }

      return response.text().then(text => {
        try {
          return JSON.parse(text);
        } catch (e) {
          console.warn("Réponse non-JSON reçue:", text);
          return { success: true, reload: true };
        }
      });
    })
    .then(data => {
      console.log("Réponse serveur pour action:", data);

      closeModal("authorizeAbsenceModal");
      closeModal("rejectAbsenceModal");

      const confirmRejectBtn = document.getElementById("confirmRejectAbsenceBtn");
      if (confirmRejectBtn) {
        confirmRejectBtn.disabled = true;
        confirmRejectBtn.innerHTML = '<i class="fas fa-times mr-2"></i> Rejeter';
        confirmRejectBtn.classList.add("opacity-50", "cursor-not-allowed");
      }

      if (data.success || data.reload) {
        const message = action === "validate" 
          ? "Absence autorisée avec succès" 
          : "Absence rejetée avec succès";

        if (data.updated_absence) {
          updateLocalAbsenceData(data.updated_absence);
        }

        showMessageModal("Succès", [message], "success");

        setTimeout(() => {
          refreshAbsenceDisplay();
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        }, 500);
      } else {
        showMessageModal(
          "Erreur",
          data.messages?.errors || ["Une erreur s'est produite"],
          "error"
        );
      }
    })
    .catch(error => {
      console.error("Erreur lors de l'action:", error);
      closeModal("authorizeAbsenceModal");
      closeModal("rejectAbsenceModal");
      
      const confirmRejectBtn = document.getElementById("confirmRejectAbsenceBtn");
      if (confirmRejectBtn) {
        confirmRejectBtn.disabled = true;
        confirmRejectBtn.innerHTML = '<i class="fas fa-times mr-2"></i> Rejeter';
        confirmRejectBtn.classList.add("opacity-50", "cursor-not-allowed");
      }
      
      showMessageModal("Erreur", ["Erreur lors de l'action : " + error.message], "error");
    });
}

function updateLocalAbsenceData(updatedAbsence) {
  console.log("Mise à jour des données locales pour l'absence:", updatedAbsence);
  
  const absenceIndex = absences.findIndex(absence => 
    String(absence.id) === String(updatedAbsence.id)
  );
  
  if (absenceIndex !== -1) {
    absences[absenceIndex].statut = updatedAbsence.statut;
    console.log(`Absence ID ${updatedAbsence.id} mise à jour localement:`, absences[absenceIndex]);
  } else {
    console.warn(`Absence ID ${updatedAbsence.id} non trouvée dans les données locales`);
  }
}

// Afficher un modal
export function showModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) {
    console.error(`Modal ${modalId} non trouvé`);
    return;
  }

  modal.classList.remove("hidden");
  const modalContent = modal.querySelector("div[id$='Content']") || modal.querySelector("div.transform");
  if (modalContent) {
    setTimeout(() => {
      modalContent.classList.remove("scale-95", "opacity-0");
      modalContent.classList.add("scale-100", "opacity-100");
    }, 10);
  }
  document.body.style.overflow = "hidden";
}

// Fermer un modal
export function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  const modalContent = modal.querySelector("div[id$='Content']") || modal.querySelector("div.transform");
  
  if (modalContent) {
    modalContent.classList.remove("scale-100", "opacity-100");
    modalContent.classList.add("scale-95", "opacity-0");
    
    setTimeout(() => {
      modal.classList.add("hidden");
      document.body.style.overflow = "auto";
      
      if (modalId === "rejectAbsenceModal") {
        const reasonInput = document.getElementById("rejectReason");
        const confirmBtn = document.getElementById("confirmRejectAbsenceBtn");
        
        if (reasonInput) {
          reasonInput.value = "";
        }
        
        if (confirmBtn) {
          confirmBtn.disabled = true;
          confirmBtn.innerHTML = '<i class="fas fa-times mr-2"></i> Rejeter';
          confirmBtn.classList.add("opacity-50", "cursor-not-allowed");
          confirmBtn.classList.remove("hover:bg-red-700", "focus:ring-2", "focus:ring-red-500");
        }
      }
    }, 300);
  } else {
    modal.classList.add("hidden");
    document.body.style.overflow = "auto";
  }

  eventBus.publish("modal:closed", { modalId });
}

// Afficher le modal de messages
function showMessageModal(title, messages, type) {
  const messageModal = document.getElementById("messageAbsences");
  const messageModalContent = document.getElementById("messageAbsencesModalContent");
  if (!messageModal || !messageModalContent) return;

  const modalTitle = messageModalContent.querySelector("h3 span");
  const icon = messageModalContent.querySelector("h3 i");
  const messageContainer = messageModalContent.querySelector(".p-4.sm\\:p-6");

  if (modalTitle && icon && messageContainer) {
    modalTitle.textContent = title;
    icon.className = `fas fa-info-circle mr-2 ${type === "error" ? "text-red-500" : "text-green-600"}`;

    messageContainer.innerHTML = messages.map(msg => `
      <p class="${type === "error" ? "text-red-600" : "text-green-600"} font-semibold text-sm sm:text-base mb-2">
        ${type === "error" ? "❌" : "✅"} ${msg}
      </p>
    `).join("") + `
      <div class="flex justify-end mt-4">
        <button type="button" class="close-modals px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
          <i class="fas fa-times mr-2"></i> Fermer
        </button>
      </div>
    `;
  }

  showModal("messageAbsences");
}

// Configurer les filtres pour les absences 
function setupFilters() {
  const searchInput = document.getElementById("search");
  const typeSelect = document.querySelector('#search_filter_types');  
  const statutSelect = document.querySelector('#search_filter_statuts'); 

  if (!searchInput || !typeSelect || !statutSelect) {
    console.warn("Éléments nécessaires pour les filtres absences non trouvés");
    return;
  }

  searchInput.addEventListener("input", filterAndDisplayAbsences);
  typeSelect.addEventListener("change", filterAndDisplayAbsences);
  statutSelect.addEventListener("change", filterAndDisplayAbsences);

  function filterAndDisplayAbsences() {
    const searchQuery = searchInput.value.trim().toLowerCase();
    const typeFilter = typeSelect.value;
    const statutFilter = statutSelect.value;

    console.log("Filtres appliqués:", { searchQuery, typeFilter, statutFilter });

    const filteredAbsences = absences.filter((absence) => {
      const nomPrenom = (absence.nom_prenom || "").toLowerCase();
      const motif = (absence.motif || "").toLowerCase();
      const statut = (absence.statut || "").toLowerCase();
      
      const typeAbsence = typesAbsences.find(t => t.libelle === absence.motif);
      const typeId = typeAbsence ? String(typeAbsence.id) : "";
      
      const statutAbsence = statutsAbsences.find(s => s.libelle.toLowerCase() === statut);
      const statutId = statutAbsence ? String(statutAbsence.id) : "";

      const matchesSearch = nomPrenom.includes(searchQuery) || motif.includes(searchQuery);
      const matchesType = typeFilter === "" || typeId === String(typeFilter);
      const matchesStatut = statutFilter === "" || statutId === String(statutFilter);

      return matchesSearch && matchesType && matchesStatut;
    });

    console.log("Absences filtrées:", filteredAbsences.length);

    updateCardsDisplay(filteredAbsences);
    updateTableDisplay(filteredAbsences);
  }
}

// Mettre à jour l'affichage des cartes
function updateCardsDisplay(filteredAbsences) {
  const cardsContainer = document.getElementById("absencesCards");
  if (!cardsContainer) return;

  if (filteredAbsences.length === 0) {
    cardsContainer.innerHTML = `
      <div class="col-span-full flex flex-col items-center justify-center p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl shadow-sm">
        <i class="fas fa-search text-4xl text-indigo-500 mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucune absence trouvée</h3>
        <p class="text-sm text-gray-600">Essayez une autre recherche.</p>
      </div>
    `;
  } else {
    cardsContainer.innerHTML = filteredAbsences.map(absence => {
      const statut = (absence.statut || "").toLowerCase();
      return `
        <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
          <div class="p-4">
            <div class="flex items-center mb-4">
              <div class="h-12 w-12 rounded-full flex items-center justify-center mr-3 border-2 shadow-sm">
                ${absence.photo && absence.photo !== "NULL" && absence.photo !== ""
                  ? `<img src="${absence.photo}" alt="${absence.nom_prenom || 'Agent'}" class="w-12 h-12 rounded-full object-cover">`
                  : getInitialsCircle(absence.nom_prenom || "")
                }
              </div>
              <div>
                <h3 class="font-semibold text-base sm:text-lg text-gray-800">
                  ${absence.nom_prenom || "Nom inconnu"}
                </h3>
              </div>
            </div>

            <div class="text-sm text-gray-600 space-y-1 mb-4">
              <div><i class="fas fa-traffic-light mr-2"></i><strong>Statut :</strong> 
                ${statut === 'autoriser' 
                  ? '<span class="text-green-600">✔️ Autorisé</span>'
                  : statut === 'rejeter' || statut === 'rejeté'
                  ? '<span class="text-red-600">❌ Rejeté</span>'
                  : statut === 'en attente'
                  ? '<span class="text-gray-600">⏳ En attente</span>'
                  : '<span class="text-gray-500">Inconnu</span>'
                }
              </div>
              <div><i class="fas fa-calendar-alt mr-2"></i><strong>Début :</strong> ${absence.debut || absence.date_debut || "Non défini"}</div>
              <div><i class="fas fa-calendar-check mr-2"></i><strong>Fin :</strong> ${absence.fin || absence.date_fin || "Non défini"}</div>
              <div><i class="fas fa-suitcase-rolling mr-2"></i><strong>Type :</strong> ${absence.motif || "Non défini"}</div>
              <div><i class="fas fa-file-alt mr-2"></i><strong>Justificatif :</strong> ${generateJustificatifDisplay(absence.justificatif)}</div>
            </div>

            <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
              ${generateActionButtons(absence, roleUtilisateur)}
            </div>
          </div>
        </div>
      `;
    }).join("");
  }
}

// Mettre à jour l'affichage du tableau
function updateTableDisplay(filteredAbsences) {
  const tableBody = document.querySelector("#absencesTable tbody");
  if (!tableBody) return;

  if (filteredAbsences.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="7" class="px-4 py-6 text-center">
          <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl shadow-sm">
            <i class="fas fa-search text-4xl text-indigo-500 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucune absence trouvée</h3>
            <p class="text-sm text-gray-600">Essayez une autre recherche.</p>
          </div>
        </td>
      </tr>
    `;
  } else {
    tableBody.innerHTML = filteredAbsences.map(absence => `
      <tr class="hover:bg-gray-50 transition-colors">
        <td class="px-4 py-3 whitespace-nowrap">
          <div class="flex items-center">
            <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
              ${absence.photo && absence.photo !== "NULL" && absence.photo !== ""
                ? `<img src="${absence.photo}" alt="${absence.nom_prenom || 'Agent'}" class="w-10 h-10 rounded-full object-cover">`
                : getInitialsCircle(absence.nom_prenom || "")
              }
            </div>
            <div>
              <div class="text-sm font-medium text-black">${absence.nom_prenom || "Nom inconnu"}</div>
            </div>
          </div>
        </td>
        <td class="px-4 py-3 whitespace-nowrap text-sm text-black">${absence.debut || absence.date_debut || "Non défini"}</td>
        <td class="px-4 py-3 whitespace-nowrap text-sm text-black">${absence.fin || absence.date_fin || "Non défini"}</td>
        <td class="px-4 py-3 whitespace-nowrap text-sm text-black">${absence.motif || "Non défini"}</td>
        <td class="px-4 py-3 whitespace-nowrap text-sm text-black text-center">
          ${generateJustificatifDisplay(absence.justificatif)}
        </td>
        <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
          ${generateStatusIcon(absence.statut)}
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

// Générer les icônes de statut
function generateStatusIcon(statut) {
  const statutLower = (statut || "").toLowerCase().trim();
  console.log("Génération icône pour statut:", statutLower);
  
  if (statutLower === 'autoriser' || statutLower === 'autorisé') {
    return '<span title="Autorisé" style="color:green; font-size: 18px;">✔️</span>';
  } else if (statutLower === 'rejeter' || statutLower === 'rejeté' || statutLower === 'refusé') {
    return '<span title="Rejeté" style="color:red; font-size:18px;">❌</span>';
  } else if (statutLower === 'en attente') {
    return '<span title="En attente" style="color:gray; font-size:18px;">⏳</span>';
  } else {
    return '<i class="fas fa-question-circle text-gray-400" title="Inconnu"></i>';
  }
}

// Générer les boutons d'action selon le rôle
function generateActionButtons(absence, roleUtilisateur) {
  const statut = (absence.statut || "").toLowerCase();
  let buttons = "";

  if (roleUtilisateur === 'secretaire') {
    if (statut === 'autoriser') {
      buttons = `
        <form action="generer_autorisation.php" method="post" target="_blank" style="display: inline;">
          <input type="hidden" name="absence_id" value="${absence.id}">
          <button type="submit" class="text-blue-600 hover:text-blue-800" title="Imprimer l'autorisation">
            <i class="fas fa-print"></i>
          </button>
        </form>
      `;
    }
    else if (statut === "rejeter" || statut === "rejeté" || statut === "refusé") {
      buttons = `
        <form action="generer_refus.php" method="post" target="_blank" style="display: inline;">
          <input type="hidden" name="absence_id" value="${absence.id}">
          <button type="submit" class="text-blue-600 hover:text-blue-800" title="Imprimer le refus">
            <i class="fas fa-print"></i>
          </button>
        </form>
      `;
    }
    else {
      buttons = `
        <button class="edit-absence-btn text-blue-600 hover:text-blue-800" data-id="${absence.id}" title="Modifier">
          <i class="fas fa-edit"></i>
        </button>
        <button class="delete-absence-btn text-red-600 hover:text-red-900" data-id="${absence.id}" title="Supprimer">
          <i class="fas fa-trash"></i>
        </button>
      `;
    }
  } else if (roleUtilisateur === 'chef de service' || roleUtilisateur === 'directrice') {
    if (statut === 'en attente') {
      buttons = `
        <button class="validate-absence-btn text-green-600 hover:text-green-800" data-id="${absence.id}" title="Autoriser">
          <i class="fas fa-check-circle"></i>
        </button>
        <button class="reject-absence-btn text-red-600 hover:text-red-800" data-id="${absence.id}" title="Rejeter">
          <i class="fas fa-times-circle"></i>
        </button>
      `;
    } else if (statut === 'autoriser') {
      buttons = '<span class="text-green-600 text-sm italic"> Déjà autorisé</span>';
    } else if (statut === 'rejeter' || statut === 'rejeté') {
      buttons = '<span class="text-red-600 text-sm italic">Déjà rejeté</span>';
    }
  }

  return buttons;
}

// Rafraîchir l'affichage
function refreshAbsenceDisplay() {
  const searchInput = document.getElementById("search");
  if (searchInput) {
    searchInput.dispatchEvent(new Event("input"));
  }
}

// S'abonner aux événements externes
eventBus.subscribe("absences:externalUpdate", (data) => {
  console.log("Mise à jour externe des absences reçue", data);
  loadAbsencesData();
  loadAgentsData();
  refreshAbsenceDisplay();
});