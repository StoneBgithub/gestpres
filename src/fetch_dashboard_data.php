<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

function sendResponse($data) {
    echo json_encode($data);
    exit;
}

// Récup(nbsp;) Récupérer les filtres depuis la requête
$filters = json_decode(file_get_contents('php://input'), true) ?: [];
$period = $filters['period'] ?? 'day';
$year = $filters['year'] ?? date('Y');
$month = $filters['month'] ?? 'all';
$week = $filters['week'] ?? 'all';
$service = $filters['service'] ?? 'all';
$bureau = $filters['bureau'] ?? 'all';
$employee = $filters['employee'] ?? 'all';

// Date dynamique
$current_date = date('Y-m-d');
error_log("Current date : $current_date");

// Récupérer la plage de dates des enregistrements
try {
    $query = "SELECT MIN(date) as min_date, MAX(date) as max_date FROM presence";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $date_range = $stmt->fetch(PDO::FETCH_ASSOC);
    $min_date = $date_range['min_date'] ?? date('Y-m-d', strtotime('-1 month'));
    $max_date = min($date_range['max_date'] ?? $current_date, $current_date);
    error_log("Plage de dates : min_date=$min_date, max_date=$max_date");
} catch (PDOException $e) {
    error_log("Erreur plage de dates : " . $e->getMessage());
    sendResponse(['error' => 'Erreur lors de la récupération de la plage de dates']);
}

// Définir la période
$start_date = $min_date;
$end_date = $current_date;
$chart_start_date = $start_date;
$chart_end_date = $end_date;
try {
    if ($period === 'day') {
        $chart_start_date = date('Y-m-d', strtotime('monday this week', strtotime($current_date)));
        $chart_start_date = max($chart_start_date, $min_date);
        $chart_end_date = $current_date;
        $start_date = $current_date;
        $end_date = $current_date;
    } elseif ($period === 'week' && $week === 'all') {
        $start_date = date('Y-m-d', strtotime('monday this week', strtotime($current_date)));
        $start_date = max($start_date, $min_date);
        $end_date = $current_date;
        $chart_start_date = $start_date;
        $chart_end_date = $end_date;
    } elseif ($period === 'month' && $month === 'all') {
        $start_date = max("$year-01-01", $min_date);
        $end_date = min("$year-12-31", $current_date);
        $chart_start_date = $start_date;
        $chart_end_date = $end_date;
    } elseif ($period === 'year') {
        $start_date = max("$year-01-01", $min_date);
        $end_date = min("$year-12-31", $current_date);
        $chart_start_date = $start_date;
        $chart_end_date = $end_date;
    }

    if ($month !== 'all') {
        $start_date = max("$year-$month-01", $min_date);
        $end_date = min(date('Y-m-t', strtotime("$year-$month-01")), $current_date);
        $chart_start_date = $start_date;
        $chart_end_date = $end_date;
    }

    if ($week !== 'all') {
        $weekStart = new DateTime("$year-$month-01");
        $weekStart->modify('+' . (($week - 1) * 7) . ' days');
        $start_date = max($weekStart->format('Y-m-d'), $min_date);
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');
        $end_date = min($weekEnd->format('Y-m-d'), $current_date);
        $chart_start_date = $start_date;
        $chart_end_date = $end_date;
    }

    error_log("Filtres reçus : " . json_encode($filters));
    error_log("Période calculée : start_date=$start_date, end_date=$end_date, chart_start_date=$chart_start_date, chart_end_date=$chart_end_date");
} catch (Exception $e) {
    error_log("Erreur calcul dates : " . $e->getMessage());
    sendResponse(['error' => 'Erreur lors du calcul des dates']);
}

// Construire les conditions de filtrage
$conditions = [];
$params = [];
if ($period === 'day') {
    $conditions[] = "p.date = :date";
    $params[':date'] = $current_date;
} else {
    $conditions[] = "p.date BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $start_date;
    $params[':end_date'] = $end_date;
}
if ($service !== 'all') {
    $conditions[] = "b.service_id = :service";
    $params[':service'] = $service;
}
if ($bureau !== 'all') {
    $conditions[] = "a.bureau_id = :bureau";
    $params[':bureau'] = $bureau;
}
if ($employee !== 'all') {
    $conditions[] = "a.id = :employee";
    $params[':employee'] = $employee;
}
$where_clause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Conditions pour chart_data
$chart_conditions = ["p.date BETWEEN :chart_start_date AND :chart_end_date"];
$chart_params = [':chart_start_date' => $chart_start_date, ':chart_end_date' => $chart_end_date];
if ($service !== 'all') $chart_conditions[] = "b.service_id = :service";
if ($bureau !== 'all') $chart_conditions[] = "a.bureau_id = :bureau";
if ($employee !== 'all') $chart_conditions[] = "a.id = :employee";
$chart_params = array_merge($chart_params, array_filter($params, fn($k) => in_array($k, [':service', ':bureau', ':employee']), ARRAY_FILTER_USE_KEY));
$chart_where_clause = "WHERE " . implode(" AND ", $chart_conditions);

// Récupérer le total des agents
try {
    $query = "SELECT COUNT(*) as total_agents FROM agent a JOIN bureau b ON a.bureau_id = b.id";
    if ($service !== 'all') $query .= " WHERE b.service_id = :service";
    elseif ($bureau !== 'all') $query .= " WHERE a.bureau_id = :bureau";
    elseif ($employee !== 'all') $query .= " WHERE a.id = :employee";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_filter($params, fn($k) => in_array($k, [':service', ':bureau', ':employee']), ARRAY_FILTER_USE_KEY));
    $total_agents = $stmt->fetch(PDO::FETCH_ASSOC)['total_agents'] ?? 0;
    error_log("Total agents : $total_agents");
} catch (PDOException $e) {
    error_log("Erreur total agents : " . $e->getMessage());
    sendResponse(['error' => 'Erreur lors de la récupération des agents']);
}

// Récupérer les agents présents
try {
    if ($period === 'day') {
        $query = "
            SELECT COUNT(DISTINCT p.agent_id) as present_agents 
            FROM presence p 
            JOIN agent a ON p.agent_id = a.id 
            JOIN bureau b ON a.bureau_id = b.id 
            $where_clause AND p.type = 'arrivée'
        ";
    } else {
        $query = "
            SELECT COUNT(DISTINCT CONCAT(p.agent_id, '_', p.date)) as present_agents 
            FROM presence p 
            JOIN agent a ON p.agent_id = a.id 
            JOIN bureau b ON a.bureau_id = b.id 
            $where_clause AND p.type = 'arrivée'
        ";
    }
    error_log("Requête présents : $query");
    error_log("Params présents : " . json_encode($params));
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $present_agents = $stmt->fetch(PDO::FETCH_ASSOC)['present_agents'] ?? 0;
    error_log("Agents présents : $present_agents");
    $present_agents_formatted = "$present_agents/$total_agents";
} catch (PDOException $e) {
    error_log("Erreur présents : " . $e->getMessage());
    $present_agents = 0;
    $present_agents_formatted = "0/$total_agents";
}

// Calculer les absences
if ($period === 'day') {
    $absent_agents = $total_agents - $present_agents;
} else {
    try {
        $workdays = getWorkingDaysBetweenDates($start_date, $end_date);
        $total_potential_presences = $total_agents * $workdays;
        $absent_agents = $total_potential_presences - $present_agents;
        if ($absent_agents < 0) $absent_agents = 0;
    } catch (Exception $e) {
        $absent_agents = 0;
    }
}
error_log("Agents absents : $absent_agents");

// Calculer le taux de présence
$presence_rate = $total_agents > 0 ? round(($present_agents / ($period === 'day' ? $total_agents : $total_agents * getWorkingDaysBetweenDates($start_date, $end_date))) * 100) : 0;
error_log("Taux de présence : $presence_rate%");

// Récupérer les retards
try {
    if ($period === 'day') {
        $query = "
            SELECT COUNT(DISTINCT p.agent_id) as late_agents 
            FROM presence p 
            JOIN agent a ON p.agent_id = a.id 
            JOIN bureau b ON a.bureau_id = b.id 
            $where_clause AND p.type = 'arrivée' AND p.heure > '09:00:00'
        ";
    } else {
        $query = "
            SELECT COUNT(DISTINCT CONCAT(p.agent_id, '_', p.date)) as late_agents 
            FROM presence p 
            JOIN agent a ON p.agent_id = a.id 
            JOIN bureau b ON a.bureau_id = b.id 
            $where_clause AND p.type = 'arrivée' AND p.heure > '09:00:00'
        ";
    }
    error_log("Requête retards : $query");
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $late_agents = $stmt->fetch(PDO::FETCH_ASSOC)['late_agents'] ?? 0;
    error_log("Agents en retard : $late_agents");
} catch (PDOException $e) {
    error_log("Erreur retards : " . $e->getMessage());
    $late_agents = 0;
}

// Récupérer les 3 dernières activités
try {
    $activity_conditions = $period === 'day' ? ["p.date = :date"] : ["p.date BETWEEN :start_date AND :end_date"];
    $activity_params = $period === 'day' ? [':date' => $current_date] : [':start_date' => $start_date, ':end_date' => $end_date];
    
    if ($service !== 'all') {
        $activity_conditions[] = "b.service_id = :service";
        $activity_params[':service'] = $service;
    }
    if ($bureau !== 'all') {
        $activity_conditions[] = "a.bureau_id = :bureau";
        $activity_params[':bureau'] = $bureau;
    }
    if ($employee !== 'all') {
        $activity_conditions[] = "a.id = :employee";
        $activity_params[':employee'] = $employee;
    }
    
    $activity_where_clause = "WHERE " . implode(" AND ", $activity_conditions);
    
    $query = "
        SELECT p.*, a.nom, a.prenom 
        FROM presence p 
        JOIN agent a ON p.agent_id = a.id 
        JOIN bureau b ON a.bureau_id = b.id 
        $activity_where_clause 
        ORDER BY p.date DESC, p.heure DESC 
        LIMIT 3
    ";
    error_log("Requête activités : $query");
    error_log("Params activités : " . json_encode($activity_params));
    $stmt = $pdo->prepare($query);
    $stmt->execute($activity_params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Activités récupérées : " . json_encode($activities));
    
    $recent_activities = '';
    if (empty($activities)) {
       $recent_activities = "
    <div class='flex flex-col items-center justify-center py-6 text-center'>
        <svg class='w-16 h-16 text-gray-300 mb-3' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'></path>
        </svg>
        <h3 class='text-gray-700 font-medium mb-1'>Aucune activité enregistrée</h3>
        <p class='text-gray-500 text-sm'>Les activités des agents apparaîtront ici dès qu'elles seront disponibles.</p>
    </div>";
    } else {
        foreach ($activities as $activity) {
            $color = $activity['type'] === 'arrivée' ? 'blue' : 'red';
            $initials = strtoupper(substr($activity['prenom'], 0, 1) . substr($activity['nom'], 0, 1));
            $recent_activities .= "
                <div class='flex items-center py-2 border-b border-gray-100 flex-col sm:flex-row'>
                    <div class='w-10 h-10 rounded-full bg-$color-100 flex items-center justify-center mb-2 sm:mb-0 sm:mr-3'>
                        <span class='text-$color-600 font-medium'>$initials</span>
                    </div>
                    <div class='flex-1 text-center sm:text-left min-w-0'>
                        <p class='text-gray-800 text-sm truncate'>{$activity['prenom']} {$activity['nom']} a enregistré son " . ($activity['type'] === 'arrivée' ? 'arrivée' : 'départ') . "</p>
                        <p class='text-gray-500 text-xs'>Le {$activity['date']} à {$activity['heure']}</p>
                    </div>
                </div>";
        }
    }
} catch (PDOException $e) {
    error_log("Erreur activités : " . $e->getMessage());
    $recent_activities = "<p class='text-gray-500 text-center'>Erreur lors du chargement des activités.</p>";
}

// Récupérer les données pour le graphique
$chart_data = [
    'labels' => [],
    'present' => [],
    'absent' => [],
    'late' => []
];
try {
    if ($period === 'year' && $month === 'all') {
        // Afficher par année
        $chart_data['labels'][] = $year;

        $year_params = $chart_params;
        $year_params[':chart_start_date'] = max("$year-01-01", $min_date);
        $year_params[':chart_end_date'] = min("$year-12-31", $chart_end_date);

        $query = "
            SELECT COUNT(DISTINCT CONCAT(p.agent_id, '_', p.date)) as count 
            FROM presence p 
            JOIN agent a ON p.agent_id = a.id 
            JOIN bureau b ON a.bureau_id = b.id 
            $chart_where_clause AND p.type = 'arrivée'
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute($year_params);
        $present = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $chart_data['present'][] = $present;

        $workdays = getWorkingDaysBetweenDates($year_params[':chart_start_date'], $year_params[':chart_end_date']);
        $chart_data['absent'][] = ($total_agents * $workdays) - $present;

        $query = "
            SELECT COUNT(DISTINCT CONCAT(p.agent_id, '_', p.date)) as count 
            FROM presence p 
            JOIN agent a ON p.agent_id = a.id 
            JOIN bureau b ON a.bureau_id = b.id 
            $chart_where_clause AND p.type = 'arrivée' AND p.heure > '09:00:00'
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute($year_params);
        $late = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $chart_data['late'][] = $late;
    } elseif ($period === 'month' && $month === 'all') {
        // Afficher par mois
        $start = new DateTime($chart_start_date);
        $end = new DateTime($chart_end_date);
        $interval = new DateInterval('P1M');
        $date_range = new DatePeriod($start, $interval, $end->modify('+1 month'));

        foreach ($date_range as $date) {
            $month_start = max($date->format('Y-m-01'), $min_date);
            $month_end = min($date->format('Y-m-t'), $chart_end_date);
            if ($month_end < $month_start) continue;

            $chart_data['labels'][] = $date->format('M Y');

            $month_params = $chart_params;
            $month_params[':chart_start_date'] = $month_start;
            $month_params[':chart_end_date'] = $month_end;

            $query = "
                SELECT COUNT(DISTINCT CONCAT(p.agent_id, '_', p.date)) as count 
                FROM presence p 
                JOIN agent a ON p.agent_id = a.id 
                JOIN bureau b ON a.bureau_id = b.id 
                $chart_where_clause AND p.type = 'arrivée'
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute($month_params);
            $present = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $chart_data['present'][] = $present;

            $workdays = getWorkingDaysBetweenDates($month_start, $month_end);
            $chart_data['absent'][] = ($total_agents * $workdays) - $present;

            $query = "
                SELECT COUNT(DISTINCT CONCAT(p.agent_id, '_', p.date)) as count 
                FROM presence p 
                JOIN agent a ON p.agent_id = a.id 
                JOIN bureau b ON a.bureau_id = b.id 
                $chart_where_clause AND p.type = 'arrivée' AND p.heure > '09:00:00'
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute($month_params);
            $late = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $chart_data['late'][] = $late;
        }
    } elseif ($period === 'month' && $month !== 'all' && $week === 'all') {
        // Afficher par semaine
        $month_start = new DateTime($chart_start_date);
        $month_end = new DateTime("$year-$month-" . date('t', strtotime("$year-$month-01"))); // Fin du mois complet
        $current = new DateTime($month_start->format('Y-m-01')); // Toujours commencer au 1er

        $week_number = 1;
        while ($current <= $month_end) {
            $week_start = $current->format('Y-m-d');
            $week_end_date = clone $current;
            $week_end_date->modify('+6 days');
            $week_end = $week_end_date->format('Y-m-d');

            $chart_data['labels'][] = "Semaine $week_number";

            // Limiter les données à chart_end_date pour les requêtes
            $data_week_end = min($week_end, $chart_end_date);

            $week_params = $chart_params;
            $week_params[':chart_start_date'] = $week_start;
            $week_params[':chart_end_date'] = $data_week_end;

            $query = "
                SELECT COUNT(DISTINCT CONCAT(p.agent_id, '_', p.date)) as count 
                FROM presence p 
                JOIN agent a ON p.agent_id = a.id 
                JOIN bureau b ON a.bureau_id = b.id 
                WHERE p.date BETWEEN :chart_start_date AND :chart_end_date AND p.type = 'arrivée'
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute($week_params);
            $present = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $chart_data['present'][] = $present;

            $workdays = getWorkingDaysBetweenDates($week_start, $data_week_end);
            $chart_data['absent'][] = ($total_agents * $workdays) - $present;

            $query = "
                SELECT COUNT(DISTINCT CONCAT(p.agent_id, '_', p.date)) as count 
                FROM presence p 
                JOIN agent a ON p.agent_id = a.id 
                JOIN bureau b ON a.bureau_id = b.id 
                WHERE p.date BETWEEN :chart_start_date AND :chart_end_date AND p.type = 'arrivée' AND p.heure > '09:00:00'
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute($week_params);
            $late = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $chart_data['late'][] = $late;

            $current->modify('+7 days');
            $week_number++;
        }
    } elseif ($period === 'week' && $week !== 'all') {
        // Afficher par jour
        $start = new DateTime($chart_start_date);
        $end = new DateTime($chart_end_date);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($start, $interval, $end);

        foreach ($date_range as $date) {
            $current_date = $date->format('Y-m-d');
            if ($current_date > $chart_end_date) break;

            $chart_data['labels'][] = $date->format('d M');

            $day_params = [':date' => $current_date];
            if ($service !== 'all') $day_params[':service'] = $service;
            if ($bureau !== 'all') $day_params[':bureau'] = $bureau;
            if ($employee !== 'all') $day_params[':employee'] = $employee;

            $day_conditions = ["p.date = :date"];
            if ($service !== 'all') $day_conditions[] = "b.service_id = :service";
            if ($bureau !== 'all') $day_conditions[] = "a.bureau_id = :bureau";
            if ($employee !== 'all') $day_conditions[] = "a.id = :employee";
            $day_where_clause = "WHERE " . implode(" AND ", $day_conditions);

            $query = "
                SELECT COUNT(DISTINCT p.agent_id) as count 
                FROM presence p 
                JOIN agent a ON p.agent_id = a.id 
                JOIN bureau b ON a.bureau_id = b.id 
                $day_where_clause AND p.type = 'arrivée'
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute($day_params);
            $present = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $chart_data['present'][] = $present;

            $chart_data['absent'][] = $total_agents - $present;

            $query = "
                SELECT COUNT(DISTINCT p.agent_id) as count 
                FROM presence p 
                JOIN agent a ON p.agent_id = a.id 
                JOIN bureau b ON a.bureau_id = b.id 
                $day_where_clause AND p.type = 'arrivée' AND p.heure > '09:00:00'
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute($day_params);
            $late = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $chart_data['late'][] = $late;
        }
    } else {
        // Par défaut : par jour
        $start = new DateTime($chart_start_date);
        $end = new DateTime($chart_end_date);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($start, $interval, $end);

        foreach ($date_range as $date) {
            $current_date = $date->format('Y-m-d');
            if ($current_date > $chart_end_date) break;

            $chart_data['labels'][] = $date->format('d M');

            $day_params = [':date' => $current_date];
            if ($service !== 'all') $day_params[':service'] = $service;
            if ($bureau !== 'all') $day_params[':bureau'] = $bureau;
            if ($employee !== 'all') $day_params[':employee'] = $employee;

            $day_conditions = ["p.date = :date"];
            if ($service !== 'all') $day_conditions[] = "b.service_id = :service";
            if ($bureau !== 'all') $day_conditions[] = "a.bureau_id = :bureau";
            if ($employee !== 'all') $day_conditions[] = "a.id = :employee";
            $day_where_clause = "WHERE " . implode(" AND ", $day_conditions);

            $query = "
                SELECT COUNT(DISTINCT p.agent_id) as count 
                FROM presence p 
                JOIN agent a ON p.agent_id = a.id 
                JOIN bureau b ON a.bureau_id = b.id 
                $day_where_clause AND p.type = 'arrivée'
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute($day_params);
            $present = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $chart_data['present'][] = $present;

            $chart_data['absent'][] = $total_agents - $present;

            $query = "
                SELECT COUNT(DISTINCT p.agent_id) as count 
                FROM presence p 
                JOIN agent a ON p.agent_id = a.id 
                JOIN bureau b ON a.bureau_id = b.id 
                $day_where_clause AND p.type = 'arrivée' AND p.heure > '09:00:00'
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute($day_params);
            $late = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $chart_data['late'][] = $late;
        }
    }
    error_log("Chart data : " . json_encode($chart_data));
} catch (Exception $e) {
    error_log("Erreur chart_data : " . $e->getMessage());
    $chart_data = ['labels' => [], 'present' => [], 'absent' => [], 'late' => []];
}

function getWorkingDaysBetweenDates($startDate, $endDate) {
    $begin = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($begin, $interval, $end);
    $workdays = 0;
    foreach ($period as $day) {
        if ($day->format('N') < 6) $workdays++;
    }
    return max(1, $workdays);
}

sendResponse([
    'total_agents' => $total_agents,
    'present_agents' => $present_agents,
    'present_agents_formatted' => $present_agents_formatted,
    'absent_agents' => $absent_agents,
    'presence_rate' => $presence_rate,
    'late_agents' => $late_agents,
    'recent_activities' => $recent_activities,
    'chart_data' => $chart_data,
    'min_date' => $min_date,
    'max_date' => $max_date,
]);
?>