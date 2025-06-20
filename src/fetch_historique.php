<?php
require "db_connect.php";

try {
    $stmt = $pdo->query("
        SELECT 
            j.action_type as action_type,
            j.donnees as donnees,
            j.date_action as date_action
        FROM journal_actions j
        ORDER BY j.date_action DESC;
    ");
    
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($actions as $action) {
        // Couleur du badge selon le type d'action
        $couleur = match (strtolower($action['action_type'])) {
            'ajouter' => 'bg-green-100 text-green-800',
            'modifier' => 'bg-yellow-100 text-yellow-800',
            'supprimer' => 'bg-red-100 text-red-800',
            'telecharger' => 'bg-blue-100 text-blue-800',
            'generer' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800'
        };

        echo "<tr>";
        echo "<td><span class='inline-block $couleur text-xs font-semibold px-2.5 py-0.5 rounded-full'>{$action['action_type']}</span></td>";
        // Données enregistrées (JSON)
        echo "<td class='flex flex-wrap gap-2'>";
        $donnees = json_decode($action['donnees'], true);
        if (is_array($donnees)) {
            foreach ($donnees as $cle => $valeur) {
                if (!empty($valeur)) {
                    echo "<span class='inline-block $couleur text-xs font-semibold px-2.5 py-0.5 rounded-full'>
                            $cle : $valeur
                          </span>";
                }
            }
        } else {
            echo "<span class='text-gray-500 italic'>Aucune donnée</span>";
        }
        echo "</td>";

        echo "<td>{$action['date_action']}</td>";
        echo "</tr>";
    }
} catch (PDOException $e) {
    error_log("Erreur dans fetch_historique.php : " . $e->getMessage());
    echo "<tr><td colspan='3'>Erreur lors du chargement de l'historique : " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>