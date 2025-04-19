<?php
// fichier dashboard.php
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
    <link href="./assets/css/all.min.css" rel="stylesheet">
    <link href="./assets/css/poppins.css" rel="stylesheet">
   
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
                        <?php
                        // Inclure la connexion à la base de données
                        require_once 'db_connect.php';

                        // Récupérer les informations de l'utilisateur connecté
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

                        // Vérifier si l'utilisateur existe
                        if ($user) {
                            // Déterminer l'image à afficher
                            if (!empty($user['photo']) && file_exists('./Uploads/' . $user['photo'])) {
                                // Si une photo existe, utiliser le chemin relatif depuis le dossier d'Uploads
                                $profile_image = './Uploads/' . htmlspecialchars($user['photo']);
                            } else {
                                // Générer les initiales pour l'avatar
                                $initials = strtoupper(substr($user['nom'], 0, 1) . substr($user['prenom'], 0, 1));
                                // Créer un avatar avec le même style que agents_content.php et un padding interne accru
                                $profile_image = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='36' height='36' viewBox='0 0 36 36'%3E%3Crect width='36' height='36' fill='%23e0e7ff' rx='18'/%3E%3Ctext x='50%' y='50%' dy='.3em' text-anchor='middle' fill='%233b82f6' font-family='Poppins, sans-serif' font-size='14' font-weight='500'%3E" . htmlspecialchars($initials) . "%3C/text%3E%3C/svg%3E";
                            }
                        ?>
                        <div class="flex items-center">
                            <img src="<?php echo $profile_image; ?>" alt="Photo de profil" class="h-9 w-9 rounded-full object-cover">
                            <span class="ml-2 text-gray-700 font-medium"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                        </div>
                        <?php
                        } else {
                            // Gestion d'erreur si l'utilisateur n'est pas trouvé
                            echo '<span class="ml-2 text-red-700 font-medium">Utilisateur non trouvé</span>';
                        }
                        ?>
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
    <script src="./assets/js/qrcode.min.js"></script>
    <!-- Charger le point d'entrée de l'application -->
    <script type="module" src="./js/app.js"></script>
</body>
</html>