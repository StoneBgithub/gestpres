import { eventBus } from "../config.js";

// Liste des services, bureaux et agents
let services = [];
let bureaux = [];
let agents = [];

// Fonction d'initialisation principale
export function init() {
  console.log("Initialisation du module de paramétrage");
  loadServicesData();
  loadBureauxData();
  loadAgentsData();
  initModals();
  setupListeners();
  setupFilters();
  checkAndShowMessageModal();
}

// Charger les données des services depuis l'élément script
function loadServicesData() {
  const servicesDataElement = document.getElementById("servicesData");
  if (servicesDataElement) {
    try {
      services = JSON.parse(servicesDataElement.textContent).map((service, index) => {
        if (!service.id) {
          service.id = service.service_id || service._id || null;
          console.warn(`Service à l'index ${index} n'a pas d'ID défini`, service);
        }
        service.id = String(service.id);
        return service;
      });
      console.log(`${services.length} services chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données services:", e);
      services = [];
      alert("Erreur lors du chargement des données des services.");
    }
  } else {
    console.warn("Élément servicesData non trouvé");
  }
}

// Charger les données des bureaux depuis l'élément script
function loadBureauxData() {
  const bureauxDataElement = document.getElementById("bureauxData");
  if (bureauxDataElement) {
    try {
      bureaux = JSON.parse(bureauxDataElement.textContent).map((bureau, index) => {
        if (!bureau.id) {
          bureau.id = bureau.bureau_id || bureau._id || null;
          console.warn(`Bureau à l'index ${index} n'a pas d'ID défini`, bureau);
        }
        bureau.id = String(bureau.id);
        return bureau;
      });
      console.log(`${bureaux.length} bureaux chargés`);
    } catch (e) {
      console.error("Erreur lors du parsing des données bureaux:", e);
      bureaux = [];
      alert("Erreur lors du chargement des données des bureaux.");
    }
  } else {
    console.warn("Élément bureauxData non trouvé");
  }
}

// Charger les données des agents
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
        "#serviceModal, #bureauModal, #deleteServiceModal, #deleteBureauModal, #messageModal"
      );
      if (modal) {
        closeModal(modal.id);
      }
    });
  });

  document
    .querySelectorAll("#serviceModal, #bureauModal, #deleteServiceModal, #deleteBureauModal, #messageModal")
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

    // Bouton "Ajouter un service"
    if (target.matches(".add-service-btn")) {
      addService();
    }

    // Bouton "Ajouter un bureau"
    if (target.matches(".add-bureau-btn")) {
      addBureau();
    }

    // Boutons d'édition service
    if (
      target.matches(".edit-service-btn") ||
      target.closest(".edit-service-btn")
    ) {
      const btn = target.matches(".edit-service-btn")
        ? target
        : target.closest(".edit-service-btn");
      const serviceId = btn.getAttribute("data-id");
      editService(serviceId);
    }

    // Boutons d'édition bureau
    if (
      target.matches(".edit-bureau-btn") ||
      target.closest(".edit-bureau-btn")
    ) {
      const btn = target.matches(".edit-bureau-btn")
        ? target
        : target.closest(".edit-bureau-btn");
      const bureauId = btn.getAttribute("data-id");
      editBureau(bureauId);
    }

    // Boutons de suppression service
    if (
      target.matches(".delete-service-btn") ||
      target.closest(".delete-service-btn")
    ) {
      const btn = target.matches(".delete-service-btn")
        ? target
        : target.closest(".delete-service-btn");
      const serviceId = btn.getAttribute("data-id");
      confirmDeleteService(serviceId);
    }

    // Boutons de suppression bureau
    if (
      target.matches(".delete-bureau-btn") ||
      target.closest(".delete-bureau-btn")
    ) {
      const btn = target.matches(".delete-bureau-btn")
        ? target
        : target.closest(".delete-bureau-btn");
      const bureauId = btn.getAttribute("data-id");
      confirmDeleteBureau(bureauId);
    }
  });
}

// Fonction pour ouvrir le modal d'ajout de service
export function addService() {
  const modal = document.getElementById("serviceModal");
  if (!modal) return;
  
  document.getElementById("serviceModalTitle").innerHTML =
    '<i class="fas fa-building mr-2 text-indigo-600"></i><span>Ajouter un nouveau service</span>';
  
  const form = document.getElementById("serviceForm");
  if (form) {
    form.reset();
    document.getElementById("service_id").value = "";
    document.getElementById("service_action").value = "add";
  }
  
  showModal("serviceModal");
}

// Fonction pour ouvrir le modal d'ajout de bureau
export function addBureau() {
  const modal = document.getElementById("bureauModal");
  if (!modal) return;
  
  document.getElementById("bureauModalTitle").innerHTML =
    '<i class="fas fa-door-open mr-2 text-indigo-600"></i><span>Ajouter un nouveau bureau</span>';
  
  const form = document.getElementById("bureauForm");
  if (form) {
    form.reset();
    document.getElementById("bureau_id").value = "";
    document.getElementById("bureau_action").value = "add";
  }
  
  showModal("bureauModal");
}

// Fonction pour ouvrir le modal d'édition de service
export function editService(serviceId) {
  const modal = document.getElementById("serviceModal");
  if (!modal) return;
  
  document.getElementById("serviceModalTitle").innerHTML =
    '<i class="fas fa-edit mr-2 text-indigo-600"></i><span>Modifier le service</span>';

  const serviceIdStr = String(serviceId);
  const service = services.find((s) => s.id === serviceIdStr);
  if (!service) {
    alert("Service non trouvé.");
    return;
  }

  const form = document.getElementById("serviceForm");
  if (!form) return;

  const fields = {
    service_id: service.id,
    service_action: "update",
    libele: service.libele || "",
    chef_service_id: service.chef_service_id || "",
  };

  for (const [id, value] of Object.entries(fields)) {
    const field = document.getElementById(id);
    if (field) {
      field.value = value || "";
    }
  }

  showModal("serviceModal");
}

// Fonction pour ouvrir le modal d'édition de bureau
export function editBureau(bureauId) {
  const modal = document.getElementById("bureauModal");
  if (!modal) return;
  
  document.getElementById("bureauModalTitle").innerHTML =
    '<i class="fas fa-edit mr-2 text-indigo-600"></i><span>Modifier le bureau</span>';

  const bureauIdStr = String(bureauId);
  const bureau = bureaux.find((b) => b.id === bureauIdStr);
  if (!bureau) {
    alert("Bureau non trouvé.");
    return;
  }

  const form = document.getElementById("bureauForm");
  if (!form) return;

  const fields = {
    bureau_id: bureau.id,
    bureau_action: "update",
    libele: bureau.libele || "",
    service_id: bureau.service_id || "",
  };

  for (const [id, value] of Object.entries(fields)) {
    const field = document.getElementById(id);
    if (field) {
      field.value = value || "";
    }
  }

  showModal("bureauModal");
}

// Fonction pour ouvrir le modal de suppression de service
export function confirmDeleteService(serviceId) {
  const modal = document.getElementById("deleteServiceModal");
  if (!modal) return;

  const serviceIdStr = String(serviceId);
  const service = services.find((s) => s.id === serviceIdStr);
  if (!service) {
    alert("Service non trouvé pour suppression.");
    return;
  }

  // Mettre à jour le nom du service dans le modal
  const serviceNameElement = document.getElementById("deleteServiceName");
  if (serviceNameElement) {
    serviceNameElement.textContent = service.libele;
  }

  const confirmDeleteBtn = document.getElementById("confirmDeleteServiceBtn");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.href = `?page=parametrage&action=delete_service&id=${serviceId}`;
  }

  showModal("deleteServiceModal");
  eventBus.publish("services:deleteRequested", { serviceId });
}

// Fonction pour ouvrir le modal de suppression de bureau
export function confirmDeleteBureau(bureauId) {
  const modal = document.getElementById("deleteBureauModal");
  if (!modal) return;

  const bureauIdStr = String(bureauId);
  const bureau = bureaux.find((b) => b.id === bureauIdStr);
  if (!bureau) {
    alert("Bureau non trouvé pour suppression.");
    return;
  }

  // Mettre à jour le nom du bureau dans le modal
  const bureauNameElement = document.getElementById("deleteBureauName");
  if (bureauNameElement) {
    bureauNameElement.textContent = bureau.libele;
  }

  const confirmDeleteBtn = document.getElementById("confirmDeleteBureauBtn");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.href = `?page=parametrage&action=delete_bureau&id=${bureauId}`;
  }

  showModal("deleteBureauModal");
  eventBus.publish("bureaux:deleteRequested", { bureauId });
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

// Configurer les filtres
function setupFilters() {
  const searchServiceInput = document.getElementById("searchService");
  const searchBureauInput = document.getElementById("searchBureau");
  const serviceFilterSelect = document.getElementById("filterServiceForBureau");
  
  // Filtres pour les services
  if (searchServiceInput) {
    searchServiceInput.addEventListener("input", filterAndDisplayServices);
  }
  
  // Filtres pour les bureaux
  if (searchBureauInput) {
    searchBureauInput.addEventListener("input", filterAndDisplayBureaux);
  }
  
  if (serviceFilterSelect) {
    serviceFilterSelect.addEventListener("change", filterAndDisplayBureaux);
  }

  // Affichage initial
  filterAndDisplayServices();
  filterAndDisplayBureaux();
}

// Filtrer et afficher les services
function filterAndDisplayServices() {
  const searchInput = document.getElementById("searchService");
  const servicesContainer = document.getElementById("servicesContainer");
  const servicesTableBody = document.querySelector("#servicesTable tbody");
  
  if (!searchInput || !servicesContainer || !servicesTableBody) {
    console.warn("Éléments nécessaires pour les filtres services non trouvés");
    return;
  }

  const searchQuery = searchInput.value.trim().toLowerCase();
  
  const filteredServices = services.filter((service) => {
    const libele = service.libele ? service.libele.toLowerCase() : "";
    const chefNom = service.chef_nom ? service.chef_nom.toLowerCase() : "";
    return libele.includes(searchQuery) || chefNom.includes(searchQuery);
  });

  // Affichage en cartes (mobile)
  servicesContainer.innerHTML = filteredServices
    .map(
      (service) => `
      <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
        <div class="p-4">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-building text-indigo-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-lg text-gray-800">${service.libele || "Service sans nom"}</h3>
                <div class="flex items-center text-gray-600 text-sm">
                  <i class="fas fa-user-tie mr-1"></i>
                  <span>${service.chef_nom || "Aucun chef assigné"}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="flex gap-2 pt-3 border-t border-gray-100">
            <button class="edit-service-btn px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" data-id="${service.id}">
              <i class="fas fa-edit mr-1"></i> Modifier
            </button>
            <button class="delete-service-btn px-3 py-1 text-sm bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" data-id="${service.id}">
              <i class="fas fa-trash mr-1"></i> Supprimer
            </button>
          </div>
        </div>
      </div>
    `
    )
    .join("");

  // Affichage en tableau (desktop)
  if (filteredServices.length === 0) {
    servicesTableBody.innerHTML = `
      <tr>
        <td colspan="3" class="px-4 py-6 text-center">
          <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl shadow-sm">
            <i class="fas fa-search text-4xl text-indigo-500 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucun service trouvé</h3>
            <p class="text-sm text-gray-600">Essayez une autre recherche.</p>
          </div>
        </td>
      </tr>
    `;
  } else {
    servicesTableBody.innerHTML = filteredServices
      .map(
        (service) => `
        <tr class="hover:bg-gray-50 transition-colors">
          <td class="px-4 py-3 text-sm font-medium text-gray-900">${service.libele || "Service sans nom"}</td>
          <td class="px-4 py-3 text-sm text-gray-500">
            ${service.chef_nom 
              ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                   <i class="fas fa-user mr-1"></i> ${service.chef_nom}
                 </span>`
              : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                   <i class="fas fa-exclamation-triangle mr-1"></i> Non assigné
                 </span>`
            }
          </td>
          <td class="px-4 py-3 text-right text-sm font-medium">
            <div class="flex space-x-2 justify-end">
              <button class="edit-service-btn text-blue-600 hover:text-blue-900 transition-colors" data-id="${service.id}" title="Modifier">
                <i class="fas fa-edit"></i>
              </button>
              <button class="delete-service-btn text-red-600 hover:text-red-900 transition-colors" data-id="${service.id}" title="Supprimer">
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

// Filtrer et afficher les bureaux
function filterAndDisplayBureaux() {
  const searchInput = document.getElementById("searchBureau");
  const serviceFilter = document.getElementById("filterServiceForBureau");
  const bureauxContainer = document.getElementById("bureauxContainer");
  const bureauxTableBody = document.querySelector("#bureauxTable tbody");
  
  if (!searchInput || !bureauxContainer || !bureauxTableBody) {
    console.warn("Éléments nécessaires pour les filtres bureaux non trouvés");
    return;
  }

  const searchQuery = searchInput.value.trim().toLowerCase();
  const serviceFilterValue = serviceFilter ? serviceFilter.value : "";
  
  const filteredBureaux = bureaux.filter((bureau) => {
    const libele = bureau.libele ? bureau.libele.toLowerCase() : "";
    const serviceNom = bureau.service_nom ? bureau.service_nom.toLowerCase() : "";
    const matchesSearch = libele.includes(searchQuery) || serviceNom.includes(searchQuery);
    const matchesService = serviceFilterValue === "" || bureau.service_nom === serviceFilterValue;
    
    return matchesSearch && matchesService;
  });

  // Affichage en cartes (mobile)
  bureauxContainer.innerHTML = filteredBureaux
    .map(
      (bureau) => `
      <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
        <div class="p-4">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-door-open text-emerald-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-lg text-gray-800">${bureau.libele || "Bureau sans nom"}</h3>
                <div class="flex items-center text-gray-600 text-sm">
                  <i class="fas fa-building mr-1"></i>
                  <span>${bureau.service_nom || "Service non défini"}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="flex gap-2 pt-3 border-t border-gray-100">
            <button class="edit-bureau-btn px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" data-id="${bureau.id}">
              <i class="fas fa-edit mr-1"></i> Modifier
            </button>
            <button class="delete-bureau-btn px-3 py-1 text-sm bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" data-id="${bureau.id}">
              <i class="fas fa-trash mr-1"></i> Supprimer
            </button>
          </div>
        </div>
      </div>
    `
    )
    .join("");

  // Affichage en tableau (desktop)
  if (filteredBureaux.length === 0) {
    bureauxTableBody.innerHTML = `
      <tr>
        <td colspan="3" class="px-4 py-6 text-center">
          <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl shadow-sm">
            <i class="fas fa-search text-4xl text-emerald-500 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucun bureau trouvé</h3>
            <p class="text-sm text-gray-600">Essayez une autre recherche ou un autre filtre.</p>
          </div>
        </td>
      </tr>
    `;
  } else {
    bureauxTableBody.innerHTML = filteredBureaux
      .map(
        (bureau) => `
        <tr class="hover:bg-gray-50 transition-colors">
          <td class="px-4 py-3 text-sm font-medium text-gray-900">${bureau.libele || "Bureau sans nom"}</td>
          <td class="px-4 py-3 text-sm text-gray-500">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
              <i class="fas fa-building mr-1"></i> ${bureau.service_nom || "Service non défini"}
            </span>
          </td>
          <td class="px-4 py-3 text-right text-sm font-medium">
            <div class="flex space-x-2 justify-end">
              <button class="edit-bureau-btn text-blue-600 hover:text-blue-900 transition-colors" data-id="${bureau.id}" title="Modifier">
                <i class="fas fa-edit"></i>
              </button>
              <button class="delete-bureau-btn text-red-600 hover:text-red-900 transition-colors" data-id="${bureau.id}" title="Supprimer">
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

// S'abonner aux événements externes
eventBus.subscribe("services:externalUpdate", (data) => {
  console.log("Mise à jour externe des services reçue", data);
  loadServicesData();
  filterAndDisplayServices();
});

eventBus.subscribe("bureaux:externalUpdate", (data) => {
  console.log("Mise à jour externe des bureaux reçue", data);
  loadBureauxData();
  filterAndDisplayBureaux();
});

// Fonctions utilitaires
export function refreshServices() {
  loadServicesData();
  filterAndDisplayServices();
}

export function refreshBureaux() {
  loadBureauxData();
  filterAndDisplayBureaux();
}

export function refreshAll() {
  loadServicesData();
  loadBureauxData();
  loadAgentsData();
  filterAndDisplayServices();
  filterAndDisplayBureaux();
}