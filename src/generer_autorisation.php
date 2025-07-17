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
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Attestation d'autorisation d'absence</title>
            <style>
                body {
                    font-family: "Times New Roman", Times, serif;
                    margin: 40px 60px;
                    font-size: 14pt;
                    color: #000;
                }
                .header {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 30px;
                    border-bottom: 2px solid black;
                    padding-bottom: 8px;
                }
                .header-left {
                    font-weight: bold;
                    font-size: 14pt;
                    line-height: 1.3;
                }
                .header-right {
                    font-weight: bold;
                    font-size: 14pt;
                    font-style: italic;
                    position: relative;
                    text-align: center;
                    min-width: 180px;
                }
                
                h2 {
                    text-align: center;
                    text-decoration: underline;
                    margin-bottom: 30px;
                    font-weight: bold;
                    font-size: 18pt;
                }
                .content p {
                    margin: 14px 0;
                    line-height: 1.5;
                }
                .signature {
                    margin-top: 60px;
                    text-align: right;
                    font-weight: bold;
                    font-size: 14pt;
                }
                .signature .service {
                    margin-bottom: 60px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="header-left">
                    <strong>Ministère de la Fonction Publique <br>
                    du Travail Social et de la Sécurité Sociale</strong>
                   <strong>Direction des Systèmes d'informations</strong> 
                </div>
                <div class="header-right">
                    <div class="rep-congo">République du Congo</div>
                    Unité – Travail – Progrès
                </div>
            </div><br><br>

            <h2>Attestation d'autorisation d'absence</h2>

            <div class="content">
                <p>
                    La présente attestation stipule que l’agent <strong><?= htmlspecialchars($data['prenom'] . ' ' . $data['nom']) ?></strong>, 
                    affecté au service <strong><?= htmlspecialchars($data['service_agent']) ?></strong>, est autorisé à s’absenter 
                    pour la période allant du <strong><?= date('d/m/Y', strtotime($data['date_debut'])) ?></strong> au 
                    <strong><?= date('d/m/Y', strtotime($data['date_fin'])) ?></strong>, pour la cause suivante : 
                    <strong><?= htmlspecialchars($data['motif']) ?></strong>.
                </p>
                <p>
                    Description complémentaire : <em><?= nl2br(htmlspecialchars($data['description'])) ?></em>
                </p>
            </div>

            <div class="signature">

            <div>Fait à Brazzaville le, <?= date('d/m/Y') ?></div><br><br>
                
                <div><?= htmlspecialchars($data['valideur_prenom'] . ' ' . $data['valideur_nom']) ?></div> <br><br><br><br>
                <div class="service">chef de service <?= htmlspecialchars($data['service_valideur']) ?></div>
            </div>

            <script>
                window.onload = function () {
                    window.print();
                }
            </script>
        </body>
        </html>
        <?php
    } else {
        echo "Aucune information trouvée.";
    }
}
?>
