<?php
/**
 * REPARADOR COMPLETO - NovaPe
 * 
 * Sube este archivo a public_html y accede desde el navegador:
 * https://novape.me/reparar.php?key=novape360
 * 
 * ¡BORRA este archivo cuando termine!
 */

$secretKey = 'novape360';
if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    die('Unauthorized');
}

header('Content-Type: text/html; charset=utf-8');
echo '<html><head><title>Reparador NovaPe</title>';
echo '<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;max-width:900px;margin:auto}';
echo '.ok{color:#00ff88}.err{color:#ff4444}.warn{color:#ffaa00}.section{background:#16213e;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #0f3460}';
echo 'h1{color:#00ff88}h2{color:#0f3460;background:#00d4ff;padding:8px 12px;border-radius:4px;display:inline-block}</style></head><body>';
echo '<h1>🔧 Reparador NovaPe</h1>';

$basePath = dirname(__DIR__);
$envPath = $basePath . '/novape/.env';

// ============================================================
// REPARACIÓN 1: Corregir .env de producción
// ============================================================
echo '<div class="section"><h2>⚙️ 1. Corregir .env</h2><br><br>';

if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    $original = $envContent;
    
    // APP_ENV -> production
    $envContent = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=production', $envContent);
    echo '<span class="ok">✅ APP_ENV=production</span><br>';
    
    // APP_DEBUG -> false
    $envContent = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=false', $envContent);
    echo '<span class="ok">✅ APP_DEBUG=false (ya no mostrará errores internos a los usuarios)</span><br>';
    
    // APP_URL -> https://novape.me
    $envContent = preg_replace('/^APP_URL=.*/m', 'APP_URL=https://novape.me', $envContent);
    echo '<span class="ok">✅ APP_URL=https://novape.me</span><br>';
    
    // SESSION_DRIVER -> file (más confiable que database para cPanel)
    $envContent = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=file', $envContent);
    echo '<span class="ok">✅ SESSION_DRIVER=file (más estable en cPanel)</span><br>';
    
    // CACHE_STORE -> file
    $envContent = preg_replace('/^CACHE_STORE=.*/m', 'CACHE_STORE=file', $envContent);
    echo '<span class="ok">✅ CACHE_STORE=file</span><br>';
    
    if ($envContent !== $original) {
        file_put_contents($envPath, $envContent);
        echo '<br><span class="ok">✅ Archivo .env actualizado correctamente</span><br>';
    }
} else {
    echo '<span class="err">❌ .env no encontrado</span><br>';
}
echo '</div>';

// ============================================================
// REPARACIÓN 2: Resetear contraseña del admin
// ============================================================
echo '<div class="section"><h2>🔑 2. Resetear contraseña admin</h2><br><br>';

if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    preg_match('/DB_HOST=(.+)/', $envContent, $m); $dbHost = trim($m[1] ?? '127.0.0.1');
    preg_match('/DB_DATABASE=(.+)/', $envContent, $m); $dbName = trim($m[1] ?? '');
    preg_match('/DB_USERNAME=(.+)/', $envContent, $m); $dbUser = trim($m[1] ?? '');
    preg_match('/DB_PASSWORD=(.+)/', $envContent, $m); $dbPass = trim($m[1] ?? '');
    preg_match('/DB_PORT=(.+)/', $envContent, $m); $dbPort = trim($m[1] ?? '3306');
    
    try {
        $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName}", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Primero, ver qué columnas tiene la tabla usuario
        $cols = $pdo->query("DESCRIBE usuario")->fetchAll(PDO::FETCH_COLUMN);
        echo "Columnas de la tabla usuario: " . implode(', ', $cols) . "<br><br>";
        
        // Buscar admin por email
        $stmt = $pdo->prepare("SELECT id, nombres, email, estado, password_hash FROM usuario WHERE email = ?");
        $stmt->execute(['admin@novape.com']);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "Admin encontrado: ID={$admin['id']}, {$admin['nombres']}, estado={$admin['estado']}<br>";
            
            // Resetear contraseña
            $newHash = password_hash('12345678', PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE usuario SET password_hash = ?, estado = 'activo' WHERE email = ?")
                ->execute([$newHash, 'admin@novape.com']);
            echo '<span class="ok">✅ Contraseña reseteada a: 12345678</span><br>';
            
            // Verificar que el usuario tiene el rol admin en la tabla pivote
            $stmt = $pdo->prepare("SELECT r.id, r.nombre FROM rol r INNER JOIN usuario_rol ur ON r.id = ur.rol_id WHERE ur.usuario_id = ?");
            $stmt->execute([$admin['id']]);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($roles)) {
                echo '<span class="warn">⚠️ El admin NO tiene roles asignados. Asignando rol admin...</span><br>';
                
                // Buscar el rol admin
                $stmt = $pdo->query("SELECT id FROM rol WHERE nombre = 'admin'");
                $adminRol = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($adminRol) {
                    $pdo->prepare("INSERT IGNORE INTO usuario_rol (usuario_id, rol_id) VALUES (?, ?)")
                        ->execute([$admin['id'], $adminRol['id']]);
                    echo '<span class="ok">✅ Rol admin asignado correctamente</span><br>';
                } else {
                    echo '<span class="err">❌ No existe el rol "admin" en la tabla rol</span><br>';
                    echo 'Roles existentes:<br>';
                    $allRoles = $pdo->query("SELECT * FROM rol")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($allRoles as $r) {
                        echo "&nbsp;&nbsp;- ID: {$r['id']}, Nombre: {$r['nombre']}<br>";
                    }
                }
            } else {
                echo 'Roles del admin: ';
                foreach ($roles as $r) {
                    echo "{$r['nombre']} (ID: {$r['id']}), ";
                }
                echo '<br>';
            }
            
            // Verificar permisos del rol admin
            $stmt = $pdo->prepare("SELECT p.nombre FROM permiso p INNER JOIN rol_permiso rp ON p.id = rp.permiso_id INNER JOIN rol r ON r.id = rp.rol_id WHERE r.nombre = 'admin' LIMIT 5");
            $stmt->execute();
            $permisos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($permisos)) {
                echo 'Permisos del rol admin (primeros 5): ' . implode(', ', $permisos) . '<br>';
            } else {
                echo '<span class="warn">⚠️ El rol admin no tiene permisos asignados</span><br>';
            }
            
        } else {
            echo '<span class="err">❌ No existe admin@novape.com. Creando...</span><br>';
            $newHash = password_hash('12345678', PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO usuario (nombres, apellidos, email, password_hash, estado, tipo_documento, dni, telefono, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())")
                ->execute(['Eduardo', 'Capcha', 'admin@novape.com', $newHash, 'activo', 'DNI', '12345678', '987654321']);
            
            $newAdminId = $pdo->lastInsertId();
            echo '<span class="ok">✅ Admin creado con ID: ' . $newAdminId . '</span><br>';
            
            // Asignar rol admin
            $stmt = $pdo->query("SELECT id FROM rol WHERE nombre = 'admin'");
            $adminRol = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($adminRol) {
                $pdo->prepare("INSERT INTO usuario_rol (usuario_id, rol_id) VALUES (?, ?)")
                    ->execute([$newAdminId, $adminRol['id']]);
                echo '<span class="ok">✅ Rol admin asignado</span><br>';
            }
        }
        
        echo '<br><span class="ok" style="font-size:18px">🔑 Credenciales: admin@novape.com / 12345678</span><br>';
        
    } catch (PDOException $e) {
        echo '<span class="err">❌ Error: ' . $e->getMessage() . '</span><br>';
    }
} else {
    echo '<span class="err">❌ .env no encontrado</span><br>';
}
echo '</div>';

// ============================================================
// REPARACIÓN 3: Limpiar caché de Laravel
// ============================================================
echo '<div class="section"><h2>🧹 3. Limpiar caché</h2><br><br>';

$cacheDirs = [
    $basePath . '/novape/bootstrap/cache/config.php',
    $basePath . '/novape/bootstrap/cache/routes-v7.php',
    $basePath . '/novape/bootstrap/cache/events.php',
];

foreach ($cacheDirs as $cacheFile) {
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
        $name = basename($cacheFile);
        echo "<span class='ok'>✅ Cache eliminada: $name</span><br>";
    }
}

// Limpiar vistas compiladas
$viewsPath = $basePath . '/novape/storage/framework/views';
if (is_dir($viewsPath)) {
    $files = glob($viewsPath . '/*.php');
    $count = count($files);
    foreach ($files as $file) {
        unlink($file);
    }
    echo "<span class='ok'>✅ $count vistas compiladas eliminadas</span><br>";
}

echo '<span class="ok">✅ Caché limpiada. Laravel regenerará todo automáticamente.</span><br>';
echo '</div>';

// ============================================================
// REPARACIÓN 4: Verificar tablas de sesiones y cache
// ============================================================
echo '<div class="section"><h2>📊 4. Verificar tablas necesarias</h2><br><br>';
try {
    // Verificar tabla sessions
    $result = $pdo->query("SHOW TABLES LIKE 'sessions'")->fetch();
    echo $result ? '<span class="ok">✅ Tabla sessions existe</span><br>' : '<span class="warn">⚠️ Tabla sessions no existe (no importa si usamos file driver)</span><br>';
    
    // Verificar tabla cache
    $result = $pdo->query("SHOW TABLES LIKE 'cache'")->fetch();
    echo $result ? '<span class="ok">✅ Tabla cache existe</span><br>' : '<span class="warn">⚠️ Tabla cache no existe (no importa si usamos file driver)</span><br>';
    
    // Contar registros importantes
    $tables = ['usuario', 'producto', 'categoria', 'pedido', 'marca', 'rol', 'permiso'];
    echo '<br>Resumen de datos:<br>';
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "&nbsp;&nbsp;$table: $count registros<br>";
        } catch (PDOException $e) {
            echo "&nbsp;&nbsp;<span class='err'>$table: ERROR - " . $e->getMessage() . "</span><br>";
        }
    }
} catch (PDOException $e) {
    echo '<span class="err">❌ ' . $e->getMessage() . '</span><br>';
}
echo '</div>';

echo '<br><h1 style="color:#00ff88">✅ ¡Reparación completada!</h1>';
echo '<p>Ahora intenta iniciar sesión en <a href="https://novape.me/admin/login" style="color:#00d4ff">novape.me/admin/login</a> con:</p>';
echo '<p style="font-size:20px;color:#00ff88">📧 admin@novape.com &nbsp;&nbsp; 🔑 12345678</p>';
echo '<br><p><span class="warn">⚠️ BORRA este archivo (reparar.php) y diagnostico.php de public_html cuando termines.</span></p>';
echo '</body></html>';
