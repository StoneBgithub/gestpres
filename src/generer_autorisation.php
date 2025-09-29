<?php
require_once "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['absence_id'])) {
    $absence_id = $_POST['absence_id'];

    $sql = "
    SELECT 
        a.nom, a.prenom,
        s.libele AS service_agent,
        t.libelle AS motif,
        aj.description,
        aj.date_debut,
        aj.date_fin,
        l.agent_id AS valideur_id,
        va.nom AS valideur_nom,
        va.prenom AS valideur_prenom,
        sv.libele AS service_valideur
    FROM absence aj
    JOIN agent a ON aj.agent_id = a.id
    JOIN type_absence t ON aj.id_type_absence = t.id
    JOIN bureau b ON a.bureau_id = b.id
    JOIN service s ON b.service_id = s.id
    JOIN login l ON aj.validation = l.id
    JOIN agent va ON va.id = l.agent_id
    JOIN bureau bv ON va.bureau_id = bv.id
    JOIN service sv ON bv.service_id = sv.id
    WHERE aj.id = :absence_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['absence_id' => $absence_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Calcul de la durée
        $date_debut = new DateTime($data['date_debut']);
        $date_fin = new DateTime($data['date_fin']);
        $duree = $date_debut->diff($date_fin)->days + 1;
        
        // Génération d'un numéro d'autorisation unique
        $numero_autorisation = 'AUT-' . date('Y') . '-' . str_pad($absence_id, 4, '0', STR_PAD_LEFT);
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Autorisation d'Absence N° <?= $numero_autorisation ?></title>
            <style>
                @page {
                    size: A4;
                    margin: 20mm;
                }
                
                body {
                    font-family: "Segoe UI", "DejaVu Sans", Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    font-size: 12pt;
                    line-height: 1.6;
                    color: black;
                    background: #fff;
                }

                /* En-tête officiel */
                .header {
                    position: relative;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                    padding: 20px;
                     color: #666;
    margin-top: 10px;
                    
                }

                .header-content {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                }

                .ministry-section {
                    display: flex;
                    align-items: flex-start;
                    flex: 1;
                }

                .flag-emblem {
                    width: 12px;
                    height: 80px;
                    margin-right: 20px;
                    display: flex;
                    flex-direction: column;
                    border-radius: 3px;
                    overflow: hidden;
                    box-shadow: 0 3px 6px rgba(0,0,0,0.15);
                }

                .flag-stripe {
                    flex: 1;
                }

                .flag-stripe.green { background: #228B22; }
                .flag-stripe.yellow { background: #FFD700; }
                .flag-stripe.red { background: #DC143C; }

                .ministry-info h1 {
                    font-size: 12pt;
                    font-weight: bold;
                    color: black;
                    margin: 0 0 5px 0;
                    text-transform: uppercase;
                    letter-spacing: 0.8px;
                }

                .ministry-info h2 {
                    font-size: 12pt;
                    color: black;
                    margin: 0 0 8px 0;
                     font-weight: bolder;
                    font-weight: 600;
                     letter-spacing: 0.8px;
                    text-transform: uppercase;
                }

                .direction {
                    font-size: 12pt;
                    color: black;
                    font-weight: bold;
                }

                .republic-info {
                    text-align: right;
                    color: black;
                }

                .republic-name {
                    font-size: 14pt;
                    font-weight: bold;
                    margin-bottom: 8px;
                }

                .national-motto {
                    font-size: 12pt;
                    font-style: italic;
                    font-weight: 600;
                }

                /* Document info */
                .document-header {
                    color: black;
                    margin-bottom: 30px;
                    text-align: center;
                }

                .document-title {
                    text-align: center;
                    font-size: 20pt;
                    font-weight: bold;
                    margin: 0 0 10px 0;
                    text-transform: uppercase;
                    letter-spacing: 1.2px;
                }

                .document-number {
                    font-size: 14pt;
                    opacity: 0.9;
                    margin: 0;
                }

                /* Contenu principal */
                .main-content {
                    
                    margin-bottom: 30px;
                }

                .authorization-text {
                    font-size: 13pt;
                    line-height: 1.8;
                  margin-left: 25px;
                  margin-right: 30px;
                    text-align: justify;
                    margin-bottom: 25px;
                    color: black;
                }

                .highlight {
                    font-weight: bold;
                    color: black;
                }


                /* Section signature */
                .signature-section {
                    margin-top: 50px;
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                }

                .document-info {
                    flex: 1;
                }

                .issue-date {
                    font-size: 12pt;
                    margin-left: 25px;
                    color: black;
                    margin-bottom: 10px;
                }

                .signature-block {
                    text-align: center;
                    min-width: 250px;
                    padding: 20px;
                    
                }

                .authority-title {
                    font-size: 11pt;
                    font-weight: bold;
                    color: black;
                    margin-bottom: 50px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .authority-name {
                    font-size: 13pt;
                    font-weight: bold;
                    color: black;
                    margin-bottom: 10px;
                }

                .authority-position {
                    font-size: 11pt;
                    font-weight: 600;
                    text-transform: capitalize;
                }

                /* Validation stamp */
                .validation-stamp {
                    position: absolute;
                    top: 20px;
                    right: 20px;
                    width: 120px;
                    height: 120px;
                    border: 3px solid #27ae60;
                    border-radius: 50%;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    background: rgba(39, 174, 96, 0.1);
                    font-size: 10pt;
                    font-weight: bold;
                    color: #27ae60;
                    text-align: center;
                    line-height: 1.2;
                }

                /* Footer */
                .document-footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 2px solid #e9ecef;
                    text-align: center;
                    font-size: 10pt;
                    color: #666;
                }

                .confidential-notice {
                    background: #fff3cd;
                    color: #856404;
                    padding: 10px;
                    border-radius: 4px;
                    font-weight: 600;
                    margin-top: 10px;
                }

                /* Print styles */
                @media print {
                    body { -webkit-print-color-adjust: exact; }
                    .main-content { box-shadow: none; }
                    .signature-section { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
           

            <!-- En-tête officiel -->
            <header class="header">
                <div class="header-content">
                    <div class="ministry-section">
                        <div class="flag-emblem">
                            <div class="flag-stripe green"></div>
                            <div class="flag-stripe yellow"></div>
                            <div class="flag-stripe red"></div>
                        </div>
                        <div class="ministry-info">
                            <h1>Ministère de la Fonction Publique</h1>
                            <h1>du Travail Social et de la Sécurité Sociale</h1>
                            <div class="direction">Direction des Systèmes d'Information</div>
                        </div>
                    </div>
                    <div class="republic-info">
                        <div class="republic-name">République du Congo</div>
                        <div class="national-motto"> Unité – Travail – Progrès</div>
                    </div>
                </div>
            </header>
<hr style="border: none; border-top: 2px solid gray; margin: 20px 0;">
            <!-- Titre du document -->
            <div class="document-header">
                <h1 class="document-title">Autorisation d'Absence</h1>
                <p class="document-number">N° <?= $numero_autorisation ?></p>
            </div>

            <!-- Contenu principal -->
            <main class="main-content">
               <div class="authorization-text">
    La présente autorisation certifie que 
    <span class="highlight"><?= htmlspecialchars(strtoupper($data['prenom']) . ' ' . strtoupper($data['nom'])) ?></span>, 
    agent en service au 
    <span class="highlight"><?= htmlspecialchars($data['service_agent']) ?></span>, est formellement autorisé(e) 
    à s'absenter de ses fonctions pour la période comprise entre le 
    <span class="highlight"><?= date('d/m/Y', strtotime($data['date_debut'])) ?></span> 
    et le <span class="highlight"><?= date('d/m/Y', strtotime($data['date_fin'])) ?></span> inclus,
    pour le motif suivant : 
    <span class="highlight"><?= htmlspecialchars($data['motif']) ?></span>.
</div>

                <div class="authorization-text">
                    Cette autorisation est délivrée pour permettre à l'intéressé(e) de justifier son absence auprès 
                    de toute autorité compétente. Elle prend effet immédiatement et demeure valable pour la période 
                    susmentionnée.
                </div>
            </main>

            <!-- Section signature -->
            <div class="signature-section">
                <div class="document-info">
                    <div class="issue-date">
                        <strong>Fait à Brazzaville, le <?= date('d/m/Y') ?></strong>
                    </div>
                </div>
                
                <div class="signature-block">
                    <div class="authority-title">Le Chef de Service</div>
                    <div class="authority-name"><?= htmlspecialchars(strtoupper($data['valideur_prenom']) . ' ' . strtoupper($data['valideur_nom'])) ?></div>
                    <div class="authority-position"><?= htmlspecialchars($data['service_valideur']) ?></div>
                </div>
            </div>

            <!-- Pied de page -->
            <footer class="document-footer">
                <div>Document officiel - Ministère de la Fonction Publique, du Travail Social et de la Sécurité Sociale</div>
                <div class="confidential-notice">
                    ⚠ Ce document est strictement personnel et ne peut être utilisé que par la personne désignée
                </div>
            </footer>

            <script>
                window.onload = function () {
                    // Auto-impression après 1 seconde pour laisser le temps au CSS de se charger
                    setTimeout(function() {
                        window.print();
                    }, 1000);
                }
            </script>
        </body>
        </html>
        <?php
    } else {
        echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
                <h2 style="color: #e74c3c;">❌ Erreur</h2>
                <p>Aucune information d\'absence trouvée pour l\'ID spécifié.</p>
              </div>';
    }
} else {
    echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
            <h2 style="color: #f39c12;">⚠ Accès non autorisé</h2>
            <p>Cette page nécessite un ID d\'absence valide.</p>
          </div>';
}
?>