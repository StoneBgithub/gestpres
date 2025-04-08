import { initNavigation } from "./navigation.js";
import { utils, eventBus } from "./config.js";

// Fonction principale d'initialisation
function initApp() {
  console.log("Initialisation de l'application");

  // Initialiser la navigation
  initNavigation();

  // Charger le module spécifique à la page courante
  const currentPage = utils.getCurrentPage();
  loadPageModule(currentPage);

  // Écouter les changements de page
  eventBus.subscribe("page:loaded", ({ page }) => {
    loadPageModule(page);
  });
}

// Charger dynamiquement le module correspondant à la page
function loadPageModule(page) {
  const moduleName = page.replace("_content", "");
  import(`./modules/${moduleName}.js`)
    .then((module) => {
      if (module.init) {
        module.init();
        console.log(`Module ${moduleName} chargé et initialisé`);
      }
    })
    .catch((err) => {
      console.log(
        `Aucun module spécifique pour ${page} ou erreur de chargement:`,
        err
      );
    });
}

// Lancer l'application au chargement du DOM
document.addEventListener("DOMContentLoaded", initApp);
