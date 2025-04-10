// modules/presence.js - Logique pour la page de gestion de présence
import { serviceIcons, utils, eventBus } from "../config.js";

// Fonction d'initialisation principale
export function init() {
  console.log("Initialisation du module de présence");
  initFilters();
  initToggleButtons();
  setupFilterActions();
  setupDependencies(); // Ajout pour gérer les dépendances
}

// Initialiser les filtres
function initFilters() {
  const toggleFiltersBtn = document.getElementById("toggle-filters-btn");
  const advancedFilters = document.getElementById("advanced-filters");
  const filterIcon = document.getElementById("filter-icon");

  // Nettoyer les gestionnaires d'événements précédents
  if (toggleFiltersBtn) {
    const newToggleBtn = toggleFiltersBtn.cloneNode(true);
    toggleFiltersBtn.parentNode.replaceChild(newToggleBtn, toggleFiltersBtn);

    // Ajouter le nouveau gestionnaire
    newToggleBtn.addEventListener("click", function (e) {
      e.preventDefault();
      if (
        advancedFilters.style.display === "none" ||
        advancedFilters.style.display === ""
      ) {
        advancedFilters.style.display = "block";
        filterIcon.setAttribute("d", "M6 12h12");
      } else {
        advancedFilters.style.display = "none";
        filterIcon.setAttribute("d", "M12 6v6m0 0v6m0-6h6m-6 0H6");
      }
    });
  }
}

// Initialiser les boutons de basculement (tous/arrivées/départs)
function initToggleButtons() {
  const toggleAllBtn = document.getElementById("toggle-all");
  const toggleArrivalsBtn = document.getElementById("toggle-arrivals");
  const toggleDeparturesBtn = document.getElementById("toggle-departures");
  const entryRows = document.querySelectorAll(".entry-row");

  if (!toggleAllBtn || !toggleArrivalsBtn || !toggleDeparturesBtn) return;

  // Réinitialiser les gestionnaires d'événements
  [toggleAllBtn, toggleArrivalsBtn, toggleDeparturesBtn].forEach((btn) => {
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
  });

  // Récupérer les nouveaux éléments
  const newToggleAllBtn = document.getElementById("toggle-all");
  const newToggleArrivalsBtn = document.getElementById("toggle-arrivals");
  const newToggleDeparturesBtn = document.getElementById("toggle-departures");

  // Ajouter les nouveaux gestionnaires
  newToggleAllBtn.addEventListener("click", function () {
    entryRows.forEach((row) => (row.style.display = "table-row"));
    updateButtonStyles(newToggleAllBtn, [
      newToggleArrivalsBtn,
      newToggleDeparturesBtn,
    ]);
  });

  newToggleArrivalsBtn.addEventListener("click", function () {
    entryRows.forEach((row) => {
      row.style.display = row.classList.contains("arrival")
        ? "table-row"
        : "none";
    });
    updateButtonStyles(newToggleArrivalsBtn, [
      newToggleAllBtn,
      newToggleDeparturesBtn,
    ]);
  });

  newToggleDeparturesBtn.addEventListener("click", function () {
    entryRows.forEach((row) => {
      row.style.display = row.classList.contains("departure")
        ? "table-row"
        : "none";
    });
    updateButtonStyles(newToggleDeparturesBtn, [
      newToggleAllBtn,
      newToggleArrivalsBtn,
    ]);
  });

  // Activer le bouton "Tous" par défaut
  newToggleAllBtn.click();
}

// Configuration des actions de filtrage avancées
function setupFilterActions() {
  const applyFiltersBtn = document.getElementById("apply-filters");
  const resetFiltersBtn = document.getElementById("reset-filters");
  const filterForm = document.getElementById("filter-form");
  const timeRangeSelect = document.getElementById("time-range");
  const customTimeDiv = document.getElementById("custom-time");

  if (!applyFiltersBtn || !resetFiltersBtn || !filterForm) return;

  // Réinitialiser les gestionnaires d'événements
  [applyFiltersBtn, resetFiltersBtn].forEach((btn) => {
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
  });

  // Récupérer les nouveaux éléments
  const newApplyFiltersBtn = document.getElementById("apply-filters");
  const newResetFiltersBtn = document.getElementById("reset-filters");

  // Gérer la plage horaire personnalisée
  if (timeRangeSelect) {
    timeRangeSelect.addEventListener("change", function () {
      customTimeDiv.style.display = this.value === "custom" ? "block" : "none";
    });
  }

  // Appliquer les filtres (soumission au serveur)
  newApplyFiltersBtn.addEventListener("click", function (e) {
    e.preventDefault();
    console.log("Application des filtres...");
    filterForm.submit(); // Soumettre le formulaire au serveur
    eventBus.publish("presence:filtered", getFilterValues());
  });

  // Réinitialiser les filtres
  newResetFiltersBtn.addEventListener("click", function () {
    console.log("Réinitialisation des filtres...");
    filterForm.reset();
    if (document.getElementById("date"))
      document.getElementById("date").value = "";
    if (timeRangeSelect) timeRangeSelect.value = "all";
    if (customTimeDiv) customTimeDiv.style.display = "none";
    if (document.getElementById("status"))
      document.getElementById("status").value = "all";
    if (document.getElementById("service"))
      document.getElementById("service").value = "all";
    if (document.getElementById("bureau")) {
      document.getElementById("bureau").value = "all";
      document.getElementById("bureau").disabled = true;
    }
    if (document.getElementById("employee")) {
      document.getElementById("employee").value = "all";
      document.getElementById("employee").disabled = true;
    }
    filterForm.submit(); // Soumettre pour recharger avec les valeurs par défaut
    eventBus.publish("presence:filtersReset", {});
  });
}

// Gestion des dépendances entre Service, Bureau et Employé
function setupDependencies() {
  const serviceSelect = document.getElementById("service");
  const bureauSelect = document.getElementById("bureau");
  const employeeSelect = document.getElementById("employee");

  if (!serviceSelect || !bureauSelect || !employeeSelect) return;

  // Désactiver par défaut
  bureauSelect.disabled = true;
  employeeSelect.disabled = true;

  // Mise à jour des bureaux en fonction du service
  serviceSelect.addEventListener("change", function () {
    const selectedService = this.value;
    bureauSelect.innerHTML = '<option value="all">Tous les bureaux</option>';
    employeeSelect.innerHTML = '<option value="all">Tous les employés</option>';
    bureauSelect.disabled = selectedService === "all";
    employeeSelect.disabled = true;

    if (selectedService !== "all") {
      fetch(`fetch_bureaux.php?service=${encodeURIComponent(selectedService)}`)
        .then((response) => response.json())
        .then((data) => {
          data.forEach((bureau) => {
            const option = document.createElement("option");
            option.value = bureau.libele;
            option.textContent = bureau.libele;
            bureauSelect.appendChild(option);
          });
        })
        .catch((error) => console.error("Erreur lors du chargement des bureaux :", error));
    }
  });

  // Mise à jour des employés en fonction du bureau
  bureauSelect.addEventListener("change", function () {
    const selectedBureau = this.value;
    employeeSelect.innerHTML = '<option value="all">Tous les employés</option>';
    employeeSelect.disabled = selectedBureau === "all";

    if (selectedBureau !== "all") {
      fetch(`fetch_agents.php?bureau=${encodeURIComponent(selectedBureau)}`)
        .then((response) => response.json())
        .then((data) => {
          data.forEach((agent) => {
            const option = document.createElement("option");
            option.value = agent.nom_prenom;
            option.textContent = agent.nom_prenom;
            employeeSelect.appendChild(option);
          });
        })
        .catch((error) => console.error("Erreur lors du chargement des employés :", error));
    }
  });
}

// Fonction pour récupérer les valeurs des filtres
function getFilterValues() {
  return {
    date: document.getElementById("date")?.value,
    timeRange: document.getElementById("time-range")?.value,
    status: document.getElementById("status")?.value,
    service: document.getElementById("service")?.value,
    bureau: document.getElementById("bureau")?.value,
    employee: document.getElementById("employee")?.value,
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

// Exporter d'autres fonctions spécifiques au module si nécessaire
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
  // Logique pour mettre à jour les données de présence
});