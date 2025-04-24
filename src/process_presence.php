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
    
    // Récupérer les données de la requête POST
    $matricule = isset($_POST['matricule']) ? trim($_POST['matricule']) : '';
    $presenceDate = isset($_POST['presence_date']) ? trim($_POST['presence_date']) : date('Y-m-d');
    $presenceTime = isset($_POST['presence_time']) ? trim($_POST['presence_time']) : date('H:i:s');
    
    if (empty($matricule)) {
        echo json_encode(['success' => false, 'message' => 'Matricule non fourni']);
        exit;
    }
    
    // Valider la date et l'heure si fournies
    if ($presenceDate !== date('Y-m-d')) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $presenceDate) || !strtotime($presenceDate)) {
            echo json_encode(['success' => false, 'message' => 'Format de date invalide']);
            exit;
        }
    }
    if ($presenceTime !== date('H:i:s')) {
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $presenceTime)) {
            echo json_encode(['success' => false, 'message' => 'Format d\'heure invalide']);
            exit;
        }
    }
    
    // Vérifier si l'agent existe
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
    
    // Vérifier si l'agent a déjà scanné à la date spécifiée
    $stmtPresence = $conn->prepare("
        SELECT id, type, heure 
        FROM presence 
        WHERE agent_id = :agent_id AND date = :presence_date 
        ORDER BY heure ASC
    ");
    $stmtPresence->bindParam(':agent_id', $agent['id']);
    $stmtPresence->bindParam(':presence_date', $presenceDate);
    $stmtPresence->execute();
    
    $presences = $stmtPresence->fetchAll(PDO::FETCH_ASSOC);
    $presenceCount = count($presences);
    
    // Déterminer le type de présence (arrivée ou départ)
    if ($presenceCount == 0) {
        // Premier scan de la journée = arrivée
        $type = 'arrivée';
    } elseif ($presenceCount == 1) {
        // Deuxième scan = départ
        $type = 'depart';
    } else {
        // Déjà fait deux scans à cette date
        echo json_encode([
            'success' => false, 
            'message' => 'Vous avez déjà enregistré votre arrivée et votre départ pour cette date'
        ]);
        exit;
    }
    
    // Enregistrer la présence
    $stmtInsert = $conn->prepare("
        INSERT INTO presence (agent_id, date, heure, type) 
        VALUES (:agent_id, :presence_date, :presence_time, :type)
    ");
    $stmtInsert->bindParam(':agent_id', $agent['id']);
    $stmtInsert->bindParam(':presence_date', $presenceDate);
    $stmtInsert->bindParam(':presence_time', $presenceTime);
    $stmtInsert->bindParam(':type', $type);
    $stmtInsert->execute();
    
    // Retourner les données de l'agent et le type d'enregistrement
    $response = [
        'success' => true,
        'type' => $type,
        'agent' => [
            'nom' => $agent['nom'],
            'prenom' => $agent['prenom'],
            'bureau' => $agent['bureau'],
            'service' => $agent['service'],
            'photo' => $agent['photo']
        ]
    ];
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>