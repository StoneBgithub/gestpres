/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php", // Cible les fichiers PHP à la racine
    "./**/*.php", // Cible tous les fichiers PHP dans tous les sous-dossoirs
  ],
  theme: {
    extend: {
      colors: {
        // Couleurs primaires (couleurs principales de la marque)
        congo: {
          green: {
            dark: "#014023", // Vert sombre de l'armoirie
            DEFAULT: "#038C33", // Vert standard du drapeau
            light: "#5CBF15", // Vert clair
          },
          yellow: {
            pale: "#E9F2A0", // Jaune pâle
            DEFAULT: "#F2CE16", // Jaune standard
            dark: "#A6882E", // Jaune sombre
          },
          red: "#D91A1A", // Rouge
          black: "#0D0D0D", // Noir
        },
        // Couleurs pour l'interface utilisateur
        ui: {
          primary: "#038C33", // Action principale, boutons principaux
          secondary: "#F2CE16", // Actions secondaires
          accent: "#D91A1A", // Notifications, alertes, badges
          background: {
            light: "#FFFFFF", // Fond clair
            dark: "#0D0D0D", // Fond sombre
          },
          text: {
            dark: "#0D0D0D", // Texte sur fond clair
            light: "#FFFFFF", // Texte sur fond sombre
            muted: "#6B7280", // Texte secondaire
          },
        },
      },
      fontFamily: {
        sans: ["Inter", "sans-serif"],
        display: ["Montserrat", "sans-serif"],
      },
      borderRadius: {
        sm: "0.125rem",
        DEFAULT: "0.25rem",
        md: "0.375rem",
        lg: "0.5rem",
        xl: "1rem",
      },
      boxShadow: {
        sm: "0 1px 2px 0 rgba(0, 0, 0, 0.05)",
        DEFAULT:
          "0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)",
        md: "0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)",
        lg: "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)",
      },
    },
  },
  plugins: [],
};
