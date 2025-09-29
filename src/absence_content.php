<?php
// Démarrer la session uniquement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données
require_once "db_connect.php";

// Configuration pour upload de fichiers
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

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
$login_id = null;

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
$types_absences = [];
try {
    $stmt51 = $pdo->query("SELECT id, libelle FROM type_absence ORDER BY libelle");
    $types_absences = $stmt51->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans absence_content.php (récupération types d'absence) : " . $e->getMessage());
    $messages['errors'][] = "Erreur de connexion à la base de données.";
}

// Récupérer les statuts des absences
$statuts = [];
try {
    $stmt61 = $pdo->query("SELECT id, libelle FROM statut_absence ORDER BY libelle");
    $statuts = $stmt61->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans absence_content.php (récupération statuts) : " . $e->getMessage());
    $messages['errors'][] = "Erreur de connexion à la base de données.";
}

// Récupérer le rôle de l'utilisateur connecté
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

// Récupérer les bureaux
try {
    $sql = "SELECT libele FROM bureau";
    $stmt = $pdo->query($sql);
    $bureaux2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans compte_content.php (récupération bureaux) : " . $e->getMessage());
    $bureaux2 = [];
}

// Récupérer agents avec leurs bureaux
try {
    $sql = "SELECT a.id, a.bureau_id, CONCAT(a.nom, ' ', a.prenom) AS nom_prenom, a.email, b.libele AS bureau
            FROM agent a
            JOIN bureau b ON a.bureau_id = b.id
            LEFT JOIN login l ON a.id = l.agent_id
            WHERE l.agent_id IS NULL";
    $stmt = $pdo->query($sql);
    $agents_bureau = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur dans compte_content.php (récupération agents bureau) : " . $e->getMessage());
    $agents_bureau = [];
}

/**
 * Fonction pour sécuriser l'upload de fichier
 */
function secureFileUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Aucun fichier uploadé ou erreur durant l\'upload'];
    }
    
    // Vérifier la taille
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'error' => 'Fichier trop volumineux (max 5MB)'];
    }
    
    // Vérifier l'extension
    $pathInfo = pathinfo($file['name']);
    $extension = strtolower($pathInfo['extension'] ?? '');
    
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Type de fichier non autorisé'];
    }
    
    // Vérifier le type MIME réel
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'error' => 'Type de fichier non autorisé (MIME)'];
    }
    
    // Créer le nom de fichier sécurisé
    $fileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9_\-\.]/", "_", $pathInfo['filename']) . '.' . $extension;
    
    $uploadDir = "justificatifs/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'path' => $filePath];
    }
    
    return ['success' => false, 'error' => 'Erreur lors de l\'enregistrement du fichier'];
}

/**
 * Fonction pour déterminer qui doit autoriser une absence
 */
function getAutorisationInfo($pdo, $duree_jours) {
    $libelle_role = ($duree_jours > 15) ? 'directrice' : 'chef de service';
    
    try {
        $stmtRole = $pdo->prepare("SELECT id FROM role WHERE LOWER(libelle) = LOWER(:libelle)");
        $stmtRole->execute(['libelle' => $libelle_role]);
        $role_id = $stmtRole->fetchColumn();
        
        if ($role_id) {
            $stmtLogin = $pdo->prepare("SELECT id FROM login WHERE role_id = :role_id AND statut = 'activé' LIMIT 1");
            $stmtLogin->execute(['role_id' => $role_id]);
            $autorisation_par = $stmtLogin->fetchColumn();
            
            return [
                'success' => (bool)$autorisation_par,
                'autorisation_par' => $autorisation_par,
                'role_requis' => $libelle_role
            ];
        }
    } catch (PDOException $e) {
        error_log("Erreur getAutorisationInfo : " . $e->getMessage());
    }
    
    return ['success' => false, 'error' => "Le rôle '$libelle_role' n'existe pas ou aucun utilisateur actif"];
}

/**
 * Fonction de debug pour vérifier les statuts
 */
function debugStatutsAbsence($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, libelle FROM statut_absence ORDER BY id");
        $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("=== DEBUG STATUTS ABSENCE ===");
        foreach ($statuts as $statut) {
            error_log("ID: " . $statut['id'] . " - Libellé: '" . $statut['libelle'] . "' - Trimmed: '" . trim($statut['libelle']) . "' - Lower: '" . strtolower(trim($statut['libelle'])) . "'");
        }
        error_log("=== FIN DEBUG STATUTS ===");
        
        return $statuts;
    } catch (PDOException $e) {
        error_log("Erreur debug statuts: " . $e->getMessage());
        return [];
    }
}

// Décommenter pour diagnostiquer les statuts
// debugStatutsAbsence($pdo);

// Gestion des requêtes POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($messages['errors'])) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
        case 'update':
            // Vérifier les permissions pour ajout/modification
            if ($role_utilisateur !== 'secretaire') {
                $messages['errors'][] = "Vous n'avez pas les permissions pour cette action.";
                break;
            }
            
            $absence_id = isset($_POST['absence_id']) ? (int)$_POST['absence_id'] : null;
            $agent_id = $_POST['agent_id'] ?? null;
            $id_type_absence = $_POST['motif'] ?? null;
            $date_debut = $_POST['date_debut'] ?? null;
            $date_fin = $_POST['date_fin'] ?? null;
            $description = $_POST['description'] ?? '';
            $justificatif_path = null;
            
            // CORRECTION: Validation améliorée des champs requis
            $validation_errors = [];
            
            if (empty($agent_id)) {
                $validation_errors[] = "L'agent doit être sélectionné.";
            }
            
            if (empty($id_type_absence)) {
                $validation_errors[] = "Le motif d'absence doit être sélectionné.";
            }
            
            if (empty($date_debut)) {
                $validation_errors[] = "La date de début est requise.";
            }
            
            if (empty($date_fin)) {
                $validation_errors[] = "La date de fin est requise.";
            }
            
            // Vérifier que l'agent existe
            if (!empty($agent_id)) {
                try {
                    $stmt_check_agent = $pdo->prepare("SELECT id FROM agent WHERE id = :agent_id");
                    $stmt_check_agent->execute(['agent_id' => $agent_id]);
                    if (!$stmt_check_agent->fetchColumn()) {
                        $validation_errors[] = "L'agent sélectionné n'existe pas.";
                    }
                } catch (PDOException $e) {
                    error_log("Erreur vérification agent : " . $e->getMessage());
                    $validation_errors[] = "Erreur lors de la vérification de l'agent.";
                }
            }
            
            // Vérifier que le type d'absence existe
            if (!empty($id_type_absence)) {
                try {
                    $stmt_check_type = $pdo->prepare("SELECT id FROM type_absence WHERE id = :type_id");
                    $stmt_check_type->execute(['type_id' => $id_type_absence]);
                    if (!$stmt_check_type->fetchColumn()) {
                        $validation_errors[] = "Le type d'absence sélectionné n'existe pas.";
                    }
                } catch (PDOException $e) {
                    error_log("Erreur vérification type absence : " . $e->getMessage());
                    $validation_errors[] = "Erreur lors de la vérification du type d'absence.";
                }
            }
            
            if (!empty($validation_errors)) {
                $messages['errors'] = array_merge($messages['errors'], $validation_errors);
                break;
            }
            
            // Gestion des fichiers
            if (isset($_FILES['justificatif']) && $_FILES['justificatif']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = secureFileUpload($_FILES['justificatif']);
                if ($uploadResult['success']) {
                    $justificatif_path = $uploadResult['path'];
                } else {
                    $messages['errors'][] = "Erreur upload fichier: " . $uploadResult['error'];
                    break;
                }
            } elseif ($action === 'update' && $absence_id) {
                // Conserver l'ancien justificatif si pas de nouveau fichier
                try {
                    $stmt = $pdo->prepare("SELECT justificatif FROM absence WHERE id = :id");
                    $stmt->execute(['id' => $absence_id]);
                    $justificatif_path = $stmt->fetchColumn();
                } catch (PDOException $e) {
                    error_log("Erreur récupération justificatif : " . $e->getMessage());
                }
            } elseif (isset($_FILES['justificatif']) && $_FILES['justificatif']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Il y a eu une erreur d'upload
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximum autorisée par PHP.',
                    UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximum du formulaire.',
                    UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
                    UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque.',
                    UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload du fichier.'
                ];
                $error_code = $_FILES['justificatif']['error'];
                $messages['errors'][] = "Erreur upload: " . ($upload_errors[$error_code] ?? "Erreur inconnue ($error_code)");
                break;
            }
            
            // Validation des dates
            if ($date_debut && $date_fin) {
                $d1 = new DateTime($date_debut);
                $d2 = new DateTime($date_fin);
                
                if ($d1 > $d2) {
                    $messages['errors'][] = "La date de début ne peut pas être postérieure à la date de fin.";
                    break;
                }
                
                $duree_jours = $d1->diff($d2)->days + 1;
                
                // Vérifier qui peut autoriser cette absence
                $autorisationInfo = getAutorisationInfo($pdo, $duree_jours);
                if (!$autorisationInfo['success']) {
                    $messages['errors'][] = $autorisationInfo['error'] ?? "Erreur dans la détermination de l'autorisation.";
                    break;
                }
            }
            
            // Récupérer le statut "en attente"
            try {
                $stmtStatut = $pdo->prepare("SELECT id FROM statut_absence WHERE LOWER(TRIM(libelle)) = 'en attente' LIMIT 1");
                $stmtStatut->execute();
                $id_statut = $stmtStatut->fetchColumn();
                
                if (!$id_statut) {
                    $messages['errors'][] = "Le statut 'en attente' est introuvable dans la base de données.";
                    break;
                }
            } catch (PDOException $e) {
                error_log("Erreur récupération statut : " . $e->getMessage());
                $messages['errors'][] = "Erreur lors de la récupération du statut: " . $e->getMessage();
                break;
            }
            
            // Traitement selon l'action
            try {
                if ($action === 'add') {
                    // AJOUT D'UNE NOUVELLE ABSENCE
                    error_log("Tentative d'ajout absence - agent_id: $agent_id, type: $id_type_absence, debut: $date_debut, fin: $date_fin");
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO absence (agent_id, date_debut, date_fin, id_type_absence, justificatif, id_statut, description)
                        VALUES (:agent_id, :date_debut, :date_fin, :id_type_absence, :justificatif, :id_statut, :description)
                    ");
                    
                    $result = $stmt->execute([
                        'agent_id' => $agent_id,
                        'date_debut' => $date_debut,
                        'date_fin' => $date_fin,
                        'id_type_absence' => $id_type_absence,
                        'justificatif' => $justificatif_path,
                        'id_statut' => $id_statut,
                        'description' => $description
                    ]);
                    
                    if ($result) {
                        $messages['success'][] = "Absence ajoutée avec succès.";
                        error_log("Absence ajoutée avec succès - ID: " . $pdo->lastInsertId());
                    } else {
                        $messages['errors'][] = "Échec de l'insertion en base de données.";
                        error_log("Échec insertion absence - Infos PDO: " . print_r($stmt->errorInfo(), true));
                    }
                    
                } elseif ($action === 'update' && $absence_id) {
                    // MODIFICATION D'UNE ABSENCE EXISTANTE
                    error_log("Tentative de modification absence ID: $absence_id - agent_id: $agent_id, type: $id_type_absence");
                    
                    // Vérifier que l'absence existe et n'est pas déjà autorisée
                    $stmtCheck = $pdo->prepare("
                        SELECT s.libelle as statut 
                        FROM absence a 
                        JOIN statut_absence s ON a.id_statut = s.id 
                        WHERE a.id = :id
                    ");
                    $stmtCheck->execute(['id' => $absence_id]);
                    $currentStatus = $stmtCheck->fetchColumn();
                    
                    if (!$currentStatus) {
                        $messages['errors'][] = "Absence introuvable.";
                        break;
                    }
                    
                    if (strtolower(trim($currentStatus)) === 'autoriser') {
                        $messages['errors'][] = "Impossible de modifier une absence déjà autorisée.";
                        break;
                    }
                    
                    // Effectuer la mise à jour
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
                    
                    $result = $stmt->execute([
                        'agent_id' => $agent_id,
                        'date_debut' => $date_debut,
                        'date_fin' => $date_fin,
                        'id_type_absence' => $id_type_absence,
                        'justificatif' => $justificatif_path,
                        'description' => $description,
                        'id' => $absence_id
                    ]);
                    
                    if ($result) {
                        $messages['success'][] = "Absence modifiée avec succès.";
                        error_log("Absence modifiée avec succès - ID: $absence_id");
                    } else {
                        $messages['errors'][] = "Échec de la modification en base de données.";
                        error_log("Échec modification absence - Infos PDO: " . print_r($stmt->errorInfo(), true));
                    }
                }
                
            } catch (PDOException $e) {
                error_log("Erreur PDO " . ($action === 'add' ? 'ajout' : 'modification') . " absence - Code: " . $e->getCode() . " Message: " . $e->getMessage());
                $messages['errors'][] = "Erreur base de données: " . $e->getMessage();
            }
            
            break;
        case 'validate':
    // --- Vérification des permissions ---
    if (!in_array($role_utilisateur, ['chef de service', 'directrice'])) {
        $messages['errors'][] = "Vous n'avez pas les permissions pour autoriser une absence.";
        break;
    }

    $absence_id = (int)($_POST['absence_id'] ?? 0);
    if ($absence_id <= 0) {
        $messages['errors'][] = "ID d'absence manquant ou invalide.";
        break;
    }

    error_log("Validation absence ID $absence_id par rôle $role_utilisateur");

    try {
        // --- Récupération de l'absence ---
        $stmt = $pdo->prepare("
            SELECT a.id, a.agent_id, a.id_statut, a.date_debut, a.date_fin,
                   DATEDIFF(a.date_fin, a.date_debut) + 1 AS duree_jours,
                   s.libelle AS statut
            FROM absence a
            JOIN statut_absence s ON a.id_statut = s.id
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $absence_id]);
        $absence = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$absence) {
            $messages['errors'][] = "Absence introuvable.";
            break;
        }

        $statutActuel = strtolower(trim($absence['statut']));
        if ($statutActuel !== 'en attente') {
            $messages['errors'][] = "Absence non autorisable (statut actuel : {$absence['statut']}).";
            break;
        }

        // --- Vérification des permissions selon la durée ---
        $duree = (int)$absence['duree_jours'];
        $autorise = match (true) {
            $role_utilisateur === 'directrice' => true,
            $role_utilisateur === 'chef de service' && $duree <= 15 => true,
            default => false
        };

        if (!$autorise) {
            $messages['errors'][] = "Seule la directrice peut autoriser une absence de plus de 15 jours.";
            break;
        }

        // --- Récupération du statut "autorisé" ---
        $stmt = $pdo->query("
            SELECT id
            FROM statut_absence
            WHERE LOWER(TRIM(libelle)) IN ('autoriser','autorisé','approuvé','validé')
            ORDER BY FIELD(LOWER(TRIM(libelle)), 'autoriser','autorisé','approuvé','validé')
            LIMIT 1
        ");
        $statutAutoriseId = $stmt->fetchColumn();

        if (!$statutAutoriseId) {
            $messages['errors'][] = "Aucun statut d'autorisation valide trouvé en base.";
            break;
        }

        // --- Récupération du statut "en attente" ---
        $stmt = $pdo->query("
            SELECT id
            FROM statut_absence
            WHERE LOWER(TRIM(libelle)) = 'en attente'
            LIMIT 1
        ");
        $statutEnAttenteId = $stmt->fetchColumn();
        error_log("DEBUG statut_en_attente_id: " . $statutEnAttenteId);

        if (!$statutEnAttenteId) {
            $messages['errors'][] = "Statut 'en attente' introuvable en base.";
            break;
        }

        // --- Transaction sécurisée ---
        $pdo->beginTransaction();

        // --- Mise à jour du statut d'absence ---
        $stmt = $pdo->prepare("
            UPDATE absence 
            SET id_statut = :statut, validation = :login_id, date_autorisation = NOW()
            WHERE id = :id AND id_statut = :statut_attente
        ");
        $stmt->execute([
            'statut'         => $statutAutoriseId,
            'login_id'       => $login_id,
            'id'             => $absence_id,
            'statut_attente' => $statutEnAttenteId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("L'absence n'est plus en attente (mise à jour impossible).");
        }

        // --- Insertion automatique dans la table presence ---
        $date_debut = new DateTime($absence['date_debut']);
        $date_fin   = new DateTime($absence['date_fin']);
        $today      = new DateTime();

        $stmtPresence = $pdo->prepare("
            INSERT INTO presence (agent_id, date, heure, type)
            VALUES (:agent_id, :date, :heure, :type)
        ");

        for ($date = clone $date_debut; $date <= $date_fin; $date->modify('+1 day')) {
            if ($date <= $today) {
                // Vérifier si les lignes existent déjà
                $check = $pdo->prepare("SELECT 1 FROM presence WHERE agent_id=:agent_id AND date=:date LIMIT 1");
                $check->execute([
                    'agent_id' => $absence['agent_id'],
                    'date'     => $date->format('Y-m-d'),
                ]);

                if (!$check->fetchColumn()) {
                    // Insérer arrivée avant 09h et départ à 14h30
                    foreach (['arrivée'=>'08:50:00', 'depart'=>'14:30:00'] as $type => $heure) {
                        $stmtPresence->execute([
                            'agent_id' => $absence['agent_id'],
                            'date'     => $date->format('Y-m-d'),
                            'heure'    => $heure,
                            'type'     => $type,
                        ]);
                    }
                }
            }
        }

        $pdo->commit();
        $messages['success'][] = "Absence (ID $absence_id) autorisée et présences insérées.";
        error_log("Absence ID $absence_id validée par login ID $login_id");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $messages['errors'][] = "Erreur lors de l'autorisation : " . $e->getMessage();
        error_log("Erreur validation absence : " . $e->getMessage());
    }
    break;

case 'reject':
    // --- Vérification des permissions ---
    if (!in_array($role_utilisateur, ['chef de service', 'directrice'])) {
        $messages['errors'][] = "Vous n'avez pas les permissions pour rejeter une absence.";
        break;
    }

    $absence_id = (int)($_POST['absence_id'] ?? 0);
    $motif_rejet = trim($_POST['motif_rejet'] ?? ''); // Récupérer le motif de rejet
    
    if ($absence_id <= 0) {
        $messages['errors'][] = "ID d'absence manquant ou invalide.";
        break;
    }

    // Validation du motif de rejet
    if (empty($motif_rejet)) {
        $messages['errors'][] = "Le motif du rejet est obligatoire.";
        break;
    }

    if (strlen($motif_rejet) < 10) {
        $messages['errors'][] = "Le motif du rejet doit contenir au moins 10 caractères.";
        break;
    }

    if (strlen($motif_rejet) > 500) {
        $messages['errors'][] = "Le motif du rejet ne peut pas dépasser 500 caractères.";
        break;
    }

    error_log("Rejet absence ID $absence_id par rôle $role_utilisateur avec motif: $motif_rejet");

    try {
        // --- Récupérer l'absence ---
        $stmt = $pdo->prepare("
            SELECT a.id, a.id_statut, DATEDIFF(a.date_fin, a.date_debut) + 1 AS duree_jours, 
                   s.libelle AS statut, CONCAT(ag.nom, ' ', ag.prenom) AS agent_nom
            FROM absence a
            JOIN statut_absence s ON a.id_statut = s.id
            JOIN agent ag ON a.agent_id = ag.id
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $absence_id]);
        $absence = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$absence) {
            $messages['errors'][] = "Absence introuvable.";
            break;
        }

        if (strtolower(trim($absence['statut'])) !== 'en attente') {
            $messages['errors'][] = "Cette absence n'est pas en attente de décision (statut actuel : {$absence['statut']}).";
            break;
        }

        $duree = (int)$absence['duree_jours'];
        $autorise = match (true) {
            $role_utilisateur === 'directrice' => true,
            $role_utilisateur === 'chef de service' && $duree <= 15 => true,
            default => false
        };

        if (!$autorise) {
            $messages['errors'][] = "Seule la directrice peut rejeter une absence de plus de 15 jours.";
            break;
        }

        // --- Récupérer les IDs des statuts ---
        $stmt = $pdo->query("
            SELECT id, libelle 
            FROM statut_absence 
            WHERE LOWER(TRIM(libelle)) IN ('rejeter','rejeté','refusé','refuser')
            LIMIT 1
        ");
        $statutRejete = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$statutRejete) {
            $messages['errors'][] = "Aucun statut de rejet valide trouvé en base.";
            break;
        }

        $stmt = $pdo->query("
            SELECT id 
            FROM statut_absence 
            WHERE LOWER(TRIM(libelle)) = 'en attente'
            LIMIT 1
        ");
        $statutEnAttenteId = $stmt->fetchColumn();
        if (!$statutEnAttenteId) {
            $messages['errors'][] = "Statut 'en attente' introuvable en base.";
            break;
        }

        // --- Transaction sécurisée ---
        $pdo->beginTransaction();
        
        // Mettre à jour l'absence avec le motif de rejet
        $stmt = $pdo->prepare("
            UPDATE absence
            SET id_statut = :statut_rejete,
                validation = :login_id,
                date_autorisation = NOW(),
                motif_rejet = :motif_rejet
            WHERE id = :id
              AND id_statut = :statut_en_attente
        ");
        
        $stmt->execute([
            'statut_rejete' => $statutRejete['id'],
            'login_id'      => $login_id,
            'motif_rejet'   => $motif_rejet,
            'id'            => $absence_id,
            'statut_en_attente' => $statutEnAttenteId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("L'absence n'est plus en attente.");
        }

        // Log pour traçabilité
        error_log("Absence rejetée - ID: $absence_id, Agent: {$absence['agent_nom']}, Motif: $motif_rejet, Par: login_id $login_id");

        $pdo->commit();
        $messages['success'][] = "Absence (ID $absence_id) rejetée avec succès. Motif enregistré.";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $messages['errors'][] = "Erreur lors du rejet : " . $e->getMessage();
        error_log("Erreur rejet absence : " . $e->getMessage());
    }
    break;


if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Retourner une réponse JSON pour les requêtes AJAX
    header('Content-Type: application/json');
    
    if (!empty($messages['success'])) {
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'messages' => $messages
        ]);
    }
    exit();
}
    }}
// Suppression d'une absence (via GET)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && empty($messages['errors'])) {
    $id = (int)$_GET['id'];
    
    // Vérifier les permissions
    if ($role_utilisateur !== 'secretaire') {
        $messages['errors'][] = "Vous n'avez pas les permissions pour supprimer une absence.";
    } else {
        try {
            // Récupérer les informations de l'absence avant suppression
            $stmt = $pdo->prepare("
                SELECT a.*, s.libelle as statut
                FROM absence a
                JOIN statut_absence s ON a.id_statut = s.id
                WHERE a.id = :id 
            ");
            $stmt->execute(['id' => $id]);
            $absence = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($absence) {
                // Vérifier que l'absence n'est pas déjà autorisée
                if (strtolower(trim($absence['statut'])) === 'autoriser') {
                    $messages['errors'][] = "Impossible de supprimer une absence déjà autorisée.";
                } else {
                    // Supprimer l'enregistrement
                    $stmt48 = $pdo->prepare("DELETE FROM absence WHERE id = :id");
                    $stmt48->execute(['id' => $id]);
                    
                    // Supprimer le justificatif s'il existe
                    if (!empty($absence['justificatif']) && file_exists($absence['justificatif'])) {
                        unlink($absence['justificatif']);
                    }
                    
                    $messages['success'][] = "Absence supprimée avec succès.";
                }
            } else {
                $messages['errors'][] = "Absence introuvable.";
            }
        } catch (PDOException $e) {
            error_log("Erreur suppression absence : " . $e->getMessage());
            $messages['errors'][] = "Erreur lors de la suppression.";
        }
    }
}

// Construction de la requête principale pour récupérer les absences
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
        s.libelle AS statut,
        aj.description,
        DATEDIFF(aj.date_fin, aj.date_debut) + 1 as duree_jours
    FROM absence aj
    INNER JOIN agent a ON aj.agent_id = a.id
    INNER JOIN type_absence t ON aj.id_type_absence = t.id
    INNER JOIN statut_absence s ON aj.id_statut = s.id
";

$params = [];
$whereConditions = [];

// Filtrage par rôle utilisateur
if ($role_utilisateur === 'chef de service') {
    // Chef de service : absences <= 15 jours de son service uniquement
    $whereConditions[] = "DATEDIFF(aj.date_fin, aj.date_debut) + 1 <= 15";
    $whereConditions[] = "a.bureau_id IN (
        SELECT b.id 
        FROM bureau b 
        WHERE b.service_id = (
            SELECT b2.service_id
            FROM agent ag
            INNER JOIN bureau b2 ON ag.bureau_id = b2.id
            WHERE ag.id = :agent_id
        )
    )";
    $params['agent_id'] = $agent_conn;
    
} elseif ($role_utilisateur === 'directrice') {
    // Directrice : absences > 15 jours uniquement
    $whereConditions[] = "DATEDIFF(aj.date_fin, aj.date_debut) + 1 > 15";
}
// Secrétaire : voir toutes les absences (pas de filtre)

// Ajout de la clause WHERE si nécessaire
if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY aj.date_debut DESC";

// Exécution de la requête
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération absences : " . $e->getMessage());
    $messages['errors'][] = "Erreur lors du chargement des absences.";
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
               <select name="motif" id="search_filter_types"
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
                <select name="statut" id="search_filter_statuts" 
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
            <button class="edit-absence-btn text-blue-600 hover:text-blue-900 text-sm" data-id="<?= htmlspecialchars($absence['id']) ?>" title="Modifier">
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
                 Déjà autorisé
            </span>
        <?php elseif ($statut === 'rejeter'): ?>
            <span class="text-red-600 text-sm italic">
                 Déjà rejeté
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
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class=" text-blue-600 textfont-medium text-xs">
                                        <?= strtoupper(substr($absence['prenom'], 0, 1) . substr($absence['nom'], 0, 1)) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-black"><?= htmlspecialchars($absence['nom_prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-black"><?= htmlspecialchars($absence['debut'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-black"><?= htmlspecialchars($absence['fin'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-black"><?= htmlspecialchars($absence['motif'], ENT_QUOTES, 'UTF-8') ?></td>
 <td class="px-4 py-3 whitespace-nowrap text-sm text-black text-center">
    <?php if (!empty($absence['justificatif']) && file_exists($absence['justificatif'])): ?>
        <?php 
            $ext = strtolower(pathinfo($absence['justificatif'], PATHINFO_EXTENSION));
            $url = htmlspecialchars($absence['justificatif'], ENT_QUOTES, 'UTF-8');
        ?>
        
        <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
            <!-- Icône œil pour image -->
            <a href="<?= $url ?>" target="_blank" title="Voir l'image" class="text-blue-600 hover:opacity-75 text-xl">
                <i class="fas fa-eye"></i>
            </a>
        <?php elseif ($ext === 'pdf'): ?>
            <!-- Icône œil pour PDF -->
            <a href="<?= $url ?>" target="_blank" title="Voir le PDF" class="text-red-600 hover:opacity-75 text-xl">
                <i class="fas fa-eye"></i>
            </a>
        <?php else: ?>
            <!-- Icône œil pour fichier générique -->
            <a href="<?= $url ?>" target="_blank" title="Télécharger le fichier" class="text-gray-600 hover:opacity-75 text-xl">
                <i class="fas fa-eye"></i>
            </a>
        <?php endif; ?>
    <?php else: ?>
        <span class="text-gray-400 italic">Aucun</span>
    <?php endif; ?>
</td>


<td class="px-4 py-3 whitespace-nowrap text-sm text-center">
    <?php if (strtolower($absence['statut']) === 'autoriser'): ?>
        <span title="autoriser" style="color:green; font-size: 18px;">✔️</span>
    <?php elseif (strtolower($absence['statut']) === 'rejeter'): ?>
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
            <button type="submit" class="text-blue-600 hover:text-blue-800" title="Imprimer l'autorisation">
                <i class="fas fa-print"></i>
            </button>
        </form>
    <?php elseif ($statut === 'rejeter'): ?>
        <!-- Affichage motif de rejet -->
             <form action="generer_refus.php" method="post" target="_blank">
            <input type="hidden" name="absence_id" value="<?= htmlspecialchars($absence['id']) ?>">
            <button type="submit" class="text-blue-600 hover:text-blue-800" title="Imprimer le refus de la demande d'absence">
                <i class="fas fa-print"></i>
            </button>
        </form>
        
    <?php else: ?>
        <!-- Modifier / Supprimer -->
        <button class="edit-absence-btn text-blue-600 hover:text-blue-800"
                data-id="<?= htmlspecialchars($absence['id'], ENT_QUOTES, 'UTF-8') ?>" title="Modifier">
            <i class="fas fa-edit"></i>
        </button>
        <button class="delete-absence-btn text-red-600 hover:text-red-900"
                data-id="<?= htmlspecialchars($absence['id'], ENT_QUOTES, 'UTF-8') ?>" title="Supprimer">
            <i class="fas fa-trash"></i>
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
                   Déjà autorisé
                </span>
            <?php elseif ($statut === 'rejeter'): ?>
                <span class="text-red-600 text-sm italic">
                     Déjà rejeté
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
             <input type="hidden" id="absence_id" name="absence_id" value="">
            <input type="hidden" id="formActions" name="action" value="add">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Filtrer par bureau -->
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
                        <select name="motif" id="filter_types" required
    class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
    <option value="">Choisir un motif</option>
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

                <!-- Description -->
                <div class="mb-4 sm:col-span-2 flex justify-center">
    <div class="w-full max-w-2xl">
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1 text-center">Description</label>
        <textarea name="description" id="description" rows="3"
                  class="w-full pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Ajouter une description ou des détails supplémentaires..."></textarea>
    </div>
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
  <div
    class="bg-white rounded-lg shadow-2xl w-full max-w-md p-4 sm:p-6 transform transition-all duration-300 scale-95 opacity-0"
    id="rejectAbsenceModalContent"
  >
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
      <p class="text-gray-700 text-sm sm:text-base mb-4">
        Êtes-vous sûr de vouloir rejeter cette demande d'absence ? Cette action est irréversible.
      </p>

      <!-- Champ pour le motif du rejet -->
      <label for="rejectReason" class="block text-sm font-medium text-gray-700 mb-2">
        Motif du rejet <span class="text-red-500">*</span>
      </label>
      <textarea
        id="rejectReason"
        rows="3"
        class="w-full border rounded-lg p-2 mb-6 focus:ring-2 focus:ring-red-500 focus:outline-none"
        placeholder="Saisissez le motif du rejet..."
      ></textarea>

      <div class="flex justify-end space-x-3">
        <button
          type="button"
          class="close-modals px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center"
        >
          <i class="fas fa-times mr-2"></i> Annuler
        </button>

        <button
          id="confirmRejectAbsenceBtn"
          disabled
          class="px-3 py-2 text-sm bg-red-600 text-white rounded-lg opacity-50 cursor-not-allowed transition-all flex items-center"
        >
          <i class="fas fa-times mr-2"></i> Rejeter
        </button>
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