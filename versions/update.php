<?php

// Exécuter la commande système
$output = [];
$returnValue = 0;
exec('php mc.php download', $output, $returnValue);

// Vérifier le code de retour de la commande
if ($returnValue === 0) {
    // La commande s'est exécutée avec succès
    http_response_code(200);
    echo "Commande exécutée avec succès.";
} else {
    // La commande a renvoyé une erreur
    http_response_code(400);
    echo "Erreur lors de l'exécution de la commande.";
}

?>

