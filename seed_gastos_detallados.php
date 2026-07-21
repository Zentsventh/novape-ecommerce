<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Limpiar la tabla de gastos para inyectar la nueva semilla con más detalle
DB::table('gastos')->truncate();

$mesActual = Carbon::now();
$inicioMes = $mesActual->copy()->startOfMonth();
$ahora = $mesActual->format('Y-m-d H:i:s');

// Inyección de gastos con personal y servicio detallado dentro de cada categoría
$gastos = [
    // Infraestructura TI
    ['concepto' => 'Hosting AWS EC2 (Servicio Nube)', 'monto' => 350.00, 'categoria' => 'Infraestructura TI', 'tipo' => 'fijo'],
    ['concepto' => 'Dominio .pe Anual - Proveedor: PuntoPE', 'monto' => 140.00, 'categoria' => 'Infraestructura TI', 'tipo' => 'fijo'],
    
    // Planilla
    ['concepto' => 'Sueldo Programador Backend - Eduardo Capcha', 'monto' => 3500.00, 'categoria' => 'Planilla', 'tipo' => 'fijo'],
    ['concepto' => 'Sueldo Desarrollador Frontend - Maria Torres', 'monto' => 3500.00, 'categoria' => 'Planilla', 'tipo' => 'fijo'],
    ['concepto' => 'Comisión de Ventas B2B - Juan Perez', 'monto' => 1200.00, 'categoria' => 'Planilla', 'tipo' => 'variable'],
    ['concepto' => 'Atención al Cliente Remoto - Lucia Fernandez', 'monto' => 1500.00, 'categoria' => 'Planilla', 'tipo' => 'fijo'],
    
    // Marketing
    ['concepto' => 'Campaña Meta Ads - Agencia: Marketing Ninja', 'monto' => 1500.00, 'categoria' => 'Marketing', 'tipo' => 'variable'],
    ['concepto' => 'Google Ads SEM - Freelancer: Roberto Sanchez', 'monto' => 1200.00, 'categoria' => 'Marketing', 'tipo' => 'variable'],
    ['concepto' => 'Diseño Gráfico Banners - Diseñadora: Ana Garcia', 'monto' => 600.00, 'categoria' => 'Marketing', 'tipo' => 'variable'],
    
    // Software
    ['concepto' => 'Suscripción CRM HubSpot - Licencia Anual', 'monto' => 450.00, 'categoria' => 'Software', 'tipo' => 'fijo'],
    ['concepto' => 'Google Workspace 5 Usuarios (Correos) - Google', 'monto' => 180.00, 'categoria' => 'Software', 'tipo' => 'fijo'],
    
    // Operaciones
    ['concepto' => 'Envío Courier Olva - Servicio Express', 'monto' => 450.00, 'categoria' => 'Operaciones', 'tipo' => 'variable'],
    ['concepto' => 'Motorizado Pedidos Ya - Reparto Local', 'monto' => 400.00, 'categoria' => 'Operaciones', 'tipo' => 'variable'],
    
    // Administrativo y Operativo
    ['concepto' => 'Asesoría Contable Externa - Estudio ABC SAC', 'monto' => 800.00, 'categoria' => 'Administrativo', 'tipo' => 'fijo'],
    ['concepto' => 'Servicio de Limpieza Oficina - CleanSoft SAC', 'monto' => 300.00, 'categoria' => 'Administrativo', 'tipo' => 'fijo'],
    ['concepto' => 'Alquiler de Oficina Surco - Inmobiliaria R.', 'monto' => 2000.00, 'categoria' => 'Operativo', 'tipo' => 'fijo'],
    ['concepto' => 'Pago de Recibo de Luz Oficina - Enel', 'monto' => 250.00, 'categoria' => 'Operativo', 'tipo' => 'variable']
];

foreach ($gastos as $g) {
    DB::table('gastos')->insert([
        'concepto' => $g['concepto'],
        'monto' => $g['monto'],
        'categoria' => $g['categoria'],
        'tipo' => $g['tipo'],
        'fecha_gasto' => $inicioMes->copy()->addDays(rand(1, 20))->format('Y-m-d H:i:s'),
        'created_at' => $ahora,
        'updated_at' => $ahora
    ]);
}
echo "Gastos detallados re-inyectados exitosamente.\n";
