<div class="bg-gray-50 p-6">
  <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <h1 class="text-2xl font-bold text-gray-700">Statistiques : <span id="current-filter">Aujourd'hui</span></h1>
    
    <div class="flex flex-wrap gap-2">
      <button id="filter-day" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">Aujourd'hui</button>
      <button id="filter-week" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cette semaine</button>
      <button id="filter-month" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Ce mois</button>
      <button id="filter-year" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Cette année</button>
      
      <a href="#" class="toggle-filters-btn px-3 py-1.5 bg-white border border-gray-200 text-blue-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path class="filter-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Filtres avancés
      </a>
    </div>
  </div>
  
  <div class="advanced-filters bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-6" style="display: none;">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div>
        <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Année</label>
        <select id="year" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
          <option value="2025" selected>2025</option>
          <option value="2024">2024</option>
          <option value="2023">2023</option>
        </select>
      </div>
      
      <div>
        <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Mois</label>
        <select id="month" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
          <option value="all">Tous les mois</option>
          <option value="04" selected>Avril</option>
          <option value="03">Mars</option>
          <option value="02">Février</option>
          <option value="01">Janvier</option>
          <option value="custom">Personnalisé...</option>
        </select>
      </div>
      
      <div>
        <label for="week" class="block text-sm font-medium text-gray-700 mb-1">Semaine</label>
        <select id="week" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
          <option value="all">Toutes les semaines</option>
          <option value="14" selected>Semaine 14 (1-7 avr.)</option>
          <option value="13">Semaine 13 (25-31 mars)</option>
          <option value="12">Semaine 12 (18-24 mars)</option>
          <option value="custom">Personnalisé...</option>
        </select>
      </div>
    </div>
    
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
          <option value="present">Présent</option>
          <option value="absent">Absent</option>
          <option value="late">En retard</option>
          <option value="excused">Absence justifiée</option>
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
    
    <div class="mt-6 flex justify-end space-x-3">
      <button id="reset-filters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Réinitialiser</button>
      <button id="apply-filters" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">Appliquer les filtres</button>
    </div>
  </div>
  
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Agents Présents</p>
          <h3 class="text-2xl font-bold">45/50</h3>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Absences</p>
          <h3 class="text-2xl font-bold">5</h3>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Taux Présence</p>
          <h3 class="text-2xl font-bold">90%</h3>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex items-center">
        <div class="w-12 h-12 rounded-lg bg-yellow-100 flex items-center justify-center mr-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Retards</p>
          <h3 class="text-2xl font-bold">3</h3>
        </div>
      </div>
    </div>
  </div>
  
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Suivi des Présences</h2>
        <div class="flex space-x-2">
          <button id="chart-view-day" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-200 transition">Jour</button>
          <button id="chart-view-week" class="px-3 py-1 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 transition">Semaine</button>
          <button id="chart-view-month" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-200 transition">Mois</button>
        </div>
      </div>
      <div class="h-64">
        <canvas id="presence-chart"></canvas>
      </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Activités Récentes</h2>
        <button class="text-blue-600 text-sm hover:text-blue-800 transition">Voir tout</button>
      </div>
      <div class="space-y-3">
        <div class="flex items-center py-2 border-b border-gray-100">
          <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
            <span class="text-blue-600 font-medium">JD</span>
          </div>
          <div>
            <p class="text-gray-800">Jean Dupont a enregistré son arrivée</p>
            <p class="text-gray-500 text-sm">Il y a 10 minutes</p>
          </div>
        </div>
        <div class="flex items-center py-2 border-b border-gray-100">
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
            <span class="text-green-600 font-medium">ML</span>
          </div>
          <div>
            <p class="text-gray-800">Marie Laforêt a justifié son absence</p>
            <p class="text-gray-500 text-sm">Il y a 25 minutes</p>
          </div>
        </div>
        <div class="flex items-center py-2">
          <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
            <span class="text-red-600 font-medium">PT</span>
          </div>
          <div>
            <p class="text-gray-800">Pierre Tremblay est en retard</p>
            <p class="text-gray-500 text-sm">Il y a 45 minutes</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  
</div>
