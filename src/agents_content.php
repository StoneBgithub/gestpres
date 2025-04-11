<?php 
require "db_connect.php";
$search = $_GET['search'] ?? '';



try {
    $stmt5 = $pdo->query("SELECT id, libele FROM bureau");
    $bureaux2 = $stmt5->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Gestion des requêtes POST (ajout ou modification)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? 'add'; // Par défaut, ajout
    $agent_id = isset($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenoms'] ?? ''); 
    $matricule = trim($_POST['matricule'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $bureau_id = trim($_POST['bureau_id'] ?? '');

    $errors = [];

    // Vérification des champs obligatoires
    if (empty($nom)) $errors[] = "Le nom est requis.";
    if (empty($prenom)) $errors[] = "Le prénom est requis.";
    if (empty($matricule)) $errors[] = "Le matricule est requis.";
    if (empty($email)) $errors[] = "L'email est requis.";
    if (empty($telephone)) $errors[] = "Le téléphone est requis.";
    if (empty($bureau_id)) $errors[] = "Le bureau est requis.";

    // Vérification de l'email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    }

    // Vérification d'unicité du matricule (uniquement pour ajout ou si modifié)
    if (!empty($matricule)) {
        $stmt5 = $pdo->prepare("SELECT matricule FROM agent WHERE id != :id AND matricule = :matricule");
        $stmt5->execute(['id' => $agent_id ?? 0, 'matricule' => $matricule]);
        if ($stmt5->fetchColumn()) {
            $errors[] = "Ce matricule est déjà utilisé.";
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p class='text-red-600 font-semibold'>• $error</p>";
        }
    } else {
        // Gestion de la photo
        $photoName = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoTmp = $_FILES['photo']['tmp_name'];
            $original = basename($_FILES['photo']['name']);
            $cleanName = preg_replace("/[^a-zA-Z0-9_\-\.]/", "_", $original);
            $photoName = uniqid() . '_' . $cleanName;
        
            $targetDir = "photos/";
            $targetFile = "{$targetDir}{$photoName}";
        
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
        
            if (!move_uploaded_file($photoTmp, $targetFile)) {
                echo "<p class='text-red-600'>Erreur lors du téléchargement de la photo.</p>";
                exit;
            }
        
            // 👉 On stocke le chemin "photos/nomphoto" pour l'enregistrer en base
            $photoPath = $targetFile;
        } else {
            $photoPath = null; // ou une valeur par défaut si tu préfères
        }

        try {
            if ($action === 'add' && !$agent_id) {
                // Ajout d’un nouvel agent
                $stmt5 = $pdo->prepare("INSERT INTO agent (matricule, nom, prenom, email, telephone, photo, bureau_id)
        VALUES (:matricule, :nom, :prenom, :email, :telephone, :photo, :bureau_id)");
                $stmt5->execute([
                    ':matricule' => $_POST['matricule'],
                    ':nom'       => $_POST['nom'],
                    ':prenom'    => $_POST['prenoms'],
                    ':email'     => $_POST['email'],
                    ':telephone' => $_POST['telephone'],
                    ':photo'     => $photoPath,
                    ':bureau_id' => $_POST['bureau_id']
                ]);
                $message = "Agent enregistré avec succès.";
            } elseif ($action === 'update' && $agent_id) {
                // Modification d’un agent existant
                $sql = "UPDATE agent SET 
                        matricule = :matricule, 
                        nom = :nom, 
                        prenom = :prenom, 
                        email = :email, 
                        telephone = :telephone, 
                        bureau_id = :bureau_id";
                if ($photoName) {
                    $sql .= ", photo = :photo";
                }
                $sql .= " WHERE id = :id";

                $stmt5 = $pdo->prepare($sql);
                $params = [
                    'id' => $agent_id,
                    'matricule' => $matricule,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'telephone' => $telephone,
                    'bureau_id' => $bureau_id
                ];
                if ($photoName) {
                    $params['photo'] = $photoName;
                }
                $stmt5->execute($params);
                $message = "Agent mis à jour avec succès.";
            }

            echo "<p class='text-green-600 font-semibold'>✅ $message</p>";
        } catch (PDOException $e) {
            echo "<p class='text-red-600 font-semibold'>Erreur : " . $e->getMessage() . "</p>";
        }
    }
}








if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $sql4 = "DELETE FROM agent WHERE id = :id";
    $stmt4 = $pdo->prepare($sql4);
    $stmt4->execute(['id' => $id]);
    
    // header("Location: ?page=agents_content&search=" . urlencode($search));
    // exit;
}



$sql = "SELECT 
CONCAT(prenom, ' ',nom) AS nom_prenom,
    a.id,
    a.nom,
    a.prenom,
    a.matricule,
    a.email,
    a.telephone,
    a.photo,
    b.libele AS libele_bureau,
    s.libele AS libele_service
FROM agent a
JOIN bureau b ON a.bureau_id = b.id
JOIN service s ON b.service_id = s.id
where telephone like :search or CONCAT(prenom, ' ',nom) like :search";



$stmt = $pdo->prepare($sql);
$stmt->execute(['search' => "%$search%"]);
$agents = $stmt->fetchAll();

$sql2 = "SELECT libele from service";
$stmt2 = $pdo->query($sql2);
$services = $stmt2->fetchAll();
// $services = $stmt2->fetchAll(PDO::FETCH_ASSOC);


$sql3 = "SELECT b.libele, b.service_id, s.libele AS service_libele 
         FROM bureau b 
         JOIN service s ON b.service_id = s.id";
$stmt3 = $pdo->query($sql3);
$bureaux = $stmt3->fetchAll();
// $bureaux = $stmt3->fetchAll(PDO::FETCH_ASSOC);

$sql6 ="SELECT
a.id,
    a.nom,
    a.prenom,
    a.matricule,
    a.email,
    a.telephone,
    a.photo,
    b.libele AS libele_bureau,
    FROM agent a
JOIN bureau b ON a.bureau_id = b.id";

$stmt6 = $pdo->prepare($sql6);
$agents2 = $stmt6->fetchAll();

?>
<?php
// Stocker les données dans un élément invisible pour le JS
echo '<script id="agentsData" type="application/json">' . json_encode($agents) . '</script>';
echo '<script id="bureauxData" type="application/json">' . json_encode($bureaux) . '</script>';
echo '<script id="agentsData2" type="application/json">' . json_encode($agents2) . '</script>';

?>


<!-- Filtres et recherche -->
<div class="bg-gradient-to-r from-indigo-50 to-blue-50 p-5 rounded-xl shadow-sm mb-6 transition-allhover:shadow-md">
    <div class="flex items-center mb-4">
        <i class="fas fa-filter text-indigo-600 mr-2"></i>
        <h2 class="text-lg font-semibold text-gray-700">Recherche et filtres</h2>
    </div>
    <form action="#" method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <input type="hidden" name="page" value="agents_content">
        <div class="relative">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche par nom/prénom</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" value="<?= htmlspecialchars($search) ?>" name="search" id="search"
                    placeholder="Rechercher un agent..."
                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>
        </div>
        <div>
            <label for="filter_service" class="block text-sm font-medium text-gray-700 mb-1">Filtrer par service</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-building text-gray-400"></i>
                </div>
                <select name="filter_service" id="filter_service"
                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Tous les services</option>
                    <?php foreach ($services as $service): ?>
                    <option value="<?= htmlspecialchars(string: $service['libele']) ?>">
                        <?= htmlspecialchars(string: $service['libele']) ?></option> <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label for="filter_bureau" class="block text-sm font-medium text-gray-700 mb-1">Filtrer par bureau</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-door-open text-gray-400"></i>
                </div>
                <select disabled name="filter_bureau" id="filter_bureau"
                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all ">
                    <option value="">Tous les bureaux</option>
                </select>
            </div>
        </div>

        <div class=" flex items-end space-x-2">
            <a href="?page=agents_content"
                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                <i class="fas fa-redo-alt"></i>
            </a>
            <button type="button"
                class="add-agent-btn px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i> Ajouter un agent
            </button>
        </div>
    </form>
</div>

<!-- Affichage des agents - Vue carte -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:hidden gap-4 mb-6" id="agentsCards">
    <?php foreach ($agents as $agent): ?>
    <div
        class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
        <div class="p-4">
            <div class="flex items-center mb-4">
                <div class="h-14 w-14 rounded-full flex items-center justify-center mr-3 border-2 shadow-sm">
                    <img src="<?= $agent['photo'] ?>" alt="Photo de profil" class="rounded-full">
                </div>
                <div>
                    <h3 class="font-semibold text-lg text-gray-800"><?= $agent['nom_prenom'] ?></h3>
                    <div class="flex items-center text-gray-600 text-sm">
                        <i class="fas fa-briefcase mr-1"></i>
                        <span><?= $agent['libele_service'] ?></span>
                    </div>
                </div>
            </div>
            <div class="space-y-2 mb-4">
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-door-open w-5 text-center mr-2"></i>
                    <span><?= $agent['libele_bureau'] ?></span>
                </div>
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-phone-alt w-5 text-center mr-2"></i>
                    <span><?= $agent['telephone'] ?></span>
                </div>
            </div>
            <div class="flex justify-between pt-3 border-t border-gray-100">
                <button
                    class="edit-agent-btn px-3 py-1 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors"
                    data-id="<?= $agent['id'] ?>">
                    <i class="fas fa-edit mr-1"></i> Modifier
                </button>
                <button
                    class="qr-agent-btn px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors"
                    data-id="<?= $agent['id'] ?>">
                    <i class="fas fa-qrcode mr-1"></i> QR Code
                </button>
                <button
                    class="delete-agent-btn px-3 py-1 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors"
                    data-id="<?= $agent['id'] ?>">
                    <i class="fas fa-trash mr-1"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Affichage des agents - Vue tableau -->
<div class="hidden lg:block overflow-hidden rounded-xl shadow-sm bg-white" id="agentsTable">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 text-gray-700 text-sm uppercase font-semibold">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Agent</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Service</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Bureau</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Téléphone</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($agents as $agent): ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">

                    <div class="flex items-center">
                        <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3 border">
                            <?php 
                    $photoExists = !empty($agent['photo']) && $agent['photo'] !== 'NULL' &&
                    file_exists($agent['photo']);
                    if ($photoExists):
                     ?>
                            <img src="<?= $agent['photo'] ?>" alt="<?php echo htmlspecialchars($agent['nom_prenom']); 
                                ?>" class="rounded-full"
                                onerror="this.parentNode.innerHTML = getInitialsCircle('<?php echo htmlspecialchars($agent['nom_prenom']); ?>')">
                            <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-600 font-medium">
                                    <?php echo strtoupper(substr($agent['nom_prenom'], 0, 1) . substr(explode(' ', $agent['nom_prenom'])[1], 0, 1)); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class=" text-sm font-medium text-gray-900"><?= $agent['nom_prenom'] ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td class=" px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <span class="text-sm text-gray-900"><?= $agent['libele_service'] ?></span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $agent['libele_bureau'] ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $agent['telephone'] ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex space-x-2 justify-end">
                        <button class="edit-agent-btn text-blue-600 hover:text-blue-900 transition-colors"
                            data-id="<?= $agent['id'] ?>" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="qr-agent-btn text-green-600 hover:text-green-900 transition-colors"
                            data-id="<?= $agent['id'] ?>" title="Générer QR Code">
                            <i class="fas fa-qrcode"></i>
                        </button>
                        <button class="delete-agent-btn text-red-600 hover:text-red-900 transition-colors"
                            data-id="<?= $agent['id'] ?>" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modales -->
<div id="agentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl transform transition-all duration-300 scale-95 opacity-0"
        id="agentModalContent">
        <div class="border-b px-6 py-4 flex justify-between items-center">
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-user-plus mr-2 text-indigo-600"></i>
                <span>Ajouter un nouvel agent</span>
            </h3>
            <button class="close-modal text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="agentForm" action="?page=agents_content" method="post" enctype="multipart/form-data" class="p-6">
            <input type="hidden" id="agent_id" name="agent_id" value="">
            <input type="hidden" id="action" name="action" value="add"> <!-- Ajouté pour distinguer add/update -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nom -->
                <div>
                    <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="nom" id="nom" required
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>
                </div>

                <!-- Prénoms -->
                <div>
                    <label for="prenoms" class="block text-sm font-medium text-gray-700 mb-1">Prénoms</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user-tag text-gray-400"></i>
                        </div>
                        <input type="text" name="prenoms" id="prenoms" required
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>
                </div>

                <!-- Matricule -->
                <div>
                    <label for="matricule" class="block text-sm font-medium text-gray-700 mb-1">Matricule</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-id-badge text-gray-400"></i>
                        </div>
                        <input type="text" name="matricule" id="matricule" required
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="email" id="email" required
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>
                </div>

                <!-- Téléphone -->
                <div>
                    <label for="telephone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-phone-alt text-gray-400"></i>
                        </div>
                        <input type="tel" name="telephone" id="telephone" maxlength="9" pattern="\d{9}" required
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>
                </div>

                <!-- Photo -->
                <div>
                    <label for="photo" class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-camera text-gray-400"></i>
                        </div>
                        <input type="file" name="photo" id="photo" accept="image/*"
                            class="block w-full pl-10 pr-4 py-2 border border-gray-300 text-gray-700 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer file:cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 transition-all">
                    </div>
                </div>

                <!-- Bureau -->
                <div>
                    <label for="bureau" class="block text-sm font-medium text-gray-700 mb-1">Bureau</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-building text-gray-400"></i>
                        </div>
                        <select id="bureau_id" name="bureau_id" required class="border px-2 py-1">
                            <option value="">-- Choisir un bureau --</option>
                            <?php foreach ($bureaux2 as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['libele']) ?></option>
                            <?php endforeach; ?>
                        </select>

                    </div>
                </div>

            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button"
                    class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95 opacity-0"
        id="deleteModalContent">
        <div class="border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>
                <span>Confirmer la suppression</span>
            </h3>
            <button class="close-modal text-gray-400hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <p class="text-gray-700 mb-6">Êtes-vous sûr de vouloir supprimer cet agent ? Cette action est
                irréversible.
            </p>
            <div class="flex justify-end space-x-3">
                <button type="button"
                    class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </button>
                <a id="confirmDeleteBtn" href="#"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Supprimer
                </a>

            </div>
        </div>
    </div>
</div>

<div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95 opacity-0"
        id="qrModalContent">
        <div class="border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-qrcode mr-2 text-green-600"></i>
                <span>QR Code de l'agent</span>
            </h3>
            <button class="close-modal text-gray-400hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="flex justify-center mb-4">
                <div id="qrCodeContainer" class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                </div>
            </div>
            <div class="text-center mb-6">
                <p id="qrAgentName" class="text-lg font-medium text-gray-800"></p>
                <p id="qrAgentInfo" class="text-sm text-gray-600"></p>
            </div>
            <div class="flex justify-center space-x-3">
                <button type="button"
                    class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-times mr-2"></i> Fermer
                </button>
                <button type="button" id="downloadQRBtn"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all flex items-center">
                    <i class="fas fa-download mr-2"></i> Télécharger
                </button>
            </div>
        </div>
    </div>
</div>