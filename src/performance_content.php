<?php
require_once 'db_connect.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Filtres et recherche -->
    <div class="bg-white p-6 rounded-xl shadow-sm mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div class="filter-container">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher un agent</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="search" class="focus:ring-congo-green focus:border-congo-green block w-full pl-10 sm:text-sm border-gray-300 rounded-md py-2 px-4 border" placeholder="Nom de l'agent">
                    </div>
                </div>
                <div>
                    <label for="bureau" class="block text-sm font-medium text-gray-700 mb-1">Bureau</label>
                    <select id="bureau" class="focus:ring-congo-green focus:border-congo-green block w-full sm:text-sm border-gray-300 rounded-md py-2 px-4 border">
                        <option value="">Tous les bureaux</option>
                        <option value="bureau-1">Bureau A</option>
                        <option value="bureau-2">Bureau B</option>
                        <option value="bureau-3">Bureau C</option>
                        <option value="bureau-4">Bureau D</option>
                    </select>
                </div>
                <div>
                    <label for="service" class="block text-sm font-medium text-gray-700 mb-1">Service</label>
                    <select id="service" class="focus:ring-congo-green focus:border-congo-green block w-full sm:text-sm border-gray-300 rounded-md py-2 px-4 border">
                        <option value="">Tous les services</option>
                        <option value="service-1">Ressources Humaines</option>
                        <option value="service-2">Comptabilité</option>
                        <option value="service-3">Informatique</option>
                        <option value="service-4">Marketing</option>
                        <option value="service-5">Commercial</option>
                    </select>
                </div>
            </div>
            <div>
                <button id="apply-filters" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-congo-green hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-congo-green">
                    <i class="fas fa-filter mr-2"></i> Appliquer les filtres
                </button>
            </div>
        </div>
    </div>

    <!-- Statistiques globales -->
    <div class="bg-white p-6 rounded-xl shadow-sm mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Statistiques globales</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="global-stats">
            <!-- Statistiques remplies dynamiquement via JS -->
        </div>
    </div>

    <!-- Classement des agents -->
    <div class="bg-white p-6 rounded-xl shadow-sm mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Classement des agents</h2>
            <div class="flex space-x-2">
                <button id="export-data" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50">
                    <i class="fas fa-download mr-1"></i> Exporter
                </button>
                <select id="period" class="border border-gray-300 rounded-md text-sm py-1 px-3">
                    <option value="month">Ce mois</option>
                    <option value="quarter">Ce trimestre</option>
                    <option value="year">Cette année</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rang</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bureau</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heures travaillées</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taux de présence</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="agents-ranking">
                    <!-- Données remplies dynamiquement via JS -->
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="flex justify-between items-center mt-4 border-t border-gray-200 pt-3">
            <div>
                <p class="text-sm text-gray-500" id="pagination-info"></p>
            </div>
            <div class="flex space-x-2" id="pagination-controls">
                <!-- Contrôles de pagination remplies dynamiquement via JS -->
            </div>
        </div>
    </div>
</div>

<style>
    .transition-all {
        transition: all 0.3s ease;
    }
    .badge-success {
        background-color: #10B981;
    }
    .badge-warning {
        background-color: #F59E0B;
    }
    .badge-danger {
        background-color: #EF4444;
    }
    .text-congo-green {
        color: #0F766E;
    }
    .bg-congo-green-light {
        background-color: #ECFDF5;
    }
    .border-congo-green {
        border-color: #0F766E;
    }
    .hover-congo-green:hover {
        background-color: #0F766E;
        color: white;
    }
    /* Correction de la disposition des filtres */
    .filter-container {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-end;
    }
    .filter-container > div {
        flex: 1;
        min-width: 200px;
    }
    @media (max-width: 768px) {
        .filter-container {
            flex-direction: column;
        }
        .filter-container > div {
            width: 100%;
        }
    }
</style>