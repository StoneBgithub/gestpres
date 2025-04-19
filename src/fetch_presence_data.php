<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

error_log("=== Démarrage fetch_presence_data.php ===");

try {
    require_once 'db_connect.php';
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Connexion PDO non initialisée");
    }

    error_log("Connexion PDO OK");

    // Paramètres
    $service = $_GET['service'] ?? 'all';
    $bureau = $_GET['bureau'] ?? 'all';
    $employee = $_GET['employee'] ?? 'all';
    $date = $_GET['date'] ?? '';
    $time_range = $_GET['time_range'] ?? 'all';
    $custom_start = $_GET['custom_start'] ?? '';
    $custom_end = $_GET['custom_end'] ?? '';
    $status = $_GET['status'] ?? 'all';
    $type = $_GET['type'] ?? 'all'; // Nouveau paramètre pour le type

    error_log("Paramètres : service=$service, bureau=$bureau, employee=$employee, date=$date, time_range=$time_range, status=$status, type=$type");

    // Construire les conditions dynamiques
    $conditions = [];
    $params = [];

    if ($date) {
        $conditions[] = "p.date = :date";
        $params[':date'] = $date;
    }

    if ($time_range !== 'all') {
        if ($time_range === 'morning') {
            $conditions[] = "p.heure BETWEEN '08:00:00' AND '12:00:00'";
        } elseif ($time_range === 'afternoon') {
            $conditions[] = "p.heure BETWEEN '12:00:00' AND '18:00:00'";
        } elseif ($time_range === 'custom' && $custom_start && $custom_end) {
            $conditions[] = "p.heure BETWEEN :custom_start AND :custom_end";
            $params[':custom_start'] = $custom_start;
            $params[':custom_end'] = $custom_end;
        }
    }

    if ($status !== 'all') {
        if ($status === 'on-time') {
            $conditions[] = "((p.type = 'arrivée' AND p.heure <= '09:00:00') OR (p.type = 'depart' AND p.heure >= '17:00:00'))";
        } elseif ($status === 'late') {
            $conditions[] = "p.type = 'arrivée' AND p.heure > '09:00:00'";
        } elseif ($status === 'early') {
            $conditions[] = "p.type = 'depart' AND p.heure < '17:00:00'";
        }
    }

    if ($service !== 'all') {
        $conditions[] = "s.libele = :service";
        $params[':service'] = $service;
    }

    if ($bureau !== 'all') {
        $conditions[] = "b.id = :bureau";
        $params[':bureau'] = $bureau;
    }

    if ($employee !== 'all') {
        $conditions[] = "a.id = :employee";
        $params[':employee'] = $employee;
    }

    if ($type !== 'all') {
        $conditions[] = "p.type = :type";
        $params[':type'] = $type;
    }

    $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    // Requête SQL
    $sql = "
        SELECT p.id, p.date, p.heure, p.type, 
               CONCAT(a.nom, ' ', a.prenom) AS nom_prenom, 
               a.photo, s.libele AS service, b.libele AS bureau
        FROM presence p
        JOIN agent a ON p.agent_id = a.id
        JOIN bureau b ON a.bureau_id = b.id
        JOIN service s ON b.service_id = s.id
        $where_clause
        ORDER BY p.date DESC, p.heure DESC
    ";

    error_log("SQL : $sql");
    error_log("Params : " . json_encode($params));

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Résultats : " . count($results) . " lignes");

    // Formater les résultats
    $formatted_results = array_map(function ($row) {
        return [
            'id' => $row['id'],
            'date' => $row['date'] ? date('d/m/Y', strtotime($row['date'])) : '-',
            'heure' => $row['heure'] ? date('H:i', strtotime($row['heure'])) : '-',
            'type' => $row['type'],
            'nom_prenom' => $row['nom_prenom'] ?: '-',
            'photo' => $row['photo'] ?: '',
            'service' => $row['service'] ?: '-',
            'bureau' => $row['bureau'] ?: '-'
        ];
    }, $results);

    error_log("Envoi JSON");
    echo json_encode($formatted_results);

} catch (Exception $e) {
    error_log("Erreur : " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    error_log("Trace : " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>