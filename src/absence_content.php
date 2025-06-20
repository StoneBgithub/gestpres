<?php
// Démarrer la session uniquement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données
require_once "db_connect.php";

// Récupérer les paramètres de recherche et filtres
$search = $_GET['search'] ?? '';
$filter_agent = $_GET['filter_agent'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_statut = $_GET['filter_statut'] ?? '';
$date= $_GET['date'] ?? '';
$filter_service=$_GET['filtrer_services'] ?? '';

// Vérifier si l'utilisateur est connecté
$agent_conn = $_SESSION['user_id'] ?? null;
$messages = ['success' => [], 'errors' => []];
if (!$agent_conn) {
    header("Location: login.php");
    exit();
}

// Vérifier si l'utilisateur connecté a un login.id valide
try {
    $stmt = $pdo->prepare("SELECT id FROM login WHERE agent_id = :agent_id");
    $stmt->execute(['agent_id' => $agent_conn]);
    $login_id = $stmt->fetchColumn();
    if (!$login_id) {
        $messages['errors'][] = "Erreur : Aucun compte de connexion trouvé pour l'utilisateur connecté.";
    }
} catch (PDOException $e) {
    error_log("Erreur dans absences_justifiees.php (vérification login) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la vérification du compte.";
}

// Récupérer les agents pour le formulaire


// Types d'absences justifiées
$sql = "SELECT id, libelle FROM type_absence";
$stmt = $pdo->query($sql);

// Stockage sous forme de tableau associatif [id => libelle]
$types_absences = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $types_absences[$row['id']] = $row['libelle'];
}
// Statuts des absences
$sql2 = "SELECT id, libelle FROM statut_absence";
$result = $pdo->query($sql2);
$statuts = [];
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $statuts[$row['id']] = $row['libelle'];
}

$sql3 = "SELECT id, libele FROM service";
$stmt1 = $pdo->query($sql3);
$services = [];
while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
    $services[$row['id']] = $row['libele'];
}

$sql4 = "SELECT id, libele FROM bureau";
$stmt4 = $pdo->query($sql4);
$bureaux2 = [];
while ($row = $stmt4->fetch(PDO::FETCH_ASSOC)) {
    $bureaux2[$row['id']] = $row['libele'];
}

// Gestion des requêtes POST (ajout ou modification)
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($messages['errors'])) {
    $action = $_POST['actions'] ?? 'add';
    $absence_id = isset($_POST['absence_id']) ? (int)$_POST['absence_id'] : null;
    $agent_id = isset($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;
    $type_absence = trim($_POST['type_absence'] ?? '');
    $date_debut_absence = trim($_POST['date_debut_absence'] ?? '');
    $date_fin_absence = trim($_POST['date_fin_absence'] ?? '');
    $bureaux2= trim($_POST['bureau_id'] ?? '');
    $motif= trim($_POST['type_absence'] ?? '');
    $statut = trim($_POST['statut'] ?? 'en_attente');
    $description = trim($_POST['description'] ?? '');

    // Vérification des champs obligatoires
    if (!$agent_id) $messages['errors'][] = "L'agent est requis.";
    if (empty($type_absence)) $messages['errors'][] = "Le type d'absence est requis.";
    if (empty($date_debut_absence)) $messages['errors'][] = "La date de début est requise.";
    if (empty($date_fin_absence)) $messages['errors'][] = "La date de fin est requise.";
    if (empty($motif)) $messages['errors'][] = "Le motif est requis.";

    // Vérification des dates
    if (!empty($date_debut_absence) && !empty($date_fin_absence)) {
        $debut = new DateTime($date_debut_absence);
        $fin = new DateTime($date_fin_absence);
        if ($debut > $fin) {
            $messages['errors'][] = "La date de début ne peut pas être postérieure à la date de fin.";
        }
    }

    // Vérification des chevauchements d'absences pour le même agent
    if (!empty($date_debut_absence) && !empty($date_fin_absence) && $agent_id) {
        try {
            $sql_overlap = "SELECT COUNT(*) FROM absences_justifiees 
                           WHERE agent_id = :agent_id 
                           AND id != :absence_id
                           AND statut != 'refusee'
                           AND (
                               (date_debut <= :date_debut AND date_fin >= :date_debut) OR
                               (date_debut <= :date_fin AND date_fin >= :date_fin) OR
                               (date_debut >= :date_debut AND date_fin <= :date_fin)
                           )";
            $stmt_overlap = $pdo->prepare($sql_overlap);
            $stmt_overlap->execute([
                'agent_id' => $agent_id,
                'absence_id' => $absence_id ?? 0,
                'date_debut' => $date_debut_absence,
                'date_fin' => $date_fin_absence
            ]);
            
            if ($stmt_overlap->fetchColumn() > 0) {
                $messages['errors'][] = "Une absence existe déjà pour cet agent sur cette période.";
            }
        } catch (PDOException $e) {
            error_log("Erreur dans absences_justifiees.php (vérification chevauchement) : " . $e->getMessage());
            $messages['errors'][] = "Erreur lors de la vérification des chevauchements.";
        }
    }

    // Gestion du justificatif
    $justificatif_path = null;
    if (empty($messages['errors']) && isset($_FILES['justificatif']) && $_FILES['justificatif']['error'] === UPLOAD_ERR_OK) {
        $justificatifTmp = $_FILES['justificatif']['tmp_name'];
        $original = basename($_FILES['justificatif']['name']);
        $cleanName = preg_replace("/[^a-zA-Z0-9_\-\.]/", "_", $original);
        $justificatifName = uniqid() . '_' . $cleanName;

        $targetDir = "justificatifs/";
        $justificatif_path = $targetDir . $justificatifName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (!move_uploaded_file($justificatifTmp, $justificatif_path)) {
            $messages['errors'][] = "Erreur lors du téléchargement du justificatif.";
        }

        // Si modification, supprimer l'ancien justificatif
        if ($action === 'update' && $absence_id && empty($messages['errors'])) {
            try {
                $stmt = $pdo->prepare("SELECT justificatif FROM absences_justifiees WHERE id = :id");
                $stmt->execute(['id' => $absence_id]);
                $oldJustificatif = $stmt->fetchColumn();
                
                if ($oldJustificatif && file_exists($oldJustificatif)) {
                    unlink($oldJustificatif);
                }
            } catch (PDOException $e) {
                error_log("Erreur dans absences_justifiees.php (suppression ancien justificatif) : " . $e->getMessage());
            }
        }
    }

    // Ajout ou modification de l'absence
    if (empty($messages['errors'])) {
        try {
            if ($action === 'add' && !$absence_id) {
                // Ajout d'une nouvelle absence
                $stmt = $pdo->prepare("INSERT INTO absences_justifiees (agent_id, type_absence, date_debut, date_fin, motif, statut, commentaire, justificatif, date_creation)
                                      VALUES (:agent_id, :type_absence, :date_debut, :date_fin, :motif, :statut, :commentaire, :justificatif, NOW())");
                $stmt->execute([
                    'agent_id' => $agent_id,
                    'type_absence' => $type_absence,
                    'date_debut' => $date_debut_absence,
                    'date_fin' => $date_fin_absence,
                    'motif' => $motif,
                    'statut' => $statut,
                    'commentaire' => $commentaire,
                    'justificatif' => $justificatif_path
                ]);
                $messages['success'][] = "Absence justifiée enregistrée avec succès.";

                // Journalisation
                if ($login_id) {
                    $donnees = json_encode([
                        'agent_id' => $agent_id,
                        'type_absence' => $type_absence,
                        'date_debut' => $date_debut_absence,
                        'date_fin' => $date_fin_absence,
                        'motif' => $motif,
                        'statut' => $statut
                    ], JSON_UNESCAPED_UNICODE);
                    $date_action = date('Y-m-d H:i:s');
                    $action_type = 'ajouter_absence';

                    $stmt = $pdo->prepare("INSERT INTO journal_actions (ag_id, action_type, donnees, date_action)
                                           VALUES (:ag_id, :action_type, :donnees, :date_action)");
                    $stmt->execute([
                        'ag_id' => $login_id,
                        'action_type' => $action_type,
                        'donnees' => $donnees,
                        'date_action' => $date_action,
                    ]);
                }
            } elseif ($action === 'update' && $absence_id) {
                // Modification d'une absence existante
                $sql = "UPDATE absences_justifiees SET 
                        agent_id = :agent_id,
                        type_absence = :type_absence,
                        date_debut = :date_debut,
                        date_fin = :date_fin,
                        motif = :motif,
                        statut = :statut,
                        commentaire = :commentaire";
                if ($justificatif_path) {
                    $sql .= ", justificatif = :justificatif";
                }
                $sql .= " WHERE id = :id";

                $stmt = $pdo->prepare($sql);
                $params = [
                    'id' => $absence_id,
                    'agent_id' => $agent_id,
                    'type_absence' => $type_absence,
                    'date_debut' => $date_debut_absence,
                    'date_fin' => $date_fin_absence,
                    'motif' => $motif,
                    'statut' => $statut,
                    'commentaire' => $commentaire
                ];
                if ($justificatif_path) {
                    $params['justificatif'] = $justificatif_path;
                }
                $stmt->execute($params);
                $messages['success'][] = "Absence justifiée mise à jour avec succès.";

                // Journalisation
                if ($login_id) {
                    $donnees = json_encode([
                        'agent_id' => $agent_id,
                        'type_absence' => $type_absence,
                        'date_debut' => $date_debut_absence,
                        'date_fin' => $date_fin_absence,
                        'motif' => $motif,
                        'statut' => $statut
                    ], JSON_UNESCAPED_UNICODE);
                    $date_action = date('Y-m-d H:i:s');
                    $action_type = 'modifier_absence';

                    $stmt = $pdo->prepare("INSERT INTO journal_actions (ag_id, action_type, donnees, date_action)
                                           VALUES (:ag_id, :action_type, :donnees, :date_action)");
                    $stmt->execute([
                        'ag_id' => $login_id,
                        'action_type' => $action_type,
                        'donnees' => $donnees,
                        'date_action' => $date_action,
                    ]);
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur dans absences_justifiees.php (ajout/modification) : " . $e->getMessage());
            $messages['errors'][] = "Erreur lors de l'enregistrement de l'absence.";
        }
    }
}

// Suppression d'une absence
if (isset($_GET['actions']) && $_GET['actions'] === 'delete' && isset($_GET['id']) && empty($messages['errors'])) {
    $id = (int)$_GET['id'];

    try {
        // Récupérer les informations de l'absence avant suppression
        $stmt = $pdo->prepare("SELECT aj.*, CONCAT(a.prenom, ' ', a.nom) AS nom_agent
                               FROM absences_justifiees aj
                               JOIN agent a ON aj.agent_id = a.id
                               WHERE aj.id = :id");
        $stmt->execute(['id' => $id]);
        $absence = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($absence) {
            // Supprimer l'enregistrement de l'absence
            $stmt = $pdo->prepare("DELETE FROM absences_justifiees WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Supprimer le justificatif s'il existe
            if (!empty($absence['justificatif']) && file_exists($absence['justificatif'])) {
                unlink($absence['justificatif']);
            }

            $messages['success'][] = "Absence supprimée avec succès.";

            // Journalisation
            if ($login_id) {
                $donnees = json_encode([
                    'agent_id' => $absence['agent_id'],
                    'nom_agent' => $absence['nom_agent'],
                    'type_absence' => $absence['type_absence'],
                    'date_debut' => $absence['date_debut'],
                    'date_fin' => $absence['date_fin'],
                    'motif' => $absence['motif']
                ], JSON_UNESCAPED_UNICODE);
                $date_action = date('Y-m-d H:i:s');
                $action_type = 'supprimer_absence';

                $stmt = $pdo->prepare("INSERT INTO journal_actions (ag_id, action_type, donnees, date_action)
                                       VALUES (:ag_id, :action_type, :donnees, :date_action)");
                $stmt->execute([
                    'ag_id' => $login_id,
                    'action_type' => $action_type,
                    'donnees' => $donnees,
                    'date_action' => $date_action,
                ]);
            }
        } else {
            $messages['errors'][] = "Erreur : Absence introuvable.";
        }
    } catch (PDOException $e) {
        error_log("Erreur dans absences_justifiees.php (suppression) : " . $e->getMessage());
        $messages['errors'][] = "Erreur lors de la suppression.";
    }
}

// Construction de la requête de recherche
$sql = "SELECT 
    aj.id,
    aj.type_absence,
    aj.date_debut,
    aj.date_fin,
    aj.motif,
    aj.statut,
    aj.commentaire,
    aj.justificatif,
    aj.date_creation,
    CONCAT(a.prenom, ' ', a.nom) AS nom_agent,
    a.matricule,
    DATEDIFF(aj.date_fin, aj.date_debut) + 1 AS duree_jours
FROM absences aj
JOIN agent a ON aj.agent_id = a.id
WHERE 1=1";

$params = [];

$agents = [];
$sql5 = "SELECT 
    a.id,
    a.nom,
    a.prenom,
    CONCAT(a.prenom, ' ', a.nom) AS nom_prenom,
    a.matricule,
    a.email,
    a.telephone,
    a.photo,
    a.bureau_id,
    b.libele AS libele_bureau,
    s.libele AS libele_service
FROM agent a
JOIN bureau b ON a.bureau_id = b.id
JOIN service s ON b.service_id = s.id
WHERE a.telephone LIKE :search OR CONCAT(a.prenom, ' ', a.nom) LIKE :search";

try {
    $stmt5 = $pdo->prepare($sql);
    $stmt5->execute(['search' => "%$search%"]);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans agent_content.php (récupération agents) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des agents.";
}
// Filtres de recherche
if (!empty($search)) {
    $sql .= " AND (CONCAT(a.prenom, ' ', a.nom) LIKE :search OR aj.motif LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($filter_agent)) {
    $sql .= " AND aj.agent_id = :filter_agent";
    $params['filter_agent'] = $filter_agent;
}

if (!empty($filter_type)) {
    $sql .= " AND aj.type_absence = :filter_type";
    $params['filter_type'] = $filter_type;
}

if (!empty($filter_statut)) {
    $sql .= " AND aj.statut = :filter_statut";
    $params['filter_statut'] = $filter_statut;
}

if (!empty($date_debut)) {
    $sql .= " AND aj.date_debut >= :date_debut";
    $params['date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND aj.date_fin <= :date_fin";
    $params['date_fin'] = $date_fin;
}

$sql .= " ORDER BY aj.date_creation DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans absences_justifiees.php (récupération absences) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des absences.";
    $absences = [];
}
?>

<?php
// Stocker les données dans un élément invisible pour le JS
echo '<script id="agentsData" type="application/json">' . json_encode($agents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="bureauxData" type="application/json">' . json_encode($bureaux2, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
?>

<!-- Filtres et recherche -->
<div class="bg-gradient-to-r from-indigo-50 to-blue-50 p-4 sm:p-6 rounded-xl shadow-sm mb-6 transition-all hover:shadow-md">
    <div class="flex items-center mb-4">
        <i class="fas fa-filter text-indigo-600 mr-2"></i>
        <h2 class="text-base sm:text-lg font-semibold text-gray-700">Recherche et filtres</h2>
    </div>
    <form action="#" method="get" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <input type="hidden" name="page" value="absence_content">
        <div class="relative">
           <label for="search" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Recherche par nom/motif</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" name="search" id="search"
                    placeholder="Rechercher un agent..."
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>
        </div>
        <div>
            <label for="filter_service" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par type d'absence</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-building text-gray-400"></i>
                </div>
                <select name="filter_service" id="filter_service"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les types</option>
                    <?php foreach ($types_absences as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $filter_type === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label for="filter_bureau" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par statut</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-door-open text-gray-400"></i>
                </div>
                <select  name="filter_bureau" id="filter_bureau"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                 <option value="">Tous les statuts</option>
    <?php foreach ($statuts as $id => $libelle): ?>
        <option value="<?= $id ?>" <?= ($filter_statut == $id) ? 'selected' : '' ?>>
            <?= htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') ?>
        </option>
    <?php endforeach; ?>
                </select>
            </div>
        </div>
      <div class="flex items-end space-x-2">
            <a href="?page=absence_content"
                class="px-3 py-2 text-sm bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                <i class="fas fa-redo-alt"></i>
            </a>
            <button type="button"
                class="add-agent-btns px-3 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i> Nouvelle absence
            </button>
        </div>
    </form>
</div>

<!-- Affichage des agents - Vue tableau -->
<div class="hidden lg:block overflow-x-auto rounded-xl shadow-sm bg-white" id="agentTables">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-semibold">
            <tr>
                <th scope="col" class="px-4 py-3 text-left">Agent</th>
                <th scope="col" class="px-4 py-3 text-left">Date de Debut</th>
                <th scope="col" class="px-4 py-3 text-left">Date de fin</th>
                <th scope="col" class="px-4 py-3 text-left">Type d'absence</th>
                <th scope="col" class="px-4 py-3 text-right">Justificatif</th>
                <th scope="col" class="px-4 py-3 text-right">Autorisation</th>
            </tr>
        </thead>
        
    </table>
</div>
<div id="agentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md sm:max-w-lg md:max-w-4xl p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
         id="agentModalContent">
        <!-- Header modal -->
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 id="modalTitle" class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-user-plus mr-2 text-indigo-600"></i>
                <span>Ajouter un nouvel agent</span>
            </h3>
            <button class="close-modals text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Formulaire -->
        <form id="agentForm" action="?page=absence_content" method="post" enctype="multipart/form-data" class="p-4 sm:p-6">
            <input type="hidden" id="agent_ids" name="agent_id" value="">
            <input type="hidden" id="actions" name="action" value="add">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Bureau -->
                <div>
                    <label for="bureau_id" class="block text-sm font-medium text-gray-700 mb-1">Bureau</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-building text-gray-400"></i>
                        </div>
                      <select name="bureau_id" id="bureau_ids"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                     <option>Choisir un bureau</option>
                    <?php foreach ($bureaux2 as $key => $label): ?>
                        <option value="<?= $key ?>": ?>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
</select>

                    </div>
                </div>

                <!-- Agent -->
                <div>
                    <label for="agent_id" class="block text-sm font-medium text-gray-700 mb-1">Agent</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user-tag text-gray-400"></i>
                        </div>
                        <select disabled id="agent_id" name="agent_id" required
                                class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="" >choisir un agent</option>
                            <!-- Options dynamiques à insérer ici -->
                        </select>
                    </div>
                </div>

                <!-- Date début -->
                <div>
                    <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                    <input type="date" name="date_debut" id="date_debut" required
                           class="w-full pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Date fin -->
                <div>
                    <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                    <input type="date" name="date_fin" id="date_fin" required
                           class="w-full pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Motif -->
                <div>
                    <label for="type_absence" class="block text-sm font-medium text-gray-700 mb-1">Motif</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-list text-gray-400"></i>
                        </div>
                         <select name="type_absence" id="type_absence"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                     <option>choisir un motif</option>
                    <?php foreach ($types_absences as $key => $label): ?>
                        <option value="<?= $key ?>": ?>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-info-circle text-gray-400"></i>
                        </div>
                        <input type="text" name="description" id="description" maxlength="255"
                               class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <!-- Justificatif -->
                <div class="sm:col-span-2">
                    <label for="justificatif" class="block text-sm font-medium text-gray-700 mb-1">Justificatif</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-camera text-gray-400"></i>
                        </div>
                        <input type="file" name="justificatif" id="justificatif" accept="image/*"
                               class="block w-full pl-10 pr-4 py-2 border border-gray-300 text-sm rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer file:cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
                    </div>
                </div>
            </div>

            <!-- Boutons -->
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button"
                        class="close-modal px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <button type="submit"
                        class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>


<div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
         id="qrModalContents">
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-qrcode mr-2 text-green-600"></i>
                <span>QR Code de l'agent</span>
            </h3>
            <button class="close-modals text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 sm:p-6">
            <div class="flex justify-center mb-4">
                <div id="qrCodeContainer" class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm"></div>
            </div>
            <div class="text-center mb-6">
                <p id="qrAgentName" class="text-base sm:text-lg font-medium text-gray-800"></p>
                <p id="qrAgentInfo" class="text-xs sm:text-sm text-gray-600"></p>
            </div>
            <div class="flex justify-center space-x-3">
                <button type="button"
                        class="close-modals px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Fermer
                </button>
                <button type="button" id="downloadQRBtns"
                        class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-download mr-2"></i> Télécharger
                </button>
            </div>
        </div>
    </div>
</div>

</div>
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
         id="deleteModalContent">
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>
                <span>Confirmer la suppression</span>
            </h3>
            <button class="close-modals text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 sm:p-6">
            <p class="text-gray-700 text-sm sm:text-base mb-6">Êtes-vous sûr de vouloir supprimer cet agent ? Cette action est irréversible.</p>
            <div class="flex justify-end space-x-3">
                <button type="button"
                        class="close-modal px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <a id="confirmDeleteBtn" href="#"
                   class="px-3 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Supprimer
                </a>
            </div>
        </div>
    </div>
</div>