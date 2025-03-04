<?php
// Commande à exécuter
$command = "cd /home/clients/6087b00a271549b68ab2a98fe0a647f5/sites/cdn.bagou450.com/versions && php mc.php download";

// Exécution de la commande
$output = shell_exec($command);
$status = ($output !== null) ? 200 : 400;

// Retourner le code de réponse HTTP approprié
http_response_code($status);

// Retourner la sortie de la commande, même en cas d'erreur
echo $output;
?>

