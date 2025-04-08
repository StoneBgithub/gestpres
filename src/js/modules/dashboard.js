import { chartConfig, utils, eventBus } from "../config.js";

// Référence au graphique
let presenceChart = null;

// Fonction d'initialisation principale
export function init() {
  console.log("Initialisation du module tableau de bord");
  initializeChart();
  setupPeriodButtons();
  setupFilters();
}

// Initialiser le graphique de présence
function initializeChart() {
  const chartElement = document.getElementById("presence-chart");
  if (!chartElement) {
    console.warn("Élément 'presence-chart' non trouvé");
    return;
  }

  // Vérifier si Chart.js est chargé, sinon le charger
  if (typeof Chart === "undefined") {
    utils.loadScript(
      "./../node_modules/chart.js/dist/chart.umd.js",
      () => {
        createPresenceChart();
      },
      true
    );
  } else {
    createPresenceChart();
  }
}

// Créer le graphique de présence
function createPresenceChart() {
  const chartElement = document.getElementById("presence-chart");
  if (!chartElement) return;

  const ctx = chartElement.getContext("2d");

  // Données par défaut (hebdomadaire)
  const weekLabels = [
    "Lundi",
    "Mardi",
    "Mercredi",
    "Jeudi",
    "Vendredi",
    "Samedi",
    "Dimanche",
  ];
  const presenceData = {
    labels: weekLabels,
    datasets: [
      {
        label: "Présents",
        data: [48, 47, 45, 49, 45, 12, 8],
        backgroundColor: "rgba(59, 130, 246, 0.2)", // Bleu clair avec transparence
        borderColor: "rgba(59, 130, 246, 1)", // Bleu
        borderWidth: 2,
        tension: 0.4,
        fill: true,
      },
      {
        label: "Absents",
        data: [2, 3, 5, 1, 5, 1, 0],
        backgroundColor: "rgba(239, 68, 68, 0.2)", // Rouge clair avec transparence
        borderColor: "rgba(239, 68, 68, 1)", // Rouge
        borderWidth: 2,
        tension: 0.4,
        fill: true,
      },
      {
        label: "En retard",
        data: [3, 2, 1, 4, 2, 0, 0],
        backgroundColor: "rgba(245, 158, 11, 0.2)", // Orange clair avec transparence
        borderColor: "rgba(245, 158, 11, 1)", // Orange
        borderWidth: 2,
        tension: 0.4,
        fill: true,
      },
    ],
  };

  // Configuration du graphique
  const config = {
    type: "line",
    data: presenceData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "top",
          labels: {
            usePointStyle: true,
            boxWidth: 10,
            font: {
              family: "Poppins",
            },
          },
        },
        tooltip: {
          mode: "index",
          intersect: false,
          backgroundColor: "rgba(255, 255, 255, 0.9)",
          titleColor: "#111827",
          bodyColor: "#4B5563",
          borderColor: "#E5E7EB",
          borderWidth: 1,
          bodyFont: {
            family: "Poppins",
          },
          titleFont: {
            family: "Poppins",
            weight: "bold",
          },
          callbacks: {
            label: function (context) {
              return `${context.dataset.label}: ${context.raw} agents`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            font: {
              family: "Poppins",
            },
          },
        },
        y: {
          beginAtZero: true,
          grid: {
            color: "rgba(156, 163, 175, 0.1)",
          },
          ticks: {
            precision: 0,
            font: {
              family: "Poppins",
            },
          },
        },
      },
      interaction: {
        mode: "nearest",
        axis: "x",
        intersect: false,
      },
    },
  };

  // Créer le graphique
  presenceChart = new Chart(ctx, config);
  eventBus.publish("dashboard:chartCreated", { chart: presenceChart });
}

// Configurer les boutons de période
function setupPeriodButtons() {
  const dailyBtn = document.getElementById("daily-btn");
  const weeklyBtn = document.getElementById("weekly-btn");
  const monthlyBtn = document.getElementById("monthly-btn");

  if (!dailyBtn || !weeklyBtn || !monthlyBtn) return;

  // Réinitialiser les gestionnaires d’événements
  [dailyBtn, weeklyBtn, monthlyBtn].forEach((btn) => {
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
  });

  // Récupérer les nouveaux éléments
  const newDailyBtn = document.getElementById("daily-btn");
  const newWeeklyBtn = document.getElementById("weekly-btn");
  const newMonthlyBtn = document.getElementById("monthly-btn");

  // Ajouter les nouveaux gestionnaires
  newDailyBtn.addEventListener("click", () => {
    updateChartToDaily();
    utils.setActiveButton(newDailyBtn, [newWeeklyBtn, newMonthlyBtn]);
  });

  newWeeklyBtn.addEventListener("click", () => {
    updateChartToWeekly();
    utils.setActiveButton(newWeeklyBtn, [newDailyBtn, newMonthlyBtn]);
  });

  newMonthlyBtn.addEventListener("click", () => {
    updateChartToMonthly();
    utils.setActiveButton(newMonthlyBtn, [newDailyBtn, newWeeklyBtn]);
  });

  // Activer le bouton par défaut (hebdomadaire)
  if (chartConfig.defaultPeriod === "daily") newDailyBtn.click();
  else if (chartConfig.defaultPeriod === "weekly") newWeeklyBtn.click();
  else if (chartConfig.defaultPeriod === "monthly") newMonthlyBtn.click();
  else newWeeklyBtn.click(); // Par défaut comme dans dashboard-main.js
}

// Mettre à jour le graphique en mode journalier
function updateChartToDaily() {
  if (!presenceChart) return;

  const labels = [
    "8h",
    "9h",
    "10h",
    "11h",
    "12h",
    "13h",
    "14h",
    "15h",
    "16h",
    "17h",
  ];
  const presents = [30, 42, 48, 49, 25, 28, 47, 49, 48, 30];
  const absents = [20, 8, 2, 1, 25, 22, 3, 1, 2, 20];
  const lates = [5, 3, 0, 0, 0, 5, 2, 0, 0, 0];

  presenceChart.data.labels = labels;
  presenceChart.data.datasets[0].data = presents;
  presenceChart.data.datasets[1].data = absents;
  presenceChart.data.datasets[2].data = lates;
  presenceChart.update();

  eventBus.publish("dashboard:periodChanged", { period: "daily" });
}

// Mettre à jour le graphique en mode hebdomadaire
function updateChartToWeekly() {
  if (!presenceChart) return;

  const labels = [
    "Lundi",
    "Mardi",
    "Mercredi",
    "Jeudi",
    "Vendredi",
    "Samedi",
    "Dimanche",
  ];
  const presents = [48, 47, 45, 49, 45, 12, 8];
  const absents = [2, 3, 5, 1, 5, 1, 0];
  const lates = [3, 2, 1, 4, 2, 0, 0];

  presenceChart.data.labels = labels;
  presenceChart.data.datasets[0].data = presents;
  presenceChart.data.datasets[1].data = absents;
  presenceChart.data.datasets[2].data = lates;
  presenceChart.update();

  eventBus.publish("dashboard:periodChanged", { period: "weekly" });
}

// Mettre à jour le graphique en mode mensuel
function updateChartToMonthly() {
  if (!presenceChart) return;

  const labels = ["Sem 1", "Sem 2", "Sem 3", "Sem 4"];
  const presents = [245, 238, 243, 220];
  const absents = [15, 22, 17, 40];
  const lates = [12, 15, 10, 8];

  presenceChart.data.labels = labels;
  presenceChart.data.datasets[0].data = presents;
  presenceChart.data.datasets[1].data = absents;
  presenceChart.data.datasets[2].data = lates;
  presenceChart.update();

  eventBus.publish("dashboard:periodChanged", { period: "monthly" });
}

// Configurer les filtres avancés
function setupFilters() {
  const toggleFiltersBtn = document.querySelector(".toggle-filters-btn");
  const advancedFilters = document.querySelector(".advanced-filters");
  const filterIcon = document.querySelector(".filter-icon");

  if (!toggleFiltersBtn || !advancedFilters || !filterIcon) return;

  const newToggleFiltersBtn = toggleFiltersBtn.cloneNode(true);
  toggleFiltersBtn.parentNode.replaceChild(
    newToggleFiltersBtn,
    toggleFiltersBtn
  );

  newToggleFiltersBtn.addEventListener("click", (e) => {
    e.preventDefault();
    const isHidden =
      advancedFilters.style.display === "none" ||
      advancedFilters.style.display === "";
    advancedFilters.style.display = isHidden ? "block" : "none";
    filterIcon.setAttribute(
      "d",
      isHidden ? "M6 12h12" : "M12 6v6m0 0v6m0-6h6m-6 0H6"
    );
  });
}

// S'abonner aux événements pertinents
eventBus.subscribe("dashboard:externalUpdate", (data) => {
  console.log("Mise à jour externe du tableau de bord reçue", data);
});
