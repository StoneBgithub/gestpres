<?php
// fetch_absence.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connect.php';

try {
    // Requête pour récupérer agents avec absences longues >=15j dans les 30 derniers jours
    $query = "
        SELECT 
            a.id AS agent_id,
            a.nom,
            a.prenom,
            a.email,
            b.libele AS bureau,
            MAX(p.date) AS derniere_presence,
            DATEDIFF(CURDATE(), MAX(p.date)) AS nb_jours_absence_estimee,
            ab.date_debut,
            ab.date_fin,
            DATEDIFF(ab.date_fin, ab.date_debut) + 1 AS nb_jours_absence
        FROM agent a
        LEFT JOIN presence p ON a.id = p.agent_id
        LEFT JOIN absence ab 
            ON a.id = ab.agent_id 
            AND DATEDIFF(ab.date_fin, ab.date_debut) + 1 >= 15
            AND ab.date_fin >= CURDATE() - INTERVAL 30 DAY
        LEFT JOIN bureau b ON a.bureau_id = b.id
        WHERE ab.id IS NOT NULL
        GROUP BY a.id, a.nom, a.prenom, a.email, b.libele, ab.date_debut, ab.date_fin
        ORDER BY nb_jours_absence DESC, a.nom ASC;
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $agents_absents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traitement des données agents
    $today = new DateTime();

    foreach ($agents_absents as &$agent) {
        // Valeurs par défaut
        $agent['nom'] = $agent['nom'] ?? 'Non renseigné';
        $agent['prenom'] = $agent['prenom'] ?? 'Non renseigné';
        $agent['email'] = $agent['email'] ?? '';
        $agent['bureau'] = $agent['bureau'] ?? 'Non assigné';

        // Formatage des dates d'absence
        if (!empty($agent['date_debut'])) {
            $agent['date_debut_formatted'] = date('d/m/Y', strtotime($agent['date_debut']));
        } else {
            $agent['date_debut_formatted'] = 'N/A';
        }

        if (!empty($agent['date_fin'])) {
            $agent['date_fin_formatted'] = date('d/m/Y', strtotime($agent['date_fin']));
        } else {
            $agent['date_fin_formatted'] = 'N/A';
        }

        // Calcul du statut
        if (!empty($agent['date_fin'])) {
            $date_fin = new DateTime($agent['date_fin']);
            $agent['statut'] = $date_fin >= $today ? 'En cours' : 'Terminée';
        } else {
            $agent['statut'] = 'Inconnu';
        }

        // Classe CSS selon durée absence
        $nb_jours = (int)($agent['nb_jours_absence'] ?? 0);
        if ($nb_jours >= 30) {
            $agent['css_class'] = 'critical';
        } elseif ($nb_jours >= 20) {
            $agent['css_class'] = 'warning';
        } else {
            $agent['css_class'] = 'attention';
        }
    }
    unset($agent); // sécurité unset référence

    // Préparer la réponse JSON
    $response = [
        'success' => true,
        'agents_absents' => $agents_absents,
        'statistiques' => [
            'total' => count($agents_absents),
            'total_absences_longues' => (int)$statistiques['total_absences_longues'],
            'duree_moyenne' => round((float)$statistiques['duree_moyenne'], 1),
            'duree_max' => (int)$statistiques['duree_max'],
            'duree_min' => (int)$statistiques['duree_min'],
        ],
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => count($agents_absents) > 0
            ? count($agents_absents) . ' agent(s) avec absence prolongée détecté(s)'
            : 'Aucune absence prolongée détectée',
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données',
        'message' => 'Impossible de récupérer les données d\'absences',
        'debug' => $e->getMessage(), // À supprimer en prod
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => 'Une erreur inattendue s\'est produite',
        'debug' => $e->getMessage(), // À supprimer en prod
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
}
