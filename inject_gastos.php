<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// 1. Mover compras recientes al mes actual para que figuren en el Dashboard sin necesidad de cambiar los filtros manuales.
$mesActual = Carbon::now();
$inicioMes = $mesActual->copy()->startOfMonth();

// Tomar la mitad de las compras y ponerlas en este mes (Julio 2026, por ejemplo)
$compras = DB::table('compras')->get();
$count = $compras->count();
$comprasUpdate = $compras->random((int)($count / 2));

foreach ($comprasUpdate as $c) {
    // Fecha aleatoria dentro de este mes
    $fecha = $inicioMes->copy()->addDays(rand(0, 20))->format('Y-m-d H:i:s');
    DB::table('compras')->where('id', $c->id)->update([
        'fecha_compra' => $fecha,
        'created_at' => $fecha,
        'updated_at' => $fecha
    ]);
}

// 2. Limpiar gastos anteriores
DB::table('gastos')->truncate();

// 3. Insertar gastos realistas de un proyecto MVP (Start-up / E-commerce en Peru) en Soles (PEN)
$gastos = [
    [
        'concepto' => 'Hosting en la Nube (AWS/DigitalOcean)',
        'monto' => 350.00,
        'categoria' => 'Infraestructura TI',
        'tipo' => 'fijo'
    ],
    [
        'concepto' => 'Renovación de Dominio Anual (.pe / .com)',
        'monto' => 140.00,
        'categoria' => 'Infraestructura TI',
        'tipo' => 'fijo'
    ],
    [
        'concepto' => 'Desarrollo y Mantenimiento Web (Programadores)',
        'monto' => 4500.00,
        'categoria' => 'Planilla',
        'tipo' => 'fijo'
    ],
    [
        'concepto' => 'Personal de Ventas / Atención al Cliente',
        'monto' => 2800.00,
        'categoria' => 'Planilla',
        'tipo' => 'fijo'
    ],
    [
        'concepto' => 'Campaña Publicitaria Meta Ads (Facebook/Instagram)',
        'monto' => 1500.00,
        'categoria' => 'Marketing',
        'tipo' => 'variable'
    ],
    [
        'concepto' => 'Campaña Google Ads (Búsqueda)',
        'monto' => 1200.00,
        'categoria' => 'Marketing',
        'tipo' => 'variable'
    ],
    [
        'concepto' => 'Suscripciones SaaS (Correo corporativo, CRM, WhatsApp API)',
        'monto' => 450.00,
        'categoria' => 'Software',
        'tipo' => 'fijo'
    ],
    [
        'concepto' => 'Logística y Envíos (Gastos de contingencia)',
        'monto' => 850.00,
        'categoria' => 'Operaciones',
        'tipo' => 'variable'
    ],
    [
        'concepto' => 'Servicios Contables y Legales',
        'monto' => 800.00,
        'categoria' => 'Administrativo',
        'tipo' => 'fijo'
    ]
];

$ahora = $mesActual->format('Y-m-d H:i:s');

foreach ($gastos as $g) {
    DB::table('gastos')->insert([
        'concepto' => $g['concepto'],
        'monto' => $g['monto'],
        'categoria' => $g['categoria'],
        'tipo' => $g['tipo'],
        'fecha_gasto' => $inicioMes->copy()->addDays(rand(1, 15))->format('Y-m-d H:i:s'),
        'created_at' => $ahora,
        'updated_at' => $ahora
    ]);
}

echo "Gastos operativos reales inyectados y compras sincronizadas al mes actual.\n";
