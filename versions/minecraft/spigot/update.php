<?php

// Récupérer le contenu JSON depuis l'URL
$jsonResultats = file_get_contents('http://c8ef7df1-4bff-42e1-be8f.cf9ec3becfc7-spigot.bagou450.com');

if ($jsonResultats === false) {
    // Erreur lors de la récupération du contenu JSON
    http_response_code(500); // Réponse d'erreur interne du serveur
    echo "Une erreur s'est produite lors de la récupération du contenu JSON.";
} else {
    // Décodez le JSON pour obtenir un tableau de fichiers
    $listeFichiers = json_decode($jsonResultats, true);

    // Log message: La liste des versions a été récupérée
    echo "La liste des versions a été récupérée.\n";

    // Tableau pour stocker les informations sur les fichiers
    $resultat = [];

    // Parcourir la liste des fichiers
    foreach ($listeFichiers as $fichier) {
        $nomFichier = $fichier['name'];
        $url = $fichier['url'];
        $sha256 = $fichier['sha256'];

        $nom = pathinfo($nomFichier, PATHINFO_FILENAME); // Obtenir le nom du fichier sans extension
        $version = str_replace('.jar', '', $nom); // La version est le nom du fichier sans l'extension .jar

        $cheminFichier = './' . $nom; // Chemin où le fichier sera téléchargé

        // Vérifier si le fichier existe déjà
        if (file_exists($cheminFichier)) {
            // Vérifier le hash SHA256
            $hashLocal = hash_file('sha256', $cheminFichier);

            if ($hashLocal === $sha256) {
                // Le fichier existe déjà et a le même hash, ajouter les informations à la liste
                echo "La version $version existe déjà.\n";
                $infosFichier = [
                    "name" => $nomFichier,
                    "version" => $version
                ];
                $resultat[] = $infosFichier;
                continue;
            }
        }

        $tentatives = 3; // Nombre de tentatives de téléchargement

        while ($tentatives > 0) {
            // Vérifier si le téléchargement a réussi
            if (file_put_contents($cheminFichier, file_get_contents($url)) !== false) {
                echo "La version $version a été téléchargée.\n";
                break; // Sortir de la boucle si le téléchargement est réussi
            }

            $tentatives--; // Réduire le nombre de tentatives restantes
        }

        // Vérifier si le téléchargement a échoué après toutes les tentatives
        if ($tentatives === 0) {
            // Erreur lors du téléchargement du fichier
            http_response_code(500); // Réponse d'erreur interne du serveur
            echo "Une erreur s'est produite lors du téléchargement du fichier.";
            exit; // Arrêter l'exécution du script
        }

        // Ajouter les informations sur le fichier à la liste
        $infosFichier = [
            "name" => $nomFichier,
            "version" => $version
        ];
        $resultat[] = $infosFichier;
    }

    // Trier les fichiers par version (du plus grand au plus petit)
    usort($resultat, 'comparerVersions');

    // Convertir la liste des fichiers triés en JSON
    $jsonFichiers = json_encode($resultat, JSON_PRETTY_PRINT);

    // Chemin du fichier JSON de sortie
    $cheminSortie = '../getlist/Spigot.json';

    $tentativesEcriture = 3; // Nombre de tentatives d'écriture du fichier JSON de sortie

    while ($tentativesEcriture > 0) {
        // Écrire le JSON dans le fichier de sortie
        if (file_put_contents($cheminSortie, $jsonFichiers) !== false) {
            // Succès
            http_response_code(200); // Réponse de succès
            echo "Le fichier JSON a été généré avec succès.";
            break; // Sortir de la boucle si l'écriture est réussie
        }

        $tentativesEcriture--; // Réduire le nombre de tentatives restantes
    }

    // Vérifier si l'écriture du fichier JSON a échoué après toutes les tentatives
    if ($tentativesEcriture === 0) {
        // Erreur lors de l'écriture du fichier JSON de sortie
        http_response_code(500); // Réponse d'erreur interne du serveur
        echo "Une erreur s'est produite lors de l'écriture du fichier JSON de sortie.";
    }
}

/**
 * Fonction pour comparer les versions dans l'ordre décroissant.
 * Utilisé pour le tri des fichiers par version.
 */
function comparerVersions($a, $b)
{
    return version_compare($b["version"], $a["version"]);
}

