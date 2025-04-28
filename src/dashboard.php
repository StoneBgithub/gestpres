<?php
// fichier dashboard.php
session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Utiliser le rôle stocké dans la session
$role = $_SESSION['role'] ?? 'viewer'; // Par défaut, utiliser 'viewer' si le rôle n'est pas défini

// Connexion à la base de données
require_once 'db_connect.php';

// Déterminer si c'est une requête AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Si c'est une requête AJAX, ne retourner que le contenu demandé
if ($isAjax) {
    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard_content';
    $valid_pages = ['dashboard_content', 'agents_content', 'presence_content', 'absences_content', 'performance_content'];

    // Restreindre l'accès à performance_content pour les viewers
    if ($page === 'performance_content' && $role === 'viewer') {
        http_response_code(403);
        echo json_encode(['error' => 'Accès non autorisé pour les utilisateurs avec le rôle viewer.']);
        exit();
    }

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/fontawesome/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8fafc; 
        }
        .gradient-bg { 
            background: linear-gradient(135deg, #038C33 0%, #014023 100%); 
        }
        .content-transition { 
            transition: all 0.3s ease; 
        }
        .active-menu { 
            background: linear-gradient(135deg, #038C33 0%, #014023 100%); 
            color: white; 
            font-weight: bold; 
        }
        .font-display {
            font-family: 'Montserrat', sans-serif;
        }
        .font-body {
            font-family: 'Inter', sans-serif;
        }
        .card-shine {
            position: relative;
            overflow: hidden;
        }
        .card-shine::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.1) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(30deg);
            transition: transform 0.5s;
            pointer-events: none;
        }
        .card-shine:hover::after {
            transform: rotate(30deg) translate(0, 50%);
        }
        .custom-btn {
            background: linear-gradient(135deg, #038C33 0%, #014023 100%) !important;
            color: white !important;
            transition: all 0.3s ease;
            position: relative;
            z-index: 10;
        }
        .custom-btn:hover {
            background: #F2CE16 !important;
            color: #014023 !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .custom-btn:disabled {
            background: #e5e7eb !important;
            color: #6b7280 !important;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .filter-badge {
            background: #E9F2A0;
            color: #014023;
        }
        .bg-congo-yellow-pale {
            background-color: #E9F2A0;
        }
        .text-congo-green-dark {
            color: #014023;
        }
        /* Override JavaScript-applied blue styles */
        button.bg-blue-600 {
            background: linear-gradient(135deg, #038C33 0%, #014023 100%) !important;
            color: white !important;
        }
        button.bg-blue-600:hover {
            background: #F2CE16 !important;
            color: #014023 !important;
        }
        /* Ensure toggle-filters-btn consistency */
        .toggle-filters-btn:hover {
            background: #E9F2A0 !important;
            color: #014023 !important;
        }
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
                    <h1 id="page-title" class="text-2xl font-bold text-gray-800 font-display">
                        <?php
                        $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard_content';
                        $menu_items = [
                            'dashboard_content' => 'Tableau de Bord',
                            'agents_content' => 'Gestion des Agents',
                            'presence_content' => 'Gestion de Présence',
                            'absences_content' => 'Gestion d\'Absence',
                            'performance_content' => 'Performance des Agents',
                        ];
                        echo isset($menu_items[$page]) ? $menu_items[$page] : ucfirst(str_replace('_content', '', $page));
                        ?>
                    </h1>
                    <div class="flex items-center space-x-4">
                        <?php
                        $user_id = $_SESSION['user_id'];
                        $stmt = $pdo->prepare("
                            SELECT a.nom, a.prenom, a.photo, l.role 
                            FROM agent a 
                            INNER JOIN login l ON a.id = l.agent_id 
                            WHERE a.id = :user_id
                        ");
                        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        $stmt->execute();
                        $user = $stmt->fetch();
                        if ($user) {
                            $profile_image = !empty($user['photo']) && file_exists('./Uploads/' . $user['photo']) 
                                ? './Uploads/' . htmlspecialchars($user['photo']) 
                                : "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='44' height='44' viewBox='0 0 44 44'%3E%3Ccircle cx='22' cy='22' r='22' fill='%23E1F5E6' fill-opacity='0.9'/%3E%3Ctext x='50%' y='50%' dy='.3em' text-anchor='middle' fill='%23025928' font-family='Inter, sans-serif' font-size='18' font-weight='600'%3E" . htmlspecialchars(strtoupper(substr($user['nom'], 0, 1) . substr($user['prenom'], 0, 1))) . "%3C/text%3E%3C/svg%3E";
                        ?>
                        <div class="flex items-center">
                            <img src="<?php echo $profile_image; ?>" alt="Photo de profil" class="h-11 w-11 rounded-full object-cover shadow-sm">
                            <span class="ml-2 text-gray-700 font-medium font-body"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                        </div>
                        <?php
                        } else {
                            echo '<span class="ml-2 text-red-700 font-medium font-body">Utilisateur non trouvé</span>';
                        }
                        ?>
                    </div>
                </div>
            </header>
            
            <main class="p-6 content-transition" id="main-content">
                <?php
                $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard_content';
                $valid_pages = ['dashboard_content', 'agents_content', 'presence_content', 'absences_content', 'performance_content'];

                // Restreindre l'accès à performance_content pour les viewers
                if ($page === 'performance_content' && $role === 'viewer') {
                    echo '<div class="text-red-600 font-bold">Accès non autorisé : Vous n\'avez pas la permission d\'accéder à la page Performance des Agents.</div>';
                } elseif (in_array($page, $valid_pages)) {
                    include $page . '.php';
                } else {
                    include 'dashboard_content.php';
                }
                ?>
            </main>
        </div>
    </div>
    <script src="./assets/js/qrcode.min.js"></script>
    <script type="module" src="./js/app.js"></script>
</body>
</html>