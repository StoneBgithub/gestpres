// modules/presence.js - Logique pour la page de gestion de présence
import { serviceIcons, utils, eventBus } from "../config.js";

// Fonction d'initialisation principale
export function init() {
  console.log("Initialisation du module de présence");
  initFilters();
  initToggleButtons();
  setupFilterActions();
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

  if (!applyFiltersBtn || !resetFiltersBtn) return;

  // Réinitialiser les gestionnaires d'événements
  [applyFiltersBtn, resetFiltersBtn].forEach((btn) => {
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
  });

  // Récupérer les nouveaux éléments
  const newApplyFiltersBtn = document.getElementById("apply-filters");
  const newResetFiltersBtn = document.getElementById("reset-filters");

  // Appliquer les filtres
  newApplyFiltersBtn.addEventListener("click", function () {
    console.log("Application des filtres...");
    const date = document.getElementById("date")?.value;
    const timeRange = document.getElementById("time-range")?.value;
    const status = document.getElementById("status")?.value;
    const service = document.getElementById("service")?.value;
    const bureau = document.getElementById("bureau")?.value;
    const employee = document.getElementById("employee")?.value;

    const entryRows = document.querySelectorAll(".entry-row");
    entryRows.forEach((row) => {
      const rowDate = row.querySelector("td:nth-child(5)")?.textContent;
      const rowService = row.querySelector("td:nth-child(3)")?.textContent;
      const rowBureau = row.querySelector("td:nth-child(4)")?.textContent;
      const rowEmployee = row.querySelector("td:nth-child(2)")?.textContent;

      const matchesDate = !date || rowDate.includes(date);
      const matchesService = service === "all" || rowService === service;
      const matchesBureau = bureau === "all" || rowBureau === bureau;
      const matchesEmployee =
        employee === "all" || rowEmployee.includes(employee);

      row.style.display =
        matchesDate && matchesService && matchesBureau && matchesEmployee
          ? "table-row"
          : "none";
    });

    // Publier un événement pour informer d'autres modules potentiels
    eventBus.publish("presence:filtered", {
      date,
      timeRange,
      status,
      service,
      bureau,
      employee,
    });
  });

  // Réinitialiser les filtres
  newResetFiltersBtn.addEventListener("click", function () {
    console.log("Réinitialisation des filtres...");

    if (document.getElementById("date"))
      document.getElementById("date").value = "2025-04-05";
    if (document.getElementById("time-range"))
      document.getElementById("time-range").value = "all";
    if (document.getElementById("status"))
      document.getElementById("status").value = "all";
    if (document.getElementById("service"))
      document.getElementById("service").value = "all";
    if (document.getElementById("bureau"))
      document.getElementById("bureau").value = "all";
    if (document.getElementById("employee"))
      document.getElementById("employee").value = "all";

    const entryRows = document.querySelectorAll(".entry-row");
    entryRows.forEach((row) => (row.style.display = "table-row"));

    // Publier un événement pour informer d'autres modules potentiels
    eventBus.publish("presence:filtersReset", {});
  });
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
