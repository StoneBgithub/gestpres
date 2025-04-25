import { eventBus } from "../config.js";

export function init() {
  console.log("Initialisation du module performance");

  // Éléments DOM
  const searchInput = document.getElementById("search");
  const serviceSelect = document.getElementById("service");
  const bureauSelect = document.getElementById("bureau");
  const applyFiltersBtn = document.getElementById("apply-filters");
  const periodSelect = document.getElementById("period");
  const globalStats = document.getElementById("global-stats");
  const agentsRanking = document.getElementById("agents-ranking");
  const paginationInfo = document.getElementById("pagination-info");
  const paginationControls = document.getElementById("pagination-controls");
  const exportBtn = document.getElementById("export-data");

  let currentPage = 1;

  // Charger les services
  fetch("fetch_services.php")
    .then((response) => {
      if (!response.ok)
        throw new Error("Erreur lors du chargement des services");
      return response.json();
    })
    .then((services) => {
      serviceSelect.innerHTML = '<option value="">Tous les services</option>';
      services.forEach((service) => {
        const option = document.createElement("option");
        option.value = service.id;
        option.textContent = service.libele;
        serviceSelect.appendChild(option);
      });
    })
    .catch((error) => {
      console.error("Erreur lors du chargement des services:", error);
      serviceSelect.innerHTML =
        '<option value="">Erreur de chargement</option>';
    });

  // Charger les bureaux en fonction du service
  serviceSelect.addEventListener("change", () => {
    const serviceId = serviceSelect.value;
    bureauSelect.innerHTML = '<option value="">Tous les bureaux</option>';
    if (serviceId) {
      fetch(`fetch_bureaux.php?service_id=${serviceId}`)
        .then((response) => {
          if (!response.ok)
            throw new Error("Erreur lors du chargement des bureaux");
          return response.json();
        })
        .then((bureaux) => {
          bureaux.forEach((bureau) => {
            const option = document.createElement("option");
            option.value = bureau.id;
            option.textContent = bureau.libele;
            bureauSelect.appendChild(option);
          });
        })
        .catch((error) => {
          console.error("Erreur lors du chargement des bureaux:", error);
          bureauSelect.innerHTML =
            '<option value="">Erreur de chargement</option>';
        });
    }
  });

  // Fonction pour charger les données
  function loadData(page = 1) {
    currentPage = page;
    const params = new URLSearchParams({
      search: searchInput.value.trim(),
      service_id: serviceSelect.value,
      bureau_id: bureauSelect.value,
      period: periodSelect.value,
      page: page,
    });

    globalStats.innerHTML =
      '<p class="text-center text-gray-500">Chargement...</p>';
    agentsRanking.innerHTML =
      '<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Chargement...</td></tr>';

    // Dans la fonction loadData, juste après fetch :
    fetch(`fetch_performances.php?${params.toString()}`)
      .then((response) => {
        if (!response.ok) {
          return response.json().then((err) => {
            throw new Error(err.error || "Erreur serveur");
          });
        }
        return response.json();
      })
      .then((data) => {
        console.log("Données reçues de fetch_performances.php :", data); // Log pour déboguer
        // ... reste du code inchangé ...
        globalStats.innerHTML = `
                    <div class="bg-gray-50 p-4 rounded-lg shadow-sm border-l-4 border-congo-green">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Heures totales travaillées</p>
                                <p class="text-2xl font-bold text-congo-green">${data.stats.total_hours} h</p>
                            </div>
                            <div class="rounded-full bg-congo-green-light p-3">
                                <i class="fas fa-clock text-congo-green text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Basé sur la période sélectionnée</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg shadow-sm border-l-4 border-congo-green">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Taux de présence moyen</p>
                                <p class="text-2xl font-bold text-congo-green">${data.stats.avg_attendance_rate}%</p>
                            </div>
                            <div class="rounded-full bg-congo-green-light p-3">
                                <i class="fas fa-user-check text-congo-green text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Basé sur les jours ouvrables</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg shadow-sm border-l-4 border-congo-green">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Heures supplémentaires</p>
                                <p class="text-2xl font-bold text-congo-green">${data.stats.total_overtime} h</p>
                            </div>
                            <div class="rounded-full bg-congo-green-light p-3">
                                <i class="fas fa-hourglass-half text-congo-green text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Après 14h</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg shadow-sm border-l-4 border-congo-green">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Agents à 100% de présence</p>
                                <p class="text-2xl font-bold text-congo-green">${data.stats.perfect_attendance}</p>
                            </div>
                            <div class="rounded-full bg-congo-green-light p-3">
                                <i class="fas fa-medal text-congo-green text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Présence complète</p>
                    </div>
                `;

        // Mettre à jour le classement des agents
        agentsRanking.innerHTML = "";
        if (data.agents.length === 0) {
          agentsRanking.innerHTML =
            '<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Aucun agent trouvé</td></tr>';
        } else {
          // Modification de la partie du code où les rangs sont générés

          // Correction du code JavaScript pour le classement des agents et autres styles

          // Partie pour la génération du classement des agents
          data.agents.forEach((agent, index) => {
            const rank = (page - 1) * 10 + index + 1;
            const presenceRate = Math.round(agent.presence_rate);
            const badgeClass =
              presenceRate >= 90
                ? "badge-success"
                : presenceRate >= 80
                ? "badge-warning"
                : "badge-danger";
            const badgeText =
              presenceRate >= 90
                ? "Excellent"
                : presenceRate >= 80
                ? "Bon"
                : "À améliorer";
            const initials = `${agent.nom.charAt(0)}${agent.prenom.charAt(
              0
            )}`.toUpperCase();

            // Définir des couleurs distinctes pour les rangs
            let rankBgColor;
            if (rank === 1) {
              // Couleur dorée
              rankBgColor = "bg-yellow-400";
            } else if (rank === 2) {
              // Argent
              rankBgColor = "bg-gray-300";
            } else if (rank === 3) {
              // Bronze
              rankBgColor = "bg-orange-700";
            } else {
              rankBgColor = "bg-gray-200";
            }

            agentsRanking.innerHTML += `
    <tr class="hover:bg-gray-50 transition-all">
      <td class="px-4 py-3 whitespace-nowrap">
        <div class="flex items-center justify-center w-8 h-8 rounded-full ${rankBgColor} text-white font-bold shadow-sm">
          ${rank}
        </div>
      </td>
      <td class="px-4 py-3 whitespace-nowrap">
        <div class="flex items-center">
          <div class="flex-shrink-0 h-10 w-10">
            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold">
              ${initials}
            </div>
          </div>
          <div class="ml-4">
            <div class="text-sm font-medium text-gray-900">${agent.nom} ${
              agent.prenom
            }</div>
            <div class="text-sm text-gray-500">${agent.email || "-"}</div>
          </div>
        </div>
      </td>
      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${
        agent.bureau || "-"
      }</td>
      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${
        agent.service || "-"
      }</td>
      <td class="px-4 py-3 whitespace-nowrap">
        <div class="text-sm text-gray-900 font-medium">${Math.round(
          agent.total_hours
        )} h</div>
        <div class="text-xs text-gray-500">+${Math.round(
          agent.overtime_hours
        )} h supp.</div>
      </td>
      <td class="px-4 py-3 whitespace-nowrap">
        <div class="flex items-center">
          <div class="text-sm font-medium text-gray-900 mr-2">${presenceRate}%</div>
          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeClass} text-white">
            ${badgeText}
          </span>
        </div>
      </td>
    </tr>
  `;
          });

          // Pour les statistiques globales, on utilise le code d'origine
          globalStats.innerHTML = `
  <div class="bg-gray-50 p-4 rounded-lg shadow-sm border-l-4 border-congo-green">
      <div class="flex justify-between items-center">
          <div>
              <p class="text-sm font-medium text-gray-500">Heures totales travaillées</p>
              <p class="text-2xl font-bold text-congo-green">${data.stats.total_hours} h</p>
          </div>
          <div class="rounded-full bg-congo-green-light p-3">
              <i class="fas fa-clock text-congo-green text-xl"></i>
          </div>
      </div>
      <p class="text-xs text-gray-500 mt-2">Basé sur la période sélectionnée</p>
  </div>
  <div class="bg-gray-50 p-4 rounded-lg shadow-sm border-l-4 border-congo-green">
      <div class="flex justify-between items-center">
          <div>
              <p class="text-sm font-medium text-gray-500">Taux de présence moyen</p>
              <p class="text-2xl font-bold text-congo-green">${data.stats.avg_attendance_rate}%</p>
          </div>
          <div class="rounded-full bg-congo-green-light p-3">
              <i class="fas fa-user-check text-congo-green text-xl"></i>
          </div>
      </div>
      <p class="text-xs text-gray-500 mt-2">Basé sur les jours ouvrables</p>
  </div>
  <div class="bg-gray-50 p-4 rounded-lg shadow-sm border-l-4 border-congo-green">
      <div class="flex justify-between items-center">
          <div>
              <p class="text-sm font-medium text-gray-500">Heures supplémentaires</p>
              <p class="text-2xl font-bold text-congo-green">${data.stats.total_overtime} h</p>
          </div>
          <div class="rounded-full bg-congo-green-light p-3">
              <i class="fas fa-hourglass-half text-congo-green text-xl"></i>
          </div>
      </div>
      <p class="text-xs text-gray-500 mt-2">Après 14h</p>
  </div>
  <div class="bg-gray-50 p-4 rounded-lg shadow-sm border-l-4 border-congo-green">
      <div class="flex justify-between items-center">
          <div>
              <p class="text-sm font-medium text-gray-500">Agents à 100% de présence</p>
              <p class="text-2xl font-bold text-congo-green">${data.stats.perfect_attendance}</p>
          </div>
          <div class="rounded-full bg-congo-green-light p-3">
              <i class="fas fa-medal text-congo-green text-xl"></i>
          </div>
      </div>
      <p class="text-xs text-gray-500 mt-2">Présence complète</p>
  </div>
`;
        }

        // Mettre à jour la pagination
        paginationInfo.textContent = `Affichage de ${
          (page - 1) * 10 + 1
        } à ${Math.min(page * 10, data.pagination.total_agents)} sur ${
          data.pagination.total_agents
        } agents`;
        paginationControls.innerHTML = `
                    <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50 ${
                      page === 1 ? "disabled:opacity-50" : ""
                    }" id="prev-page" ${page === 1 ? "disabled" : ""}>
                        Précédent
                    </button>
                    ${Array.from(
                      { length: Math.min(data.pagination.total_pages, 5) },
                      (_, i) => {
                        const pageNum = i + 1;
                        return `
                            <button class="px-3 py-1 border rounded-md text-sm font-medium ${
                              pageNum === page
                                ? "border-congo-green bg-congo-green text-white"
                                : "border-gray-300 hover:bg-gray-50"
                            }" data-page="${pageNum}">
                                ${pageNum}
                            </button>
                        `;
                      }
                    ).join("")}
                    <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50 ${
                      page >= data.pagination.total_pages
                        ? "disabled:opacity-50"
                        : ""
                    }" id="next-page" ${
          page >= data.pagination.total_pages ? "disabled" : ""
        }>
                        Suivant
                    </button>
                `;

        // Ajouter des écouteurs pour la pagination
        document.querySelectorAll("[data-page]").forEach((button) => {
          button.addEventListener("click", () =>
            loadData(parseInt(button.dataset.page))
          );
        });
        const prevPage = document.getElementById("prev-page");
        const nextPage = document.getElementById("next-page");
        if (prevPage)
          prevPage.addEventListener("click", () => loadData(page - 1));
        if (nextPage)
          nextPage.addEventListener("click", () => loadData(page + 1));
      })
      .catch((error) => {
        console.error("Erreur lors du chargement des données:", error);
        globalStats.innerHTML =
          '<p class="text-center text-red-500">Erreur lors du chargement des statistiques</p>';
        agentsRanking.innerHTML =
          '<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-red-500">Erreur lors du chargement des agents</td></tr>';
        paginationControls.innerHTML = "";
      });
  }

  // Écouteurs pour les filtres
  applyFiltersBtn.addEventListener("click", () => loadData(1));
  periodSelect.addEventListener("change", () => loadData(1));
  searchInput.addEventListener("input", () => {
    clearTimeout(searchInput.dataset.timeout);
    searchInput.dataset.timeout = setTimeout(() => loadData(1), 500);
  });

  // Exportation des données
  exportBtn.addEventListener("click", () => {
    const params = new URLSearchParams({
      search: searchInput.value.trim(),
      service_id: serviceSelect.value,
      bureau_id: bureauSelect.value,
      period: periodSelect.value,
      page: 1,
      per_page: 1000,
    });

    fetch(`fetch_performances.php?${params.toString()}`)
      .then((response) => {
        if (!response.ok) throw new Error("Erreur lors de l'exportation");
        return response.json();
      })
      .then((data) => {
        const csv = [
          "Rang,Agent,Bureau,Service,Heures travaillées,Heures supp.,Taux de présence",
          ...data.agents.map((agent, index) =>
            [
              index + 1,
              `"${agent.nom} ${agent.prenom}"`,
              `"${agent.bureau || "-"}"`,
              `"${agent.service || "-"}"`,
              Math.round(agent.total_hours),
              Math.round(agent.overtime_hours),
              Math.round(agent.presence_rate),
            ].join(",")
          ),
        ].join("\n");

        const blob = new Blob([csv], { type: "text/csv" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = "performances_agents.csv";
        a.click();
        URL.revokeObjectURL(url);
      })
      .catch((error) => console.error("Erreur lors de l'exportation:", error));
  });

  // Charger les données initiales
  loadData();
}
