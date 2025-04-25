// js/modules/presence.js
import { ajaxQueue, debounce } from "../utils.js";

export function init() {
  setupFilters();
  setupToggleButtons();
  loadInitialData();
}

function setupFilters() {
  const applyFilters = debounce(() => {
    const filters = getFilterValues();
    updatePresenceList(filters);
  }, 300);

  document
    .getElementById("apply-filters")
    ?.addEventListener("click", applyFilters);
  document.getElementById("reset-filters")?.addEventListener("click", () => {
    document.getElementById("filter-form").reset();
    updatePresenceList({ type: document.getElementById("type-filter").value });
  });

  document.getElementById("service")?.addEventListener("change", () => {
    const bureauSelect = document.getElementById("bureau");
    bureauSelect.innerHTML = '<option value="all">Tous les bureaux</option>';
    bureauSelect.disabled = document.getElementById("service").value === "all";
    if (document.getElementById("service").value !== "all") {
      ajaxQueue
        .add({
          action: "fetch_bureaux",
          service_id: document.getElementById("service").value,
        })
        .then((data) =>
          data.forEach((bureau) => {
            bureauSelect.innerHTML += `<option value="${bureau.id}">${bureau.libele}</option>`;
          })
        );
    }
  });
}

function setupToggleButtons() {
  const buttons = [
    { id: "toggle-all", type: "all" },
    { id: "toggle-arrivals", type: "arrivée" },
    { id: "toggle-departures", type: "depart" },
  ];

  buttons.forEach(({ id, type }) => {
    document.getElementById(id)?.addEventListener("click", () => {
      document.getElementById("type-filter").value = type;
      updatePresenceList({ type });
      buttons.forEach((b) =>
        document
          .getElementById(b.id)
          .classList.toggle("bg-blue-600", b.id === id)
      );
    });
  });

  document.getElementById("toggle-all")?.click();
}

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

function updatePresenceList(filters) {
  ajaxQueue
    .add({ action: "fetch_presence_data", filters })
    .then((data) => {
      const tbody = document.querySelector("#presence-table tbody");
      tbody.innerHTML = data.length
        ? data
            .map(
              (presence) => `
                <tr>
                    <td>${presence.nom_prenom}</td>
                    <td>${presence.service}</td>
                    <td>${presence.bureau}</td>
                    <td>${presence.date}</td>
                    <td>${presence.heure}</td>
                    <td>${
                      presence.type === "arrivée" ? "Arrivée" : "Départ"
                    }</td>
                </tr>
            `
            )
            .join("")
        : '<tr><td colspan="6">Aucune présence trouvée</td></tr>';
    })
    .catch(() => {
      document.querySelector("#presence-table tbody").innerHTML =
        '<tr><td colspan="6">Erreur de chargement</td></tr>';
    });
}

function loadInitialData() {
  updatePresenceList({ type: "all" });
}
