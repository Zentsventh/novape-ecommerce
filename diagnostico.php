<?php
/**
 * DIAGNÓSTICO Y REPARACIÓN 360° - NovaPe
 * 
 * Sube este archivo a public_html y accede desde el navegador:
 * https://novape.me/diagnostico.php?key=novape360
 * 
 * ¡IMPORTANTE! Borra este archivo de cPanel cuando termines.
 */

$secretKey = 'novape360';
if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    die('Unauthorized');
}

header('Content-Type: text/html; charset=utf-8');
echo '<html><head><title>Diagnóstico NovaPe 360°</title>';
echo '<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;max-width:900px;margin:auto}';
echo '.ok{color:#00ff88}.err{color:#ff4444}.warn{color:#ffaa00}.section{background:#16213e;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #0f3460}';
echo 'h1{color:#00d4ff}h2{color:#0f3460;background:#00d4ff;padding:8px 12px;border-radius:4px;display:inline-block}pre{background:#0a0a1a;padding:10px;border-radius:4px;overflow-x:auto}</style></head><body>';
echo '<h1>🔍 Diagnóstico NovaPe 360°</h1>';
echo '<p>Fecha: ' . date('Y-m-d H:i:s') . '</p>';

// ============================================================
// 1. ESTRUCTURA DE ARCHIVOS
// ============================================================
echo '<div class="section"><h2>📁 1. Estructura de Archivos</h2><br><br>';

$basePath = dirname(__DIR__);
$checks = [
    'novape/'                    => $basePath . '/novape',
    'novape/.env'                => $basePath . '/novape/.env',
    'novape/vendor/'             => $basePath . '/novape/vendor',
    'novape/bootstrap/app.php'   => $basePath . '/novape/bootstrap/app.php',
    'novape/artisan'             => $basePath . '/novape/artisan',
    'novape/storage/'            => $basePath . '/novape/storage',
    'novape/storage/logs/'       => $basePath . '/novape/storage/logs',
    'novape/storage/framework/'  => $basePath . '/novape/storage/framework',
    'novape/storage/framework/views/'    => $basePath . '/novape/storage/framework/views',
    'novape/storage/framework/cache/'    => $basePath . '/novape/storage/framework/cache',
    'novape/storage/framework/sessions/' => $basePath . '/novape/storage/framework/sessions',
    'public_html/index.php'      => __DIR__ . '/index.php',
    'public_html/build/'         => __DIR__ . '/build',
    'public_html/build/manifest.json' => __DIR__ . '/build/manifest.json',
    'public_html/.htaccess'      => __DIR__ . '/.htaccess',
    'public_html/storage/'       => __DIR__ . '/storage',
];

foreach ($checks as $label => $path) {
    $exists = file_exists($path);
    $icon = $exists ? '<span class="ok">✅</span>' : '<span class="err">❌ FALTA</span>';
    $extra = '';
    if ($exists && is_file($path)) {
        $extra = ' (' . number_format(filesize($path)) . ' bytes)';
    }
    if ($exists && is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path) ? '<span class="ok">writable</span>' : '<span class="err">NO writable</span>';
        $extra = " [perms: $perms, $writable]";
    }
    echo "$icon $label $extra<br>";
}
echo '</div>';

// ============================================================
// 2. CONEXIÓN A BASE DE DATOS
// ============================================================
echo '<div class="section"><h2>🗄️ 2. Base de Datos</h2><br><br>';

$envPath = $basePath . '/novape/.env';
$dbConfig = [];
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    preg_match('/DB_HOST=(.+)/', $envContent, $m); $dbConfig['host'] = trim($m[1] ?? '');
    preg_match('/DB_DATABASE=(.+)/', $envContent, $m); $dbConfig['database'] = trim($m[1] ?? '');
    preg_match('/DB_USERNAME=(.+)/', $envContent, $m); $dbConfig['username'] = trim($m[1] ?? '');
    preg_match('/DB_PASSWORD=(.+)/', $envContent, $m); $dbConfig['password'] = trim($m[1] ?? '');
    preg_match('/DB_PORT=(.+)/', $envContent, $m); $dbConfig['port'] = trim($m[1] ?? '3306');
    
    echo "Host: {$dbConfig['host']}<br>";
    echo "Database: {$dbConfig['database']}<br>";
    echo "Username: {$dbConfig['username']}<br>";
    echo "Port: {$dbConfig['port']}<br>";
    
    try {
        $pdo = new PDO(
            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}",
            $dbConfig['username'],
            $dbConfig['password']
        );
        echo '<span class="ok">✅ Conexión a BD exitosa</span><br>';
        
        // Contar tablas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tablas encontradas: " . count($tables) . "<br>";
        
        // Verificar tabla de usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuario");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "Usuarios en BD: $count<br>";
        
        // Buscar admin
        $stmt = $pdo->prepare("SELECT id, nombres, email, rol, estado, password_hash FROM usuario WHERE email = ?");
        $stmt->execute(['admin@novape.com']);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo '<span class="ok">✅ Admin encontrado:</span><br>';
            echo "&nbsp;&nbsp;ID: {$admin['id']}<br>";
            echo "&nbsp;&nbsp;Nombre: {$admin['nombres']}<br>";
            echo "&nbsp;&nbsp;Email: {$admin['email']}<br>";
            echo "&nbsp;&nbsp;Rol: {$admin['rol']}<br>";
            echo "&nbsp;&nbsp;Estado: {$admin['estado']}<br>";
            $hashLen = strlen($admin['password_hash']);
            echo "&nbsp;&nbsp;Password hash length: $hashLen<br>";
            
            // Verificar si las contraseñas conocidas funcionan
            $passwords = ['12345678', 'Novape2026!', 'password123'];
            $anyMatch = false;
            foreach ($passwords as $pwd) {
                if (password_verify($pwd, $admin['password_hash'])) {
                    echo "<span class='ok'>✅ La contraseña '$pwd' SÍ coincide con el hash</span><br>";
                    $anyMatch = true;
                } else {
                    echo "<span class='err'>❌ La contraseña '$pwd' NO coincide</span><br>";
                }
            }
            
            if (!$anyMatch) {
                echo '<br><span class="warn">⚠️ Ninguna contraseña conocida funciona. Reseteando a 12345678...</span><br>';
                $newHash = password_hash('12345678', PASSWORD_BCRYPT);
                $update = $pdo->prepare("UPDATE usuario SET password_hash = ?, estado = 'activo' WHERE email = ?");
                $update->execute([$newHash, 'admin@novape.com']);
                echo '<span class="ok">✅ ¡Contraseña reseteada! Ahora usa: admin@novape.com / 12345678</span><br>';
            }
        } else {
            echo '<span class="err">❌ No existe el usuario admin@novape.com</span><br>';
            echo '<span class="warn">Creando usuario admin...</span><br>';
            $newHash = password_hash('12345678', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO usuario (nombres, apellidos, email, password_hash, rol, estado, tipo_documento, dni, telefono, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute(['Eduardo', 'Capcha', 'admin@novape.com', $newHash, 'admin', 'activo', 'DNI', '12345678', '987654321']);
            echo '<span class="ok">✅ Admin creado. Usa: admin@novape.com / 12345678</span><br>';
        }
        
    } catch (PDOException $e) {
        echo '<span class="err">❌ Error de conexión: ' . $e->getMessage() . '</span><br>';
    }
} else {
    echo '<span class="err">❌ No se encontró el archivo .env en novape/</span><br>';
}
echo '</div>';

// ============================================================
// 3. CONFIGURACIÓN DEL .ENV
// ============================================================
echo '<div class="section"><h2>⚙️ 3. Variables de Entorno (.env)</h2><br><br>';
if (file_exists($envPath)) {
    preg_match('/APP_ENV=(.+)/', $envContent, $m); echo "APP_ENV: " . trim($m[1] ?? 'NO DEFINIDO') . "<br>";
    preg_match('/APP_DEBUG=(.+)/', $envContent, $m); echo "APP_DEBUG: " . trim($m[1] ?? 'NO DEFINIDO') . "<br>";
    preg_match('/APP_URL=(.+)/', $envContent, $m); echo "APP_URL: " . trim($m[1] ?? 'NO DEFINIDO') . "<br>";
    preg_match('/APP_KEY=(.+)/', $envContent, $m); 
    $key = trim($m[1] ?? '');
    echo "APP_KEY: " . ($key ? '<span class="ok">✅ Definida (' . strlen($key) . ' chars)</span>' : '<span class="err">❌ FALTA</span>') . "<br>";
    preg_match('/MAIL_MAILER=(.+)/', $envContent, $m); echo "MAIL_MAILER: " . trim($m[1] ?? 'NO DEFINIDO') . "<br>";
    preg_match('/SESSION_DRIVER=(.+)/', $envContent, $m); echo "SESSION_DRIVER: " . trim($m[1] ?? 'NO DEFINIDO') . "<br>";
    preg_match('/CACHE_STORE=(.+)/', $envContent, $m); echo "CACHE_STORE: " . trim($m[1] ?? 'NO DEFINIDO') . "<br>";
} else {
    echo '<span class="err">❌ Archivo .env no encontrado</span><br>';
}
echo '</div>';

// ============================================================
// 4. PERMISOS DE STORAGE
// ============================================================
echo '<div class="section"><h2>🔒 4. Permisos de Storage</h2><br><br>';
$storageDirs = [
    $basePath . '/novape/storage',
    $basePath . '/novape/storage/app',
    $basePath . '/novape/storage/app/public',
    $basePath . '/novape/storage/framework',
    $basePath . '/novape/storage/framework/cache',
    $basePath . '/novape/storage/framework/cache/data',
    $basePath . '/novape/storage/framework/sessions',
    $basePath . '/novape/storage/framework/views',
    $basePath . '/novape/storage/logs',
    $basePath . '/novape/bootstrap/cache',
];

foreach ($storageDirs as $dir) {
    $label = str_replace($basePath . '/', '', $dir);
    if (!is_dir($dir)) {
        echo "<span class='warn'>⚠️ $label - NO EXISTE, creando...</span><br>";
        mkdir($dir, 0755, true);
        if (is_dir($dir)) {
            echo "<span class='ok'>✅ $label - Creada exitosamente</span><br>";
        }
    } else {
        $writable = is_writable($dir);
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        if ($writable) {
            echo "<span class='ok'>✅ $label [$perms, writable]</span><br>";
        } else {
            echo "<span class='err'>❌ $label [$perms, NO writable] - Intentando arreglar...</span><br>";
            chmod($dir, 0755);
            echo is_writable($dir) ? "<span class='ok'>✅ Arreglado</span><br>" : "<span class='err'>❌ No se pudo arreglar. Hazlo manual en cPanel</span><br>";
        }
    }
}
echo '</div>';

// ============================================================
// 5. SYMLINK DE STORAGE
// ============================================================
echo '<div class="section"><h2>🔗 5. Symlink de Storage</h2><br><br>';
$symlinkTarget = __DIR__ . '/storage';
$storagePublic = $basePath . '/novape/storage/app/public';

if (is_link($symlinkTarget)) {
    $linkDest = readlink($symlinkTarget);
    echo "Symlink existente: $symlinkTarget -> $linkDest<br>";
    if (file_exists($symlinkTarget)) {
        echo '<span class="ok">✅ Symlink funcional</span><br>';
    } else {
        echo '<span class="err">❌ Symlink roto (destino no existe)</span><br>';
    }
} elseif (is_dir($symlinkTarget)) {
    echo '<span class="warn">⚠️ storage/ es un directorio real, no un symlink</span><br>';
} else {
    echo '<span class="warn">⚠️ No existe symlink de storage. Intentando crear...</span><br>';
    if (is_dir($storagePublic)) {
        $result = @symlink($storagePublic, $symlinkTarget);
        echo $result ? '<span class="ok">✅ Symlink creado exitosamente</span><br>' : '<span class="err">❌ No se pudo crear symlink (restricción de cPanel). Crear manualmente.</span><br>';
    } else {
        echo '<span class="err">❌ La carpeta origen no existe: ' . $storagePublic . '</span><br>';
    }
}
echo '</div>';

// ============================================================
// 6. INDEX.PHP - VERIFICACIÓN
// ============================================================
echo '<div class="section"><h2>📄 6. index.php</h2><br><br>';
$indexContent = file_get_contents(__DIR__ . '/index.php');
$hasNovapePath = strpos($indexContent, 'novape/bootstrap/app.php') !== false;
$hasPublicPath = strpos($indexContent, 'usePublicPath') !== false;

echo $hasNovapePath ? '<span class="ok">✅ Rutas dinámicas para cPanel configuradas</span><br>' : '<span class="err">❌ index.php NO tiene rutas dinámicas para cPanel</span><br>';
echo $hasPublicPath ? '<span class="ok">✅ usePublicPath configurado para Vite</span><br>' : '<span class="err">❌ usePublicPath NO configurado (Vite no encontrará el manifest)</span><br>';
echo '<pre>' . htmlspecialchars($indexContent) . '</pre>';
echo '</div>';

// ============================================================
// 7. BUILD/MANIFEST - VITE
// ============================================================
echo '<div class="section"><h2>🎨 7. Vite Build (CSS/JS)</h2><br><br>';
$manifestPath = __DIR__ . '/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    echo '<span class="ok">✅ manifest.json encontrado</span><br>';
    echo "Archivos compilados: " . count($manifest) . "<br>";
    foreach ($manifest as $key => $value) {
        $file = $value['file'] ?? 'N/A';
        $filePath = __DIR__ . '/build/' . $file;
        $exists = file_exists($filePath);
        $icon = $exists ? '<span class="ok">✅</span>' : '<span class="err">❌</span>';
        echo "$icon $key -> build/$file<br>";
    }
} else {
    echo '<span class="err">❌ manifest.json NO encontrado. Los estilos y scripts NO cargarán.</span><br>';
    echo 'Verificando si existe la carpeta build/...<br>';
    if (is_dir(__DIR__ . '/build')) {
        $buildFiles = scandir(__DIR__ . '/build');
        echo "Archivos en build/: " . implode(', ', array_diff($buildFiles, ['.', '..'])) . "<br>";
    } else {
        echo '<span class="err">❌ La carpeta build/ NO existe en public_html</span><br>';
    }
}
echo '</div>';

// ============================================================
// 8. PHP INFO RESUMIDO
// ============================================================
echo '<div class="section"><h2>🖥️ 8. Servidor</h2><br><br>';
echo "PHP: " . phpversion() . "<br>";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "s<br>";
echo '</div>';

echo '<br><p><span class="warn">⚠️ IMPORTANTE: Borra este archivo (diagnostico.php) de public_html cuando termines la revisión.</span></p>';
echo '</body></html>';
