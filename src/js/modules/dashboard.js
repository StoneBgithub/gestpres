import { chartConfig, utils, eventBus } from "../config.js";

let presenceChart = null;
let presenceTrackingChart = null;
let minDate = null;
let maxDate = null;

export function init() {
  console.log("Initialisation du module dashboard");
  loadChartJs(() => {
    fetchDateRange().then(() => {
      initializeCharts();
      setupPeriodButtons();
      setupFilters();
      loadInitialData();
    });
  });
}

function loadChartJs(callback) {
  if (typeof Chart !== "undefined") {
    console.log("Chart.js déjà chargé");
    callback();
    return;
  }

  const script = document.createElement("script");
  script.src =
    "https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js";
  script.async = true;
  script.onload = () => {
    console.log("Chart.js chargé depuis CDN");
    callback();
  };
  script.onerror = (error) => {
    console.error("Erreur de chargement de Chart.js :", error);
  };
  document.head.appendChild(script);
}

function fetchDateRange() {
  return fetch("fetch_dashboard_data.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({}),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.min_date && data.max_date) {
        minDate = new Date(data.min_date);
        maxDate = new Date(data.max_date);
        console.log("Plage de dates récupérée :", minDate, maxDate);
      } else {
        console.warn("Plage de dates non disponible");
        minDate = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
        maxDate = new Date();
      }
    })
    .catch((error) => {
      console.error(
        "Erreur lors de la récupération de la plage de dates :",
        error
      );
      minDate = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
      maxDate = new Date();
    });
}

function initializeCharts() {
  const chartElement = document.getElementById("presence-chart");
  const trackingChartElement = document.getElementById(
    "presence-tracking-chart"
  );

  if (!chartElement) {
    console.warn("Élément 'presence-chart' non trouvé");
  } else {
    createPresenceChart();
  }

  if (!trackingChartElement) {
    console.warn("Élément 'presence-tracking-chart' non trouvé");
  } else {
    createPresenceTrackingChart();
  }
}

function createPresenceChart(data = null) {
  const chartElement = document.getElementById("presence-chart");
  if (!chartElement) return;

  const ctx = chartElement.getContext("2d");
  const chartData = data || {
    labels: [],
    datasets: [
      {
        label: "Présents",
        data: [],
        backgroundColor: "rgba(59, 130, 246, 0.2)", // Original blue
        borderColor: "rgba(59, 130, 246, 1)",
        borderWidth: 2,
        tension: 0.4,
        fill: true,
      },
      {
        label: "Absents",
        data: [],
        backgroundColor: "rgba(239, 68, 68, 0.2)", // Original red
        borderColor: "rgba(239, 68, 68, 1)",
        borderWidth: 2,
        tension: 0.4,
        fill: true,
      },
      {
        label: "En retard",
        data: [],
        backgroundColor: "rgba(245, 158, 11, 0.2)", // Original amber
        borderWidth: 2,
        tension: 0.4,
        fill: true,
      },
    ],
  };

  if (!chartData.labels.length) {
    console.warn("Aucune donnée pour le graphique principal, affichage vide");
    chartData.labels = ["Aucune donnée"];
    chartData.datasets.forEach((dataset) => (dataset.data = [0]));
  }

  const config = {
    type: "bar",
    data: chartData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "top" },
        tooltip: { mode: "index", intersect: false },
      },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true },
      },
    },
  };

  if (presenceChart) presenceChart.destroy();
  presenceChart = new Chart(ctx, config);
}

function createPresenceTrackingChart(data = null) {
  const chartElement = document.getElementById("presence-tracking-chart");
  if (!chartElement) return;

  const ctx = chartElement.getContext("2d");
  const chartData = data || {
    labels: [],
    datasets: [
      {
        label: "Présents",
        data: [],
        backgroundColor: "rgba(59, 130, 246, 0.2)", // Original blue
        borderColor: "rgba(59, 130, 246, 1)",
        borderWidth: 2,
        tension: 0.4,
        fill: true,
      },
      {
        label: "Absents",
        data: [],
        backgroundColor: "rgba(239, 68, 68, 0.2)", // Original red
        borderColor: "rgba(239, 68, 68, 1)",
        borderWidth: 2,
        tension: 0.4,
        fill: true,
      },
      {
        label: "En retard",
        data: [],
        backgroundColor: "rgba(245, 158, 11, 0.2)", // Original amber
        borderColor: "rgba(245, 158, 11, 1)",
        borderWidth: 2,
        tension: 0.4,
        fill: true,
      },
    ],
  };

  if (!chartData.labels.length) {
    console.warn("Aucune donnée pour le graphique de suivi, affichage vide");
    chartData.labels = ["Aucune donnée"];
    chartData.datasets.forEach((dataset) => (dataset.data = [0]));
  }

  const config = {
    type: "line",
    data: chartData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "top" },
        tooltip: { mode: "index", intersect: false },
      },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true },
      },
    },
  };

  if (presenceTrackingChart) presenceTrackingChart.destroy();
  presenceTrackingChart = new Chart(ctx, config);
}

function setupPeriodButtons() {
  const buttons = [
    { id: "filter-day", fn: () => updateChart("day"), label: "Aujourd'hui" },
    {
      id: "filter-week",
      fn: () => updateChart("week"),
      label: "Cette semaine",
    },
    { id: "filter-month", fn: () => updateChart("month"), label: "Ce mois" },
    { id: "filter-year", fn: () => updateChart("year"), label: "Cette année" },
  ];

  buttons.forEach(({ id, fn, label }) => {
    const btn = document.getElementById(id);
    if (btn) {
      btn.addEventListener("click", () => {
        fn();
        utils.setActiveButton(
          btn,
          buttons
            .map((b) => document.getElementById(b.id))
            .filter((b) => b !== btn)
        );
        document.getElementById("current-filter").textContent = label;
      });
    }
  });
}

function updateChart(period) {
  const filters = getCurrentFilters();
  filters.period = period;
  updateDashboard(filters, true);
}

function setupFilters() {
  const toggleFiltersBtn = document.querySelector(".toggle-filters-btn");
  const advancedFilters = document.querySelector(".advanced-filters");
  const applyFiltersBtn = document.getElementById("apply-filters");
  const resetFiltersBtn = document.getElementById("reset-filters");

  resetFilters();

  const yearSelect = document.getElementById("year");
  if (yearSelect && minDate && maxDate) {
    yearSelect.innerHTML = "";
    const minYear = minDate.getFullYear();
    const maxYear = maxDate.getFullYear();
    for (let y = minYear; y <= maxYear; y++) {
      const option = document.createElement("option");
      option.value = y;
      option.textContent = y;
      yearSelect.appendChild(option);
    }
    yearSelect.value = maxYear.toString();
  }

  yearSelect.addEventListener("change", () => {
    const year = yearSelect.value;
    const monthSelect = document.getElementById("month");
    const weekSelect = document.getElementById("week");
    monthSelect.innerHTML = '<option value="all">Tous les mois</option>';
    weekSelect.innerHTML = '<option value="all">Toutes les semaines</option>';
    weekSelect.disabled = true;

    if (year) {
      const months = getAvailableMonths(year);
      months.forEach((month) => {
        const option = document.createElement("option");
        option.value = month.value;
        option.textContent = month.name;
        monthSelect.appendChild(option);
      });
    }
  });

  document.getElementById("month").addEventListener("change", () => {
    const month = document.getElementById("month").value;
    const year = document.getElementById("year").value;
    const weekSelect = document.getElementById("week");
    weekSelect.innerHTML = '<option value="all">Toutes les semaines</option>';
    weekSelect.disabled = month === "all";
    if (month !== "all" && year) {
      const weeks = getWeeksInMonth(year, month);
      weeks.forEach((week) => {
        const option = document.createElement("option");
        option.value = week.weekNumber;
        option.textContent = `Semaine ${week.weekNumber} (${week.start} - ${week.end})`;
        weekSelect.appendChild(option);
      });
    }
  });

  document.getElementById("service").addEventListener("change", () => {
    const service = document.getElementById("service").value;
    const bureauSelect = document.getElementById("bureau");
    const employeeSelect = document.getElementById("employee");
    bureauSelect.innerHTML = '<option value="all">Tous les bureaux</option>';
    bureauSelect.disabled = service === "all";
    employeeSelect.innerHTML = '<option value="all">Tous les employés</option>';
    employeeSelect.disabled = true;

    if (service !== "all") {
      fetch(`fetch_bureaux.php?service_id=${service}`)
        .then((response) => response.json())
        .then((data) =>
          data.forEach((bureau) => {
            const option = document.createElement("option");
            option.value = bureau.id;
            option.textContent = bureau.libele;
            bureauSelect.appendChild(option);
          })
        );
    }
  });

  document.getElementById("bureau").addEventListener("change", () => {
    const bureau = document.getElementById("bureau").value;
    const employeeSelect = document.getElementById("employee");
    employeeSelect.innerHTML = '<option value="all">Tous les employés</option>';
    employeeSelect.disabled = bureau === "all";

    if (bureau !== "all") {
      fetch(`fetch_agents.php?bureau_id=${bureau}`)
        .then((response) => response.json())
        .then((data) =>
          data.forEach((agent) => {
            const option = document.createElement("option");
            option.value = agent.id;
            option.textContent = `${agent.prenom} ${agent.nom}`;
            employeeSelect.appendChild(option);
          })
        );
    }
  });

  if (applyFiltersBtn) {
    applyFiltersBtn.removeEventListener("click", applyFiltersHandler);
    applyFiltersBtn.addEventListener("click", applyFiltersHandler);
  }

  if (resetFiltersBtn) {
    resetFiltersBtn.removeEventListener("click", resetFiltersHandler);
    resetFiltersBtn.addEventListener("click", resetFiltersHandler);
  }

  if (toggleFiltersBtn && advancedFilters) {
    const newToggleFiltersBtn = toggleFiltersBtn.cloneNode(true);
    toggleFiltersBtn.replaceWith(newToggleFiltersBtn);

    newToggleFiltersBtn.addEventListener("click", (e) => {
      e.preventDefault();
      advancedFilters.classList.toggle("hidden");
    });
  }

  function applyFiltersHandler() {
    const filters = getCurrentFilters();
    console.log("Filtres appliqués :", filters);
    updateDashboard(filters, true);
    document.getElementById("current-filter").textContent = "Filtres avancés";
    advancedFilters.classList.add("hidden");
  }

  function resetFiltersHandler() {
    resetFilters();
    updateDashboard({ period: "day" }, true);
    document.getElementById("current-filter").textContent = "Aujourd'hui";
    advancedFilters.classList.add("hidden");
  }
}

function getAvailableMonths(year) {
  const months = [
    { value: "1", name: "Janvier" },
    { value: "2", name: "Février" },
    { value: "3", name: "Mars" },
    { value: "4", name: "Avril" },
    { value: "5", name: "Mai" },
    { value: "6", name: "Juin" },
    { value: "7", name: "Juillet" },
    { value: "8", name: "Août" },
    { value: "9", name: "Septembre" },
    { value: "10", name: "Octobre" },
    { value: "11", name: "Novembre" },
    { value: "12", name: "Décembre" },
  ];

  if (!minDate || !maxDate) return months;

  const minYear = minDate.getFullYear();
  const maxYear = maxDate.getFullYear();
  const minMonth = minYear === Number(year) ? minDate.getMonth() + 1 : 1;
  const maxMonth = maxYear === Number(year) ? maxDate.getMonth() + 1 : 12;

  return months.filter(
    (month) =>
      Number(month.value) >= minMonth && Number(month.value) <= maxMonth
  );
}

function getCurrentFilters() {
  return {
    period:
      document.getElementById("week").value !== "all"
        ? "week"
        : document.getElementById("month").value !== "all"
        ? "month"
        : document.getElementById("year").value
        ? "year"
        : "day",
    year: document.getElementById("year").value,
    month: document.getElementById("month").value,
    week: document.getElementById("week").value,
    service: document.getElementById("service").value,
    bureau: document.getElementById("bureau").value,
    employee: document.getElementById("employee").value,
  };
}

function resetFilters() {
  const yearSelect = document.getElementById("year");
  const monthSelect = document.getElementById("month");
  const weekSelect = document.getElementById("week");
  const serviceSelect = document.getElementById("service");
  const bureauSelect = document.getElementById("bureau");
  const employeeSelect = document.getElementById("employee");

  yearSelect.value = maxDate
    ? maxDate.getFullYear().toString()
    : new Date().getFullYear().toString();
  monthSelect.value = "all";
  weekSelect.innerHTML = '<option value="all">Toutes les semaines</option>';
  weekSelect.value = "all";
  weekSelect.disabled = true;
  serviceSelect.value = "all";
  bureauSelect.innerHTML = '<option value="all">Tous les bureaux</option>';
  bureauSelect.value = "all";
  bureauSelect.disabled = true;
  employeeSelect.innerHTML = '<option value="all">Tous les employés</option>';
  employeeSelect.value = "all";
  employeeSelect.disabled = true;
  console.log("Filtres réinitialisés : service=all, bureau=all, employee=all");
}

function resetToToday() {
  resetFilters();
  updateDashboard({ period: "day" }, true);
  document.getElementById("present-agents").textContent = "0";
  document.getElementById("absent-agents").textContent = "0";
  document.getElementById("presence-rate").textContent = "0%";
  document.getElementById("late-agents").textContent = "0";
  document.getElementById("recent-activities").innerHTML = "";
  createPresenceChart();
  createPresenceTrackingChart();
  document.getElementById("current-filter").textContent = "Aujourd'hui";
}

function updateDashboard(filters = { period: "day" }, updateChartFlag = false) {
  console.log("Envoi des filtres au serveur :", filters);
  fetch("fetch_dashboard_data.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(filters),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Données reçues du serveur :", data);
      if (data.error) {
        console.error("Erreur serveur :", data.error);
        resetDashboardDisplay();
      } else {
        document.getElementById("present-agents").textContent =
          filters.period === "day"
            ? data.present_agents_formatted
            : data.present_agents;
        document.getElementById("absent-agents").textContent =
          data.absent_agents;
        document.getElementById(
          "presence-rate"
        ).textContent = `${data.presence_rate}%`;
        document.getElementById("late-agents").textContent = data.late_agents;
        console.log(
          "Mise à jour des activités récentes :",
          data.recent_activities
        );
        document.getElementById("recent-activities").innerHTML =
          data.recent_activities;

        if (updateChartFlag && data.chart_data) {
          console.log("Mise à jour des graphiques avec :", data.chart_data);
          const chartData = {
            labels: data.chart_data.labels,
            datasets: [
              {
                label: "Présents",
                data: data.chart_data.present,
                backgroundColor: "rgba(59, 130, 246, 0.2)", // Original blue
                borderColor: "rgba(59, 130, 246, 1)",
                borderWidth: 2,
                tension: 0.4,
                fill: true,
              },
              {
                label: "Absents",
                data: data.chart_data.absent,
                backgroundColor: "rgba(239, 68, 68, 0.2)", // Original red
                borderColor: "rgba(239, 68, 68, 1)",
                borderWidth: 2,
                tension: 0.4,
                fill: true,
              },
              {
                label: "En retard",
                data: data.chart_data.late,
                backgroundColor: "rgba(245, 158, 11, 0.2)", // Original amber
                borderColor: "rgba(245, 158, 11, 1)",
                borderWidth: 2,
                tension: 0.4,
                fill: true,
              },
            ],
          };
          createPresenceChart(chartData);
          createPresenceTrackingChart(chartData);
        } else {
          console.warn(
            "Aucune donnée de graphique reçue ou updateChartFlag désactivé"
          );
        }
      }
    })
    .catch((error) => {
      console.error("Erreur lors de la requête :", error);
      resetDashboardDisplay();
    });
}

function resetDashboardDisplay() {
  console.warn("Réinitialisation de l'affichage du tableau de bord");
  document.getElementById("present-agents").textContent = "0";
  document.getElementById("absent-agents").textContent = "0";
  document.getElementById("presence-rate").textContent = "0%";
  document.getElementById("late-agents").textContent = "0";
  document.getElementById("recent-activities").innerHTML = `
    <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl shadow-sm animate-fade-in">
      <i class="fas fa-history text-4xl text-indigo-500 mb-4 animate-pulse"></i>
      <h3 class="text-lg font-semibold text-gray-800 mb-2 font-display">Aucune activité récente</h3>
      <p class="text-sm text-gray-600 text-center font-body">Changez la période ou les filtres pour voir les activités.</p>
    </div>
  `;
  createPresenceChart();
  createPresenceTrackingChart();
}

function loadInitialData() {
  resetFilters();
  const initialFilters = {
    period: "day",
    service: "all",
    bureau: "all",
    employee: "all",
  };
  console.log("Chargement initial avec filtres :", initialFilters);
  console.log(
    "Date actuelle côté client :",
    new Date().toISOString().split("T")[0]
  );
  updateDashboard(initialFilters, true);
  document.getElementById("current-filter").textContent = "Aujourd'hui";
  utils.setActiveButton(
    document.getElementById("filter-day"),
    ["filter-week", "filter-month", "filter-year"].map((id) =>
      document.getElementById(id)
    )
  );
}

function getWeeksInMonth(year, month) {
  const weeks = [];
  const firstDayOfMonth = new Date(year, month - 1, 1);
  const lastDayOfMonth = new Date(year, month, 0);
  const effectiveLastDay = lastDayOfMonth < maxDate ? lastDayOfMonth : maxDate;

  let weekStart = new Date(firstDayOfMonth);
  let weekNumber = 1;

  while (weekStart <= effectiveLastDay) {
    let weekEnd = new Date(weekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    if (weekEnd > effectiveLastDay) weekEnd = new Date(effectiveLastDay);

    weeks.push({
      weekNumber: weekNumber,
      start: weekStart.toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "short",
      }),
      end: weekEnd.toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "short",
      }),
    });

    weekStart.setDate(weekStart.getDate() + 7);
    weekNumber++;
  }

  return weeks;
}

eventBus.subscribe("page:loaded", ({ page }) => {
  if (page === "dashboard_content") init();
});
