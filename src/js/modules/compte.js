import { eventBus } from "../config.js";

// Liste des comptes, agents, bureaux, rôles et statuts
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
  console.log("Initialisation du module de gestion des comptes");
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
}

// Charger les données des comptes
function loadComptesData() {
  const comptesDataElement = document.getElementById("comptesData");
  if (comptesDataElement) {
    try {
      comptes = JSON.parse(comptesDataElement.textContent).map(
        (compte, index) => {
          if (!compte.id) {
            compte.id = compte.login_id || compte._id || `temp-id-${index}`;
            console.warn(
              `Compte à l'index ${index} n'a pas d'ID défini`,
              compte
            );
          }
          compte.id = String(compte.id);
          return compte;
        }
      );
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
  const rolesDataElement = document.getElementById("roleData");
  if (rolesDataElement) {
    try {
      roles = JSON.parse(rolesDataElement.textContent);
      console.log(`${roles.length} rôles chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données rôles:", e);
      roles = [];
    }
  }
}

// Charger les données des statuts
function loadStatutsData() {
  const statutsDataElement = document.getElementById("statutData");
  if (statutsDataElement) {
    try {
      statuts = JSON.parse(statutsDataElement.textContent);
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
        console.log("Messages détectés:", messages);
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
      const modal = this.closest("#compteModal, #deleteModal, #messageModal");
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

  // Fermeture des modals avec la touche Échap
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeModal("compteModal");
      closeModal("deleteModal");
      closeModal("messageModal");
    }
  });
}

// Configurer les écouteurs d'événements
function setupListeners() {
  document.body.addEventListener("click", function (e) {
    const target = e.target;

    // Bouton "Ajouter un compte"
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

  // Gestion du sélecteur de bureau pour mettre à jour les agents
  const modalBureauSelect = document.getElementById("filter_bureau");
  if (modalBureauSelect) {
    modalBureauSelect.addEventListener("change", function () {
      updateModalAgentsOptions(this.value);
    });
  }

  // Gestion de la sélection d'agent pour mettre à jour agent_idss
  const agentSelect = document.getElementById("filter_agent");
  const agentIdInput = document.getElementById("agent_idss");
  if (agentSelect && agentIdInput) {
    const newAgentSelect = agentSelect.cloneNode(true);
    agentSelect.parentNode.replaceChild(newAgentSelect, agentSelect);
    newAgentSelect.addEventListener("change", function () {
      agentIdInput.value = this.value;
      console.log("Agent sélectionné, agent_idss mis à jour:", this.value);
    });
  }

  // Soumission du formulaire via AJAX - VERSION CORRIGÉE
  const compteForm = document.getElementById("compteForm");
  if (compteForm) {
    compteForm.addEventListener("submit", function (e) {
      e.preventDefault();
      console.log("Soumission du formulaire déclenchée");

      const bureauSelect = document.getElementById("filter_bureau");
      const agentSelect = document.getElementById("filter_agent");
      const roleSelect = document.getElementById("role");
      const passwordInput = document.getElementById("mot_de_passe");
      const agentIdInput = document.getElementById("agent_idss");
      const action = document.getElementById("formAction").value;

      // Validation côté client
      let errors = [];
      if (
        !agentSelect ||
        !agentSelect.value ||
        agentSelect.value === "Aucun agent sans compte"
      ) {
        errors.push(
          "Le champ agent est requis. Veuillez sélectionner un agent valide."
        );
      }
      if (!roleSelect || !roleSelect.value) {
        errors.push("Le champ rôle est requis.");
      }
      if (action === "add" && (!passwordInput || !passwordInput.value)) {
        errors.push("Le champ mot de passe est requis pour l'ajout.");
      }

      if (errors.length > 0) {
        console.log("Erreurs de validation:", errors);
        showMessageModal("Erreur", errors, "error");
        return;
      }

      const formData = new FormData();
      formData.append("action", action);
      formData.append("agent_id", agentSelect.value);
      formData.append("role_id", roleSelect.value);
      if (passwordInput && passwordInput.value) {
        formData.append("mot_de_passe", passwordInput.value);
      }

      // Debug des données envoyées
      console.log("Données FormData:", {
        action: formData.get("action"),
        agent_id: formData.get("agent_id"),
        role_id: formData.get("role_id"),
        mot_de_passe: formData.get("mot_de_passe") ? "***" : null,
      });

      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML =
          '<i class="fas fa-spinner fa-spin mr-2"></i> Traitement...';
      }

      // Requête AJAX corrigée
      fetch("./compte_content.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        headers: {
          "X-Requested-With": "XMLHttpRequest", // Assurer que la requête est marquée comme AJAX
        },
      })
        .then((response) => {
          console.log("Statut de réponse:", response.status);
          console.log("Headers de réponse:", [...response.headers.entries()]);

          if (!response.ok) {
            throw new Error(`Erreur HTTP ! Statut : ${response.status}`);
          }

          const contentType = response.headers.get("content-type");
          console.log("Content-Type:", contentType);

          // Vérifier si c'est du JSON
          if (contentType && contentType.includes("application/json")) {
            return response.json();
          } else {
            // Si ce n'est pas du JSON, récupérer le texte pour debug
            return response.text().then((text) => {
              console.log("Réponse texte brute:", text);
              // Essayer de parser le JSON manuellement
              try {
                return JSON.parse(text);
              } catch (e) {
                throw new Error(
                  "Réponse non JSON reçue: " + text.substring(0, 200)
                );
              }
            });
          }
        })
        .then((data) => {
          console.log("Réponse serveur parsée:", data);

          if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML =
              '<i class="fas fa-save mr-2"></i> Enregistrer';
          }

          closeModal("compteModal");

          if (data.success) {
            showMessageModal(
              "Succès",
              data.messages.success || ["Opération réussie"],
              "success"
            );
            // Recharger la page pour actualiser les données
            setTimeout(() => {
              window.location.reload();
            }, 1500);
          } else {
            showMessageModal(
              "Erreur",
              data.messages.errors || ["Une erreur est survenue"],
              "error"
            );
          }
        })
        .catch((error) => {
          console.error("Erreur lors de la soumission:", error);

          if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML =
              '<i class="fas fa-save mr-2"></i> Enregistrer';
          }

          showMessageModal(
            "Erreur",
            ["Erreur lors de l'envoi du formulaire : " + error.message],
            "error"
          );
        });
    });
  }
}

// Mettre à jour les options des agents selon le bureau - VERSION CORRIGÉE
function updateModalAgentsOptions(bureauLibelle) {
  const agentSelect = document.getElementById("filter_agent");
  const agentIdInput = document.getElementById("agent_idss");
  if (!agentSelect || !agentIdInput) return;

  agentSelect.innerHTML = '<option value="">Choisir un agent</option>';
  agentIdInput.value = "";

  if (bureauLibelle) {
    const agentsDuBureau = agents.filter(
      (agent) => agent.bureau === bureauLibelle
    );

    console.log("Agents du bureau sélectionné:", agentsDuBureau);

    if (agentsDuBureau.length > 0) {
      agentsDuBureau.forEach((agent) => {
        const option = document.createElement("option");
        option.value = agent.id;
        option.textContent = agent.nom_prenom;
        agentSelect.appendChild(option);
      });
      agentSelect.disabled = false;
    } else {
      const option = document.createElement("option");
      option.value = "";
      option.textContent = "Aucun agent sans compte";
      agentSelect.appendChild(option);
      agentSelect.disabled = true;
    }
  } else {
    agentSelect.disabled = true;
  }
}

// Ouvre le modal d'ajout de compte
export function addCompte() {
  const modal = document.getElementById("compteModal");
  if (!modal) return;

  document.getElementById("modalTitle").innerHTML =
    '<i class="fas fa-user-plus mr-2 text-indigo-600"></i><span>Ajouter un nouvel utilisateur</span>';

  const form = document.getElementById("compteForm");
  if (form) {
    form.reset();
    document.getElementById("agent_idss").value = "";
    document.getElementById("formAction").value = "add";
    document.getElementById("mot_de_passe").value = "";
    document.getElementById("mot_de_passe").placeholder =
      "Entrez le mot de passe";
    document.getElementById("mot_de_passe").required = true;

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

  passwordToggleInitialized = false;
  setupPasswordToggle();
  showModal("compteModal");
}

// Ouvre le modal d'édition
export function editCompte(compteId) {
  const modal = document.getElementById("compteModal");
  if (!modal) return;

  document.getElementById("modalTitle").innerHTML =
    '<i class="fas fa-user-edit mr-2 text-indigo-600"></i><span>Modifier un utilisateur</span>';

  const compteIdStr = String(compteId);
  const compte = comptes.find((c) => c.id === compteIdStr);
  if (!compte) {
    showMessageModal("Erreur", ["Utilisateur non trouvé."], "error");
    return;
  }

  const form = document.getElementById("compteForm");
  if (!form) return;

  // Réinitialiser le formulaire
  form.reset();

  // Remplir les champs
  document.getElementById("agent_idss").value = compte.agent_id || "";
  document.getElementById("formAction").value = "update";
  document.getElementById("role").value = compte.role_id || "";
  document.getElementById("mot_de_passe").value = "";
  document.getElementById("mot_de_passe").placeholder =
    "Nouveau mot de passe (optionnel)";
  document.getElementById("mot_de_passe").required = false;

  // Gérer la sélection du bureau et de l'agent
  const bureauSelect = document.getElementById("filter_bureau");
  const agentSelect = document.getElementById("filter_agent");

  if (bureauSelect && agentSelect) {
    // Pré-remplir bureau (libele du bureau)
    bureauSelect.value = compte.bureau || "";
    bureauSelect.disabled = true; // Griser le bureau

    // Pré-remplir agent : vider les options et ajouter seulement l'agent actuel
    agentSelect.innerHTML = ""; // Vider les options
    const option = document.createElement("option");
    option.value = compte.agent_id;
    option.textContent = compte.nom_prenom || "Agent inconnu";
    agentSelect.appendChild(option);
    agentSelect.value = compte.agent_id;
    agentSelect.disabled = true; // Griser l'agent
  }

  passwordToggleInitialized = false;
  setupPasswordToggle();
  showModal("compteModal");
}

// Ouvre le modal de suppression
export function confirmDelete(compteId) {
  const modal = document.getElementById("deleteModal");
  if (!modal) return;

  const compteIdStr = String(compteId);
  const compte = comptes.find((c) => c.id === compteIdStr);
  if (!compte) {
    showMessageModal(
      "Erreur",
      ["Utilisateur non trouvé pour suppression."],
      "error"
    );
    return;
  }

  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.onclick = function (e) {
      e.preventDefault();
      performDelete(compteId);
    };
  }

  showModal("deleteModal");
}

// Exécute la suppression via AJAX - VERSION CORRIGÉE
function performDelete(compteId) {
  const formData = new FormData();
  formData.append("action", "delete");
  formData.append("id", compteId);

  console.log("Suppression du compte ID:", compteId);

  fetch("./compte_content.php", {
    method: "POST",
    body: formData,
    credentials: "same-origin",
    headers: {
      "X-Requested-With": "XMLHttpRequest", // Assurer que la requête est marquée comme AJAX
    },
  })
    .then((response) => {
      console.log("Statut de réponse suppression:", response.status);

      if (!response.ok) {
        throw new Error(`Erreur HTTP ! Statut : ${response.status}`);
      }

      const contentType = response.headers.get("content-type");
      if (contentType && contentType.includes("application/json")) {
        return response.json();
      } else {
        return response.text().then((text) => {
          console.log("Réponse texte suppression:", text);
          try {
            return JSON.parse(text);
          } catch (e) {
            throw new Error(
              "Réponse non JSON reçue: " + text.substring(0, 200)
            );
          }
        });
      }
    })
    .then((data) => {
      console.log("Réponse serveur pour suppression:", data);
      closeModal("deleteModal");

      if (data.success) {
        showMessageModal(
          "Succès",
          data.messages.success || ["Compte supprimé avec succès."],
          "success"
        );
        // Recharger la page pour actualiser les données
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        showMessageModal(
          "Erreur",
          data.messages.errors || [
            "Une erreur s'est produite lors de la suppression.",
          ],
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Erreur lors de la suppression:", error);
      showMessageModal(
        "Erreur",
        ["Erreur lors de la suppression : " + error.message],
        "error"
      );
    });
}

// Configurer le toggle du mot de passe
function setupPasswordToggle() {
  const passwordInput = document.getElementById("mot_de_passe");
  const eyeIcon = document.getElementById("eyeIcon");
  if (!passwordInput || !eyeIcon || passwordToggleInitialized) return;

  const newEyeIcon = eyeIcon.cloneNode(true);
  eyeIcon.parentNode.replaceChild(newEyeIcon, eyeIcon);

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

// Afficher un modal
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
  document.body.style.overflow = "hidden";
}

// Fermer un modal
export function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  const modalContent = document.getElementById(`${modalId}Content`);
  if (modalContent) {
    modalContent.classList.remove("scale-100", "opacity-100");
    modalContent.classList.add("scale-95", "opacity-0");
    setTimeout(() => {
      modal.classList.add("hidden");
      document.body.style.overflow = "auto";
    }, 300);
  } else {
    modal.classList.add("hidden");
    document.body.style.overflow = "auto";
  }

  eventBus.publish("modal:closed", { modalId });
}

// Afficher le modal de messages
function showMessageModal(title, messages, type) {
  const messageModal = document.getElementById("messageModal");
  const messageModalContent = document.getElementById("messageModalContent");
  if (!messageModal || !messageModalContent) return;

  const modalTitle = messageModalContent.querySelector("h3 span");
  const icon = messageModalContent.querySelector("h3 i");
  const messageContainer = messageModalContent.querySelector(".p-4.sm\\:p-6");

  if (modalTitle && icon && messageContainer) {
    modalTitle.textContent = title;
    icon.className = `fas fa-info-circle mr-2 ${
      type === "error" ? "text-red-500" : "text-green-600"
    }`;

    messageContainer.innerHTML =
      messages
        .map(
          (msg) => `
            <p class="${
              type === "error" ? "text-red-500" : "text-green-600"
            } font-semibold text-sm sm:text-base mb-2">
                ${type === "error" ? "❌" : "✅"} ${msg}
            </p>
        `
        )
        .join("") +
      `
            <div class="flex justify-end mt-4">
                <button type="button" class="close-modal px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Fermer
                </button>
            </div>
        `;
  }

  showModal("messageModal");
}

// Configurer les filtres
function setupFilters() {
  const searchInput = document.getElementById("search");
  const roleSelect = document.getElementById("filter_role");
  const statutSelect = document.getElementById("filter_statut");
  const comptesTableBody = document.querySelector("#compteTable tbody");

  if (!searchInput || !roleSelect || !statutSelect || !comptesTableBody) {
    console.warn("Éléments nécessaires pour les filtres non trouvés");
    return;
  }

  searchInput.addEventListener("input", filterAndDisplayComptes);
  roleSelect.addEventListener("change", filterAndDisplayComptes);
  statutSelect.addEventListener("change", filterAndDisplayComptes);

  function filterAndDisplayComptes() {
    const searchQuery = searchInput.value.trim().toLowerCase();
    const roleFilter = roleSelect.value;
    const statutFilter = statutSelect.value;

    const filteredComptes = comptes.filter((compte) => {
      const nomPrenom = compte.nom_prenom
        ? compte.nom_prenom.toLowerCase()
        : "";
      const matchesSearch = nomPrenom.includes(searchQuery);
      const matchesRole = roleFilter === "" || compte.role === roleFilter;
      const matchesStatut =
        statutFilter === "" || compte.statut === statutFilter;

      return matchesSearch && matchesRole && matchesStatut;
    });

    comptesTableBody.innerHTML =
      filteredComptes.length === 0
        ? `
            <tr>
                <td colspan="7" class="px-4 py-6 text-center">
                    <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl shadow-sm animate-fade-in">
                        <i class="fas fa-search text-4xl text-indigo-500 mb-4 animate-pulse"></i>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Oups, aucun compte trouvé !</h3>
                        <p class="text-sm text-gray-600">Essayez une autre recherche.</p>
                    </div>
                </td>
            </tr>
        `
        : filteredComptes
            .map((compte) => {
              const statutClass =
                compte.statut === "activé"
                  ? "bg-green-100 text-green-800"
                  : "bg-red-100 text-red-800";
              return `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                                ${
                                  compte.photo &&
                                  compte.photo !== "NULL" &&
                                  compte.photo !== ""
                                    ? `<img src="${compte.photo}" alt="${
                                        compte.nom_prenom || "Utilisateur"
                                      }" class="rounded-full object-cover" onerror="this.parentNode.innerHTML = '${getInitialsCircle(
                                        compte.nom_prenom || ""
                                      ).replace(/'/g, "\\'")}';"/>`
                                    : getInitialsCircle(compte.nom_prenom || "")
                                }
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">${
                                  compte.nom_prenom || "Nom inconnu"
                                }</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${
                          compte.role || "Non défini"
                        }</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${
                      compte.bureau || "Non défini"
                    }</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${
                      compte.connexion
                        ? new Date(compte.connexion).toLocaleString("fr-FR", {
                            day: "2-digit",
                            month: "2-digit",
                            year: "numeric",
                            hour: "2-digit",
                            minute: "2-digit",
                          })
                        : "Jamais"
                    }</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statutClass}">${
                compte.statut || "Non défini"
              }</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${
                      compte.etat || "Non défini"
                    }</td>
                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex space-x-2 justify-end">
                            <button class="edit-compte-btn text-blue-600 hover:text-blue-900 transition-colors" data-id="${
                              compte.id
                            }" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="delete-compte-btn text-red-600 hover:text-red-900 transition-colors" data-id="${
                              compte.id
                            }" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            })
            .join("");
  }
}

// Rafraîchir l'affichage
function refreshDisplay() {
  const searchInput = document.getElementById("search");
  if (searchInput) {
    searchInput.dispatchEvent(new Event("input"));
  }
}

// S'abonner aux événements externes
eventBus.subscribe("comptes:externalUpdate", (data) => {
  console.log("Mise à jour externe des comptes reçue", data);
  loadComptesData();
  loadAgentsData();
  refreshDisplay();
});
