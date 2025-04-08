<?php
// Démarrer la session en début de fichier
session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Déterminer si c'est une requête AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Si c'est une requête AJAX, ne retourner que le contenu demandé
if ($isAjax) {
    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard_content';
    $valid_pages = ['dashboard_content', 'agents_content', 'presence_content', 'absences_content'];
    if (in_array($page, $valid_pages)) {
        include $page . '.php';
    } else {
        include 'dashboard_content.php';
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSI - Tableau de Bord</title>
    <link href="./assets/css/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .gradient-bg { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .content-transition { transition: all 0.3s ease; }
        .active-menu { background-color: #3b82f6; color: white; }
    </style>
</head>
<body>
    <div class="flex h-screen overflow-hidden">
        <!-- Inclusion du sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1 overflow-y-auto">
            <header class="bg-white shadow-sm">
                <div class="px-6 py-4 flex justify-between items-center">
                    <h1 id="page-title" class="text-2xl font-bold text-gray-800">
                        <?php
                        $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard_content';
                        $menu_items = [
                            'dashboard_content' => 'Tableau de Bord',
                            'agents_content' => 'Gestion des Agents',
                            'presence_content' => 'Gestion de Présence',
                            'absences_content' => 'Gestion d\'Absence'
                        ];
                        echo isset($menu_items[$page]) ? $menu_items[$page] : ucfirst(str_replace('_content', '', $page));
                        ?>
                    </h1>
                    <div class="flex items-center space-x-4">
                        <button class="text-gray-500 hover:text-gray-700 focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>
                        <div class="flex items-center">
                            <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="Photo de profil" class="h-8 w-8 rounded-full">
                            <span class="ml-2 text-gray-700 font-medium">Admin DSI</span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Contenu dynamique -->
            <main class="p-6 content-transition" id="main-content">
                <?php
                $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard_content';
                $valid_pages = ['dashboard_content', 'agents_content', 'presence_content', 'absences_content'];
                if (in_array($page, $valid_pages)) {
                    include $page . '.php';
                } else {
                    include 'dashboard_content.php'; // Page par défaut
                }
                ?>
            </main>
        </div>
    </div>

    <!-- Charger le point d'entrée de l'application -->
    <script type="module" src="./js/app.js"></script>
</body>
</html>