<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Activer l'affichage des erreurs pour le débogage (à désactiver en production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Récupérer les paramètres
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : null;
    $bureau_id = isset($_GET['bureau_id']) ? (int)$_GET['bureau_id'] : null;
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 10;

    // Déterminer la période
    $end_date = new DateTime();
    $start_date = (clone $end_date);
    if ($period === 'quarter') {
        $start_date->modify('-3 months');
    } elseif ($period === 'year') {
        $start_date->modify('-1 year');
    } else {
        $start_date->modify('-1 month');
    }
    $start_date_str = $start_date->format('Y-m-d');
    $end_date_str = $end_date->format('Y-m-d');

    // Calculer les jours ouvrables (lundi à vendredi)
    $interval = $start_date->diff($end_date);
    $working_days = 0;
    $current_date = (clone $start_date);
    while ($current_date <= $end_date) {
        if ($current_date->format('N') <= 5) {
            $working_days++;
        }
        $current_date->modify('+1 day');
    }

    // Log pour vérifier la période
    error_log("Période : $start_date_str à $end_date_str, Jours ouvrables : $working_days");

    // Requête de débogage pour voir les données brutes
    $debug_sql = "
        SELECT agent_id, date, heure, type
        FROM presence
        WHERE date BETWEEN :start_date AND :end_date
        ORDER BY agent_id, date, type
    ";
    $debug_stmt = $pdo->prepare($debug_sql);
    $debug_stmt->execute([':start_date' => $start_date_str, ':end_date' => $end_date_str]);
    $debug_data = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Données brutes presence : " . print_r($debug_data, true));

    // Requête pour les agents
    $sql = "
    SELECT 
        a.id, a.nom, a.prenom, a.telephone, a.matricule,  /* Remplacer a.email par a.telephone */
        b.libele AS bureau, s.libele AS service,
        COUNT(DISTINCT CASE WHEN p.type = 'arrivée' AND p.date BETWEEN :start_date AND :end_date THEN p.date END) AS days_present,
        COALESCE(SUM(CASE 
            WHEN p.type = 'depart' AND p2.heure IS NOT NULL AND p2.heure < p.heure
            THEN GREATEST(
                TIME_TO_SEC(TIMEDIFF(
                    CASE WHEN p.heure <= '14:00:00' THEN p.heure ELSE '14:00:00' END, 
                    p2.heure
                )) / 3600, 
                0
            )
            ELSE 0 
        END), 0) AS regular_hours,
        COALESCE(SUM(CASE 
            WHEN p.type = 'depart' AND p.heure > '14:00:00' AND p2.heure IS NOT NULL AND p2.heure < p.heure
            THEN GREATEST(
                TIME_TO_SEC(TIMEDIFF(p.heure, '14:00:00')) / 3600, 
                0
            )
            ELSE 0 
        END), 0) AS overtime_hours
    FROM agent a
    LEFT JOIN bureau b ON a.bureau_id = b.id
    LEFT JOIN service s ON b.service_id = s.id
    LEFT JOIN presence p ON a.id = p.agent_id AND p.date BETWEEN :start_date AND :end_date
    LEFT JOIN presence p2 ON p.agent_id = p2.agent_id 
        AND p.date = p2.date 
        AND p2.type = 'arrivée' 
        AND p.type = 'depart'
    LEFT JOIN absence_justifiee aj ON a.id = aj.agent_id
        AND p.date BETWEEN aj.date_debut AND aj.date_fin
    WHERE aj.id IS NULL
";

    $params = [':start_date' => $start_date_str, ':end_date' => $end_date_str];

    // Appliquer les filtres
    if ($search) {
        $sql .= " AND (a.nom LIKE :search OR a.prenom LIKE :search OR a.matricule LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if ($service_id) {
        $sql .= " AND s.id = :service_id";
        $params[':service_id'] = $service_id;
    }
    if ($bureau_id) {
        $sql .= " AND b.id = :bureau_id";
        $params[':bureau_id'] = $bureau_id;
    }

    $sql .= " GROUP BY a.id, a.nom, a.prenom, a.email, a.matricule, b.libele, s.libele";
    $sql .= " ORDER BY (
        COALESCE(SUM(CASE 
            WHEN p.type = 'depart' AND p2.heure IS NOT NULL AND p2.heure < p.heure
            THEN GREATEST(
                TIME_TO_SEC(TIMEDIFF(
                    CASE WHEN p.heure <= '14:00:00' THEN p.heure ELSE '14:00:00' END, 
                    p2.heure
                )) / 3600, 
                0
            )
            ELSE 0 
        END), 0) +
        COALESCE(SUM(CASE 
            WHEN p.type = 'depart' AND p.heure > '14:00:00' AND p2.heure IS NOT NULL AND p2.heure < p.heure
            THEN GREATEST(
                TIME_TO_SEC(TIMEDIFF(p.heure, '14:00:00')) / 3600, 
                0
            )
            ELSE 0 
        END), 0)
    ) DESC";
    $sql .= " LIMIT :per_page OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', ($page - 1) * $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log pour déboguer les agents
    error_log("Agents récupérés : " . print_r($agents, true));

    // Requête pour le total d'agents
    $count_sql = "
        SELECT COUNT(DISTINCT a.id)
        FROM agent a
        LEFT JOIN bureau b ON a.bureau_id = b.id
        LEFT JOIN service s ON b.service_id = s.id
        LEFT JOIN presence p ON a.id = p.agent_id AND p.date BETWEEN :start_date AND :end_date
        LEFT JOIN absence_justifiee aj ON a.id = aj.agent_id
            AND p.date BETWEEN aj.date_debut AND aj.date_fin
        WHERE aj.id IS NULL
    ";
    $count_params = [':start_date' => $start_date_str, ':end_date' => $end_date_str];
    if ($search) {
        $count_sql .= " AND (a.nom LIKE :search OR a.prenom LIKE :search OR a.matricule LIKE :search)";
        $count_params[':search'] = "%$search%";
    }
    if ($service_id) {
        $count_sql .= " AND s.id = :service_id";
        $count_params[':service_id'] = $service_id;
    }
    if ($bureau_id) {
        $count_sql .= " AND b.id = :bureau_id";
        $count_params[':bureau_id'] = $bureau_id;
    }
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_agents = $count_stmt->fetchColumn();
    $total_pages = ceil($total_agents / $per_page);

    // Requête pour les statistiques globales
    $stats_sql = "
        SELECT 
            COALESCE(SUM(CASE 
                WHEN p.type = 'depart' AND p2.heure IS NOT NULL AND p2.heure < p.heure
                THEN GREATEST(
                    TIME_TO_SEC(TIMEDIFF(
                        CASE WHEN p.heure <= '14:00:00' THEN p.heure ELSE '14:00:00' END, 
                        p2.heure
                    )) / 3600, 
                    0
                )
                ELSE 0 
            END), 0) AS total_regular_hours,
            COALESCE(SUM(CASE 
                WHEN p.type = 'depart' AND p.heure > '14:00:00' AND p2.heure IS NOT NULL AND p2.heure < p.heure
                THEN GREATEST(
                    TIME_TO_SEC(TIMEDIFF(p.heure, '14:00:00')) / 3600, 
                    0
                )
                ELSE 0 
            END), 0) AS total_overtime,
            COUNT(DISTINCT CASE 
                WHEN p.type = 'arrivée' AND p.date BETWEEN :start_date AND :end_date
                THEN CONCAT(p.agent_id, p.date) 
                ELSE NULL 
            END) AS total_attendance,
            COUNT(DISTINCT CASE 
                WHEN p.type = 'arrivée' AND p.date BETWEEN :start_date AND :end_date
                THEN p.agent_id 
                ELSE NULL 
            END) AS perfect_attendance
        FROM presence p
        LEFT JOIN presence p2 ON p.agent_id = p2.agent_id 
            AND p.date = p2.date 
            AND p2.type = 'arrivée' 
            AND p.type = 'depart'
        LEFT JOIN absence_justifiee aj ON p.agent_id = aj.agent_id
            AND p.date BETWEEN aj.date_debut AND aj.date_fin
        WHERE p.date BETWEEN :start_date AND :end_date
        AND aj.id IS NULL
    ";
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute([':start_date' => $start_date_str, ':end_date' => $end_date_str]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // Log pour déboguer les statistiques
    error_log("Statistiques : " . print_r($stats, true));

    // Calculer le taux de présence moyen
    $total_possible_attendance = $total_agents * $working_days;
    $avg_attendance_rate = $total_possible_attendance > 0 ? ($stats['total_attendance'] / $total_possible_attendance) * 100 : 0;

    // Formater les agents
    $agents = array_map(function($agent) use ($working_days) {
        $agent['presence_rate'] = $working_days > 0 ? ($agent['days_present'] / $working_days) * 100 : 0;
        $agent['total_hours'] = $agent['regular_hours'] + $agent['overtime_hours'];
        return $agent;
    }, $agents);

    // Log pour déboguer les agents formatés
    error_log("Agents formatés : " . print_r($agents, true));

    // Réponse JSON
    echo json_encode([
        'agents' => $agents,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_agents' => $total_agents
        ],
        'stats' => [
            'total_hours' => round($stats['total_regular_hours'] + $stats['total_overtime'], 1),
            'total_overtime' => round($stats['total_overtime'], 1),
            'avg_attendance_rate' => round($avg_attendance_rate, 1),
            'perfect_attendance' => (int)$stats['perfect_attendance']
        ]
    ], JSON_NUMERIC_CHECK);
} catch (Exception $e) {
    error_log("Erreur serveur : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>