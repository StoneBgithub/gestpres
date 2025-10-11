import { eventBus } from "../config.js";

// Liste des comptes, chefs de service, bureaux, rôles et statuts
let comptes = [];
let chefsService = [];
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

// Fonction pour obtenir la classe CSS selon le statut
function getStatutClass(statut) {
  // Normaliser le statut pour éviter les problèmes de casse
  const statutNormalise = statut ? statut.toLowerCase() : "";

  switch (statutNormalise) {
    case "activé":
    case "active": // Au cas où
      return "bg-green-100 text-green-800";
    case "désactivé":
    case "desactive": // Au cas où
    case "désactive": // Au cas où
      return "bg-red-100 text-red-800";
    default:
      return "bg-gray-100 text-gray-800";
  }
}

// Fonction pour normaliser les valeurs de statut
function normalizeStatut(statut) {
  if (!statut) return "activé";

  const statutStr = String(statut).toLowerCase().trim();

  // Gérer les différentes variations possibles
  if (
    statutStr === "désactivé" ||
    statutStr === "desactive" ||
    statutStr === "inactif"
  ) {
    return "désactivé";
  }

  return "activé"; // Valeur par défaut
}

// Fonction pour obtenir l'icône selon le statut
function getStatutIcon(statut) {
  // Normaliser le statut pour éviter les problèmes de casse
  const statutNormalise = statut ? statut.toLowerCase() : "";

  switch (statutNormalise) {
    case "activé":
    case "active":
      return "";
    case "désactivé":
    case "desactive":
    case "désactive":
      return "";
    default:
      return "❓";
  }
}

// Fonction pour obtenir le texte du statut
function getStatutText(statut) {
  // Normaliser le statut pour éviter les problèmes de casse
  const statutNormalise = statut ? statut.toLowerCase() : "";

  switch (statutNormalise) {
    case "activé":
    case "active":
      return "Activé";
    case "désactivé":
    case "desactive":
    case "désactive":
      return "Désactivé";
    default:
      return statut || "Non défini";
  }
}

// Fonction d'initialisation principale
export function init() {
  console.log("Initialisation du module de gestion des comptes");

  // Debug
  console.log(
    "Élément chefsServiceData:",
    document.getElementById("chefsServiceData")
  );

  loadComptesData();
  loadChefsServiceData();

  // Debug après chargement
  console.log("Chefs de service chargés:", chefsService);

  loadBureauxData();
  loadRolesData();
  loadStatutsData();
  initModals();
  setupListeners();
  setupFilters();
  checkAndShowMessageModal();
  setupPasswordToggle();
  populateChefsServiceSelect();
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

// Charger les données des chefs de service
function loadChefsServiceData() {
  const chefsServiceDataElement = document.getElementById("chefsServiceData");
  if (chefsServiceDataElement) {
    try {
      chefsService = JSON.parse(chefsServiceDataElement.textContent);
      console.log(`${chefsService.length} chefs de service chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données chefs service:", e);
      chefsService = [];
    }
  } else {
    console.warn("Élément chefsServiceData non trouvé");
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

// Remplir le sélecteur des chefs de service
function populateChefsServiceSelect() {
  const chefServiceSelect = document.getElementById("filter_chef_service");
  if (!chefServiceSelect) {
    console.error("Élément filter_chef_service non trouvé");
    return;
  }

  chefServiceSelect.innerHTML =
    '<option value="">Choisir un chef de service</option>';

  if (chefsService.length === 0) {
    console.warn("Aucun chef de service disponible");
    const option = document.createElement("option");
    option.value = "";
    option.textContent = "Aucun chef de service disponible";
    chefServiceSelect.appendChild(option);
    return;
  }

  console.log(
    `Remplissage du sélecteur avec ${chefsService.length} chefs de service`
  );

  chefsService.forEach((chef) => {
    const option = document.createElement("option");
    option.value = chef.id;

    // Format: "Nom Prénom - Service"
    let displayText = `${chef.nom} ${chef.prenom}`;
    if (chef.service) {
      displayText += ` - ${chef.service}`;
    }

    option.textContent = displayText;
    option.dataset.service = chef.service || "";
    option.dataset.serviceId = chef.service_id || "";
    chefServiceSelect.appendChild(option);
  });
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

  // Gestion du sélecteur de chef de service pour déterminer automatiquement le rôle
  const chefServiceSelect = document.getElementById("filter_chef_service");
  const roleIdInput = document.getElementById("auto_role_id");
  const agentIdInput = document.getElementById("agent_idss");

  if (chefServiceSelect && roleIdInput && agentIdInput) {
    chefServiceSelect.addEventListener("change", function () {
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption && selectedOption.dataset.service) {
        const serviceName = selectedOption.dataset.service;
        let roleId;

        // Déterminer le rôle automatiquement selon le service
        if (serviceName.includes("Secrétariat")) {
          roleId = 7; // Rôle secrétaire
        } else if (serviceName.includes("Direction Générale")) {
          roleId = 6; // Rôle directeur
        } else {
          roleId = 5; // Rôle chef de service
        }

        roleIdInput.value = roleId;
        agentIdInput.value = this.value;
        console.log(
          `Chef de service sélectionné: ${this.value}, Service: ${serviceName}, Rôle auto-assigné: ${roleId}`
        );
      }
    });
  }

  // Soumission du formulaire via AJAX
  const compteForm = document.getElementById("compteForm");
  if (compteForm) {
    compteForm.addEventListener("submit", function (e) {
      e.preventDefault();
      console.log("Soumission du formulaire déclenchée");

      const chefServiceSelect = document.getElementById("filter_chef_service");
      const passwordInput = document.getElementById("mot_de_passe");
      const roleIdInput = document.getElementById("auto_role_id");
      const agentIdInput = document.getElementById("agent_idss");
      const action = document.getElementById("formAction").value;

      // Validation côté client
      let errors = [];
      if (!chefServiceSelect || !chefServiceSelect.value) {
        errors.push("Le champ chef de service est requis.");
      }
      if (action === "add" && (!passwordInput || !passwordInput.value)) {
        errors.push("Le champ mot de passe est requis pour l'ajout.");
      }
      if (!roleIdInput.value) {
        errors.push("Erreur : Aucun rôle n'a été déterminé automatiquement.");
      }

      if (errors.length > 0) {
        console.log("Erreurs de validation:", errors);
        showMessageModal("Erreur", errors, "error");
        return;
      }

      const formData = new FormData();
      formData.append("action", action);
      formData.append("agent_id", chefServiceSelect.value);
      formData.append("role_id", roleIdInput.value);

      const statutSelect = document.getElementById("statut_compte");
      if (statutSelect) {
        const selectedStatut = normalizeStatut(statutSelect.value);
        formData.append("statut", selectedStatut);
        console.log("Statut sélectionné:", selectedStatut); // Pour debug
      } else {
        formData.append("statut", "activé"); // Valeur par défaut
        console.warn(
          "Sélecteur de statut non trouvé, utilisation de la valeur par défaut"
        );
      }

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

      // Requête AJAX
      fetch("./compte_content.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
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
    document.getElementById("auto_role_id").value = "";
    document.getElementById("mot_de_passe").value = "";
    document.getElementById("mot_de_passe").placeholder =
      "Entrez le mot de passe";
    document.getElementById("mot_de_passe").required = true;

    const statutSelect = document.getElementById("statut_compte");
    if (statutSelect) {
      statutSelect.value = "activé";
    }

    const chefServiceSelect = document.getElementById("filter_chef_service");
    if (chefServiceSelect) {
      chefServiceSelect.disabled = false;
      chefServiceSelect.value = "";
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
  document.getElementById("auto_role_id").value = compte.role_id || "";
  document.getElementById("mot_de_passe").value = "";
  document.getElementById("mot_de_passe").placeholder =
    "Nouveau mot de passe (optionnel)";
  document.getElementById("mot_de_passe").required = false;

  const statutSelect = document.getElementById("statut_compte");
  if (statutSelect) {
    statutSelect.value = compte.statut || "activé";
    console.log("Statut chargé pour édition:", compte.statut);
  }

  // Pré-sélectionner le chef de service
  const chefServiceSelect = document.getElementById("filter_chef_service");
  if (chefServiceSelect) {
    // Vider le select
    chefServiceSelect.innerHTML =
      '<option value="">Choisir un chef de service</option>';

    // Créer une option avec le nom et service du chef
    const option = document.createElement("option");
    option.value = compte.agent_id;

    // Afficher "Nom Prénom - Service" au lieu de l'ID
    const displayText = `${compte.nom} ${compte.prenom} - ${
      compte.service || "Service non défini"
    }`;
    option.textContent = displayText;

    chefServiceSelect.appendChild(option);
    chefServiceSelect.value = compte.agent_id;
    chefServiceSelect.disabled = true; // Grisé en mode édition

    console.log("Chef de service affiché:", displayText);
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

// Exécute la suppression via AJAX
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
      "X-Requested-With": "XMLHttpRequest",
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

      // CORRECTION : Comparer directement avec la valeur de la base de données
      const matchesStatut =
        statutFilter === "" || compte.statut === statutFilter;

      return matchesSearch && matchesRole && matchesStatut;
    });

    comptesTableBody.innerHTML =
      filteredComptes.length === 0
        ? `
          <tr>
              <td colspan="8" class="px-4 py-6 text-center">
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
              // APPEL DES FONCTIONS UTILITAIRES
              const statutClass = getStatutClass(compte.statut);
              const statutIcon = getStatutIcon(compte.statut);
              const statutText = getStatutText(compte.statut);

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
                    compte.service || "Non défini"
                  }</td>
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
                      <!-- CORRECTION : Utilisation complète des fonctions utilitaires -->
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statutClass}">
                          ${statutIcon} ${statutText}
                      </span>
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
  loadChefsServiceData();
  refreshDisplay();
});
