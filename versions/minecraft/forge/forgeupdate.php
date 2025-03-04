<?php
if(is_dir('tmp')) {
    deleteDir('tmp');
}
$requestUri = "https://api.serverjars.com/api/fetchAll/modded/forge";
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $requestUri);
curl_setopt($ch, CURLOPT_SSH_COMPRESSION, true);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL => $requestUri
]);
$xml = json_decode(curl_exec($ch));
    foreach (array_splice($xml->response, 0, 10) as $version) {
        $requestUri = "https://cdn.bagou450.com/versions/minecraft/forge/" . $version->version . ".txt";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $requestUri);
        curl_setopt($ch, CURLOPT_SSH_COMPRESSION, true);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $requestUri
        ]);
        $result = curl_exec($ch);

        curl_close($ch);
        if(curl_getinfo($ch, CURLINFO_HTTP_CODE) === 404) {
            installforge($version);
        } else {
            if($result !== $version->md5) {
                installforge($version);
            }
        }

    }
    exit('Good');
    function installforge(object $version) {
        if(!mkdir('tmp/' . $version->version, 0777, true)) {
            deleteDir('tmp');
            exit('ERROR CAN T CREATE THE TMP FOLDER OF '  . $version->version);
        }
        $requestUri = "https://api.serverjars.com/api/fetchJar/modded/forge/" . $version->version;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $requestUri);
        curl_setopt($ch, CURLOPT_SSH_COMPRESSION, true);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $requestUri
        ]);
        $result = curl_exec($ch);
        if(file_put_contents('tmp/' . $version->version . "/forge.jar", $result)) {
          
            exec('cd tmp/' . $version->version . ' && ../../jdk/bin/java -jar forge.jar --installServer', $output, $return);
            if($return !== 0) {
                deleteDir('tmp');
                exit('ERROR CAN T INSTALL FORGE ON '  . $version->version);

            }
            if(version_compare($version->version, '1.20.3', '<')) {
                $file = scandir('tmp/' . $version->version.'/libraries/net/minecraftforge/forge/', 1);
                if(count($file) < 3 ) {
                    deleteDir('tmp');
                    exit('ERROR CAN T GET DIR OF ' . $version->version);
                }
            if(!copy('tmp/' . $version->version. '/libraries/net/minecraftforge/forge/' . $file[0] . '/unix_args.txt', 'tmp/' . $version->version . '/unix_args.txt')) {
                deleteDir('tmp');
                exit('ERROR CAN T COPY FILE OF ' . $version->version);
            }
            } else {
                $matchingFiles = glob('tmp/' . $version->version . '/forge*shim.jar');
                if ($matchingFiles) rename($matchingFiles[0], 'tmp/' . $version->version . '/server.jar');
             
            }
           
            
           
            unlink('tmp/' . $version->version . "/forge.jar");
            exec('cd tmp/' . $version->version . ' && zip -r ' . $version->version . '.zip *', $output, $return);
            if($return !== 0) {
                deleteDir('tmp');
                exit('ERROR CAN T COMPRESS FORGE ON '  . $version->version);

            }
            rename('tmp/' . $version->version . '/' . $version->version . '.zip', './' . $version->version . '.zip');
            file_put_contents('./' . $version->version . '.txt', $version->md5);
            echo 'Good for ' . $version->version;
        } else {

            exit('ERROR');
        }

    }
    function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}


?>
