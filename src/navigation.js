// navigation.js - Gestion de la navigation sans rechargement de page
import { pageTitles, utils, eventBus } from "./config.js";

// Module d'initialisation de la navigation
export function initNavigation() {
  console.log("Initialisation de la navigation");

  // Sélectionner tous les liens du menu
  const menuLinks = document.querySelectorAll(".sidebar-menu");

  // Ajouter un gestionnaire d'événements à chaque lien
  menuLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const page = this.getAttribute("data-page");
      history.pushState({ page: page }, "", `dashboard.php?page=${page}`);
      loadContent(page);
      updatePageTitle(page);
      updateActiveMenu(page);
    });
  });

  // Gérer les événements de navigation du navigateur (retour/avance)
  window.addEventListener("popstate", function (e) {
    if (e.state && e.state.page) {
      loadContent(e.state.page);
      updatePageTitle(e.state.page);
      updateActiveMenu(e.state.page);
    }
  });

  // Initialiser les événements sur le contenu initial
  initContentEvents();

  // Initialiser la page courante en fonction de l'URL
  const currentPage = utils.getCurrentPage();
  updateActiveMenu(currentPage);

  // Informer les autres modules que la navigation est prête
  eventBus.publish("navigation:ready", { currentPage });

  // Gérer la mise à jour des notifications
  window.addEventListener("notifications:updated", () => {
    updateNotificationBadge();
  });
}

// Fonction pour mettre à jour le badge de notification
function updateNotificationBadge() {
  const badgeContainer = document.getElementById("notification-container");
  if (!badgeContainer) return;

  fetch("get_notifications.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Erreur lors de la récupération des notifications:", data.error);
        return;
      }
      const badge = document.getElementById("notification-badge");
      if (badge) badge.remove();
      if (data.nbnouvelles > 0) {
        const newBadge = document.createElement("span");
        newBadge.id = "notification-badge";
        newBadge.className = "badge-rond";
        newBadge.setAttribute("aria-label", `Nouvelles actions non vues : ${data.nbnouvelles}`);
        newBadge.setAttribute("aria-live", "polite");
        newBadge.textContent = data.nbnouvelles;
        badgeContainer.appendChild(newBadge);
      }
    })
    .catch((error) => {
      console.error("Erreur lors de la mise à jour du badge:", error);
    });
}

// Fonction pour charger le contenu via AJAX
function loadContent(page) {
  const mainContent = document.getElementById("main-content");
  mainContent.style.opacity = "0.5";

  fetch(`${page}.php`)
    .then((response) => response.text())
    .then((html) => {
      mainContent.innerHTML = html;
      setTimeout(() => {
        mainContent.style.opacity = "1";
      }, 100);

      // Informer les autres modules que le contenu a changé
      eventBus.publish("page:loaded", { page });

      // Initialiser les événements sur le nouveau contenu
      initContentEvents();
    })
    .catch((error) => {
      console.error("Erreur lors du chargement du contenu:", error);
      mainContent.style.opacity = "1";
    });
}

// Fonction pour mettre à jour le titre de la page
function updatePageTitle(page) {
  const pageTitle = document.getElementById("page-title");
  if (pageTitle) {
    pageTitle.textContent = pageTitles[page] || page.replace("_content", "");
  }
}

// Fonction pour mettre à jour l'état actif du menu
function updateActiveMenu(currentPage) {
  const menuLinks = document.querySelectorAll(".sidebar-menu");

  menuLinks.forEach((link) => {
    link.classList.remove(
      "active-menu",
      "gradient-bg",
      "text-white",
      "font-bold"
    );
    link.classList.add("text-congo-black");
  });

  const activeLink = document.querySelector(
    `.sidebar-menu[data-page="${currentPage}"]`
  );

  if (activeLink) {
    activeLink.classList.remove("text-congo-black");
    activeLink.classList.add(
      "active-menu",
      "gradient-bg",
      "text-white",
      "font-bold"
    );
  }
}

// Fonction pour initialiser les événements sur le contenu chargé dynamiquement
function initContentEvents() {
  const contentLinks = document.querySelectorAll(
    '#main-content a[href*="dashboard.php?page="]'
  );
  contentLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const url = new URL(this.href);
      const page = url.searchParams.get("page");
      if (page) {
        history.pushState({ page: page }, "", `dashboard.php?page=${page}`);
        loadContent(page);
        updatePageTitle(page);
        updateActiveMenu(page);
      }
    });
  });
}

// S'abonner aux événements spécifiques aux différentes pages
eventBus.subscribe("page:loaded", ({ page }) => {
  console.log(`Page chargée: ${page}`);

  // Supprimer les scripts spécifiques à la page précédente
  const oldScripts = document.querySelectorAll("script.page-specific");
  oldScripts.forEach((script) => script.remove());

  // Charger les modules spécifiques à la page actuelle
  import(`./modules/${page.replace("_content", "")}.js`)
    .then((module) => {
      if (module.init) {
        module.init();
      }
    })
    .catch((err) => {
      console.log(`Pas de module spécifique pour ${page}`);
    });
});