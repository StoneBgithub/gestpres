import { eventBus } from "../config.js";

export function initHistorique() {
  console.log("Initialisation du module historique...");
  initModals();
  setupEventListeners();
}


