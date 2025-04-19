import { eventBus } from "../config.js";

// Liste des agents et bureaux
let agents = [];
let bureaux = [];

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
  console.log("Initialisation du module de gestion des agents");
  loadAgentsData();
  loadBureauxData();
  initModals();
  setupListeners();
  setupFilters();
  checkAndShowMessageModal();
}

// Charger les données des agents depuis l’élément script
function loadAgentsData() {
  const agentsDataElement = document.getElementById("agentsData");
  if (agentsDataElement) {
    try {
      agents = JSON.parse(agentsDataElement.textContent).map((agent, index) => {
        if (!agent.id) {
          agent.id = agent.agent_id || agent._id || null;
          console.warn(`Agent à l'index ${index} n'a pas d'ID défini`, agent);
        }
        agent.id = String(agent.id);
        return agent;
      });
      console.log(`${agents.length} agents chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données agents:", e);
      agents = [];
      alert("Erreur lors du chargement des données des agents.");
    }
  } else {
    console.warn("Élément agentsData non trouvé");
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
        "#agentModal, #deleteModal, #qrModal, #messageModal"
      );
      if (modal) {
        closeModal(modal.id);
      }
    });
  });

  document
    .querySelectorAll("#agentModal, #deleteModal, #qrModal, #messageModal")
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
      const agentId = btn.getAttribute("data-id");
      editAgent(agentId);
    }

    // Boutons de génération QR
    if (target.matches(".qr-agent-btn") || target.closest(".qr-agent-btn")) {
      const btn = target.matches(".qr-agent-btn")
        ? target
        : target.closest(".qr-agent-btn");
      const agentId = btn.getAttribute("data-id");
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
      const agentId = btn.getAttribute("data-id");
      confirmDelete(agentId);
    }
  });

  // Gestion du bouton de téléchargement du QR code
  const downloadQRBtn = document.getElementById("downloadQRBtn");
  if (downloadQRBtn) {
    downloadQRBtn.addEventListener("click", function () {
      const qrCanvas = document
        .getElementById("qrCodeContainer")
        .querySelector("canvas");
      if (qrCanvas) {
        const qrAgentName = document.getElementById("qrAgentName").textContent;
        const link = document.createElement("a");
        link.href = qrCanvas.toDataURL("image/png");
        link.download = `qrcode_${qrAgentName.replace(/\s+/g, "_")}.png`;
        link.click();
      } else {
        alert("Le QR code n'a pas été généré correctement.");
      }
    });
  }
}

// Fonction pour ouvrir le modal d’ajout d’agent
export function addAgent() {
  const modal = document.getElementById("agentModal");
  if (!modal) return;
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

// Fonction pour ouvrir le modal d’édition
export function editAgent(agentId) {
  const modal = document.getElementById("agentModal");
  if (!modal) return;
  document.getElementById("modalTitle").innerHTML =
    '<i class="fas fa-user-edit mr-2 text-indigo-600"></i><span>Modifier un agent</span>';

  const agentIdStr = String(agentId);
  const agent = agents.find((a) => a.id === agentIdStr);
  if (!agent) {
    alert("Agent non trouvé.");
    return;
  }

  const form = document.getElementById("agentForm");
  if (!form) return;

  const fields = {
    agent_id: agent.id,
    action: "update",
    matricule: agent.matricule || "",
    nom: agent.nom || "",
    prenoms: agent.prenom || "",
    email: agent.email || "",
    telephone: agent.telephone || "",
    bureau_id: agent.bureau_id || "",
  };

  for (const [id, value] of Object.entries(fields)) {
    const field = document.getElementById(id);
    if (field) {
      field.value = value || "";
    }
  }

  showModal("agentModal");
}

// Fonction pour générer et afficher le QR code
export function generateQR(agentId) {
  console.log(`Génération du QR pour l’agent ID : ${agentId}`);
  const modal = document.getElementById("qrModal");
  if (!modal) {
    console.error("Modal 'qrModal' non trouvé");
    return;
  }

  const agentIdStr = String(agentId);
  const agent = agents.find((a) => a.id === agentIdStr);
  if (!agent) {
    console.error(`Agent ID ${agentId} non trouvé`);
    alert("Agent non trouvé pour QR.");
    return;
  }

  // Mettre à jour les informations de l'agent
  document.getElementById(
    "qrAgentName"
  ).textContent = `${agent.prenom} ${agent.nom}`;
  document.getElementById(
    "qrAgentInfo"
  ).textContent = `${agent.libele_service} - ${agent.libele_bureau}`;

  // Générer le QR code avec le matricule
  const qrContainer = document.getElementById("qrCodeContainer");
  qrContainer.innerHTML = ""; // Vider le conteneur
  new QRCode(qrContainer, {
    text: agent.matricule, // Utiliser le matricule comme valeur du QR code
    width: 200,
    height: 200,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H,
  });

  showModal("qrModal");
  eventBus.publish("agents:qrGenerated", { agentId, agent });
}

// Fonction pour ouvrir le modal de suppression
export function confirmDelete(agentId) {
  const modal = document.getElementById("deleteModal");
  if (!modal) return;

  const agentIdStr = String(agentId);
  const agent = agents.find((a) => a.id === agentIdStr);
  if (!agent) {
    alert("Agent non trouvé pour suppression.");
    return;
  }

  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.href = `?page=agents_content&action=delete&id=${agentId}`;
  }

  showModal("deleteModal");
  eventBus.publish("agents:deleteRequested", { agentId });
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

// S’abonner aux événements externes
eventBus.subscribe("agents:externalUpdate", (data) => {
  console.log("Mise à jour externe des agents reçue", data);
});

// Configurer les filtres
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

  searchInput.addEventListener("input", filterAndDisplayAgents);
  bureauSelect.addEventListener("change", filterAndDisplayAgents);

  function filterAndDisplayAgents() {
    const searchQuery = searchInput.value.trim().toLowerCase();
    const serviceFilter = serviceSelect.value;
    const bureauFilter = bureauSelect.value;

    const filteredAgents = agents.filter((agent) => {
      const nomPrenom = agent.nom_prenom ? agent.nom_prenom.toLowerCase() : "";
      const phone = agent.telephone ? agent.telephone.toLowerCase() : "";
      const matchesSearch =
        nomPrenom.includes(searchQuery) || phone.includes(searchQuery);
      const matchesService =
        serviceFilter === "" || agent.libele_service === serviceFilter;
      const matchesBureau =
        bureauFilter === "" || agent.libele_bureau === bureauFilter;

      return matchesSearch && matchesService && matchesBureau;
    });

    agentsCardsContainer.innerHTML = filteredAgents
      .map(
        (agent) => `
        <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
          <div class="p-4">
            <div class="flex items-center mb-4">
              <div class="h-12 w-12 rounded-full flex items-center justify-center mr-3 border-2 shadow-sm">
                ${
                  agent.photo && agent.photo !== "NULL"
                    ? `<img src="${agent.photo}" alt="Photo de ${
                        agent.nom_prenom || "Agent"
                      }" class="rounded-full object-cover" onerror="this.parentNode.innerHTML = getInitialsCircle('${
                        agent.nom_prenom || ""
                      }')">`
                    : getInitialsCircle(agent.nom_prenom || "")
                }
              </div>
              <div>
                <h3 class="font-semibold text-base sm:text-lg text-gray-800">${
                  agent.nom_prenom || "Nom inconnu"
                }</h3>
                <div class="flex items-center text-gray-600 text-xs sm:text-sm">
                  <i class="fas fa-briefcase mr-1"></i>
                  <span>${agent.libele_service || "Non défini"}</span>
                </div>
              </div>
            </div>
            <div class="space-y-2 mb-4 text-xs sm:text-sm">
              <div class="flex items-center text-gray-600">
                <i class="fas fa-door-open w-4 text-center mr-2"></i>
                <span>${agent.libele_bureau || "Non défini"}</span>
              </div>
              <div class="flex items-center text-gray-600">
                <i class="fas fa-phone-alt w-4 text-center mr-2"></i>
                <span>${agent.telephone || "Non défini"}</span>
              </div>
            </div>
            <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
              <button class="edit-agent-btn px-2 py-1 text-xs sm:text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" data-id="${
                agent.id
              }">
                <i class="fas fa-edit mr-1"></i> Modifier
              </button>
              <button class="qr-agent-btn px-2 py-1 text-xs sm:text-sm bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors" data-id="${
                agent.id
              }">
                <i class="fas fa-qrcode mr-1"></i> QR Code
              </button>
              <button class="delete-agent-btn px-2 py-1 text-xs sm:text-sm bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" data-id="${
                agent.id
              }">
                <i class="fas fa-trash mr-1"></i> Supprimer
              </button>
            </div>
          </div>
        </div>
      `
      )
      .join("");
    agentsCardsContainer.classList.remove(
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

    if (filteredAgents.length === 0) {
      agentsTableBody.innerHTML = `
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
      agentsTableBody.innerHTML = filteredAgents
        .map(
          (agent) => `
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 whitespace-nowrap">
              <div class="flex items-center">
                <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                  ${
                    agent.photo && agent.photo !== "NULL"
                      ? `<img src="${agent.photo}" alt="Photo de ${
                          agent.nom_prenom || "Agent"
                        }" class="rounded-full object-cover" onerror="this.parentNode.innerHTML = getInitialsCircle('${
                          agent.nom_prenom || ""
                        }')">`
                      : getInitialsCircle(agent.nom_prenom || "")
                  }
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-900">${
                    agent.nom_prenom || "Nom inconnu"
                  }</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${
              agent.libele_service || "Non défini"
            }</td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${
              agent.libele_bureau || "Non défini"
            }</td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${
              agent.telephone || "Non défini"
            }</td>
            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex space-x-2 justify-end">
                <button class="edit-agent-btn text-blue-600 hover:text-blue-900 transition-colors" data-id="${
                  agent.id
                }" title="Modifier">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="qr-agent-btn text-green-600 hover:text-green-900 transition-colors" data-id="${
                  agent.id
                }" title="Générer QR Code">
                  <i class="fas fa-qrcode"></i>
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
