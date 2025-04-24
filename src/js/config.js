// config.js - Configuration centralisée pour l'application

// Icônes par service
export const serviceIcons = {
  Informatique: "fa-laptop-code",
  "Ressources Humaines": "fa-users",
  Comptabilité: "fa-calculator",
  Marketing: "fa-chart-line",
  Direction: "fa-star",
};

// Titres complets pour chaque page
export const pageTitles = {
  dashboard_content: "Tableau de Bord",
  agents_content: "Gestion des Agents",
  presence_content: "Gestion de Présence",
  absences_content: "Gestion d'Absence",
};

// Configuration des graphiques
export const chartConfig = {
  defaultPeriod: "weekly",
  colors: {
    arrival: "rgba(59, 130, 246, 0.6)", // Original blue
    departure: "rgba(239, 68, 68, 0.6)", // Original red
  },
};

// Utilitaires partagés
export const utils = {
  // Fonction pour activer un bouton et désactiver les autres
  setActiveButton(activeBtn, inactiveBtns) {
    activeBtn.classList.add("custom-btn", "text-white");
    activeBtn.classList.remove("bg-gray-200", "text-gray-700");

    inactiveBtns.forEach((btn) => {
      if (btn) {
        btn.classList.add("bg-gray-200", "text-gray-700");
        btn.classList.remove("custom-btn", "text-white");
      }
    });
  },

  // Fonction utilitaire pour charger un script dynamiquement
  loadScript(src, callback, isPageSpecific = false) {
    const script = document.createElement("script");
    script.src = src;
    if (isPageSpecific) script.className = "page-specific";
    if (callback) script.onload = callback;
    document.body.appendChild(script);
    return script;
  },

  // Obtenir la page courante depuis l'URL
  getCurrentPage() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get("page") || "dashboard_content";
  },
};

// Gestionnaire d'événements centralisé
export const eventBus = {
  events: {},

  subscribe(event, callback) {
    if (!this.events[event]) {
      this.events[event] = [];
    }
    this.events[event].push(callback);
    return () => this.unsubscribe(event, callback);
  },

  unsubscribe(event, callback) {
    if (this.events[event]) {
      this.events[event] = this.events[event].filter((cb) => cb !== callback);
    }
  },

  publish(event, data) {
    if (this.events[event]) {
      this.events[event].forEach((callback) => callback(data));
    }
  },

  clear(event) {
    if (event) {
      delete this.events[event];
    } else {
      this.events = {};
    }
  },
};
