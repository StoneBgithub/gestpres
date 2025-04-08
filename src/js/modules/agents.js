import { eventBus } from "../config.js";

// Liste des agents (sera remplie lors de l'initialisation)
let agents = [];

// Fonction d'initialisation principale
export function init() {
  console.log("Initialisation du module de gestion des agents");
  loadAgentsData();
  initModals();
  setupListeners();
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
    }
  } else {
    console.warn("Élément agentsData non trouvé");
  }
}

// Initialiser les modales
function initModals() {
  document.querySelectorAll(".close-modal").forEach((btn) => {
    btn.addEventListener("click", function () {
      // Trouver le modal parent en utilisant un sélecteur basé sur la structure
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
    if (target.matches(".add-agent-btn") || target.closest(".add-agent-btn")) {
      openAddModal();
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
    confirmDeleteBtn.addEventListener("click", function (e) {
      // La logique est gérée par l’attribut href
    });
  }
}

// Fonction pour ouvrir le modal d’ajout d’un nouvel agent
export function openAddModal() {
  console.log("Ouverture du modal pour ajouter un nouvel agent");
  const modal = document.getElementById("agentModal");
  if (!modal) {
    console.error("Modal 'agentModal' non trouvé");
    return;
  }

  const modalTitle = document.getElementById("modalTitle");
  modalTitle.innerHTML =
    '<i class="fas fa-user-plus mr-2 text-indigo-600"></i><span>Ajouter un nouvel agent</span>';

  // Réinitialiser le formulaire
  const form = document.getElementById("agentForm");
  if (form) {
    form.reset();
    document.getElementById("agent_id").value = "";
  }

  showModal("agentModal");
  eventBus.publish("agents:addStarted", {});
}

// Fonction pour ouvrir le modal d’édition
export function editAgent(agentId) {
  console.log(
    `Ouverture du modal d’édition pour l’agent avec l’ID : ${agentId}`
  );
  const modal = document.getElementById("agentModal");
  if (!modal) {
    console.error("Modal 'agentModal' non trouvé");
    return;
  }

  const modalTitle = document.getElementById("modalTitle");
  modalTitle.innerHTML =
    '<i class="fas fa-user-edit mr-2 text-indigo-600"></i><span>Modifier un agent</span>';

  const agent = agents.find((a) => a.id === agentId);
  if (agent) {
    document.getElementById("agent_id").value = agent.id;
    document.getElementById("nom").value = agent.nom;
    // Ajouter d'autres champs si présents dans le formulaire
  }

  showModal("agentModal");
  eventBus.publish("agents:editStarted", { agentId, agent });
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
