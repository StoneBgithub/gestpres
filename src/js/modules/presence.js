// modules/presence.js - Logique pour la page de gestion de présence
import { serviceIcons, utils, eventBus } from "../config.js";

// Fonction d'initialisation principale
export function init() {
  console.log("Initialisation du module de présence");
  initFilters();
  initToggleButtons();
  setupFilterActions();
  setupDependencies();
  updateFilterIndicator(); // Initialiser l'indicateur
}

// Initialiser les filtres
function initFilters() {
  const toggleFiltersBtn = document.getElementById("toggle-filters-btn");
  const advancedFilters = document.getElementById("advanced-filters");
  const filterIcon = document.getElementById("filter-icon");

  if (toggleFiltersBtn && advancedFilters) {
    const newToggleBtn = toggleFiltersBtn.cloneNode(true);
    toggleFiltersBtn.parentNode.replaceChild(newToggleBtn, toggleFiltersBtn);

    newToggleBtn.addEventListener("click", function (e) {
      e.preventDefault();
      if (
        advancedFilters.style.display === "none" ||
        advancedFilters.style.display === ""
      ) {
        advancedFilters.style.display = "block";
        if (filterIcon) {
          filterIcon.setAttribute("d", "M6 12h12");
        }
      } else {
        advancedFilters.style.display = "none";
        if (filterIcon) {
          filterIcon.setAttribute("d", "M12 6v6m0 0v6m0-6h6m-6 0H6");
        }
      }
    });
  } else {
    console.warn("Éléments de filtres avancés non trouvés :", {
      toggleFiltersBtn,
      advancedFilters,
      filterIcon,
    });
  }
}

// Initialiser les boutons de basculement (tous/arrivées/départs)
function initToggleButtons() {
  const toggleAllBtn = document.getElementById("toggle-all");
  const toggleArrivalsBtn = document.getElementById("toggle-arrivals");
  const toggleDeparturesBtn = document.getElementById("toggle-departures");

  if (!toggleAllBtn || !toggleArrivalsBtn || !toggleDeparturesBtn) {
    console.warn("Boutons de basculement non trouvés");
    return;
  }

  // Réinitialiser les gestionnaires d'événements
  [toggleAllBtn, toggleArrivalsBtn, toggleDeparturesBtn].forEach((btn) => {
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
  });

  const newToggleAllBtn = document.getElementById("toggle-all");
  const newToggleArrivalsBtn = document.getElementById("toggle-arrivals");
  const newToggleDeparturesBtn = document.getElementById("toggle-departures");

  // Appliquer le filtre de type
  function applyTypeFilter(type) {
    const typeFilter = document.getElementById("type-filter");
    if (typeFilter) {
      typeFilter.value = type;
    }

    const filterValues = getFilterValues();
    const params = new URLSearchParams({
      service: filterValues.service,
      bureau: filterValues.bureau,
      employee: filterValues.employee,
      date: filterValues.date,
      time_range: filterValues.timeRange,
      custom_start: filterValues.customStart,
      custom_end: filterValues.customEnd,
      status: filterValues.status,
      type: type,
    });

    console.log("Envoi de la requête avec params :", params.toString());

    fetch(`fetch_presence_data.php?${params.toString()}`)
      .then((response) => {
        if (!response.ok) throw new Error(`Erreur réseau: ${response.status}`);
        return response.json();
      })
      .then((data) => {
        console.log("Résultats reçus :", data);
        updatePresenceList(data);
        updateFilterIndicator();
      })
      .catch((error) => {
        console.error("Erreur lors de la récupération des présences :", error);
        updatePresenceList([]);
      });
  }

  newToggleAllBtn.addEventListener("click", function () {
    applyTypeFilter("all");
    updateButtonStyles(newToggleAllBtn, [
      newToggleArrivalsBtn,
      newToggleDeparturesBtn,
    ]);
  });

  newToggleArrivalsBtn.addEventListener("click", function () {
    applyTypeFilter("arrivée");
    updateButtonStyles(newToggleArrivalsBtn, [
      newToggleAllBtn,
      newToggleDeparturesBtn,
    ]);
  });

  newToggleDeparturesBtn.addEventListener("click", function () {
    applyTypeFilter("depart");
    updateButtonStyles(newToggleDeparturesBtn, [
      newToggleAllBtn,
      newToggleArrivalsBtn,
    ]);
  });

  // Simuler un clic sur "Tous" au chargement initial
  newToggleAllBtn.click();
}

// Configuration des actions de filtrage avancées
function setupFilterActions() {
  const applyFiltersBtn = document.getElementById("apply-filters");
  const resetFiltersBtn = document.getElementById("reset-filters");
  const filterForm = document.getElementById("filter-form");
  const timeRangeSelect = document.getElementById("time-range");
  const customTimeDiv = document.getElementById("custom-time");

  if (!applyFiltersBtn || !resetFiltersBtn || !filterForm) {
    console.warn("Éléments de formulaire non trouvés :", {
      applyFiltersBtn,
      resetFiltersBtn,
      filterForm,
    });
    return;
  }

  // Réinitialiser les gestionnaires d'événements
  [applyFiltersBtn, resetFiltersBtn].forEach((btn) => {
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
  });

  const newApplyFiltersBtn = document.getElementById("apply-filters");
  const newResetFiltersBtn = document.getElementById("reset-filters");

  // Gérer la plage horaire personnalisée
  if (timeRangeSelect && customTimeDiv) {
    timeRangeSelect.addEventListener("change", function () {
      customTimeDiv.style.display = this.value === "custom" ? "block" : "none";
      updateFilterIndicator();
    });
  }

  // Appliquer les filtres
  newApplyFiltersBtn.addEventListener("click", function (e) {
    e.preventDefault();
    console.log("Application des filtres...");

    // Récupérer les valeurs des filtres
    const filterValues = getFilterValues();
    console.log("Valeurs des filtres brutes :", filterValues);

    // Construire les paramètres
    const params = new URLSearchParams({
      service: filterValues.service,
      bureau: filterValues.bureau,
      employee: filterValues.employee,
      date: filterValues.date,
      time_range: filterValues.timeRange,
      custom_start: filterValues.customStart,
      custom_end: filterValues.customEnd,
      status: filterValues.status,
      type: filterValues.type,
    });

    console.log("Envoi de la requête avec params :", params.toString());

    // Envoyer la requête AJAX
    fetch(`fetch_presence_data.php?${params.toString()}`)
      .then((response) => {
        console.log("Statut de fetch_presence_data :", response.status);
        if (!response.ok) throw new Error(`Erreur réseau: ${response.status}`);
        return response.json();
      })
      .then((data) => {
        console.log("Résultats reçus :", data);
        updatePresenceList(data);
        eventBus.publish("presence:filtered", filterValues);
        updateFilterIndicator();
      })
      .catch((error) => {
        console.error("Erreur lors de la récupération des présences :", error);
        updatePresenceList([]);
      });
  });

  // Réinitialiser les filtres
  newResetFiltersBtn.addEventListener("click", function (e) {
    e.preventDefault();
    console.log("Réinitialisation des filtres...");

    // Conserver l'état du filtre de type
    const typeFilter = document.getElementById("type-filter");
    const currentType = typeFilter ? typeFilter.value : "all";

    // Réinitialiser le formulaire
    filterForm.reset();

    // Réinitialiser explicitement tous les champs sauf le type
    const dateInput = document.getElementById("date");
    if (dateInput) {
      dateInput.value = "";
    }
    if (timeRangeSelect) {
      timeRangeSelect.value = "all";
    }
    if (customTimeDiv) {
      customTimeDiv.style.display = "none";
    }
    if (document.getElementById("custom_start")) {
      document.getElementById("custom_start").value = "";
    }
    if (document.getElementById("custom_end")) {
      document.getElementById("custom_end").value = "";
    }
    if (document.getElementById("status")) {
      document.getElementById("status").value = "all";
    }
    if (document.getElementById("service")) {
      document.getElementById("service").value = "all";
    }
    if (document.getElementById("bureau")) {
      document.getElementById("bureau").value = "all";
      document.getElementById("bureau").disabled = true;
    }
    if (document.getElementById("employee")) {
      document.getElementById("employee").value = "all";
      document.getElementById("employee").disabled = true;
    }

    // Restaurer le filtre de type
    if (typeFilter) {
      typeFilter.value = currentType;
    }

    // Mettre à jour l'indicateur
    updateFilterIndicator();

    // Rafraîchir la liste avec le filtre de type conservé
    console.log("Rafraîchissement de la liste après réinitialisation...");
    const filterValues = getFilterValues();
    const params = new URLSearchParams({
      service: filterValues.service,
      bureau: filterValues.bureau,
      employee: filterValues.employee,
      date: filterValues.date,
      time_range: filterValues.timeRange,
      custom_start: filterValues.customStart,
      custom_end: filterValues.customEnd,
      status: filterValues.status,
      type: currentType,
    });

    fetch(`fetch_presence_data.php?${params.toString()}`)
      .then((response) => {
        if (!response.ok) throw new Error(`Erreur réseau: ${response.status}`);
        return response.json();
      })
      .then((data) => {
        console.log("Résultats après réinitialisation :", data);
        updatePresenceList(data);
        eventBus.publish("presence:filtersReset", { type: currentType });
      })
      .catch((error) => {
        console.error("Erreur lors de la réinitialisation :", error);
        updatePresenceList([]);
      });
  });
}

// Gestion des dépendances entre Service, Bureau et Employé
function setupDependencies() {
  const serviceSelect = document.getElementById("service");
  const bureauSelect = document.getElementById("bureau");
  const employeeSelect = document.getElementById("employee");

  if (!serviceSelect || !bureauSelect || !employeeSelect) {
    console.warn("Un ou plusieurs sélecteurs non trouvés :", {
      serviceSelect,
      bureauSelect,
      employeeSelect,
    });
    return;
  }

  // Désactiver bureau et employé par défaut
  bureauSelect.disabled = true;
  employeeSelect.disabled = true;

  // Mappage libele → id pour les services
  let serviceIdMap = {};

  // Charger les services
  console.log("Chargement initial des services...");
  fetch("fetch_services.php")
    .then((response) => {
      console.log("Statut de fetch_services :", response.status);
      if (!response.ok) throw new Error(`Erreur réseau: ${response.status}`);
      return response.json();
    })
    .then((services) => {
      console.log("Services reçus :", services);
      services.forEach((service) => {
        serviceIdMap[service.libele] = service.id;
      });
      eventBus.publish("presence:servicesLoaded", serviceIdMap);
      console.log("Options du sélecteur service :");
      Array.from(serviceSelect.options).forEach((option) => {
        console.log(
          `  value="${option.value}", text="${
            option.textContent
          }", mapped_id="${serviceIdMap[option.value] || "non mappé"}"`
        );
      });
    })
    .catch((error) => {
      console.error("Erreur lors du chargement des services :", error);
      bureauSelect.innerHTML =
        '<option value="all">Erreur: Impossible de charger les services</option>';
    });

  // Gérer la sélection du service
  serviceSelect.addEventListener("change", function () {
    const serviceLibele = this.value;
    console.log("Service sélectionné, libellé =", serviceLibele);

    // Réinitialiser les menus déroulants dépendants
    bureauSelect.innerHTML = '<option value="all">Tous les bureaux</option>';
    employeeSelect.innerHTML = '<option value="all">Tous les employés</option>';
    bureauSelect.disabled = serviceLibele === "all";
    employeeSelect.disabled = true;

    if (serviceLibele !== "all") {
      const serviceId = serviceIdMap[serviceLibele];
      if (!serviceId) {
        console.warn("ID du service non trouvé pour :", serviceLibele);
        bureauSelect.innerHTML =
          '<option value="all">Service non reconnu</option>';
        return;
      }

      console.log("Chargement des bureaux pour service_id =", serviceId);
      fetch(`fetch_bureaux.php?service_id=${encodeURIComponent(serviceId)}`)
        .then((response) => {
          console.log("Statut de fetch_bureaux :", response.status);
          if (!response.ok)
            throw new Error(`Erreur réseau: ${response.status}`);
          return response.json();
        })
        .then((data) => {
          console.log("Bureaux reçus :", data);
          if (data.length === 0) {
            bureauSelect.innerHTML =
              '<option value="all">Aucun bureau disponible</option>';
            console.warn("Aucun bureau trouvé pour service_id =", serviceId);
          } else {
            data.forEach((bureau) => {
              const option = document.createElement("option");
              option.value = bureau.id;
              option.textContent = bureau.libele;
              bureauSelect.appendChild(option);
            });
          }
        })
        .catch((error) => {
          console.error("Erreur lors du chargement des bureaux :", error);
          bureauSelect.innerHTML =
            '<option value="all">Erreur de chargement</option>';
        });
    }
    updateFilterIndicator();
  });

  // Gérer la sélection du bureau
  bureauSelect.addEventListener("change", function () {
    const bureauId = this.value;
    console.log("Bureau sélectionné, ID =", bureauId);

    employeeSelect.innerHTML = '<option value="all">Tous les employés</option>';
    employeeSelect.disabled = bureauId === "all";

    if (bureauId !== "all") {
      console.log("Chargement des employés pour bureau_id =", bureauId);
      fetch(`fetch_agents.php?bureau_id=${encodeURIComponent(bureauId)}`)
        .then((response) => {
          console.log("Statut de fetch_agents :", response.status);
          if (!response.ok)
            throw new Error(`Erreur réseau: ${response.status}`);
          return response.json();
        })
        .then((data) => {
          console.log("Employés reçus :", data);
          if (data.length === 0) {
            employeeSelect.innerHTML =
              '<option value="all">Aucun employé disponible</option>';
          } else {
            data.forEach((agent) => {
              const option = document.createElement("option");
              option.value = agent.id;
              option.textContent = `${agent.prenom} ${agent.nom}`;
              employeeSelect.appendChild(option);
            });
          }
        })
        .catch((error) => {
          console.error("Erreur lors du chargement des employés :", error);
          employeeSelect.innerHTML =
            '<option value="all">Erreur de chargement</option>';
        });
    }
    updateFilterIndicator();
  });

  // Mettre à jour l'indicateur lors du changement d'employé
  employeeSelect.addEventListener("change", function () {
    console.log("Employé sélectionné, ID =", this.value);
    updateFilterIndicator();
  });
}

// Mettre à jour la liste des présences
function updatePresenceList(data) {
  let tbody = document.querySelector("#presence-table tbody");
  if (!tbody) {
    tbody = document.querySelector(".presence-table tbody");
  }
  if (!tbody) {
    tbody = document.querySelector("table tbody");
  }
  if (!tbody) {
    console.error(
      "Tableau des présences non trouvé. Vérifiez presence_content.php"
    );
    return;
  }

  // Vider le tableau
  tbody.innerHTML = "";

  if (data.length === 0) {
    tbody.innerHTML = `
       
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
    return;
  }

  // Remplir le tableau avec le style de la liste initiale
  data.forEach((presence) => {
    const row = document.createElement("tr");
    row.classList.add("hover:bg-gray-50", "text-sm", "entry-row");
    row.classList.add(presence.type === "arrivée" ? "arrival" : "departure");

    // Photo
    let photoCell = "";
    if (presence.photo && presence.photo !== "NULL") {
      photoCell = `
        <img src="${presence.photo}" 
             alt="Photo de ${presence.nom_prenom}"
             class="w-10 h-10 rounded-full object-cover"
             loading="lazy">
      `;
    } else {
      const initials = presence.nom_prenom
        .split(" ")
        .map((n) => n.charAt(0).toUpperCase())
        .join("")
        .slice(0, 2);
      photoCell = `
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
          <span class="text-blue-600 font-medium">${initials}</span>
        </div>
      `;
    }

    // Statut
    let statusCell = "";
    if (presence.type === "arrivée") {
      const isLate = presence.heure > "09:00";
      statusCell = `
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
          isLate
            ? "bg-yellow-100 text-yellow-800"
            : "bg-green-100 text-green-800"
        }">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
          </svg>
          ${isLate ? "Arrivée (Retard)" : "Arrivée"}
        </span>
      `;
    } else {
      const isEarly = presence.heure < "17:00";
      statusCell = `
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
          isEarly ? "bg-yellow-100 text-yellow-800" : "bg-red-100 text-red-800"
        }">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          ${isEarly ? "Départ anticipé" : "Départ"}
        </span>
      `;
    }

    row.innerHTML = `
      <td class="px-6 py-4">${photoCell}</td>
      <td class="px-6 py-4 font-medium">${presence.nom_prenom}</td>
      <td class="px-6 py-4">${presence.service}</td>
      <td class="px-6 py-4">${presence.bureau}</td>
      <td class="px-6 py-4">${presence.date}</td>
      <td class="px-6 py-4">${presence.heure}</td>
      <td class="px-6 py-4">${statusCell}</td>
    `;
    tbody.appendChild(row);
  });

  console.log("Liste des présences mise à jour avec", data.length, "entrées");
}

// Mettre à jour l'indicateur de filtres actifs
function updateFilterIndicator() {
  const filterValues = getFilterValues();
  const isFiltered =
    filterValues.date ||
    filterValues.timeRange !== "all" ||
    filterValues.status !== "all" ||
    filterValues.service !== "all" ||
    filterValues.bureau !== "all" ||
    filterValues.employee !== "all" ||
    filterValues.customStart ||
    filterValues.customEnd ||
    filterValues.type !== "all";

  const filterIndicator = document.getElementById("filter-indicator");
  if (filterIndicator) {
    filterIndicator.classList.toggle("opacity-100", isFiltered);
    filterIndicator.classList.toggle("opacity-0", !isFiltered);
    filterIndicator.classList.toggle("scale-100", isFiltered);
    filterIndicator.classList.toggle("scale-95", !isFiltered);
    filterIndicator.classList.toggle("pointer-events-none", !isFiltered);
  }
}

// Fonction pour récupérer les valeurs des filtres
function getFilterValues() {
  return {
    date: document.getElementById("date")?.value || "",
    timeRange: document.getElementById("time-range")?.value || "all",
    status: document.getElementById("status")?.value || "all",
    service: document.getElementById("service")?.value || "all",
    bureau: document.getElementById("bureau")?.value || "all",
    employee: document.getElementById("employee")?.value || "all",
    customStart: document.getElementById("custom_start")?.value || "",
    customEnd: document.getElementById("custom_end")?.value || "",
    type: document.getElementById("type-filter")?.value || "all",
  };
}

// Fonction pour mettre à jour les styles des boutons
function updateButtonStyles(activeBtn, inactiveBtns) {
  activeBtn.classList.add("bg-blue-600", "text-white");
  activeBtn.classList.remove("bg-gray-100", "text-gray-700");

  inactiveBtns.forEach((btn) => {
    btn.classList.add("bg-gray-100", "text-gray-700");
    btn.classList.remove("bg-blue-600", "text-white");
  });
}

// Exporter d'autres fonctions spécifiques au module
export function getPresenceStats() {
  const entryRows = document.querySelectorAll(".entry-row");
  const arrivals = document.querySelectorAll(".entry-row.arrival");
  const departures = document.querySelectorAll(".entry-row.departure");

  return {
    total: entryRows.length,
    arrivals: arrivals.length,
    departures: departures.length,
  };
}

// S'abonner aux événements pertinents
eventBus.subscribe("presence:externalUpdate", (data) => {
  console.log("Mise à jour externe des présences reçue", data);
});