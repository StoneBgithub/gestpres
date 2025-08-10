<?php
// Démarrer la session uniquement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données
require_once "db_connect.php";

// Récupérer le paramètre de recherche
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';

// Vérifier si l'utilisateur est connecté
$agent_conn = $_SESSION['user_id'] ?? null;
$messages = ['success' => [], 'errors' => []];
if (!$agent_conn) {
    header("Location: login.php");  
    exit();
}
$absences = [];
// Vérifier si l'utilisateur connecté a un login.id valide
try {
    $stmt31 = $pdo->prepare("SELECT id FROM login WHERE agent_id = :agent_id");
    $stmt31->execute(['agent_id' => $agent_conn]);
    $login_id = $stmt31->fetchColumn();
    if (!$login_id) {
        $messages['errors'][] = "Erreur : Aucun compte de connexion trouvé pour l'utilisateur connecté.";
    }
} catch (PDOException $e) {
    error_log("Erreur dans absence_content.php (vérification login) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la vérification du compte.";
}


// Récupérer les types d'absences
try {
    $stmt51 = $pdo->query("SELECT id, libelle FROM type_absence");
    $types_absences = $stmt51->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans absence_content.php (récupération types d'absence) : " . $e->getMessage());
    $messages['errors'][] = "Erreur de connexion à la base de données.";
}


// Récupérer les statuts des absences
try {
    $stmt61 = $pdo->query("SELECT id, libelle FROM statut_absence");
    $statuts = $stmt61->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans absence_content.php (récupération statuts) : " . $e->getMessage());
    $messages['errors'][] = "Erreur de connexion à la base de données.";
}
$role_utilisateur = null;
try {
    $stmtRole = $pdo->prepare("
        SELECT LOWER(r.libelle) 
        FROM login l 
        JOIN role r ON l.role_id = r.id 
        WHERE l.agent_id = :agent_id LIMIT 1
    ");
    $stmtRole->execute(['agent_id' => $agent_conn]);
    $role_utilisateur = $stmtRole->fetchColumn();
} catch (PDOException $e) {
    error_log("Erreur récupération rôle : " . $e->getMessage());
    $messages['errors'][] = "Erreur interne (rôle utilisateur).";
}

// Gestion des requêtes POST (ajout ou modification)
// Gestion des requêtes POST (ajout ou modification)
if ($_SERVER["REQUEST_METHOD"] === "POST" && $role_utilisateur === 'secretaire' && empty($messages['errors'])) {
    $action = $_POST['action'] ?? 'add';
    $absence_id = isset($_POST['absence_id']) ? (int)$_POST['absence_id'] : null;
    $agent_id = $_POST['agent_id'] ?? null;
    $id_type_absence = $_POST['motif'] ?? null;
    $date_debut = $_POST['date_debut'] ?? null;
    $date_fin = $_POST['date_fin'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $cree_par = $_SESSION['user_id'] ?? null;
    $justificatif_path = null;
    $hasNewJustificatif = false;

    // Validation du justificatif
    if (isset($_FILES['justificatif']) && $_FILES['justificatif']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['justificatif']['tmp_name'];
        $original = basename($_FILES['justificatif']['name']);
        $cleanName = preg_replace("/[^a-zA-Z0-9_\-\.]/", "_", $original);
        $fileName = uniqid() . '_' . $cleanName;

        $uploadDir = "justificatifs/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $justificatif_path = $uploadDir . $fileName;
        if (move_uploaded_file($tmpName, $justificatif_path)) {
            $hasNewJustificatif = true;
        } else {
            $messages['errors'][] = "Erreur lors du téléchargement du justificatif.";
        }
    }

    // Validation des champs requis
    if (!$agent_id || !$id_type_absence || !$date_debut || !$date_fin) {
        $messages['errors'][] = "Tous les champs obligatoires doivent être remplis.";
    }

    // Calcul de la durée + validation rôle autorisation
    if ($date_debut && $date_fin) {
        $d1 = new DateTime($date_debut);
        $d2 = new DateTime($date_fin);

        if ($d1 > $d2) {
            $messages['errors'][] = "La date de début ne peut pas être postérieure à la date de fin.";
        } else {
            $duree_jours = $d1->diff($d2)->days + 1;
            $libelle_role = ($duree_jours > 15) ? 'directrice' : 'chef de service';

            $stmtRole = $pdo->prepare("SELECT id FROM role WHERE libelle = :libelle");
            $stmtRole->execute(['libelle' => $libelle_role]);
            $role_id = $stmtRole->fetchColumn();

            if ($role_id) {
                $stmtLogin = $pdo->prepare("SELECT id FROM login WHERE role_id = :role_id AND statut = 'activé' LIMIT 1");
                $stmtLogin->execute(['role_id' => $role_id]);
                $autorisation_par = $stmtLogin->fetchColumn();
                if (!$autorisation_par) {
                    $messages['errors'][] = "Aucun utilisateur actif trouvé pour le rôle : $libelle_role.";
                }
            } else {
                $messages['errors'][] = "Le rôle '$libelle_role' n'existe pas.";
            }
        }
    }

    // Statut d’attente
    if (empty($messages['errors'])) {
        $stmtStatut = $pdo->prepare("SELECT id FROM statut_absence WHERE libelle = 'en attente' LIMIT 1");
        $stmtStatut->execute();
        $id_statut = $stmtStatut->fetchColumn();

        if (!$id_statut) {
            $messages['errors'][] = "Le statut 'en attente' est introuvable.";
        }
    }

    // Traitement selon l'action
    if (empty($messages['errors'])) {
        try {
            if ($action === 'add' && !$absence_id) {
                $stmt = $pdo->prepare("
                    INSERT INTO absence (agent_id, date_debut, date_fin, id_type_absence, justificatif, id_statut, description)
                    VALUES (:agent_id, :date_debut, :date_fin, :id_type_absence, :justificatif, :id_statut, :description)
                ");
                $stmt->execute([
                    'agent_id' => $agent_id,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'id_type_absence' => $id_type_absence,
                    'justificatif' => $justificatif_path,
                    'id_statut' => $id_statut,
                    'description' => $description
                ]);
                $messages['success'][] = "Absence ajoutée avec succès.";
            }

            if ($action === 'update' && $absence_id) {
                $stmt = $pdo->prepare("
                    UPDATE absence SET
                        agent_id = :agent_id,
                        date_debut = :date_debut,
                        date_fin = :date_fin,
                        id_type_absence = :id_type_absence,
                        justificatif = :justificatif,
                        description = :description
                    WHERE id = :id
                ");
                $stmt->execute([
                    'agent_id' => $agent_id,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'id_type_absence' => $id_type_absence,
                    'justificatif' => $justificatif_path,
                    'description' => $description,
                    'id' => $absence_id
                ]);
                $messages['success'][] = "Absence modifiée avec succès.";
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO : " . $e->getMessage());
            $messages['errors'][] = "Erreur lors de l'enregistrement dans la base.";
        }
    }
}

                  
// Suppression d’un agent
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && empty($messages['errors'])) {
    $id = (int)$_GET['id'];

    try {
        // Récupérer les informations de l'agent avant suppression
        $stmt = $pdo->prepare("
            SELECT agent_id, date_debut, date_fin, id_type_absence, justificatif, id_statut,description,validation 
            FROM absence
            WHERE id = :id 
        ");
        $stmt->execute(['id' => $id]);
        $absence = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($absence) {
             // Récupérer le libellé du statut
            $stmt_agent = $pdo->prepare("SELECT concat (nom, ' ' , prenom) FROM agent WHERE id = :id");
            $stmt_agent->execute(['id' => $absence['agent_id']]);
            $libele_agent = $stmt_agent->fetchColumn() ?: 'N/A';

            // Récupérer le libellé du statut
            $stmt_statut = $pdo->prepare("SELECT libelle FROM statut_absence WHERE id = :id");
            $stmt_statut->execute(['id' => $absence['statut']]);
            $libele_statut = $stmt_statut->fetchColumn() ?: 'N/A';

             // Récupérer le libellé du motif
            $stmt_type = $pdo->prepare("SELECT libelle FROM type_absence WHERE id = :id");
            $stmt_type->execute(['id' => $type['motif']]);
            $libele_type = $stmt_type->fetchColumn() ?: 'N/A';


            // Supprimer l'enregistrement de l'absence
            $stmt48 = $pdo->prepare("DELETE FROM absence WHERE id = :id");
            $stmt48->execute(['id' => $id]);

            // Supprimer la photo si elle existe
            if (!empty($absence['justificatif']) && file_exists($absence['justificatif'])) {
                unlink($bsence['justificatif']);
            }

            $messages['success'][] = "absence supprimé avec succès.";

            // Journalisation
            if ($login_id) {
                $donnees = json_encode([
                    'agent_id' =>  $libele_agent,
                    ' date_debut' => $absence['date_debut'],
                    ' date_fin' => $absence['date_fin'],
                    'id_type_absence' => $libele_motif,
                    'justificatif' => $absence['justificatif'],
                    'id_statut' => $libele_satut,
                    'description' => $absence['description']
                ], JSON_UNESCAPED_UNICODE);
                $date_action = date('Y-m-d H:i:s');
                $action_type = 'supprimer';

                $stmt = $pdo->prepare("
                    INSERT INTO journal_actions (ag_id, action_type, donnees, date_action)
                    VALUES (:ag_id, :action_type, :donnees, :date_action)
                ");
                $stmt->execute([
                    ':ag_id' => $login_id,
                    ':action_type' => $action_type,
                    ':donnees' => $donnees,
                    ':date_action' => $date_action,
                ]);
            }
        } else {
            $messages['errors'][] = "Erreur : Agent introuvable.";
        }
    } catch (PDOException $e) {
        error_log("Erreur dans absence_content.php (suppression) : " . $e->getMessage());
        $messages['errors'][] = "Erreur lors de la suppression.";
    }
}

// Construction de la requête principale
$sql = "
    SELECT 
        aj.id AS id,
        a.nom AS nom,
        a.prenom AS prenom,
        CONCAT(a.nom, ' ', a.prenom) AS nom_prenom,
        a.photo AS photo,
        aj.date_debut AS debut,
        aj.date_fin AS fin,
        t.libelle AS motif,
        aj.justificatif AS justificatif,
        s.libelle AS statut
    FROM absence aj
    INNER JOIN agent a ON aj.agent_id = a.id
    INNER JOIN type_absence t ON aj.id_type_absence = t.id
    INNER JOIN statut_absence s ON aj.id_statut = s.id
";

// Initialisation des paramètres
$params = [];

// Ajout de conditions selon le rôle de l'utilisateur
if ($role_utilisateur === 'chef de service') {
    $sql .= "
        INNER JOIN bureau b ON a.bureau_id = b.id
        INNER JOIN service serv ON b.service_id = serv.id
        WHERE DATEDIFF(aj.date_fin, aj.date_debut) + 1 <= 15
        AND serv.id = (
            SELECT b2.service_id
            FROM agent ag
            INNER JOIN bureau b2 ON ag.bureau_id = b2.id
            WHERE ag.id = :agent_id
        )
    ";
    $params['agent_id'] = $agent_conn;

} elseif ($role_utilisateur === 'directrice') {
    $sql .= "
        WHERE DATEDIFF(aj.date_fin, aj.date_debut) + 1 > 15
    ";

} else {
    // Autres rôles (secrétaire, etc.)
    $sql .= " ORDER BY aj.date_debut DESC";
}

// Exécution de la requête
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des absences : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors du chargement des absences.";
}

// Requête recherche agents
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.nom, a.prenom, CONCAT(a.prenom, ' ', a.nom) AS nom_prenom, a.matricule, 
               a.email, a.telephone, a.photo, a.bureau_id, b.libele AS libele_bureau, 
               s.libele AS libele_service 
        FROM agent a 
        JOIN bureau b ON a.bureau_id = b.id 
        JOIN service s ON b.service_id = s.id 
        WHERE a.telephone LIKE :search OR CONCAT(a.prenom, ' ', a.nom) LIKE :search
    ");
    $stmt->execute(['search' => "%$search%"]);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans absence_content.php (récupération agents) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des agents.";
}

try {
    $stmt = $pdo->query("SELECT libele FROM bureau");
    $bureaux2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans absence_content.php (récupération bureaux) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des bureaux.";
}

try {
    $stmt45 = $pdo->query("
        SELECT a.id, a.bureau_id, CONCAT(a.nom, ' ', a.prenom) AS nom_prenom, b.libele AS bureau 
        FROM agent a 
        JOIN bureau b ON a.bureau_id = b.id
        WHERE a.bureau_id = b.id
    ");
    $agents_bureau = $stmt45->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans absence_content.php (récupération agents bureau) : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors de la récupération des agents.";
}

?>

<?php
// Stocker les données dans un élément invisible pour le JS
echo '<script id="AgentsDatas" type="application/json">' . json_encode($agents_bureau, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="bureauxDatas" type="application/json">' . json_encode($bureaux2, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="AbsencesDatas" type="application/json">' . json_encode($absences, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="typesAbsencesDatas" type="application/json">' . json_encode($types_absences, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="statutsAbsencesDatas" type="application/json">' . json_encode($statuts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="roleUtilisateur" type="application/json">' . json_encode($role_utilisateur, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';

?>

<!-- Filtres et recherche -->
<div class="bg-gradient-to-r from-indigo-50 to-green-50 p-4 sm:p-6 rounded-xl shadow-sm mb-6 transition-all hover:shadow-md">
    <div class="flex items-center mb-4">
        <i class="fas fa-filter text-indigo-600 mr-2"></i>
        <h2 class="text-base sm:text-lg font-semibold text-gray-700">Recherche et filtres</h2>
    </div>
    <form action="#" method="get" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <input type="hidden" name="page" value="absence_content">
        <div class="relative">
            <label for="search_absence" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Recherche par nom/motif</label>
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
            <label for="type_absence" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par type d'absence</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-building text-gray-400"></i>
                </div>
                <select name="motif" id="filter_types"
                    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les motifs</option>
                    <?php foreach ($types_absences as $type): ?>
                        <option value="<?= htmlspecialchars($type['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($type['libelle'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label for="statut_id" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par statut</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-door-open text-gray-400"></i>
                </div>
                <select name="statut" id="filter_statuts" 
                     class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les statuts</option>
                    <?php foreach ($statuts as $statut): ?>
                        <option value="<?= htmlspecialchars($statut['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($statut['libelle'], ENT_QUOTES, 'UTF-8') ?>
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
           <?php if ($role_utilisateur === 'secretaire'): ?>
    <button type="button"
        class="add-absence-btns px-3 py-2 text-sm gradient-bg text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all flex items-center justify-center">
        <i class="fas fa-plus mr-2"></i> Nouvelle absence
    </button>
<?php endif; ?>
        </div>
    </form>
</div>

<!-- Affichage des agents - Vue carte -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6 lg:hidden" id="absencesCards">
    <?php foreach ($absences as $absence): ?>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
        <div class="p-4">
            <!-- Agent Info -->
            <div class="flex items-center mb-4">
                <div class="h-12 w-12 rounded-full flex items-center justify-center mr-3 border-2 shadow-sm">
                    <?php if (!empty($absence['photo']) && file_exists($absence['photo'])): ?>
                        <img src="<?= htmlspecialchars($absence['photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Photo de profil" class="rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <span class="text-green-600 font-medium text-xs">
                                <?= strtoupper(substr($absence['nom'], 0, 1) . substr($absence['prenom'], 0, 1)) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 class="font-semibold text-base sm:text-lg text-gray-800">
                        <?= htmlspecialchars($absence['nom_prenom'], ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                </div>
            </div>

            <!-- Absence details -->
            <div class="text-sm text-gray-600 space-y-1 mb-4">
                <div><i class="fas fa-traffic-light mr-2"></i><strong>Statut :</strong> 
                    <?php
                        $statut = strtolower($absence['statut']);
                        if ($statut === 'autoriser') {
                            echo '<span class="text-green-600">✔️ Autoriser</span>';
                        } elseif ($statut === 'rejeter') {
                            echo '<span class="text-red-600">❌ Rejeter</span>';
                        } elseif ($statut === 'en attente') {
                            echo '<span class="text-gray-600">⏳ En attente</span>';
                        } else {
                            echo '<span class="text-gray-500">Inconnu</span>';
                        }
                    ?>
                </div>
                <div><i class="fas fa-calendar-alt mr-2"></i><strong>Début :</strong> <?= htmlspecialchars($absence['debut']) ?></div>
                <div><i class="fas fa-calendar-check mr-2"></i><strong>Fin :</strong> <?= htmlspecialchars($absence['fin']) ?></div>
                <div><i class="fas fa-suitcase-rolling mr-2"></i><strong>Type :</strong> <?= htmlspecialchars($absence['motif']) ?></div>
                <div><i class="fas fa-file-alt mr-2"></i><strong>Justificatif :</strong> <?= htmlspecialchars($absence['justificatif']) ?></div>
            </div>

            <!-- Actions selon rôle -->
           <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
    <?php $statut = strtolower($absence['statut']); ?>

    <?php if ($role_utilisateur === 'secretaire'): ?>
        <?php if ($statut === 'autoriser'): ?>
            <form action="generer_autorisation.php" method="post" target="_blank">
                <input type="hidden" name="absence_id" value="<?= htmlspecialchars($absence['id']) ?>">
                <button type="submit" class="text-green-600 hover:text-green-800 text-sm" title="Imprimer l'autorisation">
                    <i class="fas fa-print mr-1"></i> 
                </button>
            </form>
        <?php else: ?>
            <button class="edit-absence-btn text-green-600 hover:text-green-900 text-sm" data-id="<?= htmlspecialchars($absence['id']) ?>" title="Modifier">
                <i class="fas fa-edit mr-1"></i> 
            </button>
            <button class="delete-absence-btn text-red-600 hover:text-red-900 text-sm" data-id="<?= htmlspecialchars($absence['id']) ?>" title="Supprimer">
                <i class="fas fa-trash mr-1"></i> 
            </button>
        <?php endif; ?>
    <?php elseif ($role_utilisateur === 'chef de service' || $role_utilisateur === 'directrice'): ?>
        <?php if ($statut === 'en attente'): ?>
            <button class="validate-absence-btn text-green-600 hover:text-green-800 text-sm" data-id="<?= htmlspecialchars($absence['id']) ?>" title="autoriser">
                <i class="fas fa-check-circle mr-1"></i> 
            </button>
            <button class="reject-absence-btn text-red-600 hover:text-red-800 text-sm" data-id="<?= htmlspecialchars($absence['id']) ?>" title="rejeter">
                <i class="fas fa-times-circle mr-1"></i> 
            </button>
        <?php elseif ($statut === 'autoriser'): ?>
            <span class="text-green-600 text-sm italic">
                <i class="fas fa-check-circle mr-1"></i> Déjà autorisé
            </span>
        <?php elseif ($statut === 'rejeter'): ?>
            <span class="text-red-600 text-sm italic">
                <i class="fas fa-times-circle mr-1"></i> Déjà rejeté
            </span>
        <?php endif; ?>
    <?php endif; ?>
</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Affichage des agents - Vue tableau -->
<div class="hidden lg:block overflow-x-auto rounded-xl shadow-sm bg-white" id="absencesTable">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-semibold">
            <tr>
                <th scope="col" class="px-4 py-3 text-left">Agent</th>
                <th scope="col" class="px-4 py-3 text-left">Date de Debut</th>
                <th scope="col" class="px-4 py-3 text-left">Date de fin</th>
                <th scope="col" class="px-4 py-3 text-left">Type d'absence</th>
                <th scope="col" class="px-4 py-3 text-right">Justificatif</th>
                <th scope="col" class="px-4 py-3 text-center">Autorisation</th>
                <th scope="col" class="px-4 py-3 text-right">Action</th>
            </tr>  
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($absences as $absence): ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                            <?php if (!empty($absence['photo']) && file_exists($absence['photo'])): ?>
                                <img src="<?= htmlspecialchars($absence['photo'], ENT_QUOTES, 'UTF-8') ?>" 
                                     alt="<?= htmlspecialchars($absence['nom_prenom'], ENT_QUOTES, 'UTF-8') ?>" 
                                     class="rounded-full object-cover"
                                     onerror="this.parentNode.innerHTML = '<div class=\'w-10 h-10 rounded-full bg-green-100 flex items-center justify-center\'><span class=\'text-green-600 font-medium text-xs\'>' + getInitials('<?= htmlspecialchars($absence['nom_prenom'], ENT_QUOTES, 'UTF-8') ?>') + '</span></div>'">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <span class="text-green-600 font-medium text-xs">
                                        <?= strtoupper(substr($absence['prenom'], 0, 1) . substr($absence['nom'], 0, 1)) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($absence['nom_prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($absence['debut'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($absence['fin'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($absence['motif'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($absence['justificatif'], ENT_QUOTES, 'UTF-8') ?></td>
               <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
    <?php if (strtolower($absence['statut']) === 'autoriser'): ?>
        <span title="autoriser" style="color:green; font-size: 18px;">✔️</span>
    <?php elseif (strtolower($absence['statut']) === 'rejeté'): ?>
        <span title="rejeter" style="color:red; font-size:18px;">❌</span>
    <?php elseif (strtolower($absence['statut']) === 'en attente'): ?>
        <span title="en attente" style="color:gray; font-size:18px;">⏳</span>
    <?php else: ?>
        <i class="fas fa-question-circle text-gray-400" title="Inconnu"></i>
    <?php endif; ?>
</td>
<td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
    <div class="flex space-x-2 justify-end">
        <?php $statut = strtolower($absence['statut']); ?>

        <?php if ($role_utilisateur === 'secretaire'): ?>
            <?php if ($statut === 'autoriser'): ?>
                <!-- Bouton Imprimer -->
                <form action="generer_autorisation.php" method="post" target="_blank">
                    <input type="hidden" name="absence_id" value="<?= htmlspecialchars($absence['id']) ?>">
                    <button type="submit" class="text-green-600 hover:text-green-800" title="Imprimer l'autorisation">
                        <i class="fas fa-print"></i>
                    </button>
                </form>
            <?php else: ?>
                <!-- Modifier / Supprimer -->
                <button class="edit-absence-btn text-green-600 hover:text-green-900"
                        data-id="<?= htmlspecialchars($absence['id'], ENT_QUOTES, 'UTF-8') ?>" title="Modifier">
                    <i class="fas fa-edit"></i>Modifier
                </button>
                <button class="delete-absence-btn text-red-600 hover:text-red-900"
                        data-id="<?= htmlspecialchars($absence['id'], ENT_QUOTES, 'UTF-8') ?>" title="Supprimer">
                    <i class="fas fa-trash"></i>Supprimer
                </button>
            <?php endif; ?>

        <?php elseif ($role_utilisateur === 'chef de service' || $role_utilisateur === 'directrice'): ?>
            <?php if ($statut === 'en attente'): ?>
                <!-- Autoriser / Refuser -->
                <button class="validate-absence-btn text-green-600 hover:text-green-800" 
                        data-id="<?= htmlspecialchars($absence['id'], ENT_QUOTES, 'UTF-8') ?>" title="autoriser">
                    <i class="fas fa-check-circle"></i>
                </button>
                <button class="reject-absence-btn text-red-600 hover:text-red-800" 
                        data-id="<?= htmlspecialchars($absence['id'], ENT_QUOTES, 'UTF-8') ?>" title="rejeter">
                    <i class="fas fa-times-circle"></i>
                </button>
            <?php elseif ($statut === 'autoriser'): ?>
                <span class="text-green-600 text-sm italic">
                    <i class="fas fa-check-circle mr-1"></i> Déjà autorisé
                </span>
            <?php elseif ($statut === 'rejeter'): ?>
                <span class="text-red-600 text-sm italic">
                    <i class="fas fa-times-circle mr-1"></i> Déjà rejeté
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal ajout/modif absence -->
<div id="absenceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md sm:max-w-lg md:max-w-4xl p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
         id="absenceModalContent">
        <!-- Header modal -->
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 id="absenceTitle" class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-user-plus mr-2 text-indigo-600"></i>
                <span>Ajouter une nouvelle absence</span>
            </h3>
            <button class="close-modals text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Formulaire -->
        <form id="absenceForm" action="?page=absence_content" method="post" enctype="multipart/form-data" class="p-4 sm:p-6">
            <input type="hidden" id="actions" name="action" value="add">
            <input type="hidden" id="absence_id" name="absence_id" value="">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label for="filter_bureau" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filtrer par bureau</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-door-open text-gray-400"></i>
                        </div>
                        <select name="bureau" id="filter_bureaux"
                            class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                           <option value="">Choisir un bureau</option>
                            <?php foreach ($bureaux2 as $b): ?>
                            <option value="<?= htmlspecialchars($b['libele'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($b['libele'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Agent -->
               <div class="mb-4">
                    <label for="select_agent_id" class="block text-sm font-medium text-gray-700 mb-1">Agent</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user-tag text-gray-400"></i>
                        </div>
                        <select disabled id="filter_agents" name="agent_id" required
                                class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">choisir un agent</option>
                            <!-- Options dynamiques -->
                        </select>
                    </div>
                </div>

                <!-- Motif -->
               <div class="mb-4">
                    <label for="type_absence" class="block text-sm font-medium text-gray-700 mb-1">Motif</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-list text-gray-400"></i>
                        </div>
                        <select name="motif" id="filter_types"
                            class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">choisir un motif</option>
                            <?php foreach ($types_absences as $type): ?>
                                <option value="<?= htmlspecialchars($type['id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($type['libelle'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                 <!-- Justificatif -->
                 <div class="mb-4">
                    <label for="justificatif" class="block text-sm font-medium text-gray-700 mb-1">Justificatif</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-camera text-gray-400"></i>
                        </div>
                        <input type="file" name="justificatif" id="justificatif" accept="image/*"
                               class="block w-full pl-10 pr-4 py-2 border border-gray-300 text-sm rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer file:cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
                    </div>
                </div>
  <!-- Date début -->
                <div class="mb-4">
                    <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                    <input type="date" name="date_debut" id="filter_date_debut" required
                           class="w-full pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Date fin -->
                <div class="mb-4">
                    <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                    <input type="date" name="date_fin" id="filter_date_fin" required
                           class="w-full pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <!-- Boutons -->
            <div class="mt-6 flex justify-end mr-6 space-x-3">
                <button type="button"
                        class="close-modals px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <button type="submit"
                        class="px-3 py-2 text-sm gradient-bg text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal suppression -->
<div id="deleteAbsenceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
        id="deleteAbsenceModalContent">
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
            <p class="text-gray-700 text-sm sm:text-base mb-6">Êtes-vous sûr de vouloir supprimer cette absence ? Cette
                action est irréversible.</p>
            <div class="flex justify-end space-x-3">
                <button type="button"
                    class="close-modals px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <a id="confirmDeleteAbsenceBtn" href="#"
                    class="px-3 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Supprimer
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'autorisation -->
<div id="authorizeAbsenceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
        id="authorizeAbsenceModalContent">
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                <span>Confirmer l'autorisation</span>
            </h3>
            <button class="close-modals text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 sm:p-6">
            <p class="text-gray-700 text-sm sm:text-base mb-6">Êtes-vous sûr de vouloir autoriser cette demande d'absence ? Cette
                action est irréversible.</p>
            <div class="flex justify-end space-x-3">
                 <button type="button"
                    class="close-modals px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <a id="confirmAuthorizeAbsenceBtn" href="#"
                    class="px-3 py-2 text-sm gradient-bg text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i>Autoriser
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de rejet -->
<div id="rejectAbsenceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
        id="rejectAbsenceModalContent">
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-times-circle mr-2 text-red-500"></i>
                <span>Confirmer le rejet</span>
            </h3>
            <button class="close-modals text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 sm:p-6">
            <p class="text-gray-700 text-sm sm:text-base mb-6">Êtes-vous sûr de vouloir rejeter cette demande d'absence ? Cette
                action est irréversible.</p>
            <div class="flex justify-end space-x-3">
                <button type="button"
                    class="close-modals px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <a id="confirmRejectAbsenceBtn" href="#"
                    class="px-3 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Rejeter
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modale pour messages de succès/erreur -->
<div id="messageAbsences" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50"
    data-messages="<?php echo htmlspecialchars(json_encode($messages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
        id="messageAbsencesModalContent">
        <div class="border-b px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                <i
                    class="fas fa-info-circle mr-2 <?php echo !empty($messages['errors']) ? 'text-red-500' : 'text-green-600'; ?>"></i>
                <span><?php echo !empty($messages['errors']) ? 'Erreur' : 'Succès'; ?></span>
            </h3>
            <button class="close-modals text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 sm:p-6">
            <?php if (!empty($messages['success'])): ?>
            <?php foreach ($messages['success'] as $msg): ?>
            <p class="text-green-600 font-semibold text-sm sm:text-base mb-2">✅
                <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($messages['errors'])): ?>
            <?php foreach ($messages['errors'] as $error): ?>
            <p class="text-red-600 font-semibold text-sm sm:text-base mb-2">❌
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
            <?php endif; ?>
            <div class="flex justify-end mt-4">
                <button type="button"
                    class="close-modals px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>  