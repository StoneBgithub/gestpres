<?php
// Configurer le fuseau horaire pour le Congo-Brazzaville
date_default_timezone_set('Africa/Brazzaville');

// Configurer les en-têtes pour autoriser les requêtes AJAX
header('Content-Type: application/json');

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gestion_presence";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer le matricule de la requête POST
    $matricule = isset($_POST['matricule']) ? $_POST['matricule'] : '';
    
    if (empty($matricule)) {
        echo json_encode(['success' => false, 'message' => 'Matricule non fourni']);
        exit;
    }
    
    // Vérifier si l'agent existe (ajout du champ photo dans la requête)
    $stmtAgent = $conn->prepare("
        SELECT a.id, a.matricule, a.nom, a.prenom, a.photo, b.libele as bureau, s.libele as service
        FROM agent a
        INNER JOIN bureau b ON a.bureau_id = b.id
        INNER JOIN service s ON b.service_id = s.id
        WHERE a.matricule = :matricule
    ");
    $stmtAgent->bindParam(':matricule', $matricule);
    $stmtAgent->execute();
    
    $agent = $stmtAgent->fetch(PDO::FETCH_ASSOC);
    
    if (!$agent) {
        echo json_encode(['success' => false, 'message' => 'Agent non trouvé avec ce matricule']);
        exit;
    }
    
    // Récupérer la date actuelle
    $currentDate = date('Y-m-d');
    
    // Vérifier si l'agent a déjà scanné aujourd'hui
    $stmtPresence = $conn->prepare("
        SELECT id, type, heure 
        FROM presence 
        WHERE agent_id = :agent_id AND date = :current_date 
        ORDER BY heure ASC
    ");
    $stmtPresence->bindParam(':agent_id', $agent['id']);
    $stmtPresence->bindParam(':current_date', $currentDate);
    $stmtPresence->execute();
    
    $presences = $stmtPresence->fetchAll(PDO::FETCH_ASSOC);
    $presenceCount = count($presences);
    
    // Obtenir l'heure actuelle
    $currentTime = date('H:i:s');
    
    // Déterminer le type de présence (arrivée ou départ)
    if ($presenceCount == 0) {
        // Premier scan de la journée = arrivée
        $type = 'arrivée';
    } elseif ($presenceCount == 1) {
        // Deuxième scan = départ
        $type = 'depart';
    } else {
        // Déjà fait deux scans aujourd'hui
        echo json_encode([
            'success' => false, 
            'message' => 'Vous avez déjà enregistré votre arrivée et votre départ pour aujourd\'hui'
        ]);
        exit;
    }
    
    // Enregistrer la présence
    $stmtInsert = $conn->prepare("
        INSERT INTO presence (agent_id, date, heure, type) 
        VALUES (:agent_id, :current_date, :current_time, :type)
    ");
    $stmtInsert->bindParam(':agent_id', $agent['id']);
    $stmtInsert->bindParam(':current_date', $currentDate);
    $stmtInsert->bindParam(':current_time', $currentTime);
    $stmtInsert->bindParam(':type', $type);
    $stmtInsert->execute();
    
    // Retourner les données de l'agent et le type d'enregistrement (ajout de la photo)
    $response = [
        'success' => true,
        'type' => $type,
        'agent' => [
            'nom' => $agent['nom'],
            'prenom' => $agent['prenom'],
            'bureau' => $agent['bureau'],
            'service' => $agent['service'],
            'photo' => $agent['photo'] // Ajout de la photo dans la réponse
        ]
    ];
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>