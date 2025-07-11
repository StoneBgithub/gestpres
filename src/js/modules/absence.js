// absence.js
import { eventBus } from "../config.js";

let absences = [];
let agents = [];
let bureaux = [];
let typesAbsences = [];
let statutsAbsences = [];

export function init() {
  console.log("Initialisation du module de gestion des absences");
  loadData();
  setupListeners();
  setupFilters();
  checkAndShowMessageModal();
}

function loadData() {
  absences = parseJsonFromElement("absencesDatas");
  agents = parseJsonFromElement("agentsDatas");
  bureaux = parseJsonFromElement("bureauxDatas");
  typesAbsences = parseJsonFromElement("typesAbsencesData");
  statutsAbsences = parseJsonFromElement("statutsAbsencesData");
}

function parseJsonFromElement(id) {
  const el = document.getElementById(id);
  if (el) {
    try {
      return JSON.parse(el.textContent);
    } catch (e) {
      console.error(`Erreur de parsing JSON pour ${id}`, e);
    }
  }
  return [];
}

function checkAndShowMessageModal() {
  const messageModal = document.getElementById("messageModal");
  if (messageModal && messageModal.dataset.messages) {
    try {
      const messages = JSON.parse(messageModal.dataset.messages);
      if ((messages.success && messages.success.length > 0) || (messages.errors && messages.errors.length > 0)) {
        showModal("messageModal");
      }
    } catch (e) {
      console.error("Erreur lors du parsing de data-messages:", e);
    }
  }
}

function setupListeners() {
  document.body.addEventListener("click", function (e) {
    const target = e.target;

    if (target.matches(".add-absence-btn")) {
      openAbsenceModal();
    }
    if (target.matches(".edit-absence-btn")) {
      const id = target.getAttribute("data-id");
      editAbsence(id);
    }
    if (target.matches(".delete-absence-btn")) {
      const id = target.getAttribute("data-id");
      confirmDeleteAbsence(id);
    }
  });

  const form = document.getElementById("absenceForm");
  if (form) {
    form.addEventListener("submit", submitAbsenceForm);
  }
}

function openAbsenceModal() {
  const form = document.getElementById("absenceForm");
  form.reset();
  form.querySelector("#absence_id").value = "";
  form.querySelector("#form_action").value = "add";
  showModal("absenceModal");
}

function editAbsence(id) {
  const absence = absences.find(a => a.id == id);
  if (!absence) return;

  const form = document.getElementById("absenceForm");
  form.querySelector("#absence_id").value = absence.id;
  form.querySelector("#form_action").value = "update";
  form.querySelector("#agent_select").value = absence.agent_id;
  form.querySelector("#type_select").value = absence.type;
  form.querySelector("#statut_select").value = absence.statut;
  form.querySelector("#date_debut").value = absence.date_debut;
  form.querySelector("#date_fin").value = absence.date_fin;

  showModal("absenceModal");
}

function confirmDeleteAbsence(id) {
  const confirmBtn = document.getElementById("confirmDeleteAbsenceBtn");
  confirmBtn.setAttribute("data-id", id);
  showModal("deleteModal");

  confirmBtn.onclick = () => {
    window.location.href = `?page=absence_content&action=delete&id=${id}`;
  };
}

function showModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove("hidden");
  const content = document.getElementById(`${id}Content`);
  if (content) {
    content.classList.add("scale-100", "opacity-100");
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.add("hidden");
}

function submitAbsenceForm(e) {
  e.preventDefault();
  const form = e.target;
  const data = new FormData(form);

  fetch(form.action, {
    method: "POST",
    body: data,
    headers: {
      "X-Requested-With": "XMLHttpRequest"
    }
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        closeModal("absenceModal");
        eventBus.publish("absence:updated");
        alert("Succès : " + (data.messages.success || ""));
      } else {
        alert("Erreur : " + (data.messages.errors || ""));
      }
    })
    .catch(err => {
      console.error(err);
      alert("Erreur serveur");
    });
}

function setupFilters() {
  const searchInput = document.getElementById("search_absence");
  const typeSelect = document.getElementById("filter_type");
  const statutSelect = document.getElementById("filter_statut");

  [searchInput, typeSelect, statutSelect].forEach(el => {
    if (el) el.addEventListener("input", filterAbsences);
  });

  filterAbsences();
}

function filterAbsences() {
  const search = document.getElementById("search_absence").value.toLowerCase();
  const type = document.getElementById("filter_type").value;
  const statut = document.getElementById("filter_statut").value;
  const tbody = document.querySelector("#absenceTable tbody");

  const filtered = absences.filter(abs => {
    const agent = agents.find(a => a.id == abs.agent_id);
    const nom = (agent?.nom_prenom || '').toLowerCase();
    return (
      (!search || nom.includes(search)) &&
      (!type || abs.type == type) &&
      (!statut || abs.statut == statut)
    );
  });

  tbody.innerHTML = filtered.length ? filtered.map(renderAbsenceRow).join('') : `
    <tr><td colspan="6" class="text-center py-4 text-gray-500">Aucune absence trouvée</td></tr>
  `;
}

function renderAbsenceRow(abs) {
  const agent = agents.find(a => a.id == abs.agent_id);
  return `
    <tr>
      <td class="px-4 py-2">${agent?.nom_prenom || "Inconnu"}</td>
      <td class="px-4 py-2">${abs.type}</td>
      <td class="px-4 py-2">${abs.date_debut}</td>
      <td class="px-4 py-2">${abs.date_fin}</td>
      <td class="px-4 py-2">${abs.statut}</td>
      <td class="px-4 py-2 text-right">
        <button class="edit-absence-btn text-indigo-600" data-id="${abs.id}"><i class="fas fa-edit"></i></button>
        <button class="delete-absence-btn text-red-600 ml-2" data-id="${abs.id}"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `;
}

eventBus.subscribe("absence:updated", () => {
  loadData();
  filterAbsences();
});
