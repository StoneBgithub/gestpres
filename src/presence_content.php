<div class="bg-gray-50 p-6">
  <!-- En-tête avec titre et toggle pour filtrer arrivées/départs -->
  <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">

    
    <!-- Filtres rapides -->
    <div class="flex flex-wrap gap-2">
      <button id="toggle-all" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">Tous</button>
      <button id="toggle-arrivals" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Arrivées</button>
      <button id="toggle-departures" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Départs</button>
      
      <!-- Bouton filtres avancés -->
      <a href="#" id="toggle-filters-btn" class="px-3 py-1.5 bg-white border border-gray-200 text-blue-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path id="filter-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Filtres avancés
      </a>
    </div>
  </div>
  
  <!-- Section des filtres avancés (masquée par défaut) -->
  <div id="advanced-filters" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-6" style="display: none;">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Sélection de date -->
      <div>
        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
        <input type="date" id="date" value="2025-04-05" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
      </div>
      
      <!-- Sélection d'horaire -->
      <div>
        <label for="time-range" class="block text-sm font-medium text-gray-700 mb-1">Plage horaire</label>
        <select id="time-range" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
          <option value="all" selected>Toute la journée</option>
          <option value="morning">Matin (8h-12h)</option>
          <option value="afternoon">Après-midi (12h-18h)</option>
          <option value="custom">Personnalisé...</option>
        </select>
      </div>
      
      <!-- Sélection de statut -->
      <div>
        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
        <select id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
          <option value="all" selected>Tous les statuts</option>
          <option value="on-time">À l'heure</option>
          <option value="late">En retard</option>
          <option value="early">Départ anticipé</option>
        </select>
      </div>
    </div>
    
    <!-- Filtres supplémentaires -->
    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
      <div>
        <label for="service" class="block text-sm font-medium text-gray-700 mb-1">Service</label>
        <select id="service" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
          <option value="all" selected>Tous les services</option>
          <option value="tech">Technique</option>
          <option value="hr">Ressources Humaines</option>
          <option value="marketing">Marketing</option>
          <option value="finance">Finance</option>
        </select>
      </div>
      <div>
        <label for="bureau" class="block text-sm font-medium text-gray-700 mb-1">Bureau</label>
        <select id="bureau" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
          <option value="all" selected>Tous les bureaux</option>
          <option value="paris">Paris</option>
          <option value="lyon">Lyon</option>
          <option value="marseille">Marseille</option>
          <option value="bordeaux">Bordeaux</option>
        </select>
      </div>
      <div>
        <label for="employee" class="block text-sm font-medium text-gray-700 mb-1">Employé</label>
        <select id="employee" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3" data-search="true">
          <option value="all" selected>Tous les employés</option>
          <option value="jd">Jean Dupont</option>
          <option value="ml">Marie Laforêt</option>
          <option value="pt">Pierre Tremblay</option>
          <option value="cp">Claire Pelletier</option>
          <option value="lb">Laurent Berger</option>
          <option value="sm">Sophia Martin</option>
          <option value="td">Thomas Dubois</option>
        </select>
      </div>
    </div>
    
    <!-- Boutons d'action -->
    <div class="mt-6 flex justify-end space-x-3">
      <button id="reset-filters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Réinitialiser</button>
      <button id="apply-filters" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">Appliquer les filtres</button>
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
          <p class="text-gray-500 text-sm">Total enregistés</p>
          <h3 class="text-2xl font-bold">78</h3>
        </div>
      </div>
    </div>
    
    <!-- Carte statistique 2 -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Arrivées</p>
          <h3 class="text-2xl font-bold">45</h3>
        </div>
      </div>
    </div>
    
    <!-- Carte statistique 3 -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Départs</p>
          <h3 class="text-2xl font-bold">33</h3>
        </div>
      </div>
    </div>
    
    <!-- Carte statistique 4 -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-yellow-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Retards</p>
          <h3 class="text-2xl font-bold">5</h3>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Tableau des présences -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100">
      <h2 class="text-lg font-semibold">Liste des présences</h2>
    </div>
    
    <!-- Tableau -->
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead class="bg-gray-50 text-gray-700 text-sm uppercase font-semibold">
          <tr>
            <th class="px-6 py-4">Photo</th>
            <th class="px-6 py-4">Nom Prénom</th>
            <th class="px-6 py-4">Service</th>
            <th class="px-6 py-4">Bureau</th>
            <th class="px-6 py-4">Date</th>
            <th class="px-6 py-4">Heure</th>
            <th class="px-6 py-4">Statut</th>
            <th class="px-6 py-4">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <!-- Rangée 1 - Arrivée -->
          <tr class="hover:bg-gray-50 text-sm entry-row arrival">
            <td class="px-6 py-4">
              <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="text-blue-600 font-medium">JD</span>
              </div>
            </td>
            <td class="px-6 py-4 font-medium">Jean Dupont</td>
            <td class="px-6 py-4">Technique</td>
            <td class="px-6 py-4">Paris</td>
            <td class="px-6 py-4">05/04/2025</td>
            <td class="px-6 py-4">08:45</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
                Arrivée
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button class="text-blue-600 hover:text-blue-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button class="text-gray-600 hover:text-gray-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          
          <!-- Rangée 2 - Départ -->
          <tr class="hover:bg-gray-50 text-sm entry-row departure">
            <td class="px-6 py-4">
              <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                <span class="text-green-600 font-medium">ML</span>
              </div>
            </td>
            <td class="px-6 py-4 font-medium">Marie Laforêt</td>
            <td class="px-6 py-4">Ressources Humaines</td>
            <td class="px-6 py-4">Paris</td>
            <td class="px-6 py-4">05/04/2025</td>
            <td class="px-6 py-4">17:30</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Départ
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button class="text-blue-600 hover:text-blue-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button class="text-gray-600 hover:text-gray-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          
          <!-- Rangée 3 - Arrivée -->
          <tr class="hover:bg-gray-50 text-sm entry-row arrival">
            <td class="px-6 py-4">
              <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <span class="text-red-600 font-medium">PT</span>
              </div>
            </td>
            <td class="px-6 py-4 font-medium">Pierre Tremblay</td>
            <td class="px-6 py-4">Finance</td>
            <td class="px-6 py-4">Lyon</td>
            <td class="px-6 py-4">05/04/2025</td>
            <td class="px-6 py-4">09:15</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
                Arrivée (Retard)
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button class="text-blue-600 hover:text-blue-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button class="text-gray-600 hover:text-gray-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          
          <!-- Rangée 4 - Départ -->
          <tr class="hover:bg-gray-50 text-sm entry-row departure">
            <td class="px-6 py-4">
              <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                <span class="text-purple-600 font-medium">CP</span>
              </div>
            </td>
            <td class="px-6 py-4 font-medium">Claire Pelletier</td>
            <td class="px-6 py-4">Marketing</td>
            <td class="px-6 py-4">Paris</td>
            <td class="px-6 py-4">05/04/2025</td>
            <td class="px-6 py-4">16:30</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Départ anticipé
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button class="text-blue-600 hover:text-blue-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button class="text-gray-600 hover:text-gray-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          
          <!-- Rangée 5 - Arrivée -->
          <tr class="hover:bg-gray-50 text-sm entry-row arrival">
            <td class="px-6 py-4">
              <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                <span class="text-yellow-600 font-medium">LB</span>
              </div>
            </td>
            <td class="px-6 py-4 font-medium">Laurent Berger</td>
            <td class="px-6 py-4">Technique</td>
            <td class="px-6 py-4">Marseille</td>
            <td class="px-6 py-4">05/04/2025</td>
            <td class="px-6 py-4">08:30</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
                Arrivée
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button class="text-blue-600 hover:text-blue-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button class="text-gray-600 hover:text-gray-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
</tbody>
</table>
  </div>
    </div>