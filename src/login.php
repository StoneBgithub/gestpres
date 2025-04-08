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
                
                // Vérifier le mot de passe (dans un environnement réel, utilisez password_verify avec des hash)
                if ($password === $user['mot_de_passe']) {
                    // Authentification réussie - Créer la session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['user_matricule'] = $user['matricule'];
                    $_SESSION['is_logged_in'] = true;
                    
                    // Rediriger vers le tableau de bord
                    header("Location: dashboard.php");
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="gradient-bg p-6 text-white text-center">
                <h1 class="text-2xl font-bold">DSI - Gestion de Présence</h1>
                <p class="text-blue-100">Connexion à l'administration</p>
            </div>
            
            <div class="p-8">
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-6">
                        <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email professionnel</label>
                        <input type="email" id="email" name="email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="nom@entreprise.com" required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Mot de passe</label>
                        <input type="password" id="password" name="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>
                    
                    <div class="flex items-center justify-between mb-6">
                        <a href="forgot-password.php" class="text-sm text-blue-600 hover:text-blue-800">
                            Mot de passe oublié?
                        </a>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-300">
                        Se connecter
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="./../index.php" class="text-sm text-blue-600 hover:text-blue-800">
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>