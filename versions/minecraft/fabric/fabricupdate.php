<?php
if(is_dir('tmp')) {
    deleteDir('tmp');
}
$requestUri = "https://api.serverjars.com/api/fetchAll/modded/fabric";
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $requestUri);
curl_setopt($ch, CURLOPT_SSH_COMPRESSION, true);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL => $requestUri
]);
$xml = json_decode(curl_exec($ch));
    foreach (array_splice($xml->response, 0, 15) as $version) {
        $requestUri = "https://cdn.bagou450.com/versions/minecraft/fabric/" . $version->version . ".txt";
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
            installfabric($version);
        } else {
            if($result !== $version->md5) {
                installfabric($version);
            }
        }
        if(is_dir('tmp')) {
            deleteDir('tmp');
        }
    }
    exit('Good');
    function installfabric(object $version) {
        if(!mkdir('tmp/' . $version->version, 0777, true)) {
            deleteDir('tmp');
            exit('ERROR CAN T CREATE THE TMP FOLDER OF '  . $version->version);
        }
        $requestUri = "https://api.serverjars.com/api/fetchJar/modded/fabric/" . $version->version;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $requestUri);
        curl_setopt($ch, CURLOPT_SSH_COMPRESSION, true);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $requestUri
        ]);
        $result = curl_exec($ch);
        if(file_put_contents('tmp/' . $version->version . "/server.jar", $result)) {
           exec('cd tmp/' . $version->version . ' && zip ' . $version->version . '.zip server.jar', $output, $return);
            if($return !== 0) {
                deleteDir('tmp');
                exit('ERROR CAN T COMPRESS FABRIC ON '  . $version->version);

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
