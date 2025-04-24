<?php
// Démarrer la session en début de fichier
session_start();

// Inclure la connexion à la base de données
require_once 'db_connect.php';

// Initialiser les variables
$error = '';

// Traitement du formulaire de connexion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et nettoyer les données du formulaire
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validation de base
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            // Rechercher l'agent par son email
            $stmt = $pdo->prepare("
                SELECT a.id, a.matricule, a.nom, a.prenom, l.mot_de_passe 
                FROM agent a 
                INNER JOIN login l ON a.id = l.agent_id 
                WHERE a.email = :email
            ");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            // Vérifier si l'agent existe
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                // Vérifier le mot de passe (non haché, comme demandé)
                if ($password === $user['mot_de_passe']) {
                    // Authentification réussie - Créer la session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['user_matricule'] = $user['matricule'];
                    $_SESSION['is_logged_in'] = true;
                    
                    // Vérifier si l'utilisateur vient de scan_presence.php
                    if (isset($_GET['from']) && $_GET['from'] === 'scan') {
                        header("Location: scan-presence.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Mot de passe incorrect.";
                }
            } else {
                $error = "Aucun compte associé à cet email.";
            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSI - Connexion</title>
    <link href="./assets/css/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #E9F2A0; /* Pale yellow background */
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            color: #0D0D0D; /* Black */
        }
        .gradient-bg {
            background: linear-gradient(135deg, #5CBF15 0%, #014023 100%); /* Vibrant to dark green */
        }
        .error-gradient-bg {
            background: linear-gradient(135deg, #D91A1A 0%, #A6882E 100%); /* Red to dark yellow */
        }
        .custom-btn {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #038C33 0%, #5CBF15 100%); /* Main to vibrant green */
            color: white;
        }
        .custom-btn:hover {
            background: #F2CE16 !important; /* Vivid yellow */
            color: #0D0D0D !important; /* Black */
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .shake-animation {
            animation: shake 0.5s;
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            50% { transform: translateX(10px); }
            75% { transform: translateX(-10px); }
            100% { transform: translateX(0); }
        }
        input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(92, 191, 21, 0.3); /* Vibrant green focus ring */
        }
        .footer-bg {
            background-color: #014023; /* Dark green */
        }
        .footer-link {
            color: #F2CE16; /* Vivid yellow */
            transition: all 0.3s ease;
        }
        .footer-link:hover {
            color: #F2C849; /* Matic yellow */
            text-decoration: underline;
        }
        .footer-divider {
            border-color: #5CBF15; /* Vibrant green */
            opacity: 0.5;
            height: 2px;
        }
        .logo-glow {
            filter: drop-shadow(0 0 10px rgba(92, 191, 21, 0.5)); /* Vibrant green glow */
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="gradient-bg p-6 text-white text-center">
                <div class="flex items-center justify-center mb-4">
                    <img src="./public/logo.svg" alt="Armoirie de la DSI" class="w-16 h-16 logo-glow pulse" aria-label="Logo de la Direction des Systèmes d'Information">
                </div>
                <h1 class="text-2xl font-bold" style="color: #FFFFFF;">DSI - Gestion de Présence</h1>
                <p class="text-white opacity-80" style="color: #E9F2A0;">Connexion à l'administration</p>
            </div>
            
            <div class="p-6 md:p-8">
                <?php if (!empty($error)): ?>
                    <div class="error-gradient-bg text-white p-4 mb-6 rounded-xl shake-animation" role="alert">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . (isset($_GET['from']) ? '?from=' . htmlspecialchars($_GET['from']) : ''); ?>" method="post" aria-label="Formulaire de connexion">
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium" style="color: #0D0D0D;">Email professionnel</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color: #A6882E;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input type="email" id="email" name="email" 
                                   class="w-full pl-10 pr-4 py-2 border rounded-lg" style="border-color: #E9F2A0;"
                                   placeholder="nom@entreprise.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium" style="color: #0D0D0D;">Mot de passe</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color: #A6882E;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.104-.896 2-2 2s-2-.896-2-2 2-4 2-4 2 .896 2 2zM16 18H8m8 0c0 1.104-.896 2-2 2h-4c-1.104 0-2-.896-2-2m8 0v-4a4 4 0 00-4-4H8a4 4 0 00-4 4v4" />
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" 
                                   class="w-full pl-10 pr-4 py-2 border rounded-lg" style="border-color: #E9F2A0;"
                                   required>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mb-6">
                        <a href="forgot-password.php" class="text-sm font-medium" style="color: #038C33;" onmouseover="this.style.color='#5CBF15'" onmouseout="this.style.color='#038C33'">
                            Mot de passe oublié ?
                        </a>
                    </div>
                    
                    <button type="submit" 
                            class="w-full custom-btn font-medium py-3 px-4 rounded-xl flex items-center justify-center" aria-label="Se connecter">
                        <span>Se connecter</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="./../index.php" class="font-medium flex items-center justify-center" style="color: #038C33;" onmouseover="this.style.color='#5CBF15'" onmouseout="this.style.color='#038C33'" aria-label="Retour à l'accueil">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>


    <script>
        // Update current year in footer
        document.getElementById('current-year').textContent = new Date().getFullYear();
    </script>
</body>
</html>