/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php", // Cible les fichiers PHP à la racine
    "./**/*.php", // Cible tous les fichiers PHP dans tous les sous-dossiers
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};
