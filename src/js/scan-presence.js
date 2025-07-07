document.addEventListener("DOMContentLoaded", function () {
  // Initialisation de Select2 avec placeholders
  $("#service").select2({
    placeholder: "Rechercher un service",
    allowClear: true,
  });
  $("#bureau").select2({
    placeholder: "Rechercher un bureau",
    allowClear: true,
  });
  $("#agent").select2({
    placeholder: "Rechercher un agent (nom et prénom)",
    allowClear: true,
  });

  function loadServices() {
    return fetch("services.php")
      .then((res) => {
        if (!res.ok) throw new Error(`Erreur HTTP ${res.status}`);
        return res.json();
      })
      .then((data) => {
        if (data.error) {
          showErrorModal(data.error || "Erreur lors du chargement des services.");
          return;
        }
        const serviceSelect = $("#service");
        serviceSelect
          .empty()
          .append('<option value="">Rechercher un service</option>');
        data.forEach((s) =>
          serviceSelect.append(`<option value="${s.id}">${s.name}</option>`)
        );
        serviceSelect.trigger("change.select2");
      })
      .catch((error) => {
        console.error("Erreur loadServices:", error);
        showErrorModal(
          "Impossible de charger les services. Vérifiez votre connexion ou contactez l'administrateur."
        );
      });
  }

  function loadBureaux(serviceId) {
    return fetch(`bureaux.php?service_id=${serviceId}`)
      .then((res) => {
        if (!res.ok) throw new Error(`Erreur HTTP ${res.status}`);
        return res.json();
      })
      .then((data) => {
        if (data.error) {
          showErrorModal(data.error || "Erreur lors du chargement des bureaux.");
          return;
        }
        const bureauSelect = $("#bureau");
        bureauSelect
          .prop("disabled", false)
          .empty()
          .append('<option value="">Rechercher un bureau</option>');
        data.forEach((b) =>
          bureauSelect.append(`<option value="${b.id}">${b.name}</option>`)
        );
        bureauSelect.trigger("change.select2");
      })
      .catch((error) => {
        console.error("Erreur loadBureaux:", error);
        showErrorModal("Impossible de charger les bureaux.");
      });
  }

  function loadAgents(serviceId = "", bureauId = "") {
    let url = "agents.php?";
    if (serviceId) url += `service_id=${serviceId}&`;
    if (bureauId) url += `bureau_id=${bureauId}`;
    return fetch(url)
      .then((res) => {
        if (!res.ok) throw new Error(`Erreur HTTP ${res.status}`);
        return res.json();
      })
      .then((data) => {
        if (data.error) {
          showErrorModal(data.error || "Erreur lors du chargement des agents.");
          return;
        }
        const agentSelect = $("#agent");
        agentSelect
          .empty()
          .append('<option value="">Rechercher un agent (nom et prénom)</option>');
        data.forEach((a) =>
          agentSelect.append(
            `<option value="${a.id}" data-service="${a.service_id}" data-bureau="${a.bureau_id}">${a.nom} ${a.prenom}</option>`
          )
        );
        agentSelect.prop("disabled", false).trigger("change.select2");
      })
      .catch((error) => {
        console.error("Erreur loadAgents:", error);
        showErrorModal("Impossible de charger les agents.");
      });
  }

  // Initialisation
  loadServices();
  loadAgents(); // Charger tous les agents pour la sélection directe

  // Événement : changement du service
  $("#service").on("change", function () {
    const serviceId = $(this).val();
    $("#bureau")
      .prop("disabled", true)
      .empty()
      .append('<option value="">Rechercher un bureau</option>')
      .trigger("change.select2");
    $("#agent")
      .prop("disabled", true)
      .empty()
      .append('<option value="">Rechercher un agent (nom et prénom)</option>')
      .trigger("change.select2");
    if (serviceId) {
      loadBureaux(serviceId);
      loadAgents(serviceId);
    }
  });

  // Événement : changement du bureau
  $("#bureau").on("change", function () {
    const bureauId = $(this).val();
    const serviceId = $("#service").val();
    $("#agent")
      .prop("disabled", true)
      .empty()
      .append('<option value="">Rechercher un agent (nom et prénom)</option>')
      .trigger("change.select2");
    if (bureauId) {
      loadAgents(serviceId, bureauId);
    } else if (serviceId) {
      loadAgents(serviceId);
    }
  });

  // Événement : changement de l'agent
  $("#agent").on("change", async function () {
    const selected = $(this).find("option:selected");
    const serviceId = selected.data("service");
    const bureauId = selected.data("bureau");
    if (serviceId && bureauId) {
      // Mettre à jour #service sans déclencher l'événement change
      $("#service").val(serviceId).trigger("change.select2");
      // Charger les bureaux et attendre que ce soit terminé
      await loadBureaux(serviceId);
      // Sélectionner le bureau après que les options sont chargées
      $("#bureau").val(bureauId).trigger("change.select2");
    }
  });
});