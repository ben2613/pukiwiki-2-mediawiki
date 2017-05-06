<?php
require_once 'puki2media.php';

$puki_dir = "/var/www/html/pukiwiki";
$mediawiki_dir = "/var/www/html/mediawiki";

function decodeName($name){
    $encoded = '';
    for($i = 0; $i < strlen($name); $i+=2){
        $encoded.='%'.$name[$i].$name[$i+1];
    }
    return urldecode($encoded);
}

function processSingle($absPath){
    global $filePath,$puki_dir, $mediawiki_dir, $convert;
    $path_parts = pathinfo($absPath);
    $name = $path_parts['basename'];
    // filter only the file with name URL encoded format
    preg_match('/^[0-9A-F]*\.txt$/', $name, $matches, PREG_OFFSET_CAPTURE);
    if(sizeof($matches) === 0){
        return false;
    }

    $content = file_get_contents($absPath);

    $content = convert($content);

    $tmpfname = tempnam("/tmp", "FOO");
    $handle = fopen($tmpfname, 'w');
    fwrite($handle, $content);
    fclose($handle);
    //echo $tmpfname;
    $title = str_replace('\'','\\\'',decodeName($path_parts['filename']));
    exec("php $mediawiki_dir/maintenance/edit.php -s 'Migrate from Puki' '$title' < $tmpfname");
    unlink($tmpfname);
}

$oldWikiFiles = scandir($puki_dir.'/wiki');
foreach ($oldWikiFiles as $ofile) {
    $absPath = $puki_dir.'/wiki/'.$ofile;
    processSingle($absPath);
}

?>
