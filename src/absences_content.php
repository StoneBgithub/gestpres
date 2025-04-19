<div class="bg-gray-50 p-6">
  <!-- En-tête avec bouton d'ajout d'absence -->
  <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div class="flex items-center space-x-3">
      <!-- Barre de recherche avec icône correctement placée -->
      <div class="relative">
        <input type="text" id="search-absences" placeholder="Rechercher un agent..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>
      
      <!-- Bouton ajouter absence qui ouvre le modal -->
      <button id="open-modal-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Ajouter une absence
      </button>
    </div>
  </div>
  
  <!-- Filtres rapides pour les types d'absence -->
  <div class="mb-6">
    <div class="flex flex-wrap gap-2">
      <button id="filter-all" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">Tous</button>
      <button id="filter-vacation" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Congés payés</button>
      <button id="filter-sick" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Maladie</button>
      <button id="filter-special" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Congés spéciaux</button>
      <button id="filter-unpaid" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Sans solde</button>
      <button id="filter-remote" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Télétravail</button>
    </div>
  </div>
  
  <!-- Cartes statistiques -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Carte statistique 1 -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Total absences</p>
          <h3 class="text-2xl font-bold">32</h3>
        </div>
      </div>
    </div>
    
    <!-- Carte statistique 2 -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Congés payés</p>
          <h3 class="text-2xl font-bold">15</h3>
        </div>
      </div>
    </div>
    
    <!-- Carte statistique 3 -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Maladie</p>
          <h3 class="text-2xl font-bold">8</h3>
        </div>
      </div>
    </div>
    
    <!-- Carte statistique 4 -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-yellow-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Télétravail</p>
          <h3 class="text-2xl font-bold">9</h3>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Tableau des absences -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
      <h2 class="text-lg font-semibold">Liste des absences</h2>
      <div class="flex items-center space-x-2">
        <span class="text-gray-500 text-sm">Afficher:</span>
        <select id="rows-per-page" class="rounded border-gray-300 text-sm py-1">
          <option value="10">10 lignes</option>
          <option value="25">25 lignes</option>
          <option value="50">50 lignes</option>
          <option value="100">100 lignes</option>
        </select>
      </div>
    </div>
    
    <!-- Tableau -->
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead class="bg-gray-50 text-gray-700 text-sm uppercase font-semibold">
          <tr>
            <th class="px-6 py-4">Photo</th>
            <th class="px-6 py-4">Nom Prénom</th>
            <th class="px-6 py-4">Téléphone</th>
            <th class="px-6 py-4">Type d'absence</th>
            <th class="px-6 py-4">Date de début</th>
            <th class="px-6 py-4">Date de fin</th>
            <th class="px-6 py-4">Durée</th>
            <th class="px-6 py-4">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100" id="absences-table-body">
          <!-- Rangée 1 - Congés payés -->
          <tr class="hover:bg-gray-50 text-sm absence-row" data-type="vacation">
            <td class="px-6 py-4">
              <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="text-blue-600 font-medium">JD</span>
              </div>
            </td>
            <td class="px-6 py-4 font-medium">Jean Dupont</td>
            <td class="px-6 py-4">06 12 34 56 78</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Congés payés
              </span>
            </td>
            <td class="px-6 py-4">10/04/2025</td>
            <td class="px-6 py-4">17/04/2025</td>
            <td class="px-6 py-4">5 jours</td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button class="text-gray-600 hover:text-gray-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button class="text-red-600 hover:text-red-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          
          <!-- Rangée 2 - Maladie -->
          <tr class="hover:bg-gray-50 text-sm absence-row" data-type="sick">
            <td class="px-6 py-4">
              <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                <span class="text-green-600 font-medium">ML</span>
              </div>
            </td>
            <td class="px-6 py-4 font-medium">Marie Laforêt</td>
            <td class="px-6 py-4">07 65 43 21 98</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                Maladie
              </span>
            </td>
            <td class="px-6 py-4">08/04/2025</td>
            <td class="px-6 py-4">15/04/2025</td>
            <td class="px-6 py-4">6 jours</td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button class="text-gray-600 hover:text-gray-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button class="text-red-600 hover:text-red-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          
          <!-- Rangée 3 - Congés spéciaux -->
          <tr class="hover:bg-gray-50 text-sm absence-row" data-type="special">
            <td class="px-6 py-4">
              <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <span class="text-red-600 font-medium">PT</span>
              </div>
            </td>
            <td class="px-6 py-4 font-medium">Pierre Tremblay</td>
            <td class="px-6 py-4">06 87 65 43 21</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                Congés spéciaux
              </span>
            </td>
            <td class="px-6 py-4">12/04/2025</td>
            <td class="px-6 py-4">12/04/2025</td>
            <td class="px-6 py-4">1 jour</td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button class="text-gray-600 hover:text-gray-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button class="text-red-600 hover:text-red-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          
          <!-- Rangée 4 - Sans solde -->
          <tr class="hover:bg-gray-50 text-sm absence-row" data-type="unpaid">
            <td class="px-6 py-4">
              <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                <span class="text-purple-600 font-medium">CP</span>
              </div>
            </td>
            <td class="px-6 py-4 font-medium">Claire Pelletier</td>
            <td class="px-6 py-4">07 23 45 67 89</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                Sans solde
              </span>
            </td>
            <td class="px-6 py-4">20/04/2025</td>
            <td class="px-6 py-4">24/04/2025</td>
            <td class="px-6 py-4">5 jours</td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button class="text-gray-600 hover:text-gray-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button class="text-red-600 hover:text-red-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          
          <!-- Rangée 5 - Télétravail -->
          <tr class="hover:bg-gray-50 text-sm absence-row" data-type="remote">
            <td class="px-6 py-4">
              <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                <span class="text-yellow-600 font-medium">LB</span>
              </div>
            </td>
            <td class="px-6 py-4 font-medium">Laurent Berger</td>
            <td class="px-6 py-4">06 54 32 10 98</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                Télétravail
              </span>
            </td>
            <td class="px-6 py-4">11/04/2025</td>
            <td class="px-6 py-4">12/04/2025</td>
            <td class="px-6 py-4">2 jours</td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button class="text-gray-600 hover:text-gray-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button class="text-red-600 hover:text-red-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
   <!-- Pagination -->
    <div class="px-6 py-4 flex justify-between items-center border-t border-gray-100">
      <div class="text-sm text-gray-500">
        Affichage de <span class="font-medium">1</span> à <span class="font-medium">5</span> sur <span class="font-medium">32</span> résultats
      </div>
      
      <div class="flex space-x-1">
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-500 bg-white hover:bg-gray-50 text-sm disabled:opacity-50" disabled>
          Précédent
        </button>
        <button class="px-3 py-1 rounded border border-transparent text-white bg-blue-600 hover:bg-blue-700 text-sm">
          1
        </button>
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 text-sm">
          2
        </button>
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 text-sm">
          3
        </button>
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 text-sm">
          4
        </button>
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-500 bg-white hover:bg-gray-50 text-sm">
          Suivant
        </button>
      </div>
    </div>
  </div>
  
  <!-- Modal Ajout d'absence -->
<div id="add-absence-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
  <div class="bg-white rounded-xl shadow-lg max-w-md w-full mx-4"> <!-- Réduit de max-w-lg à max-w-md -->
    <div class="border-b border-gray-100 px-6 py-4 flex justify-between items-center">
      <h3 class="text-lg font-semibold">Ajouter une absence</h3>
      <button id="close-modal-btn" class="text-gray-500 hover:text-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    
    <div class="p-6">
      <form id="add-absence-form">
        <!-- Champ de recherche d'agent avec autocomplete -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-medium mb-2" for="agent-search">
            Agent
          </label>
          <div class="relative">
            <input type="text" id="agent-search" placeholder="Rechercher un agent..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <!-- Conteneur pour les suggestions -->
            <div id="agent-suggestions" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
              <ul class="py-1">
                <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Jean Dupont</li>
                <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Marie Laforêt</li>
                <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Pierre Tremblay</li>
                <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Claire Pelletier</li>
                <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Laurent Berger</li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Type d'absence -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-medium mb-2" for="absence-type">
            Type d'absence
          </label>
          <select id="absence-type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">Sélectionnez un type d'absence</option>
            <option value="vacation">Congés payés</option>
            <option value="sick">Maladie</option>
            <option value="special">Congés spéciaux</option>
            <option value="unpaid">Sans solde</option>
            <option value="remote">Télétravail</option>
          </select>
        </div>
        
        <!-- Dates de début et de fin -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-gray-700 text-sm font-medium mb-2" for="start-date">
              Date de début
            </label>
            <input type="date" id="start-date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-medium mb-2" for="end-date">
              Date de fin
            </label>
            <input type="date" id="end-date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          </div>
        </div>
        
        <!-- Photo -->
        <div class="mb-6">
          <label class="block text-gray-700 text-sm font-medium mb-2" for="attachment">
            Photo
          </label>
          <div class="flex items-center">
            <label class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
              </svg>
              <span class="text-sm text-gray-600">Ajouter une photo</span>
              <input id="attachment" type="file" accept="image/*" class="hidden"> <!-- Ajout de accept="image/*" pour limiter aux images -->
            </label>
            <span id="file-name" class="ml-3 text-sm text-gray-500"></span>
          </div>
        </div>
        
        <!-- Boutons d'action -->
        <div class="flex justify-end space-x-3">
          <button type="button" id="cancel-form-btn" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
            Annuler
          </button>
          <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
  
  <!-- Script JavaScript pour la fonctionnalité de base -->
  <script>
    // Ouvrir le modal
    document.getElementById('open-modal-btn').addEventListener('click', function() {
      document.getElementById('add-absence-modal').classList.remove('hidden');
    });
    
    // Fermer le modal (via le bouton X)
    document.getElementById('close-modal-btn').addEventListener('click', function() {
      document.getElementById('add-absence-modal').classList.add('hidden');
    });
    
    // Fermer le modal (via le bouton Annuler)
    document.getElementById('cancel-form-btn').addEventListener('click', function() {
      document.getElementById('add-absence-modal').classList.add('hidden');
    });
    
    // Afficher le nom du fichier sélectionné
    document.getElementById('attachment').addEventListener('change', function() {
      const fileName = this.files[0] ? this.files[0].name : '';
      document.getElementById('file-name').textContent = fileName;
    });
    
    // Afficher les suggestions d'agents
    document.getElementById('agent-search').addEventListener('focus', function() {
      document.getElementById('agent-suggestions').classList.remove('hidden');
    });
    
    // Filtrer les absences
    const filterButtons = document.querySelectorAll('[id^="filter-"]');
    filterButtons.forEach(button => {
      button.addEventListener('click', function() {
        // Réinitialiser tous les boutons
        filterButtons.forEach(btn => {
          btn.classList.remove('bg-blue-600', 'text-white');
          btn.classList.add('bg-gray-100', 'text-gray-700');
        });
        
        // Activer le bouton sélectionné
        this.classList.remove('bg-gray-100', 'text-gray-700');
        this.classList.add('bg-blue-600', 'text-white');
        
        // Filtrer les lignes du tableau
        const filterId = this.id.replace('filter-', '');
        const rows = document.querySelectorAll('.absence-row');
        
        rows.forEach(row => {
          if (filterId === 'all' || row.dataset.type === filterId) {
            row.classList.remove('hidden');
          } else {
            row.classList.add('hidden');
          }
        });
      });
    });
    
    // Validation du formulaire
    document.getElementById('add-absence-form').addEventListener('submit', function(e) {
      e.preventDefault();
      // Ajouter ici la logique pour traiter le formulaire
      alert('Absence ajoutée avec succès !');
      document.getElementById('add-absence-modal').classList.add('hidden');
      // Réinitialiser le formulaire
      this.reset();
      document.getElementById('file-name').textContent = '';
    });
  </script>