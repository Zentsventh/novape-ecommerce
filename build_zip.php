<?php
ini_set('memory_limit', '512M');
set_time_limit(300); // 5 minutes

$zipName = 'novape_produccion.zip';
if (file_exists($zipName)) {
    unlink($zipName);
}

$zip = new ZipArchive();
if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("No se pudo crear el archivo ZIP\n");
}

echo "Creando archivo ZIP (excluyendo archivos innecesarios)...\n";

$source = realpath(__DIR__);
$iterator = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);

$excludedDirs = [
    DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR,
];
$excludedFiles = [
    'novape_produccion.zip',
    'build_zip.php',
];

$count = 0;
foreach ($files as $file) {
    $path = $file->getRealPath();
    
    // Check exclusions
    $exclude = false;
    foreach ($excludedDirs as $dir) {
        if (strpos($path, $dir) !== false) {
            $exclude = true;
            break;
        }
    }
    
    if (!$exclude) {
        $filename = $file->getFilename();
        if (in_array($filename, $excludedFiles)) {
            $exclude = true;
        }
    }
    
    if ($exclude) {
        continue;
    }

    $relativePath = substr($path, strlen($source) + 1);
    
    if ($file->isDir()) {
        $zip->addEmptyDir($relativePath);
    } else if ($file->isFile()) {
        $zip->addFile($path, $relativePath);
        $count++;
    }
}

$zip->close();
echo "¡ZIP creado con éxito! Se comprimieron $count archivos.\n";
echo "Archivo guardado como: $zipName\n";
?>
