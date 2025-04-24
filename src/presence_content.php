<?php
require_once 'db_connect.php';

// Récupérer les filtres depuis une requête POST
$filters = $_POST ?: [];
$date = $filters['date'] ?? '';
$time_range = $filters['time_range'] ?? 'all';
$status = $filters['status'] ?? 'all';
$service = $filters['service'] ?? 'all';
$bureau = $filters['bureau'] ?? 'all';
$employee = $filters['employee'] ?? 'all';
$custom_start = $filters['custom_start'] ?? '';
$custom_end = $filters['custom_end'] ?? '';
$type = $filters['type'] ?? 'all'; // Nouveau filtre pour le type

// Déterminer si des filtres sont appliqués
$filtersApplied = (
    !empty($date) ||
    $time_range !== 'all' ||
    $status !== 'all' ||
    $service !== 'all' ||
    $bureau !== 'all' ||
    $employee !== 'all' ||
    $type !== 'all'
);

// Définir la plage horaire
$start_time = '00:00:00';
$end_time = '23:59:59';
if ($time_range === 'morning') {
    $start_time = '08:00:00';
    $end_time = '12:00:00';
} elseif ($time_range === 'afternoon') {
    $start_time = '12:00:00';
    $end_time = '18:00:00';
} elseif ($time_range === 'custom' && $custom_start && $custom_end) {
    $start_time = $custom_start;
    $end_time = $custom_end;
}

// Construire les conditions dynamiques
$conditions = [];
$params = [];
if (!empty($date)) {
    $conditions[] = "p.date = :date";
    $params[':date'] = $date;
}
if ($time_range !== 'all') {
    $conditions[] = "p.heure BETWEEN :start_time AND :end_time";
    $params[':start_time'] = $start_time;
    $params[':end_time'] = $end_time;
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
    $conditions[] = "b.libele = :bureau";
    $params[':bureau'] = $bureau;
}
if ($employee !== 'all') {
    $conditions[] = "CONCAT(a.nom, ' ', a.prenom) = :employee";
    $params[':employee'] = $employee;
}
if ($type !== 'all') {
    $conditions[] = "p.type = :type";
    $params[':type'] = $type;
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Statistiques
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM presence p JOIN agent a ON p.agent_id = a.id JOIN bureau b ON a.bureau_id = b.id JOIN service s ON b.service_id = s.id $where_clause");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();

$arrivalsStmt = $pdo->prepare("SELECT COUNT(*) FROM presence p JOIN agent a ON p.agent_id = a.id JOIN bureau b ON a.bureau_id = b.id JOIN service s ON b.service_id = s.id $where_clause" . ($type === 'all' || $type === 'arrivée' ? " AND p.type = 'arrivée'" : ""));
$arrivalsStmt->execute($params);
$arrivals = $arrivalsStmt->fetchColumn();

$departuresStmt = $pdo->prepare("SELECT COUNT(*) FROM presence p JOIN agent a ON p.agent_id = a.id JOIN bureau b ON a.bureau_id = b.id JOIN service s ON b.service_id = s.id $where_clause" . ($type === 'all' || $type === 'depart' ? " AND p.type = 'depart'" : ""));
$departuresStmt->execute($params);
$departures = $departuresStmt->fetchColumn();

$lateStmt = $pdo->prepare("SELECT COUNT(*) FROM presence p JOIN agent a ON p.agent_id = a.id JOIN bureau b ON a.bureau_id = b.id JOIN service s ON b.service_id = s.id $where_clause" . ($type === 'all' || $type === 'arrivée' ? " AND p.type = 'arrivée' AND p.heure > '09:00:00'" : ""));
$lateStmt->execute($params);
$late = $lateStmt->fetchColumn();

// Liste des présences
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
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$presences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les données pour les filtres avec leurs relations
$servicesStmt = $pdo->query("SELECT id, libele FROM service");
$services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

$bureauxStmt = $pdo->query("SELECT id, libele, service_id FROM bureau");
$bureaux = $bureauxStmt->fetchAll(PDO::FETCH_ASSOC);

$agentsStmt = $pdo->query("SELECT id, CONCAT(nom, ' ', prenom) AS nom_prenom, bureau_id FROM agent");
$agents = $agentsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des présences</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
<div class="bg-gray-50 p-6">
    <!-- En-tête avec filtres rapides -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div class="flex flex-wrap gap-2">
            <button id="toggle-all" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-gray-200 transition" data-type="all">Tous</button>
            <button id="toggle-arrivals" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition" data-type="arrivée">Arrivées</button>
            <button id="toggle-departures" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition" data-type="depart">Départs</button>
            <div class="relative flex items-center">
                <a href="#" id="toggle-filters-btn" class="px-3 py-1.5 bg-white border border-gray-200 text-blue-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center">
                    Filtres avancés
                </a>
                <span id="filter-indicator" class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-600 text-white shadow-sm transition-all duration-300 <?php echo $filtersApplied ? 'opacity-100 scale-100' : 'opacity-0 scale-95 pointer-events-none'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Filtres appliqués
                </span>
            </div>
        </div>
    </div>

    <!-- Filtres avancés -->
    <div id="advanced-filters" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-6" style="display: none;">
        <form id="filter-form" method="POST">
            <input type="hidden" id="type-filter" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date); ?>" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
                </div>
                <div>
                    <label for="time-range" class="block text-sm font-medium text-gray-700 mb-1">Plage horaire</label>
                    <select id="time-range" name="time_range" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
                        <option value="all" <?php echo $time_range === 'all' ? 'selected' : ''; ?>>Toute la journée</option>
                        <option value="morning" <?php echo $time_range === 'morning' ? 'selected' : ''; ?>>Matin (8h-12h)</option>
                        <option value="afternoon" <?php echo $time_range === 'afternoon' ? 'selected' : ''; ?>>Après-midi (12h-18h)</option>
                        <option value="custom" <?php echo $time_range === 'custom' ? 'selected' : ''; ?>>Personnalisé...</option>
                    </select>
                    <div id="custom-time" class="mt-2" style="display: <?php echo $time_range === 'custom' ? 'block' : 'none'; ?>;">
                        <input type="time" name="custom_start" id="custom_start" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3 mb-2" value="<?php echo htmlspecialchars($custom_start); ?>">
                        <input type="time" name="custom_end" id="custom_end" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3" value="<?php echo htmlspecialchars($custom_end); ?>">
                    </div>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select id="status" name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                        <option value="on-time" <?php echo $status === 'on-time' ? 'selected' : ''; ?>>À l'heure</option>
                        <option value="late" <?php echo $status === 'late' ? 'selected' : ''; ?>>En retard</option>
                        <option value="early" <?php echo $status === 'early' ? 'selected' : ''; ?>>Départ anticipé</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="service" class="block text-sm font-medium text-gray-700 mb-1">Service</label>
                    <select id="service" name="service" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
                        <option value="all" <?php echo $service === 'all' ? 'selected' : ''; ?>>Tous les services</option>
                        <?php foreach ($services as $svc): ?>
                            <option value="<?php echo htmlspecialchars($svc['libele']); ?>" <?php echo $service === $svc['libele'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($svc['libele']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="bureau" class="block text-sm font-medium text-gray-700 mb-1">Bureau</label>
                    <select id="bureau" name="bureau" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
                        <option value="all" <?php echo $bureau === 'all' ? 'selected' : ''; ?>>Tous les bureaux</option>
                        <?php foreach ($bureaux as $bur): ?>
                            <option value="<?php echo htmlspecialchars($bur['libele']); ?>" <?php echo $bureau === $bur['libele'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($bur['libele']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="employee" class="block text-sm font-medium text-gray-700 mb-1">Employé</label>
                    <select id="employee" name="employee" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3">
                        <option value="all" <?php echo $employee === 'all' ? 'selected' : ''; ?>>Tous les employés</option>
                        <?php foreach ($agents as $agt): ?>
                            <option value="<?php echo htmlspecialchars($agt['nom_prenom']); ?>" <?php echo $employee === $agt['nom_prenom'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($agt['nom_prenom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="reset-filters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Réinitialiser</button>
                <button type="submit" id="apply-filters" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">Appliquer les filtres</button>
            </div>
        </form>
    </div>

    <!-- Cartes statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total enregistrés</p>
                    <h3 class="text-2xl font-bold"><?php echo $total; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Arrivées</p>
                    <h3 class="text-2xl font-bold"><?php echo $arrivals; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Départs</p>
                    <h3 class="text-2xl font-bold"><?php echo $departures; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-yellow-100 flex items-center justify-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Retards</p>
                    <h3 class="text-2xl font-bold"><?php echo $late; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des présences -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-semibold">Liste des présences</h2>
            <form action="export_presence_csv.php" method="POST" class="inline">
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                <input type="hidden" name="time_range" value="<?php echo htmlspecialchars($time_range); ?>">
                <input type="hidden" name="custom_start" value="<?php echo htmlspecialchars($custom_start); ?>">
                <input type="hidden" name="custom_end" value="<?php echo htmlspecialchars($custom_end); ?>">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                <input type="hidden" name="service" value="<?php echo htmlspecialchars($service); ?>">
                <input type="hidden" name="bureau" value="<?php echo htmlspecialchars($bureau); ?>">
                <input type="hidden" name="employee" value="<?php echo htmlspecialchars($employee); ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition">
                    Exporter en CSV
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table id="presence-table" class="w-full text-left">
                <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">Photo</th>
                        <th class="px-6 py-4">Nom Prénom</th>
                        <th class="px-6 py-4">Service</th>
                        <th class="px-6 py-4">Bureau</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Heure</th>
                        <th class="px-6 py-4 w-48">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($presences as $presence): ?>
                        <tr class="hover:bg-gray-50 text-sm entry-row <?php echo $presence['type'] === 'arrivée' ? 'arrival' : 'departure'; ?>">
                            <td class="px-6 py-4">
                                <?php 
                                $photoExists = !empty($presence['photo']) && $presence['photo'] !== 'NULL' && file_exists($presence['photo']);
                                if ($photoExists): ?>
                                    <img src="<?php echo htmlspecialchars($presence['photo']); ?>" 
                                         alt="Photo de <?php echo htmlspecialchars($presence['nom_prenom']); ?>"
                                         class="w-10 h-10 rounded-full object-cover"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-medium">
                                            <?php echo strtoupper(substr($presence['nom_prenom'], 0, 1) . substr(explode(' ', $presence['nom_prenom'])[1], 0, 1)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($presence['nom_prenom']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($presence['service']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($presence['bureau']); ?></td>
                            <td class="px-6 py-4"><?php echo date('d/m/Y', strtotime($presence['date'])); ?></td>
                            <td class="px-6 py-4"><?php echo date('H:i', strtotime($presence['heure'])); ?></td>
                            <td class="px-6 py-4 min-w-[180px]">
                                <?php if ($presence['type'] === 'arrivée'): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap <?php echo $presence['heure'] > '09:00:00' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                        <svg class="w-3.5 h-3.5 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                        </svg>
                                        <span class="truncate"><?php echo $presence['heure'] > '09:00:00' ? 'Arrivée (Retard)' : 'Arrivée'; ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap <?php echo $presence['heure'] < '17:00:00' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'; ?>">
                                        <svg class="w-3.5 h-3.5 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                        </svg>
                                        <span class="truncate"><?php echo $presence['heure'] < '17:00:00' ? 'Départ anticipé' : 'Départ'; ?></span>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>