<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Test 1: Admin - ventas hoy
echo "=== TEST 1: Admin - Ventas Hoy ===" . PHP_EOL;
$request = \Illuminate\Http\Request::create('/api/jarvis/admin/message', 'POST', ['message' => 'ventas hoy']);
$request->headers->set('Accept', 'application/json');
$response = $kernel->handle($request);
echo $response->getContent() . PHP_EOL . PHP_EOL;

// Test 2: Admin - stock bajo (NOW uses Variante table)
echo "=== TEST 2: Admin - Stock Bajo ===" . PHP_EOL;
$request2 = \Illuminate\Http\Request::create('/api/jarvis/admin/message', 'POST', ['message' => 'stock bajo']);
$request2->headers->set('Accept', 'application/json');
$response2 = $kernel->handle($request2);
echo $response2->getContent() . PHP_EOL . PHP_EOL;

// Test 3: Public - recomendaciones (with prices from Variante)
echo "=== TEST 3: Public - Recomienda Productos ===" . PHP_EOL;
$request3 = \Illuminate\Http\Request::create('/api/jarvis/public/message', 'POST', ['message' => 'que me recomiendas']);
$request3->headers->set('Accept', 'application/json');
$response3 = $kernel->handle($request3);
echo $response3->getContent() . PHP_EOL . PHP_EOL;

// Test 4: Public - greeting
echo "=== TEST 4: Public - Saludo ===" . PHP_EOL;
$request4 = \Illuminate\Http\Request::create('/api/jarvis/public/message', 'POST', ['message' => 'Hola buenos dias']);
$request4->headers->set('Accept', 'application/json');
$response4 = $kernel->handle($request4);
echo $response4->getContent() . PHP_EOL . PHP_EOL;

// Test 5: Admin - ayuda
echo "=== TEST 5: Admin - Ayuda ===" . PHP_EOL;
$request5 = \Illuminate\Http\Request::create('/api/jarvis/admin/message', 'POST', ['message' => 'ayuda']);
$request5->headers->set('Accept', 'application/json');
$response5 = $kernel->handle($request5);
echo $response5->getContent() . PHP_EOL . PHP_EOL;

// Test 6: Admin - diagnostico
echo "=== TEST 6: Admin - Diagnostico ===" . PHP_EOL;
$request6 = \Illuminate\Http\Request::create('/api/jarvis/admin/message', 'POST', ['message' => 'estado del sistema']);
$request6->headers->set('Accept', 'application/json');
$response6 = $kernel->handle($request6);
echo $response6->getContent() . PHP_EOL . PHP_EOL;

echo "=== TODOS LOS TESTS COMPLETADOS ===" . PHP_EOL;
