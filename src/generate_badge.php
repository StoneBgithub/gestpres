<?php
// generate_badge.php
require_once "db_connect.php";

// Vérifier si l'ID agent est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID agent manquant");
}

$agent_id = (int)$_GET['id'];

try {
    // Récupérer les informations de l'agent
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.nom,
            a.prenom,
            CONCAT(a.prenom, ' ', a.nom) AS nom_complet,
            a.matricule,
            a.email,
            a.telephone,
            a.photo,
            b.libele AS bureau,
            s.libele AS service
        FROM agent a
        JOIN bureau b ON a.bureau_id = b.id
        JOIN service s ON b.service_id = s.id
        WHERE a.id = :id
    ");
    
    $stmt->execute(['id' => $agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agent) {
        die("Agent non trouvé");
    }
    
    // Dimensions du badge (format B7 vertical)
    $badge_width = 874;
    $badge_height = 1240;
    
    // Créer l'image du badge
    $badge = imagecreatetruecolor($badge_width, $badge_height);
    
    // Charger l'image de fond (template)
    $background_path = 'templates/badge_template.png'; // Chemin vers votre template
    if (file_exists($background_path)) {
        $background = imagecreatefrompng($background_path);
        imagecopy($badge, $background, 0, 0, 0, 0, $badge_width, $badge_height);
        imagedestroy($background);
    } else {
        // Si pas de template, créer un fond blanc
        $white = imagecolorallocate($badge, 255, 255, 255);
        imagefill($badge, 0, 0, $white);
    }
    
    // Couleurs
    $black = imagecolorallocate($badge, 0, 0, 0);
    $blue = imagecolorallocate($badge, 0, 102, 204);
    
    // Charger et redimensionner la photo de l'agent
    $photo_ellipse_width = 266;
    $photo_ellipse_height = 279;
    $photo_x = 304;
    $photo_y = 479;
    
    if (!empty($agent['photo']) && file_exists($agent['photo'])) {
        $photo_extension = strtolower(pathinfo($agent['photo'], PATHINFO_EXTENSION));
        
        switch ($photo_extension) {
            case 'jpg':
            case 'jpeg':
                $photo_image = imagecreatefromjpeg($agent['photo']);
                break;
            case 'png':
                $photo_image = imagecreatefrompng($agent['photo']);
                break;
            case 'gif':
                $photo_image = imagecreatefromgif($agent['photo']);
                break;
            default:
                $photo_image = null;
        }
        
        if ($photo_image) {
            // Créer une image elliptique pour la photo
            $photo_ellipse = imagecreatetruecolor($photo_ellipse_width, $photo_ellipse_height);
            $transparent = imagecolorallocatealpha($photo_ellipse, 0, 0, 0, 127);
            imagefill($photo_ellipse, 0, 0, $transparent);
            imagesavealpha($photo_ellipse, true);
            
            // Redimensionner la photo pour qu'elle s'adapte à l'ellipse
            $photo_resized = imagecreatetruecolor($photo_ellipse_width, $photo_ellipse_height);
            imagecopyresampled($photo_resized, $photo_image, 0, 0, 0, 0, 
                             $photo_ellipse_width, $photo_ellipse_height, 
                             imagesx($photo_image), imagesy($photo_image));
            
            // Créer un masque elliptique
            $mask = imagecreatetruecolor($photo_ellipse_width, $photo_ellipse_height);
            $mask_bg = imagecolorallocate($mask, 0, 0, 0);
            $mask_ellipse = imagecolorallocate($mask, 255, 255, 255);
            imagefill($mask, 0, 0, $mask_bg);
            imagefilledellipse($mask, $photo_ellipse_width/2, $photo_ellipse_height/2, 
                             $photo_ellipse_width-4, $photo_ellipse_height-4, $mask_ellipse);
            
            // Appliquer le masque elliptique
            for ($x = 0; $x < $photo_ellipse_width; $x++) {
                for ($y = 0; $y < $photo_ellipse_height; $y++) {
                    $mask_color = imagecolorat($mask, $x, $y);
                    if ($mask_color == $mask_bg) {
                        imagesetpixel($photo_ellipse, $x, $y, $transparent);
                    } else {
                        $pixel = imagecolorat($photo_resized, $x, $y);
                        imagesetpixel($photo_ellipse, $x, $y, $pixel);
                    }
                }
            }
            
            // Copier la photo elliptique sur le badge
            imagecopy($badge, $photo_ellipse, $photo_x, $photo_y, 0, 0, $photo_ellipse_width, $photo_ellipse_height);
            
            imagedestroy($photo_image);
            imagedestroy($photo_resized);
            imagedestroy($photo_ellipse);
            imagedestroy($mask);
        }
    }
    
    // Charger les polices (vous devez avoir des fichiers de polices TTF)
    $font_path_large = 'fonts/arial_bold.ttf'; // Police pour le nom (60pt)
    $font_path_medium = 'fonts/arial_regular.ttf'; // Police pour le bureau (30pt)
    
    // Ajouter le nom (60pt à la position 208,49px, 778,77px)
    $nom_x = 208;
    $nom_y = 778;
    $nom_size = 60;
    
    if (file_exists($font_path_large)) {
        imagettftext($badge, $nom_size, 0, $nom_x, $nom_y, $black, $font_path_large, 
                    strtoupper($agent['nom_complet']));
    } else {
        // Fallback avec police système
        $font_size = 5; // Taille approximative
        imagestring($badge, $font_size, $nom_x, $nom_y - 20, 
                   strtoupper($agent['nom_complet']), $black);
    }
    
    // Ajouter le bureau (30pt à la position 163,38px, 852,5px)
    $bureau_x = 163;
    $bureau_y = 852;
    $bureau_size = 30;
    
    if (file_exists($font_path_medium)) {
        imagettftext($badge, $bureau_size, 0, $bureau_x, $bureau_y, $blue, $font_path_medium, 
                    $agent['bureau']);
    } else {
        // Fallback avec police système
        $font_size = 3;
        imagestring($badge, $font_size, $bureau_x, $bureau_y - 10, 
                   $agent['bureau'], $blue);
    }
    
    // Générer le QR code
    require_once 'phpqrcode/qrlib.php'; // Inclure la librairie QR code
    
    $qr_data = json_encode([
        'id' => $agent['id'],
        'nom' => $agent['nom_complet'],
        'matricule' => $agent['matricule'],
        'bureau' => $agent['bureau'],
        'service' => $agent['service']
    ]);
    
    // Créer le QR code temporaire
    $qr_temp_file = 'temp/qr_temp_' . $agent_id . '.png';
    if (!is_dir('temp')) {
        mkdir('temp', 0777, true);
    }
    
    QRcode::png($qr_data, $qr_temp_file, QR_ECLEVEL_M, 10, 2);
    
    // Charger et redimensionner le QR code
    $qr_image = imagecreatefrompng($qr_temp_file);
    $qr_width = 295;
    $qr_height = 289;
    $qr_x = 289;
    $qr_y = 915;
    
    $qr_resized = imagecreatetruecolor($qr_width, $qr_height);
    $qr_white = imagecolorallocate($qr_resized, 255, 255, 255);
    imagefill($qr_resized, 0, 0, $qr_white);
    
    imagecopyresampled($qr_resized, $qr_image, 0, 0, 0, 0, 
                      $qr_width, $qr_height, 
                      imagesx($qr_image), imagesy($qr_image));
    
    // Copier le QR code sur le badge
    imagecopy($badge, $qr_resized, $qr_x, $qr_y, 0, 0, $qr_width, $qr_height);
    
    // Nettoyer les fichiers temporaires
    imagedestroy($qr_image);
    imagedestroy($qr_resized);
    unlink($qr_temp_file);
    
    // Envoyer l'image comme réponse
    header('Content-Type: image/png');
    header('Content-Disposition: inline; filename="badge_' . $agent['matricule'] . '.png"');
    
    imagepng($badge);
    imagedestroy($badge);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la génération du badge : " . $e->getMessage());
    die("Erreur lors de la génération du badge");
}
?>