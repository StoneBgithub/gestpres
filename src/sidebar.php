<!-- sidebar.php -->
<aside class="w-64 bg-white shadow-xl border-r border-gray-100">
    <div class="h-full flex flex-col">
        <!-- Logo Section -->
        <div class="gradient-bg p-6 flex items-center space-x-3">
            <img src="./public/logo.svg" alt="DSI Logo" class="h-12 w-12">
            <div>
                <h2 class="text-xl font-bold text-white font-display">DSI</h2>
                <p class="text-blue-100 text-sm font-body">Gestion de Présence</p>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="flex-1 py-4 px-2">
            <ul class="space-y-1">
                <?php
                // Utiliser le rôle stocké dans la session
                $role = $_SESSION['role'] ?? 'viewer'; // Par défaut, utiliser 'viewer' si le rôle n'est pas défini

                // Déterminer la page actuelle à partir du paramètre URL
                $current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard_content';
                
                // Définir les éléments du menu
                $menu_items = [
                    'dashboard_content' => [
                        'title' => 'Tableau de Bord',
                        'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'
                    ],
                    'agents_content' => [
                        'title' => 'Gestion des Agents',
                        'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'
                    ],
                    'presence_content' => [
                        'title' => 'Gestion de Présence',
                        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
                    ],
                  
                ];

                // Ajouter l'élément 'Performance des Agents' uniquement pour les admins
                if ($role === 'admin') {
                    $menu_items['performance_content'] = [
                        'title' => 'Performance Agents',
                        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'
                    ];
                }

                foreach ($menu_items as $page_name => $item) {
                    // Appliquer la classe active uniquement à la page actuelle
                    $active_class = ($current_page === $page_name) ? 'active-menu gradient-bg text-white font-bold' : 'text-congo-black';
                    echo '<li>';
                    echo '<a href="dashboard.php?page=' . htmlspecialchars($page_name) . '" class="sidebar-menu flex items-center px-4 py-3 rounded-xl hover:bg-congo-yellow-pale hover:text-congo-green-dark transition-all duration-300 card-shine ' . htmlspecialchars($active_class) . '" data-page="' . htmlspecialchars($page_name) . '">';
                    echo '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
                    echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . htmlspecialchars($item['icon']) . '" />';
                    echo '</svg>';
                    echo '<span class="font-medium font-body">' . htmlspecialchars($item['title']) . '</span>';
                    echo '</a>';
                    echo '</li>';
                }
                ?>
            </ul>
        </nav>
        
        <!-- Sidebar Footer -->
        <div class="p-4 mt-auto border-t border-gray-200">
            <a href="logout.php" class="flex items-center text-red-600 hover:bg-red-100 hover:text-red-800 transition-all duration-300 rounded-xl px-4 py-3 card-shine">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="font-medium font-body">Déconnexion</span>
            </a>
        </div>
    </div>
</aside>