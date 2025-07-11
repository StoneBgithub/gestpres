import { eventBus } from "../config.js";

// Liste des comptes utilisateurs et données référentielles
let comptes = [];
let agents = [];
let bureaux = [];
let roles = [];
let statuts = [];
let passwordToggleInitialized = false;

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

// Fonction d'initialisation principale
export function init() {
  console.log("Initialisation du module de gestion des utilisateurs");
  loadComptesData();
  loadAgentsData();
  loadBureauxData();
  loadRolesData();
  loadStatutsData();
  initModals();
  setupListeners();
  setupFilters();
  checkAndShowMessageModal();
  setupPasswordToggle();
  setupFormSubmission(); 
  createAjaxEndpoint();
  setupFormSubmissionClassic();
}

// Charger les données des comptes depuis l'élément script
function loadComptesData() {
  const comptesDataElement = document.getElementById("comptesData");
  if (comptesDataElement) {
    try {
      comptes = JSON.parse(comptesDataElement.textContent).map((compte, index) => {
        if (!compte.id) {
          compte.id = compte.user_id || compte._id || null;
          console.warn(`Compte à l'index ${index} n'a pas d'ID défini`, compte);
        }
        compte.id = String(compte.id);
        return compte;
      });
      console.log(`${comptes.length} comptes chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données comptes:", e);
      comptes = [];
      alert("Erreur lors du chargement des données des comptes.");
    }
  } else {
    console.warn("Élément comptesData non trouvé");
  }
}

// Charger les données des agents
function loadAgentsData() {
  const agentsDataElement = document.getElementById("agentsData");
  if (agentsDataElement) {
    try {
      agents = JSON.parse(agentsDataElement.textContent);
      console.log(`${agents.length} agents chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données agents:", e);
      agents = [];
    }
  }
}

// Charger les données des bureaux
function loadBureauxData() {
  const bureauxDataElement = document.getElementById("bureauxData");
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
  }
}

// Charger les données des statuts
function loadStatutsData() {
  const statutDataElement = document.getElementById("statutData");
  if (statutDataElement) {
    try {
      statuts = JSON.parse(statutDataElement.textContent);
      console.log(`${statuts.length} statuts chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données statuts:", e);
      statuts = [];
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
  document.querySelectorAll(".close-modal, .close-modals").forEach((btn) => {
    btn.addEventListener("click", function () {
      const modal = this.closest(
        "#compteModal, #deleteModal, #messageModal"
      );
      if (modal) {
        closeModal(modal.id);
      }
    });
  });

  document
    .querySelectorAll("#compteModal, #deleteModal, #messageModal")
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
    if (target.matches(".add-compte-btn")) {
      addCompte();
    }

    // Boutons d'édition
    if (
      target.matches(".edit-compte-btn") ||
      target.closest(".edit-compte-btn")
    ) {
      const btn = target.matches(".edit-compte-btn")
        ? target
        : target.closest(".edit-compte-btn");
      const compteId = btn.getAttribute("data-id");
      editCompte(compteId);
    }

    // Boutons de suppression
    if (
      target.matches(".delete-compte-btn") ||
      target.closest(".delete-compte-btn")
    ) {
      const btn = target.matches(".delete-compte-btn")
        ? target
        : target.closest(".delete-compte-btn");
      const compteId = btn.getAttribute("data-id");
      confirmDelete(compteId);
    }
  });

  // Gestion du changement de bureau pour mettre à jour la liste des agents DANS LE MODAL
  const modalBureauSelect = document.getElementById("filter_bureau");
  if (modalBureauSelect) {
    modalBureauSelect.addEventListener("change", function () {
      updateModalAgentsOptions(this.value);
    });
  }
}

// Fonction pour mettre à jour les options des agents selon le bureau sélectionné DANS LE MODAL
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
export function editCompte(compteId) {
  const modal = document.getElementById("compteModal");
  if (!modal) return;
  
  document.getElementById("modalTitle").innerHTML =
    '<i class="fas fa-user-edit mr-2 text-indigo-600"></i><span>Modifier un utilisateur</span>';

  const compteIdStr = String(compteId);
  const compte = comptes.find((c) => c.id === compteIdStr);
  if (!compte) {
    alert("Utilisateur non trouvé.");
    return;
  }

  const form = document.getElementById("compteForm");
  if (!form) return;

  // Trouver l'agent associé à ce compte
  const agent = agents.find(a => a.nom_prenom === compte.nom_prenom);
  
  const fields = {
    agent_idss: compte.id,
    actions: "update",
    mot_de_passe: "", // Le mot de passe n'est pas affiché pour la sécurité
  };

  for (const [id, value] of Object.entries(fields)) {
    const field = document.getElementById(id);
    if (field) {
      field.value = value || "";
    }
  }

  // Réinitialiser le toggle du mot de passe
  const passwordInput = document.getElementById("mot_de_passe");
  const eyeIcon = document.getElementById("eyeIcon");
  if (passwordInput) {
    passwordInput.type = "password";
    passwordInput.placeholder = "Nouveau mot de passe (optionnel)";
  }
  if (eyeIcon) {
    eyeIcon.classList.remove("fa-eye-slash");
    eyeIcon.classList.add("fa-eye");
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

  showModal("compteModal");
}

// Fonction pour ouvrir le modal de suppression
export function confirmDelete(compteId) {
  const modal = document.getElementById("deleteModal");
  if (!modal) return;

  const compteIdStr = String(compteId);
  const compte = comptes.find((c) => c.id === compteIdStr);
  if (!compte) {
    alert("Utilisateur non trouvé pour suppression.");
    return;
  }

  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.href = `?page=compte_content&action=delete&id=${compteId}`;
  }

  showModal("deleteModal");
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
  loadComptesData();
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

// Configurer les filtres
function setupFilters() {
  const searchInput = document.getElementById("search");
  const roleSelect = document.getElementById("filter_role");
  const statutSelect = document.getElementById("filter_statut");
  const compteTableBody = document.querySelector("#compteTable tbody");

  if (!searchInput || !roleSelect || !statutSelect || !compteTableBody) {
    console.warn("Éléments nécessaires pour les filtres non trouvés");
    return;
  }

  // Écouteurs pour les filtres
  searchInput.addEventListener("input", filterAndDisplayComptes);
  roleSelect.addEventListener("change", filterAndDisplayComptes);
  statutSelect.addEventListener("change", filterAndDisplayComptes);

  // Affichage initial
  filterAndDisplayComptes();

  function filterAndDisplayComptes() {
    const searchQuery = searchInput.value.trim().toLowerCase();
    const roleFilter = roleSelect.value;
    const statutFilter = statutSelect.value;

    const filteredComptes = comptes.filter((compte) => {
      const nomPrenom = compte.nom_prenom ? compte.nom_prenom.toLowerCase() : "";
      const matchesSearch = nomPrenom.includes(searchQuery);
      const matchesRole = roleFilter === "" || compte.role === roleFilter;
      const matchesStatut = statutFilter === "" || compte.statut === statutFilter;

      return matchesSearch && matchesRole && matchesStatut;
    });

    // Mettre à jour le tableau
    if (filteredComptes.length === 0) {
      compteTableBody.innerHTML = `
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
      compteTableBody.innerHTML = filteredComptes
        .map((compte) => {
          // Formater la date de dernière connexion
          const connexionDate = compte.connexion ? new Date(compte.connexion).toLocaleDateString('fr-FR') : 'Jamais';
          
          // Définir les classes CSS selon le statut
          let statutClass = '';
          switch(compte.statut) {
            case 'actif':
              statutClass = 'bg-green-100 text-green-800';
              break;
            case 'inactif':
              statutClass = 'bg-red-100 text-red-800';
              break;
            case 'suspendu':
              statutClass = 'bg-yellow-100 text-yellow-800';
              break;
            default:
              statutClass = 'bg-gray-100 text-gray-800';
          }

          return `
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                <div class="flex items-center">
                  <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                    ${
                      compte.photo && compte.photo !== "NULL" && compte.photo !== ""
                        ? `<img src="${compte.photo}" alt="Photo de ${compte.nom_prenom || "Utilisateur"}" class="w-10 h-10 rounded-full object-cover" onerror="this.parentNode.innerHTML = '${getInitialsCircle(compte.nom_prenom || "").replace(/'/g, "\\'")}';"/>`
                        : getInitialsCircle(compte.nom_prenom || "")
                    }
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">${compte.nom_prenom || "Nom inconnu"}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  ${compte.role || "Non défini"}
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${compte.bureau || "Indéfini"}</td>
              <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${connexionDate}</td>
              <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statutClass}">
                  ${compte.statut || "Indéfini"}
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button data-id="${compte.id}" title="Modifier" class="edit-compte-btn text-indigo-600 hover:text-indigo-900 focus:outline-none">
                  <i class="fas fa-edit"></i>
                </button>
                <button data-id="${compte.id}" title="Supprimer" class="delete-compte-btn text-red-600 hover:text-red-900 focus:outline-none">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          `;
        })
        .join("");
    }
  }
}

// Setup toggle pour afficher/masquer mot de passe
function setupPasswordToggle() {
  const passwordInput = document.getElementById("mot_de_passe");
  const eyeIcon = document.getElementById("eyeIcon");
  
  if (!passwordInput || !eyeIcon || passwordToggleInitialized) return;

  // Supprimer les anciens écouteurs
  const newEyeIcon = eyeIcon.cloneNode(true);
  eyeIcon.parentNode.replaceChild(newEyeIcon, eyeIcon);

  // Ajouter le nouvel écouteur
  newEyeIcon.addEventListener("click", () => {
    if (passwordInput.type === "password") {
      passwordInput.type = "text";
      newEyeIcon.classList.remove("fa-eye");
      newEyeIcon.classList.add("fa-eye-slash");
    } else {
      passwordInput.type = "password";
      newEyeIcon.classList.remove("fa-eye-slash");
      newEyeIcon.classList.add("fa-eye");
    }
  });

  passwordToggleInitialized = true;
}

// GESTION DE LA SOUMISSION DU FORMULAIRE EN AJAX

function setupFormSubmission() {
  const compteForm = document.getElementById("compteForm");
  if (!compteForm) return;

  compteForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    
    // Désactiver le bouton pendant la soumission
    if (submitButton) {
      submitButton.disabled = true;
      const originalText = submitButton.innerHTML;
      submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Traitement...';
    }

    // Ajouter un header pour identifier la requête AJAX
    fetch(this.action, {
      method: "POST",
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json(); // Attendre une réponse JSON
    })
    .then((data) => {
      console.log("Réponse serveur:", data);

      if (data.success) {
        // Fermer le modal après succès
        closeModal("compteModal");

        // Rafraîchir la liste des comptes
        loadComptesData();
        
        // Rafraîchir l'affichage
        setTimeout(() => {
          refreshDisplay();
        }, 100);

        // Notification de succès
        if (data.messages && data.messages.success && data.messages.success.length > 0) {
          alert(data.messages.success.join('\n'));
        } else {
          alert("Opération réussie !");
        }
      } else {
        // Afficher les erreurs
        if (data.messages && data.messages.errors && data.messages.errors.length > 0) {
          alert("Erreurs :\n" + data.messages.errors.join('\n'));
        } else {
          alert("Une erreur s'est produite.");
        }
      }
    })
    .catch((error) => {
      console.error("Erreur lors de la soumission :", error);
      alert("Erreur lors de l'envoi du formulaire. Veuillez réessayer.");
    })
    .finally(() => {
      // Réactiver le bouton
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-save mr-2"></i> Enregistrer';
      }
    });
  });
}

// Version alternative si vous préférez ne pas utiliser AJAX
function setupFormSubmissionClassic() {
  const compteForm = document.getElementById("compteForm");
  if (!compteForm) return;

  compteForm.addEventListener("submit", function (e) {
    const submitButton = this.querySelector('button[type="submit"]');
    
    
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Traitement...';
    }

    
  });
}