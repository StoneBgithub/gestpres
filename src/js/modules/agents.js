import { eventBus } from "../config.js";

// Liste des agents (sera remplie lors de l'initialisation)
let agents = [];

let bureaux = [];

// Fonction d'initialisation principale
export function init() {
  console.log("Initialisation du module de gestion des agents");
  loadAgentsData();
  loadBureauxData();
  initModals();
  setupListeners();
  setupFilters();
}

// Charger les données des agents depuis l’élément script
function loadAgentsData() {
  const agentsDataElement = document.getElementById("agentsData");
  if (agentsDataElement) {
    try {
      agents = JSON.parse(agentsDataElement.textContent);
      console.log(`${agents.length} agents chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données agents:", e);
      agents = [];
      alert("Erreur lors du chargement des données des agents.");
    }
  } else {
    console.warn("Élément agentsData non trouvé");
    alert("Les données des agents n'ont pas pu être chargées.");
  }
}

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
  } else {
    console.warn("Élément bureauxData non trouvé");
  }
}

// Initialiser les modales
function initModals() {
  // Trouver le modal parent en utilisant un sélecteur basé sur la structure
  document.querySelectorAll(".close-modal").forEach((btn) => {
    btn.addEventListener("click", function () {
      const modal = this.closest("#agentModal, #deleteModal, #qrModal");
      if (modal) {
        const modalId = modal.id;
        closeModal(modalId);
      } else {
        console.error("Aucun modal parent trouvé pour ce bouton de fermeture");
      }
    });
  });

  // Gérer les clics en dehors des modales
  document
    .querySelectorAll("#agentModal, #deleteModal, #qrModal")
    .forEach((modal) => {
      modal.addEventListener("click", function (e) {
        if (e.target === this) {
          closeModal(this.id);
        }
      });
    });
}

// Configurer les écouteurs d’événements
function setupListeners() {
  document.body.addEventListener("click", function (e) {
    const target = e.target;

    // Bouton "Ajouter un agent"

    if (target.matches(".add-agent-btn")) {
      addAgent();
    }

    // Boutons d’édition
    if (
      target.matches(".edit-agent-btn") ||
      target.closest(".edit-agent-btn")
    ) {
      const btn = target.matches(".edit-agent-btn")
        ? target
        : target.closest(".edit-agent-btn");
      const agentId = parseInt(btn.getAttribute("data-id"), 10);
      editAgent(agentId);
    }

    // Boutons de génération QR
    if (target.matches(".qr-agent-btn") || target.closest(".qr-agent-btn")) {
      const btn = target.matches(".qr-agent-btn")
        ? target
        : target.closest(".qr-agent-btn");
      const agentId = parseInt(btn.getAttribute("data-id"), 10);
      generateQR(agentId);
    }

    // Boutons de suppression
    if (
      target.matches(".delete-agent-btn") ||
      target.closest(".delete-agent-btn")
    ) {
      const btn = target.matches(".delete-agent-btn")
        ? target
        : target.closest(".delete-agent-btn");
      const agentId = parseInt(btn.getAttribute("data-id"), 10);
      confirmDelete(agentId);
    }
  });

  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", function (e) {});
  }
}

export function addAgent() {
  console.log("Ouverture du modal pour ajouter un agent");
  const modal = document.getElementById("agentModal");
  if (!modal) {
    console.error("Modal 'agentModal' non trouvé");
    return;
  }
  document.getElementById("modalTitle").innerHTML =
    '<i class="fas fa-user-plus mr-2 text-indigo-600"></i><span>Ajouter un nouvel agent</span>';
  const form = document.getElementById("agentForm");
  if (form) {
    form.reset();
    document.getElementById("agent_id").value = "";
    document.getElementById("action").value = "add";
  }
  showModal("agentModal");
}

// Fonction pour ouvrir le modal d’ajout d’un nouvel agent
export function editAgent(agentId) {
  console.log(
    `Ouverture du modal d’édition pour l’agent avec l’ID : ${agentId}`
  );
  const modal = document.getElementById("agentModal");
  if (!modal) {
    console.error("Modal 'agentModal' non trouvé");
    return;
  }

  document.getElementById("modalTitle").innerHTML =
    '<i class="fas fa-user-edit mr-2 text-indigo-600"></i><span>Modifier un agent</span>';

  const agent = agents.find((a) => a.id === agentId);
  if (!agent) {
    console.error(`Agent avec l’ID ${agentId} non trouvé`);
    return;
  }
  console.log("Données de l’agent :", agent); // Ajoutez ceci

  const form = document.getElementById("agentForm");
  if (!form) {
    console.error("Formulaire 'agentForm' non trouvé");
    return;
  }

  const fields = {
    agent_id: agent.id,
    action: "update",
    matricule: agent.matricule,
    nom: agent.nom,
    prenoms: agent.prenom,
    email: agent.email,
    telephone: agent.telephone,
    bureau_id: agent.bureau_id,
  };

  for (const [id, value] of Object.entries(fields)) {
    const field = document.getElementById(id);
    if (field) field.value = value || "";
    else console.error(`Champ #${id} non trouvé`);
  }

  showModal("agentModal");
}

// Fonction pour ouvrir le modal QR
export function generateQR(agentId) {
  console.log(`Ouverture du modal QR pour l’agent avec l’ID : ${agentId}`);
  const modal = document.getElementById("qrModal");
  if (!modal) {
    console.error("Modal 'qrModal' non trouvé");
    return;
  }

  const agent = agents.find((a) => a.id === agentId);
  if (agent) {
    document.getElementById(
      "qrAgentName"
    ).textContent = `${agent.prenom} ${agent.nom}`;
    document.getElementById(
      "qrAgentInfo"
    ).textContent = `${agent.service} - ${agent.bureau}`;
  }

  showModal("qrModal");
  eventBus.publish("agents:qrGenerated", { agentId, agent });
}

// Fonction pour ouvrir le modal de suppression
export function confirmDelete(agentId) {
  console.log(
    `Ouverture du modal de suppression pour l’agent avec l’ID : ${agentId}`
  );
  const modal = document.getElementById("deleteModal");
  if (!modal) {
    console.error("Modal 'deleteModal' non trouvé");
    return;
  }

  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  if (!confirmDeleteBtn) {
    console.error("Élément 'confirmDeleteBtn' non trouvé dans le modal");
    return;
  }

  // Mettre à jour le href du lien avec l'ID de l'agent
  confirmDeleteBtn.href = `?page=agents_content&action=delete&id=${agentId}`;

  showModal("deleteModal");
  eventBus.publish("agents:deleteRequested", { agentId });
}

// Afficher un modal avec animation
function showModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  modal.classList.remove("hidden");
  const modalContent =
    document.getElementById(`${modalId}Content`) ||
    modal.querySelector(".modal-content");

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

  const modalContent =
    document.getElementById(`${modalId}Content`) ||
    modal.querySelector(".modal-content");

  if (modalContent) {
    modalContent.classList.remove("scale-100", "opacity-100");
    modalContent.classList.add("scale-95", "opacity-0");
    setTimeout(() => modal.classList.add("hidden"), 300);
  } else {
    modal.classList.add("hidden");
  }

  eventBus.publish("modal:closed", { modalId });
}

// S’abonner aux événements pertinents
eventBus.subscribe("agents:externalUpdate", (data) => {
  console.log("Mise à jour externe des agents reçue", data);
});

// js/agents.js

function setupFilters() {
  const searchInput = document.getElementById("search");
  const serviceSelect = document.getElementById("filter_service");
  const bureauSelect = document.getElementById("filter_bureau");
  const agentsCardsContainer = document.getElementById("agentsCards");
  const agentsTableBody = document.querySelector("#agentsTable tbody");

  if (
    !searchInput ||
    !serviceSelect ||
    !bureauSelect ||
    !agentsCardsContainer ||
    !agentsTableBody
  ) {
    console.warn("Éléments nécessaires pour les filtres non trouvés");
    return;
  }

  // Activer/désactiver le champ bureau en fonction du service
  serviceSelect.addEventListener("change", function () {
    const selectedService = this.value;
    bureauSelect.disabled = this.value === "";
    updateBureauxOptions(selectedService);
    filterAndDisplayAgents();
  });

  function updateBureauxOptions(selectedService) {
    bureauSelect.innerHTML = '<option value="">Tous les bureaux</option>';
    if (selectedService) {
      const filteredBureaux = bureaux.filter(
        (bureau) => bureau.service_libele === selectedService
      );
      filteredBureaux.forEach((bureau) => {
        const option = document.createElement("option");
        option.value = bureau.libele;
        option.textContent = bureau.libele;
        bureauSelect.appendChild(option);
      });
    }
  }

  // Écouteurs pour les autres filtres
  searchInput.addEventListener("input", filterAndDisplayAgents);
  bureauSelect.addEventListener("change", filterAndDisplayAgents);

  function filterAndDisplayAgents() {
    const searchQuery = searchInput.value.trim().toLowerCase();
    const serviceFilter = serviceSelect.value;
    const bureauFilter = bureauSelect.value;

    const filteredAgents = agents.filter((agent) => {
      const nomPrenom = agent.nom_prenom.toLowerCase(); // Utilise nom_prenom directement depuis le JSON
      const phone = agent.telephone.toLowerCase();
      const matchesSearch =
        nomPrenom.includes(searchQuery) || phone.includes(searchQuery); // Recherche sur nom_prenom ou téléphone
      const matchesService =
        serviceFilter === "" || agent.libele_service === serviceFilter;
      const matchesBureau =
        bureauFilter === "" || agent.libele_bureau === bureauFilter;

      return matchesSearch && matchesService && matchesBureau;
    });

    // Mise à jour de la vue carte
    agentsCardsContainer.innerHTML = filteredAgents
      .map(
        (agent) => `
        <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
          <div class="p-4">
            <div class="flex items-center mb-4">
              <div class="h-14 w-14 rounded-full flex items-center justify-center mr-3 border-2 shadow-sm">
                <img src="${agent.photo}" alt="Photo de profil" class="rounded-full">
              </div>
              <div>
                <h3 class="font-semibold text-lg text-gray-800">${agent.nom_prenom}</h3>
                <div class="flex items-center text-gray-600 text-sm">
                  <i class="fas fa-briefcase mr-1"></i>
                  <span>${agent.libele_service}</span>
                </div>
              </div>
            </div>
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-gray-600">
                <i class="fas fa-door-open w-5 text-center mr-2"></i>
                <span>${agent.libele_bureau}</span>
              </div>
              <div class="flex items-center text-gray-600">
                <i class="fas fa-phone-alt w-5 text-center mr-2"></i>
                <span>${agent.telephone}</span>
              </div>
            </div>
            <div class="flex justify-between pt-3 border-t border-gray-100">
              <button class="edit-agent-btn px-3 py-1 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" data-id="${agent.id}">
                <i class="fas fa-edit mr-1"></i> Modifier
              </button>
              <button class="qr-agent-btn px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors" data-id="${agent.id}">
                <i class="fas fa-qrcode mr-1"></i> QR Code
              </button>
              <button class="delete-agent-btn px-3 py-1 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" data-id="${agent.id}">
                <i class="fas fa-trash mr-1"></i> Supprimer
              </button>
            </div>
          </div>
        </div>
      `
      )
      .join("");

    // Mise à jour de la vue tableau
    agentsTableBody.innerHTML = filteredAgents
      .map(
        (agent) => `
        <tr class="hover:bg-gray-50 transition-colors">
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                <img src="${agent.photo}" alt="Photo de profil" class="rounded-full">
              </div>
              <div>
                <div class="text-sm font-medium text-gray-900">${agent.nom_prenom}</div>
              </div>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              <span class="text-sm text-gray-900">${agent.libele_service}</span>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${agent.libele_bureau}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${agent.telephone}</td>
          <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <div class="flex space-x-2 justify-end">
              <button class="edit-agent-btn text-blue-600 hover:text-blue-900 transition-colors" data-id="${agent.id}" title="Modifier">
                <i class="fas fa-edit"></i>
              </button>
              <button class="qr-agent-btn text-green-600 hover:text-green-900 transition-colors" data-id="${agent.id}" title="Générer QR Code">
                <i class="fas fa-qrcode"></i>
              </button>
              <button class="delete-agent-btn text-red-600 hover:text-red-900 transition-colors" data-id="${agent.id}" title="Supprimer">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `
      )
      .join("");
  }
}
