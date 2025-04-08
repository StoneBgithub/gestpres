<?php
// Paramètres de connexion
$host = "localhost";  // Serveur MySQL de XAMPP
$dbname = "gestion_presence";  // Nom de la base de données
$username = "root";  // Utilisateur par défaut de XAMPP
$password = "";  // Mot de passe par défaut (vide)

try {
    // Création de la connexion PDO avec gestion des erreurs UTF-8
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configurer PDO pour qu'il lance des exceptions en cas d'erreur
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // En cas d'erreur de connexion
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>