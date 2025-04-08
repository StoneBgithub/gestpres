<aside class="w-64 bg-white shadow-md">
    <div class="h-full flex flex-col">
        <!-- Logo -->
        <div class="gradient-bg p-6">
            <h2 class="text-xl font-bold text-white">DSI</h2>
            <p class="text-blue-100 text-sm">Gestion de Présence</p>
        </div>
        
        <!-- Menu de navigation -->
        <nav class="flex-1 py-4 px-2">
            <ul class="space-y-1">
                <?php
                $current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard_content';
                $menu_items = [
                    'dashboard_content' => ['title' => 'Tableau de Bord', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                    'agents_content' => ['title' => 'Gestion des Agents', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                    'presence_content' => ['title' => 'Gestion de Présence', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    'absences_content' => ['title' => 'Gestion d\'Absence', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z']
                ];

                foreach ($menu_items as $page_name => $item) {
                    $active_class = ($current_page === $page_name) ? 'active-menu' : '';
                    echo '<li>';
                    echo '<a href="dashboard.php?page=' . $page_name . '" class="sidebar-menu flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 transition-colors ' . $active_class . '" data-page="' . $page_name . '">';
                    echo '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
                    echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . $item['icon'] . '" />';
                    echo '</svg>';
                    echo '<span>' . $item['title'] . '</span>';
                    echo '</a>';
                    echo '</li>';
                }
                ?>
            </ul>
        </nav>
        
        <!-- Footer du sidebar -->
        <div class="p-4 mt-auto border-t border-gray-200">
            <a href="logout.php" class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Déconnexion</span>
            </a>
        </div>
    </div>
</aside>