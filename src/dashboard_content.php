<?php
require_once 'db_connect.php';

// Récupérer la date actuelle
$current_date = date('Y-m-d');

// Récupérer le premier enregistrement de présence pour définir la période
$stmt = $pdo->query("SELECT MIN(date) as first_date FROM presence");
$first_date = $stmt->fetch()['first_date'] ?? $current_date;
$first_year = date('Y', strtotime($first_date));
$first_month = date('m', strtotime($first_date));
$current_year = date('Y');
$current_month = date('m');

// Calculer le nombre de jours distincts avec activité
$stmt = $pdo->query("SELECT COUNT(DISTINCT date) as active_days FROM presence");
$active_days = $stmt->fetch()['active_days'];

// Conditions pour activer les boutons de filtre
$has_day = true; // Toujours actif pour aujourd'hui
$has_week = $active_days > 1; // Actif si plus d'un jour d'activité
$has_month = $active_days >= 7; // Actif si au moins une semaine
$has_year = $active_days > 30; // Actif si plus d'un mois

// Récupérer tous les agents
$stmt = $pdo->query("SELECT COUNT(*) as total_agents FROM agent");
$total_agents = $stmt->fetch()['total_agents'];

// Récupérer les agents présents aujourd'hui (au moins une arrivée)
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT agent_id) as present_agents FROM presence WHERE date = ? AND type = 'arrivée'");
$stmt->execute([$current_date]);
$present_agents = $stmt->fetch()['present_agents'];

// Calculer les absences pour aujourd'hui (agents sans aucune entrée)
$absent_agents = $total_agents - $present_agents;

// Calculer le taux de présence
$presence_rate = $total_agents > 0 ? round(($present_agents / $total_agents) * 100) : 0;

// Calculer les retards (arrivées après 9h00)
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT agent_id) as late_agents FROM presence WHERE date = ? AND type = 'arrivée' AND heure > '09:00:00'");
$stmt->execute([$current_date]);
$late_agents = $stmt->fetch()['late_agents'];

// Récupérer les 3 dernières activités récentes
$stmt = $pdo->query("SELECT p.*, a.nom, a.prenom FROM presence p JOIN agent a ON p.agent_id = a.id ORDER BY p.date DESC, p.heure DESC LIMIT 3");
$recent_activities = $stmt->fetchAll();

// Liste des mois en français
$months = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
    '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];

// Vérifier le nombre d'agents absents depuis plus de 15 jours (sans absence justifiée sur les 15 derniers jours)
$query_check_agents_absents = "
    SELECT COUNT(*) AS nb_agents_absents FROM (
        SELECT a.id
        FROM agent a
        LEFT JOIN presence p ON a.id = p.agent_id
        LEFT JOIN absence ab ON a.id = ab.agent_id
            AND ab.date_debut <= CURDATE()
            AND ab.date_fin >= CURDATE() - INTERVAL 14 DAY
        WHERE ab.id IS NULL  
        GROUP BY a.id
        HAVING MAX(p.date) IS NULL OR MAX(p.date) <= CURDATE() - INTERVAL 15 DAY
    ) AS agents_absents
";
$stmt_check = $pdo->query($query_check_agents_absents);
$nb_absences_longues= $stmt_check->fetch()['nb_agents_absents'];

$sql = "
   SELECT a.id AS agent_id,CONCAT( a.nom,' ',a.prenom) as nom_prenom ,a.telephone as telephone, b.libele AS bureau, MAX(p.date) AS derniere_presence, DATEDIFF(CURDATE(), MAX(p.date)) AS nb_jours_absence_estimee 
   FROM agent a LEFT JOIN presence p ON a.id = p.agent_id 
   LEFT JOIN absence ab ON a.id = ab.agent_id AND DATEDIFF(ab.date_fin, ab.date_debut) + 1 >= 15 AND ab.date_fin >= CURDATE() - INTERVAL 30 DAY 
   LEFT JOIN bureau b ON a.bureau_id = b.id WHERE ab.id IS NULL GROUP BY a.id, a.nom, a.prenom, a.telephone, b.libele HAVING MAX(p.date) IS NULL OR MAX(p.date) < CURDATE()
    ORDER BY nb_jours_absence_estimee DESC, a.nom ASC
";

$agents_absences = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div class="inline-flex items-center">
            <span class="text-lg font-medium text-gray-500 font-display">Statistiques</span>
            <span class="mx-2 text-gray-400">•</span>
        </div>
        
        <div class="flex flex-wrap gap-2 items-center">
            <button id="filter-day" class="px-3 py-1.5 <?php echo $has_day ? 'custom-btn text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed'; ?> rounded-lg text-sm font-medium card-shine font-body" <?php echo !$has_day ? 'disabled' : ''; ?>>Aujourd'hui</button>
            <button id="filter-week" class="px-3 py-1.5 <?php echo $has_week ? 'bg-white border border-gray-200 text-gray-700 hover:custom-btn hover:bg-gray-200 transition' : 'bg-gray-300 text-gray-500 cursor-not-allowed'; ?> rounded-lg text-sm font-medium transition card-shine font-body" <?php echo !$has_week ? 'disabled' : ''; ?>>Cette semaine</button>
            <button id="filter-month" class="px-3 py-1.5 <?php echo $has_month ? 'bg-white border border-gray-200 text-gray-700 hover:custom-btn hover:bg-gray-200 transition' : 'bg-gray-300 text-gray-500 cursor-not-allowed'; ?> rounded-lg text-sm font-medium transition card-shine font-body" <?php echo !$has_month ? 'disabled' : ''; ?>>Ce mois</button>
            <button id="filter-year" class="px-3 py-1.5 <?php echo $has_year ? 'bg-white border border-gray-200 text-gray-700 hover:custom-btn hover:bg-gray-200 transition' : 'bg-gray-300 text-gray-500 cursor-not-allowed'; ?> rounded-lg text-sm font-medium transition card-shine font-body" <?php echo !$has_year ? 'disabled' : ''; ?>>Cette année</button>
            
            <a href="#" class="toggle-filters-btn px-3 py-1.5 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-congo-yellow-pale hover:text-congo-green-dark transition card-shine font-body flex items-center">
                Filtres avancés
            </a>

        <?php if ($nb_absences_longues > 0): ?> 
    <button id="openModalBtn"
            class="alerte-absence-btns px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 rounded-lg text-sm font-medium hover:from-red-600 hover:to-red-700 transition-all duration-200 card-shine font-body flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:scale-105">
        
        <!-- Emoji cloche -->
        <span class="text-xl animate-pulse">🔔</span>

        <span>Alerte Absences</span>
        
        <!-- Badge après le texte -->
        <span class="bg-red-600 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center animate-bounce">
            <?php echo $nb_absences_longues; ?>
        </span>

    </button>
<?php endif; ?>



        </div>
    </div>
</div>

<!-- Filtres avancés (reste identique) -->
<div class="advanced-filters bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-6 hidden">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label for="year" class="block text-sm font-medium text-gray-700 mb-1 font-body">Année</label>
            <select id="year" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3 font-body">
                <?php
                for ($y = $current_year; $y >= $first_year; $y--) {
                    $selected = $y == $current_year ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label for="month" class="block text-sm font-medium text-gray-700 mb-1 font-body">Mois</label>
            <select id="month" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3 font-body">
                <option value="all" selected>Tous les mois</option>
                <?php
                $start_year = $first_year;
                $start_month = $first_month;
                $end_year = $current_year;
                $end_month = $current_month;

                for ($y = $start_year; $y <= $end_year; $y++) {
                    $month_start = ($y == $start_year) ? $start_month : '01';
                    $month_end = ($y == $end_year) ? $end_month : '12';
                    for ($m = $month_start; $m <= $month_end; $m++) {
                        $month_num = str_pad($m, 2, '0', STR_PAD_LEFT);
                        $selected = ($y == $current_year && $month_num == $current_month) ? 'selected' : '';
                        echo "<option value='$month_num'>{$months[$month_num]}</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div>
            <label for="week" class="block text-sm font-medium text-gray-700 mb-1 font-body">Semaine</label>
            <select id="week" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3 font-body" disabled>
                <option value="all" selected>Toutes les semaines</option>
            </select>
        </div>
    </div>
    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label for="service" class="block text-sm font-medium text-gray-700 mb-1 font-body">Service</label>
            <select id="service" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3 font-body">
                <option value="all" selected>Tous les services</option>
                <?php
                $stmt = $pdo->query("SELECT id, libele FROM service");
                while ($service = $stmt->fetch()) {
                    echo "<option value='{$service['id']}'>{$service['libele']}</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label for="bureau" class="block text-sm font-medium text-gray-700 mb-1 font-body">Bureau</label>
            <select id="bureau" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3 font-body" disabled>
                <option value="all" selected>Tous les bureaux</option>
            </select>
        </div>
        <div>
            <label for="employee" class="block text-sm font-medium text-gray-700 mb-1 font-body">Employé</label>
            <select id="employee" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-2 px-3 font-body" disabled>
                <option value="all" selected>Tous les employés</option>
            </select>
        </div>
    </div>
    <div class="mt-6 flex justify-end space-x-3">
        <button id="reset-filters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition card-shine font-body">Réinitialiser</button>
        <button id="apply-filters" class="px-4 py-2 custom-btn text-white rounded-lg text-sm font-medium card-shine font-body">Appliquer les filtres</button>
    </div>
</div>

<!-- Cartes statistiques (reste identique) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-shine">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <p class="text-gray-500 text-sm font-body">Présences</p>
                <h3 class="text-2xl font-bold font-display" id="present-agents"><?php echo $present_agents; ?></h3>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-shine">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-gray-500 text-sm font-body">Absences</p>
                <h3 class="text-2xl font-bold font-display" id="absent-agents"><?php echo $absent_agents; ?></h3>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-shine">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-gray-500 text-sm font-body">Taux Présence</p>
                <h3 class="text-2xl font-bold font-display" id="presence-rate"><?php echo "$presence_rate%"; ?></h3>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-shine">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-lg bg-yellow-100 flex items-center justify-center mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-gray-500 text-sm font-body">Retards</p>
                <h3 class="text-2xl font-bold font-display" id="late-agents"><?php echo $late_agents; ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques et activités récentes (reste identique) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-shine">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold font-display">Suivi des Présences</h2>
        </div>
        <div class="h-64">
            <canvas id="presence-chart"></canvas>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-shine">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold font-display">Activités Récentes</h2>
            <a href="dashboard.php?page=presence_content" class="text-blue-600 text-sm hover:text-blue-800 transition font-body">Voir tout</a>
        </div>
        <div class="space-y-3" id="recent-activities">
            <?php foreach ($recent_activities as $activity): ?>
                <div class="flex items-center py-2 border-b border-gray-100 flex-col sm:flex-row">
                    <div class="w-10 h-10 rounded-full bg-<?php echo $activity['type'] === 'arrivée' ? 'blue-100' : 'red-100'; ?> flex items-center justify-center mb-2 sm:mb-0 sm:mr-3">
                        <span class="text-<?php echo $activity['type'] === 'arrivée' ? 'blue-600' : 'red-600'; ?> font-medium font-body">
                            <?php echo strtoupper(substr($activity['prenom'], 0, 1) . substr($activity['nom'], 0, 1)); ?>
                        </span>
                    </div>
                    <div class="flex-1 text-center sm:text-left min-w-0">
                        <p class="text-gray-800 text-sm font-body truncate"><?php echo "{$activity['prenom']} {$activity['nom']} a enregistré son " . ($activity['type'] === 'arrivée' ? 'arrivée' : 'départ'); ?></p>
                        <p class="text-gray-500 text-xs font-body"><?php echo "Le {$activity['date']} à {$activity['heure']}"; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div><div id="simpleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white p-6 rounded-2xl shadow-lg relative w-11/12 max-w-5xl max-h-[90vh] flex flex-col">

    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-semibold">Agents en longue absence</h2>
      <button
        class="text-gray-500 border border-gray-300 rounded-full w-8 h-8 flex items-center justify-center close-modal shadow hover:shadow-md"
        aria-label="Fermer la modale"
      >
        &times;
      </button>
    </div>

    <!-- Conteneur scrollable -->
    <div class="space-y-3 text-sm overflow-y-auto" style="max-height:300px;">
      <?php if ($nb_absences_longues > 0): ?>
        <?php foreach ($agents_absences as $agent): ?>
          <div class="border border-gray-200 rounded-xl px-3 py-2 shadow-sm hover:shadow transition">
            <p><strong>Nom :</strong> <?= htmlspecialchars($agent['nom_prenom']) ?></p>
            <p><strong>Bureau :</strong> <?= htmlspecialchars($agent['bureau']) ?></p>
            <p><strong>Téléphone :</strong> <?= htmlspecialchars($agent['telephone']) ?></p>
            <p><strong>Dernière présence :</strong> <?= htmlspecialchars($agent['derniere_presence']) ?></p>
            <p><strong>Absence estimée :</strong> <?= htmlspecialchars($agent['nb_jours_absence_estimee']) ?> jours</p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-center text-gray-600">Aucun agent en longue absence.</p>
      <?php endif; ?>
    </div>

  <div style="text-align:center; margin-top: 1rem;">
  <a href="export_absences.php" target="_blank" 
     class="px-3 py-1.5 gradient-bg text-white rounded hover:bg-blue-700 transition font-body">
     Exporter en PDF
  </a>
</div>



  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
 const modal = document.getElementById("simpleModal");
const openBtn = document.getElementById("openModalBtn");
const closeBtn = modal.querySelector(".close-modal");

openBtn.addEventListener("click", () => {
  modal.classList.remove("hidden");
  document.body.classList.add("modal-open"); // Désactive les clics sur le fond
});

closeBtn.addEventListener("click", () => {
  modal.classList.add("hidden");
  document.body.classList.remove("modal-open"); // Réactive les clics
});

modal.addEventListener("click", (e) => {
  if (e.target === modal) {
    modal.classList.add("hidden");
    document.body.classList.remove("modal-open");
  }
});

// Attendre que la page soit chargée
  document.addEventListener('DOMContentLoaded', function() {
    const { jsPDF } = window.jspdf;
    const exportBtn = document.getElementById('exportPdfBtn');
    const modalContent = document.getElementById('modalContent');

    exportBtn.addEventListener('click', () => {
      // Crée une nouvelle instance jsPDF
      const doc = new jsPDF();

      // On récupère le texte brut de la modale
      const text = modalContent.innerText;

      // Ajoute le texte dans le PDF (x=10, y=10)
      doc.text(text, 10, 10);

      // Télécharge le PDF, ici nommé "agents_absence.pdf"
      doc.save('agents_absence.pdf');
    });
  });

</script>


