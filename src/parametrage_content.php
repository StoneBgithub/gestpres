<?php
// Démarrer la session uniquement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données
require_once "db_connect.php";


// Vérifier si l'utilisateur est connecté
$agent_conn = $_SESSION['user_id'] ?? null;
$messages = ['success' => [], 'errors' => []];
if (!$agent_conn) {
    header("Location: login.php");  
    exit();
}
// Ajouter / Modifier Service
if (isset($_POST['action']) && $_POST['action'] === 'save_service') {
    $libele = $_POST['libele'];
    $chef_id = $_POST['chef_service_id'] ?? null;
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE service SET libele = ?, chef_service_id = ? WHERE id = ?");
        $stmt->execute([$libele, $chef_id ?: null, $id]);
        $message = "Service modifié avec succès !";
    } else {
        $stmt = $pdo->prepare("INSERT INTO service (libele, chef_service_id) VALUES (?, ?)");
        $stmt->execute([$libele, $chef_id ?: null]);
        $message = "Service ajouté avec succès !";
    }
}

// Supprimer Service
if (isset($_GET['delete_service'])) {
    $pdo->prepare("DELETE FROM service WHERE id = ?")->execute([$_GET['delete_service']]);
    $message = "Service supprimé avec succès !";
}

// Ajouter / Modifier Bureau
if (isset($_POST['action']) && $_POST['action'] === 'save_bureau') {
    $libele = $_POST['libele'];
    $service_id = $_POST['service_id'];
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE bureau SET libele = ?, service_id = ? WHERE id = ?");
        $stmt->execute([$libele, $service_id, $id]);
        $message = "Bureau modifié avec succès !";
    } else {
        $stmt = $pdo->prepare("INSERT INTO bureau (libele, service_id) VALUES (?, ?)");
        $stmt->execute([$libele, $service_id]);
        $message = "Bureau ajouté avec succès !";
    }
}

// Supprimer Bureau
if (isset($_GET['delete_bureau'])) {
    $pdo->prepare("DELETE FROM bureau WHERE id = ?")->execute([$_GET['delete_bureau']]);
    $message = "Bureau supprimé avec succès !";
}

// Récupérer les données pour modification
$edit_service = null;
$edit_bureau = null;

if (isset($_GET['edit_service'])) {
    $stmt = $pdo->prepare("SELECT * FROM service WHERE id = ?");
    $stmt->execute([$_GET['edit_service']]);
    $edit_service = $stmt->fetch();
}

if (isset($_GET['edit_bureau'])) {
    $stmt = $pdo->prepare("SELECT * FROM bureau WHERE id = ?");
    $stmt->execute([$_GET['edit_bureau']]);
    $edit_bureau = $stmt->fetch();
}

// ========== Chargement Données ==========

$services = $pdo->query("
    SELECT s.*, CONCAT(a.prenom, ' ', a.nom) AS chef_nom 
    FROM service s 
    LEFT JOIN agent a ON a.id = s.chef_service_id
    ORDER BY s.libele
")->fetchAll();

$bureaux = $pdo->query("
    SELECT b.*, s.libele AS service_nom 
    FROM bureau b
    JOIN service s ON s.id = b.service_id
    ORDER BY s.libele, b.libele
")->fetchAll();

$agents = $pdo->query("SELECT id, nom, prenom FROM agent ORDER BY nom, prenom")->fetchAll();








?>

    <style>
        

        .content {
            padding: 30px;
        }

        .section {
            margin-bottom: 50px;
        }

        .section-title {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            
        }

        .section-title i {
            font-size: 1.5em;
            margin-right: 15px;
            color: #026129ff;
        }

        .section-title h2 {
            color: black;
            font-size: 1.8em;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        .form-container {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #026129ff;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #026129ff;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #27ae60,  #026129ff);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg,  #026129ff,  #026129ff);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(121, 252, 131, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #026129ff,  #026129ff);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #229954, #1e8449);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg,  #026129ff,  #026129ff);
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg,  #026129ff,  #026129ff);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-cancel {
            background: #026129ff;
            color: white;
        }

        .btn-cancel:hover {
            background: #026129ff;
            transform: translateY(-2px);
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: linear-gradient(135deg, rgba(4, 114, 50, 0.88), rgba(4, 114, 50, 0.88));
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .table tr:nth-child(even):hover {
            background: #e9ecef;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .badge-info {
            background: #d1ecf1;
            color: #026129ff;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }

        .stat-card i {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #026129ff;
        }

        .stat-card h3 {
            font-size: 2em;
            margin-bottom: 5px;
            color: #026129ff;
        }

        .stat-card p {
            color: #026129ff;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<?php
// Stocker les données dans un élément invisible pour le JS
echo '<script id="agentsData" type="application/json">' . json_encode($agents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="bureauxData" type="application/json">' . json_encode($bureaux, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
echo '<script id="servicesData" type="application/json">' . json_encode($services, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
?>

<div class="container">

    <div class="content">
        <?php if (isset($message)): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-building"></i>
                <h3><?= count($services) ?></h3>
                <p>Services</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-door-open"></i>
                <h3><?= count($bureaux) ?></h3>
                <p>Bureaux</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?= count($agents) ?></h3>
                <p>Agents</p>
            </div>
        </div>

        <!-- Gestion des Services -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-building"></i>
                <h2>Gestion des Services</h2>
            </div>

            <div class="form-container">
                <form method="post">
                    <input type="hidden" name="action" value="save_service">
                    <?php if ($edit_service): ?>
                        <input type="hidden" name="id" value="<?= $edit_service['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="libele_service">
                                <i class="fas fa-tag"></i> Libellé du Service
                            </label>
                            <input type="text" id="libele_service" name="libele" 
                                   value="<?= $edit_service ? htmlspecialchars($edit_service['libele']) : '' ?>" 
                                   required placeholder="Ex: Ressources Humaines">
                        </div>
                        
                        <div class="form-group">
                            <label for="chef_service">
                                <i class="fas fa-user-tie"></i> Chef de Service
                            </label>
                            <select id="chef_service" name="chef_service_id">
                                <option value="">-- Aucun chef assigné --</option>
                                <?php foreach ($agents as $a): ?>
                                    <option value="<?= $a['id'] ?>" 
                                            <?= ($edit_service && $edit_service['chef_service_id'] == $a['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?= $edit_service ? 'Modifier' : 'Ajouter' ?> le Service
                        </button>
                        
                        <?php if ($edit_service): ?>
                            <a href="?" class="btn btn-cancel">
                                <i class="fas fa-times"></i>
                                Annuler
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag"></i> Libellé</th>
                            <th><i class="fas fa-user-tie"></i> Chef de Service</th>
                            <th><i class="fas fa-cog"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['libele']) ?></td>
                                <td>
                                    <?php if ($s['chef_nom']): ?>

                                            <?= htmlspecialchars($s['chef_nom']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Non assigné
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="?edit_service=<?= $s['id'] ?>" class="btn btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete_service=<?= $s['id'] ?>" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce service ?')" 
                                           class="btn btn-danger" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gestion des Bureaux -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-door-open"></i>
                <h2>Gestion des Bureaux</h2>
            </div>

            <div class="form-container">
                <form method="post">
                    <input type="hidden" name="action" value="save_bureau">
                    <?php if ($edit_bureau): ?>
                        <input type="hidden" name="id" value="<?= $edit_bureau['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="libele_bureau">
                                <i class="fas fa-tag"></i> Libellé du Bureau
                            </label>
                            <input type="text" id="libele_bureau" name="libele" 
                                   value="<?= $edit_bureau ? htmlspecialchars($edit_bureau['libele']) : '' ?>" 
                                   required placeholder="Ex: Bureau 101">
                        </div>
                        
                        <div class="form-group">
                            <label for="service_bureau">
                                <i class="fas fa-building"></i> Service Associé
                            </label>
                            <select id="service_bureau" name="service_id" required>
                                <option value="">-- Choisir un service --</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= $s['id'] ?>" 
                                            <?= ($edit_bureau && $edit_bureau['service_id'] == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['libele']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            <?= $edit_bureau ? 'Modifier' : 'Ajouter' ?> le Bureau
                        </button>
                        
                        <?php if ($edit_bureau): ?>
                            <a href="?" class="btn btn-cancel">
                                <i class="fas fa-times"></i>
                                Annuler
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag"></i> Libellé</th>
                            <th><i class="fas fa-building"></i> Service Associé</th>
                            <th><i class="fas fa-cog"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bureaux as $b): ?>
                            <tr>
                                <td><?= htmlspecialchars($b['libele']) ?></td>
                                <td>
                                        <?= htmlspecialchars($b['service_nom']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="?edit_bureau=<?= $b['id'] ?>" class="btn btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete_bureau=<?= $b['id'] ?>" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce bureau ?')" 
                                           class="btn btn-danger" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

