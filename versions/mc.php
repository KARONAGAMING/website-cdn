<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
// Créer une instance de l'application Symfony Console
$application = new Application();

// Définir le nom et la version de l'application
$application->setName('Minecraft Server Downloader');
$application->setVersion('1.0.0');

// Créer une commande pour le téléchargement des fichiers JAR
$command = new \Symfony\Component\Console\Command\Command('download');
$command->setDescription('Télécharge les fichiers JAR des différentes versions de Minecraft.');
$compteur = 0;
// Configuration de la commande
$command->setCode(function (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
    // Fonction pour vérifier si un fichier existe en utilisant l'empreinte MD5
    // Déclaration des styles de formatage pour les couleurs
    global $compteur;
    $downloadStartTime = microtime(true);

    $outputFormatter = $output->getFormatter();
    $successStyle = new OutputFormatterStyle('green', null, ['bold']);
    $errorStyle = new OutputFormatterStyle('red', null, ['bold']);
    $outputFormatter->setStyle('success', $successStyle);
    $outputFormatter->setStyle('error', $errorStyle);

    // Fonction pour télécharger un fichier depuis une URL et le sauvegarder localement
    function downloadFile($url, $filePath)
    {
        $client = new Client();
        try {
            // Effectuer la requête HTTP GET avec gestion automatique des redirections
            $response = $client->get($url, ['sink' => $filePath]);
            // Vérifier le code de statut de la réponse
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                return true;
            } else {
                return false;
            }
        } catch (RequestException $e) {
            return false;
        }
    }
    function reverseJsonFile($filename) {
        // Lire le contenu du fichier JSON
        $jsonContent = file_get_contents($filename);
    
        // Décoder le contenu JSON en un tableau associatif
        $data = json_decode($jsonContent, true);
    
        // Inverser l'ordre du tableau
        $reversedData = array_reverse($data);
    
        // Encoder le tableau inversé en JSON
        $reversedJson = json_encode($reversedData, JSON_PRETTY_PRINT);
    
        // Réécrire le fichier avec le contenu inversé
        file_put_contents($filename, $reversedJson);
    }
    // Fonction pour optimiser le téléchargement en vérifiant si le fichier existe déjà et a la même empreinte MD5
    function optimizeDownload($downloadUrl, $filePath, $md5, $type)
    {
        $name = explode('/', $filePath)[2];
        if (file_exists($filePath) && $type !== null && $md5 !== null && $type !== 'skip') {
                $jsonContent = file_get_contents("minecraft/getlist/$type.json");
                $versions = json_decode($jsonContent, true);

                foreach ($versions as $version) {


                    if ($version['version'] === $name) {
                        /*echo $version['md5'] . PHP_EOL;
                        echo $md5 . PHP_EOL;*/
                        $md5Existing = $version['md5'];
                        if ($md5Existing === $md5) {
                            return true;
                        }
                        break;
                    }
                }
            /*if($type === 'md5') {
                $md5Existing = md5_file($filePath);
                if ($md5Existing === $md5) {
                    return true;
                }
            }
            if($type === 'sha1') {
                $sha1Existing = sha1_file($filePath);
                if ($sha1Existing === $md5) {
                    return true;
                }
            }
            if($type === 'sha256') {
                $sha256Existing = hash_file('sha256', $filePath);
                if ($sha256Existing === $md5) {
                    return true;
                }
            }*/
        }
        if($type === 'skip' && file_exists($filePath)) {
            return true;
        }
        echo PHP_EOL . "Download $filePath" . PHP_EOL;
        global $compteur;
        $compteur ++;
        return downloadFile($downloadUrl, $filePath);
    }
// Ajouter une fonction pour créer le fichier de liste des versions
function createVersionListFile($versionType, $versionId, $md5)
{

    if(!$md5) {
        $md5 = 'null';
    }
    $versionList = [
        'name' => "$versionType $versionId",
        'version' => "$versionId",
        'md5' => $md5
    ];

    $versionListFilePath = "minecraft/getlist_new/$versionType.json";
    
    // Vérifier si le fichier existe déjà
    if (file_exists($versionListFilePath)) {
        // Lire le contenu du fichier existant
        $existingVersions = json_decode(file_get_contents($versionListFilePath), true);
        
        // Ajouter la nouvelle version à la liste existante
        $existingVersions[] = $versionList;
        
        // Réécrire le contenu complet dans le fichier
        file_put_contents($versionListFilePath, json_encode($existingVersions, JSON_PRETTY_PRINT));
    } else {
        // Si le fichier n'existe pas, créer un nouveau fichier avec la version unique
        $versionList = [$versionList];
        file_put_contents($versionListFilePath, json_encode($versionList, JSON_PRETTY_PRINT));
    }
}


    // Déclaration des styles de formatage pour les couleurs
    $outputFormatter = $output->getFormatter();
    $successStyle = new OutputFormatterStyle('green', null, ['bold']);
    $errorStyle = new OutputFormatterStyle('red', null, ['bold']);
    $outputFormatter->setStyle('success', $successStyle);
    $outputFormatter->setStyle('error', $errorStyle);

    // Récupérer les versions Vanilla de Minecraft release via l'API Mojang
    $response = @file_get_contents('https://launchermeta.mojang.com/mc/game/version_manifest.json');
    if ($response === false) {
        $output->writeln('<error>Erreur interne du serveur lors de la récupération des versions de Minecraft release.</error>');
        return 1;
    }
    $versions_data = json_decode($response, true);
    $versions = $versions_data['versions'];

    // Création d'une instance de ProgressBar
    $progressBar = new ProgressBar($output, count($versions));
    $progressBar->setFormat("%current%/%max% [%bar%] %percent:3s%% %message%");
    $progressBar->setMessage('');


    // Parcourir les versions Vanilla release et télécharger les fichiers JAR
    foreach ($versions as $index => $versionData) {
        $versionId = $versionData['id'];
        $versionType = $versionData['type'];
        $jarFileName = $versionId;

        $jarFilePath = 'minecraft/vanilla/' . $jarFileName;
        // Vérifier si la version est une release et exclure les versions de la 1.0 à la 1.2.5
        if ($versionType === 'release') {
            $versionUrl = $versionData['url'];

            // Interroger l'URL spécifique de la version pour obtenir plus de détails
            $versionResponse = @file_get_contents($versionUrl);
            if ($versionResponse === false) {
                $output->writeln('<error>Erreur interne du serveur lors de la récupération des détails de la version '.$versionId.'.</error>');
                return 1;
            }
            
            $versionInfo = json_decode($versionResponse, true);
            $downloads = $versionInfo['downloads'];
            // Vérifier si $downloads['server']['url'] existe
            if (!isset($downloads['server']['url'])) {
                continue;
            }

            $jarDownloadUrl = $downloads['server']['url'];

            // Vérifier si le fichier existe déjà et a la même empreinte MD5
            $md5 = $downloads['server']['sha1'];
            if (optimizeDownload($jarDownloadUrl, $jarFilePath, $md5, 'skip')) {
                $progressBar->advance();
                $progressBar->setMessage(sprintf('<success>Vanilla: %s</success>', $jarFileName));
                createVersionListFile('Vanilla', $versionId, $md5);
            } else {
                $progressBar->setMessage(sprintf('<error>Vanilla: %s</error>', $jarFileName));
            }
        } else {
            $progressBar->advance();
        }

    }

   
    $progressBar->setProgress(0);

 // Récupérer les versions de BungeeCord depuis le site officiel
$response = @file_get_contents('https://ci.md-5.net/job/BungeeCord/lastStableBuild/api/json');
if ($response === false) {
    $output->writeln('<error>Erreur interne du serveur lors de la récupération des versions de BungeeCord.</error>');
    return 1;
}
$buildInfo = json_decode($response, true);
$buildNumber = $buildInfo['number'];

// Construire l'URL de téléchargement pour la dernière version
$jarDownloadUrl = "https://ci.md-5.net/job/BungeeCord/lastStableBuild/artifact/bootstrap/target/BungeeCord.jar";
$jarFilePath = 'minecraft/bungeecord/latest';

if (optimizeDownload($jarDownloadUrl, $jarFilePath, null, null)) {
    $progressBar->advance();
    $progressBar->setMessage(sprintf('<success>Bungeecord: %s</success>', 'latest'));

} else {
    $progressBar->setMessage(sprintf('<error>Bungeecord: %s</error>', 'latest'));
}

// Récupérer les versions de Velocity depuis l'API PaperMC
$response = @file_get_contents('https://papermc.io/api/v2/projects/velocity');
if ($response === false) {
    $output->writeln('<error>Erreur interne du serveur lors de la récupération des versions de Velocity.</error>');
    return 1;
}
$versions_data = json_decode($response, true)['versions'];

// Réinitialiser la barre de progression à 0% pour chaque type de version
$progressBar->setProgress(0);

// Parcourir les versions de Velocity et télécharger les fichiers JAR
foreach ($versions_data as $index => $versionData) {
    $version = $versionData;
    $versionResponse = @file_get_contents("https://papermc.io/api/v2/projects/velocity/versions/{$version}");
    if ($versionResponse === false) {
        $output->writeln('<error>Erreur interne du serveur lors de la récupération des détails de la version '.$version.' de Velocity.</error>');
        return 1;
    }
    $versionInfo = json_decode($versionResponse, true);
    $builds = $versionInfo['builds'];
    $latestBuild = end($builds);
    $buildInfos = json_decode(@file_get_contents("https://papermc.io/api/v2/projects/velocity/versions/{$version}/builds/{$latestBuild}/"), true);
    $filename = $buildInfos['downloads']['application']['name'];
    $filesha256 = $buildInfos['downloads']['application']['sha256'];
    $jarDownloadUrl = "https://papermc.io/api/v2/projects/velocity/versions/{$version}/builds/{$latestBuild}/downloads/{$filename}";
    $jarFileName = "{$version}";
    $jarFilePath = 'minecraft/velocity/' . $jarFileName;

    // Vérifier si le fichier existe déjà et a la même empreinte MD5
    if (optimizeDownload($jarDownloadUrl, $jarFilePath, $filesha256, 'Velocity')) {
        $progressBar->advance();
        $progressBar->setMessage(sprintf('<success>Velocity: %s</success>', $jarFileName));
        createVersionListFile('Velocity', $version, $filesha256);

    } else {
        $progressBar->setMessage(sprintf('<error>Velocity: %s</error>', $jarFileName));
    }
}

    // Récupérer les versions de Velocity depuis l'API PaperMC
$response = @file_get_contents('https://papermc.io/api/v2/projects/paper');
if ($response === false) {
    $output->writeln('<error>Erreur interne du serveur lors de la récupération des versions de PaperMc.</error>');
    return 1;
}
$versions_data = json_decode($response, true)['versions'];

// Réinitialiser la barre de progression à 0% pour chaque type de version
$progressBar->setProgress(0);

// Parcourir les versions de PaperMc et télécharger les fichiers JAR
foreach ($versions_data as $index => $versionData) {
    $version = $versionData;
    $versionResponse = @file_get_contents("https://papermc.io/api/v2/projects/paper/versions/{$version}");
    if ($versionResponse === false) {
        $output->writeln('<error>Erreur interne du serveur lors de la récupération des détails de la version '.$version.' de Velocity.</error>');
        return 1;
    }
    $versionInfo = json_decode($versionResponse, true);
    $builds = $versionInfo['builds'];
    $latestBuild = end($builds);
    $buildInfos = json_decode(@file_get_contents("https://papermc.io/api/v2/projects/paper/versions/{$version}/builds/{$latestBuild}/"), true);
    $filename = $buildInfos['downloads']['application']['name'];
    $filesha256 = $buildInfos['downloads']['application']['sha256'];
    $jarDownloadUrl = "https://papermc.io/api/v2/projects/paper/versions/{$version}/builds/{$latestBuild}/downloads/{$filename}";
    $jarFileName = "{$version}";
    $jarFilePath = 'minecraft/paper/' . $jarFileName;

    // Vérifier si le fichier existe déjà et a la même empreinte MD5
    if (optimizeDownload($jarDownloadUrl, $jarFilePath, $filesha256, 'Paper')) {
        $progressBar->advance();
        $progressBar->setMessage(sprintf('<success>PaperMc: %s</success>', $jarFileName));
        createVersionListFile('Paper', $version, $filesha256);
    } else {
        $progressBar->setMessage(sprintf('<error>PaperMc: %s</error>', $jarFileName));
    }
}

   /* // Récupérer les versions de Spigot via l'API Spigot
    $response = @file_get_contents('https://api.spigot.org/v2/resources/44/versions');
    if ($response === false) {
        $output->writeln('<error>Erreur interne du serveur lors de la récupération des versions de Spigot.</error>');
        return 1;
    }
    $versions_data = json_decode($response, true);

// Réinitialiser la barre de progression à 0% pour chaque type de version
    $progressBar->setProgress(0);
    // Parcourir les versions de Spigot et télécharger les fichiers JAR
    foreach ($versions_data as $index => $versionData) {
        $version = $versionData['name'];
        $jarDownloadUrl = "https://cdn.spiget.org/versions/{$version}/spigot.jar";
        $jarFileName = $version . '.jar';

        $jarFilePath = 'spigot/' . $jarFileName;

        // Vérifier si le fichier existe déjà et a la même empreinte MD5
        $md5 = $versionData['file']['md5'];
        if (optimizeDownload($jarDownloadUrl, $jarFilePath, $md5)) {
            $progressBar->advance();
            $progressBar->setMessage(sprintf('<success>%s</success>', $jarFileName));
        } else {
            $progressBar->setMessage(sprintf('<error>%s</error>', $jarFileName));
        }
    }*/



    // Récupérer les versions de PurpurMC via l'API PurpurMC
    $response = @file_get_contents('https://api.purpurmc.org/v2/purpur/');
    if ($response === false) {
        $output->writeln('<error>Erreur interne du serveur lors de la récupération des versions de PurpurMC.</error>');
        return 1;
    }
    $versions_data = json_decode($response, true);
    $versions = $versions_data['versions'];

    // Réinitialiser la barre de progression à 0% pour chaque type de version
    $progressBar->setProgress(0);

    // Parcourir les versions de PurpurMC et télécharger les fichiers JAR
    foreach ($versions as $index => $versionData) {
        $version = $versionData;
        $versionResponse = @file_get_contents("https://api.purpurmc.org/v2/purpur/{$version}");
        if ($versionResponse === false) {
            $output->writeln('<error>Erreur interne du serveur lors de la récupération des détails de la version '.$version.' de Purpur.</error>');
            return 1;
        }
        $versionInfo = json_decode($versionResponse, true);

        $build = $versionInfo['builds']['latest'];
        $jarDownloadUrl = "https://api.purpurmc.org/v2/purpur/$version/$build/download";
        $jarFileName = $version;
        $jarFilePath = 'minecraft/purpur/' . $jarFileName;
        //Get MD5
        $md5Response = @file_get_contents("https://api.purpurmc.org/v2/purpur/{$version}/{$build}");
        if ($md5Response === false) {
            $output->writeln('<error>Erreur interne du serveur lors de la récupération du md5 de la version '.$version.' de Purpur.</error>');
            return 1;
        }
        $md5Info = json_decode($md5Response, true);
        $md5 = $md5Info['md5'];

        // Vérifier si le fichier existe déjà et a la même empreinte MD5
        if (optimizeDownload($jarDownloadUrl, $jarFilePath, $md5, 'Purpur')) {
            $progressBar->advance();
            $progressBar->setMessage(sprintf('<success>Purpur: %s</success>', $jarFileName));
            createVersionListFile('Purpur', $version, $md5);

        } else {
            $progressBar->setMessage(sprintf('<error>Purpur: %s</error>', $jarFileName));
        }
    }

    // Récupérer les versions de Sponge depuis le dépôt GitHub
    $repositoryUrl = 'https://api.github.com/repos/SpongePowered/SpongeAPI/tags';
    $options = [
        'http' => [
            'header' => 'User-Agent: PHP',
        ],
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($repositoryUrl, false, $context);
    if ($response === false) {
        $output->writeln('<error>Erreur interne du serveur lors de la récupération des versions de Sponge.</error>');
        return 1;
    }
    $tags = json_decode($response, true);

    // Réinitialiser la barre de progression à 0% pour chaque type de version
    $progressBar->setProgress(0);
    // Parcourir les tags de Sponge et télécharger les fichiers JAR
    foreach ($tags as $tag) {
        $version = $tag['name'];
        $jarDownloadUrl = $tag['zipball_url'];
        $jarFileName = $version;
        $jarFilePath = 'minecraft/sponge/' . $jarFileName;

        // Vérifier si le fichier existe déjà et a la même empreinte MD5
        $md5 = $tag['commit']['sha']; 
        if (optimizeDownload($jarDownloadUrl, $jarFilePath, $md5, 'Sponge')) {
            $progressBar->advance();
            $progressBar->setMessage(sprintf('<success>Sponge: %s</success>', $jarFileName));
            createVersionListFile('Sponge', $version, $md5);

        } else {
            $progressBar->setMessage(sprintf('<error>Sponge: %s</error>', $jarFileName));
        }
    }

// Récupérer les versions de Waterfall depuis l'API PaperMC
$response = @file_get_contents('https://papermc.io/api/v2/projects/waterfall');
if ($response === false) {
    $output->writeln('<error>Erreur interne du serveur lors de la récupération des versions de Velocity.</error>');
    return 1;
}
$versions_data = json_decode($response, true)['versions'];

// Réinitialiser la barre de progression à 0% pour chaque type de version
$progressBar->setProgress(0);

// Parcourir les versions de Waterfall et télécharger les fichiers JAR
foreach ($versions_data as $index => $versionData) {
    $version = $versionData;
    $versionResponse = @file_get_contents("https://papermc.io/api/v2/projects/waterfall/versions/{$version}");
    if ($versionResponse === false) {
        $output->writeln('<error>Erreur interne du serveur lors de la récupération des détails de la version '.$version.' de Waterfall.</error>');
        return 1;
    }
    $versionInfo = json_decode($versionResponse, true);
    $builds = $versionInfo['builds'];
    $buildInfos = json_decode(@file_get_contents("https://papermc.io/api/v2/projects/waterfall/versions/{$version}/builds/{$builds[0]}/"), true);
    $filename = $buildInfos['downloads']['application']['name'];
    $filesha256 = $buildInfos['downloads']['application']['sha256'];
    $jarDownloadUrl = "https://papermc.io/api/v2/projects/waterfall/versions/{$version}/builds/{$builds[0]}/downloads/{$filename}";
    $jarFileName = "{$version}";
    $jarFilePath = 'minecraft/waterfall/' . $jarFileName;

    // Vérifier si le fichier existe déjà et a la même empreinte MD5
    if (optimizeDownload($jarDownloadUrl, $jarFilePath, $filesha256, 'Waterfall')) {
        $progressBar->advance();
        $progressBar->setMessage(sprintf('<success>Waterfall: %s</success>', $jarFileName));
        createVersionListFile('Waterfall', $version, $filesha256);

    } else {
        $progressBar->setMessage(sprintf('<error>Waterfall: %s</error>', $jarFileName));
    }
}


 // Récupérer les versions Snapshot de Minecraft via l'API Mojang
 $response = @file_get_contents('https://launchermeta.mojang.com/mc/game/version_manifest.json');
 if ($response === false) {
     $output->writeln('<error>Erreur interne du serveur lors de la récupération des versions de Minecraft Snapshot.</error>');
     return 1;
 }
 $versions_data = json_decode($response, true);
 $versions = $versions_data['versions'];
// Réinitialiser la barre de progression à 0% pour chaque type de version
 $progressBar->setProgress(0);

 // Parcourir les versions Snapshot et télécharger les fichiers JAR
 foreach ($versions as $index => $versionData) {
     $versionId = $versionData['id'];
     $versionType = $versionData['type'];
     $jarFileName = $versionId;

     $jarFilePath = 'minecraft/snapshot/' . $jarFileName;
     $releaseTime = strtotime($versionData['releaseTime']);
     // Vérifier si la version est une Snapshot
     if ($versionType === 'snapshot') {

         $versionUrl = $versionData['url'];
        
         // Interroger l'URL spécifique de la version pour obtenir plus de détails
         $versionResponse = @file_get_contents($versionUrl);
         if ($versionResponse === false) {
             $output->writeln('<error>Erreur interne du serveur lors de la récupération des détails de la version '.$versionId.'.</error>');
             continue;
         }
         $versionInfo = json_decode($versionResponse, true);
         $downloads = $versionInfo['downloads'];
          // Vérifier si $downloads['server']['url'] existe
          if (!isset($downloads['server']['url'])) {
             continue;
         }
         $jarDownloadUrl = $downloads['server']['url'];

         // Vérifier si le fichier existe déjà et a la même empreinte MD5
         $md5 = $downloads['server']['sha1'];

         if (optimizeDownload($jarDownloadUrl, $jarFilePath, $md5, 'skip')) {
             $progressBar->advance();
             $progressBar->setMessage(sprintf('<success>Snapshot: %s</success>', $jarFileName));
             createVersionListFile('Snapshot', $versionId, $md5);
         } else {
             $progressBar->setMessage(sprintf('<error>Snapshot: %s</error>', $jarFileName));
         }
     } else {
         $progressBar->advance();
     }
 }

    // Fin de la progression
    $progressBar->finish();
    $output->writeln('');

    $output->writeln('<info>Tous les fichiers JAR ont été téléchargés avec succès.</info>');
    reverseJsonFile('minecraft/getlist_new/Paper.json');
    reverseJsonFile('minecraft/getlist_new/Purpur.json');
    reverseJsonFile('minecraft/getlist_new/Waterfall.json');
    reverseJsonFile('minecraft/getlist_new/Velocity.json');
    $sourceDir = 'minecraft/getlist_new/';
    $destinationDir = 'minecraft/getlist/';
    // Parcourir les fichiers du répertoire source
    $files = scandir($sourceDir);
    foreach ($files as $file) {
        // Ignorer les dossiers "." et ".."
        if ($file === '.' || $file === '..') {
            continue;
        }

        // Chemin complet du fichier source
        $sourceFile = $sourceDir . $file;

        // Chemin complet du fichier de destination
        $destinationFile = $destinationDir . $file;

        // Vérifier si le fichier de destination existe déjà
        if (file_exists($destinationFile)) {
            // Supprimer le fichier existant
            unlink($destinationFile);
        }

        // Copier le fichier source vers le dossier de destination
        copy($sourceFile, $destinationFile);
        unlink($sourceFile);

        echo "Le fichier $file a été copié avec succès.\n";
    }

    $output->writeln('<info>Tous les json on été générer et retourner avec success.</info>');

    $downloadEndTime = microtime(true);
    $downloadExecutionTime = $downloadEndTime - $downloadStartTime;

    $output->writeln("<info>Temps d'exécution de la commande download : $downloadExecutionTime secondes et avec $compteur fichiers</info>");

    
    // Exit code 0 pour indiquer une exécution réussie
    return 0;
});

// Ajouter la commande à l'application
$application->add($command);

//Création des dossiers néssesaires
$dossiers = [
    'minecraft',
    'minecraft/bungeecord',
    'minecraft/getlist',
    'minecraft/paper',
    'minecraft/purpur',
    'minecraft/snapshot',
    'minecraft/sponge',
    'minecraft/vanilla',
    'minecraft/velocity',
    'minecraft/waterfall',
    'minecraft/getlist_new'
];
$targetFiles = glob('minecraft/getlist_new' . '/*');
foreach ($targetFiles as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}
foreach ($dossiers as $dossier) {
    if (!file_exists($dossier)) {
        mkdir($dossier);
    } 
}

// Exécuter l'application
$exitCode = $application->run();

// Vérifier le code de sortie et définir le code de statut HTTP en conséquence
if ($exitCode === 0) {
    http_response_code(200); // Succès
} else {
    http_response_code(500); // Erreur interne du serveur
}

// Sortir avec le code de sortie approprié
exit($exitCode);


