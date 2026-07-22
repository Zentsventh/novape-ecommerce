<?php
/**
 * DEPLOYER PROFESIONAL - NovaPe Ecommerce
 * 
 * Recibe un archivo ZIP desde GitHub Actions y lo extrae
 * en las carpetas correctas del hosting (novape/ y public_html/).
 * 
 * Protecciones:
 * - Clave secreta obligatoria
 * - No sobreescribe .htaccess (configuración de cPanel)
 * - No sobreescribe .env (credenciales de la base de datos)
 * - No sobreescribe storage/ (archivos subidos por los usuarios)
 * - Validación de integridad del ZIP
 * - Log de cada despliegue
 */

// ============ CONFIGURACIÓN ============
$secretKey = 'novape_super_secreto_2026';
$logFile   = dirname(__DIR__) . '/novape/storage/logs/deploy.log';

// ============ SEGURIDAD ============
header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

if (!isset($_GET['key']) || !hash_equals($secretKey, $_GET['key'])) {
    http_response_code(403);
    die('Unauthorized');
}

// ============ RECIBIR ARCHIVO ============
if (!isset($_FILES['deploy_file']) || $_FILES['deploy_file']['error'] !== UPLOAD_ERR_OK) {
    $errorCode = $_FILES['deploy_file']['error'] ?? 'NO_FILE';
    http_response_code(400);
    die('Upload error: ' . $errorCode);
}

$uploadPath = __DIR__ . '/deploy_temp_' . time() . '.zip';
if (!move_uploaded_file($_FILES['deploy_file']['tmp_name'], $uploadPath)) {
    http_response_code(500);
    die('Failed to save uploaded file');
}

// ============ ARCHIVOS PROTEGIDOS (NO SOBREESCRIBIR) ============
$protectedFiles = [
    'public_html/.htaccess',    // Configuración de cPanel + Laravel
    'public_html/.user.ini',    // Configuración PHP de cPanel
    'public_html/deployer.php', // Este mismo archivo
    'novape/.env',              // Credenciales de base de datos
];

$protectedPrefixes = [
    'novape/storage/app/',      // Archivos subidos por usuarios
    'novape/storage/logs/',     // Logs del sistema
];

// ============ EXTRAER CON PROTECCIÓN ============
$zip = new ZipArchive;
$result = $zip->open($uploadPath);

if ($result !== TRUE) {
    unlink($uploadPath);
    http_response_code(500);
    die('ZIP corrupted or invalid. Error code: ' . $result);
}

$extractPath = dirname(__DIR__);
$extracted = 0;
$skipped = 0;
$errors = 0;

for ($i = 0; $i < $zip->numFiles; $i++) {
    $fileName = $zip->getNameIndex($i);
    
    // Saltar directorios (se crearán automáticamente)
    if (substr($fileName, -1) === '/') {
        $dirPath = $extractPath . '/' . $fileName;
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
        continue;
    }
    
    // Verificar si es un archivo protegido
    $isProtected = false;
    foreach ($protectedFiles as $protected) {
        if ($fileName === $protected) {
            $isProtected = true;
            break;
        }
    }
    if (!$isProtected) {
        foreach ($protectedPrefixes as $prefix) {
            if (strpos($fileName, $prefix) === 0) {
                $isProtected = true;
                break;
            }
        }
    }
    
    if ($isProtected) {
        $skipped++;
        continue;
    }
    
    // Extraer el archivo individual
    $targetPath = $extractPath . '/' . $fileName;
    $targetDir = dirname($targetPath);
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $content = $zip->getFromIndex($i);
    if ($content !== false) {
        file_put_contents($targetPath, $content);
        $extracted++;
    } else {
        $errors++;
    }
}

$zip->close();
unlink($uploadPath);

// ============ LOG DEL DESPLIEGUE ============
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logEntry = sprintf(
    "[%s] DEPLOY OK | Extracted: %d | Skipped (protected): %d | Errors: %d | IP: %s\n",
    date('Y-m-d H:i:s'),
    $extracted,
    $skipped,
    $errors,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
);
file_put_contents($logFile, $logEntry, FILE_APPEND);

// ============ RESPUESTA ============
echo "DEPLOY_SUCCESS|extracted={$extracted}|skipped={$skipped}|errors={$errors}";
