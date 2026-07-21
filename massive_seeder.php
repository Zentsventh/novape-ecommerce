<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

// Limpiar datos
DB::table('compras')->truncate();
DB::table('compra_items')->truncate();
DB::table('movimientos_almacen')->truncate();
DB::table('stock_almacen')->truncate();
DB::table('variante')->truncate();
DB::table('producto_especificaciones')->truncate();
DB::table('producto_categoria')->truncate();
DB::table('producto_imagen')->truncate();
DB::table('producto')->truncate();
DB::table('marca')->truncate();
DB::table('categoria')->truncate();
DB::table('proveedor')->truncate();

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

// Funciones Auxiliares
function crearCompra($provId, $fecha, $items) {
    if (empty($items)) return;
    $total = 0;
    foreach($items as $item) $total += ($item['costo'] * $item['cantidad']);
    
    $numeroOrden = 'OC-2024-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $compraId = DB::table('compras')->insertGetId([
        'numero_orden' => $numeroOrden,
        'proveedor_id' => $provId,
        'total' => $total,
        'estado' => 'completado',
        'fecha_compra' => $fecha,
        'created_at' => $fecha,
        'updated_at' => $fecha
    ]);
    
    foreach($items as $item) {
        DB::table('compra_items')->insert([
            'compra_id' => $compraId,
            'producto_id' => $item['prod_id'],
            'variante_id' => $item['var_id'],
            'cantidad' => $item['cantidad'],
            'costo_unitario' => $item['costo'],
            'subtotal' => $item['costo'] * $item['cantidad'],
            'created_at' => $fecha,
            'updated_at' => $fecha
        ]);
        
        $almacenId = 1;
        $variante = DB::table('variante')->where('id', $item['var_id'])->first();
        $stockActual = $variante->stock;
        $costoActual = $variante->precio_compra ?? 0;
        
        $nuevoStock = $stockActual + $item['cantidad'];
        $nuevoPPP = $costoActual;
        if ($nuevoStock > 0) {
            $nuevoPPP = (($stockActual * $costoActual) + ($item['cantidad'] * $item['costo'])) / $nuevoStock;
        }
        
        DB::table('variante')->where('id', $item['var_id'])->update([
            'stock' => $nuevoStock,
            'precio_compra' => $nuevoPPP,
            'updated_at' => $fecha
        ]);
        
        $stockAlmacen = DB::table('stock_almacen')->where('almacen_id', $almacenId)->where('variante_id', $item['var_id'])->first();
        if ($stockAlmacen) {
            DB::table('stock_almacen')->where('id', $stockAlmacen->id)->increment('cantidad', $item['cantidad']);
        } else {
            DB::table('stock_almacen')->insert([
                'almacen_id' => $almacenId, 'variante_id' => $item['var_id'], 'cantidad' => $item['cantidad'],
                'created_at' => $fecha, 'updated_at' => $fecha
            ]);
        }
    }
}

// 5 Proveedores
$proveedores = [
    ['nombre' => 'Ingram Micro', 'ruc' => '20102030401', 'email' => 'ventas@ingrammicro.com'],
    ['nombre' => 'TechData Peru', 'ruc' => '20405060701', 'email' => 'ventas@techdata.com'],
    ['nombre' => 'Grupo Deltron', 'ruc' => '20100010001', 'email' => 'ventas@deltron.com.pe'],
    ['nombre' => 'PC Link Corp', 'ruc' => '20505050505', 'email' => 'b2b@pclink.com'],
    ['nombre' => 'Maximum Distribucion', 'ruc' => '20606060606', 'email' => 'mayorista@maximum.pe'],
];
$provIds = [];
foreach ($proveedores as $p) {
    $provIds[] = DB::table('proveedor')->insertGetId([
        'nombre' => $p['nombre'], 'ruc' => $p['ruc'], 'email' => $p['email'],
        'telefono' => '999'.rand(100000,999999), 'activo' => true,
        'created_at' => now(), 'updated_at' => now()
    ]);
}

// Estructura de Datos (7 categorias x 3 marcas x 4 productos = 84 productos)
$catalogo = [
    'Celulares' => [
        'Apple' => [
            ['nombre' => 'iPhone 15 Pro Max 256GB', 'precio' => 5999.00, 'costo_base' => 4800.00],
            ['nombre' => 'iPhone 15 Pro 128GB', 'precio' => 4999.00, 'costo_base' => 4100.00],
            ['nombre' => 'iPhone 14 128GB', 'precio' => 3499.00, 'costo_base' => 2800.00],
            ['nombre' => 'iPhone SE 2022', 'precio' => 2199.00, 'costo_base' => 1700.00],
        ],
        'Samsung' => [
            ['nombre' => 'Galaxy S24 Ultra 512GB', 'precio' => 4599.00, 'costo_base' => 3600.00],
            ['nombre' => 'Galaxy S23 FE 256GB', 'precio' => 2599.00, 'costo_base' => 2000.00],
            ['nombre' => 'Galaxy A54 5G', 'precio' => 1499.00, 'costo_base' => 1100.00],
            ['nombre' => 'Galaxy Z Fold 5', 'precio' => 6999.00, 'costo_base' => 5500.00],
        ],
        'Xiaomi' => [
            ['nombre' => 'Redmi Note 13 Pro', 'precio' => 1299.00, 'costo_base' => 950.00],
            ['nombre' => 'Xiaomi 13T Pro', 'precio' => 2499.00, 'costo_base' => 1900.00],
            ['nombre' => 'Poco X6 Pro', 'precio' => 1599.00, 'costo_base' => 1200.00],
            ['nombre' => 'Redmi 12C', 'precio' => 499.00, 'costo_base' => 350.00],
        ]
    ],
    'Cómputo' => [
        'Lenovo' => [
            ['nombre' => 'ThinkPad T14 Gen 4', 'precio' => 5500.00, 'costo_base' => 4400.00],
            ['nombre' => 'IdeaPad 3 Core i5', 'precio' => 2200.00, 'costo_base' => 1700.00],
            ['nombre' => 'Legion Pro 5i', 'precio' => 6500.00, 'costo_base' => 5200.00],
            ['nombre' => 'Yoga 7i 2-in-1', 'precio' => 3800.00, 'costo_base' => 3000.00],
        ],
        'Asus' => [
            ['nombre' => 'ROG Zephyrus G14', 'precio' => 7200.00, 'costo_base' => 5800.00],
            ['nombre' => 'TUF Gaming A15', 'precio' => 4100.00, 'costo_base' => 3300.00],
            ['nombre' => 'ZenBook 14 OLED', 'precio' => 4500.00, 'costo_base' => 3600.00],
            ['nombre' => 'VivoBook 15', 'precio' => 1800.00, 'costo_base' => 1400.00],
        ],
        'HP' => [
            ['nombre' => 'Pavilion 15 Ryzen 7', 'precio' => 2800.00, 'costo_base' => 2200.00],
            ['nombre' => 'Spectre x360', 'precio' => 6200.00, 'costo_base' => 4900.00],
            ['nombre' => 'Victus 16', 'precio' => 3500.00, 'costo_base' => 2800.00],
            ['nombre' => 'ProBook 450 G9', 'precio' => 3200.00, 'costo_base' => 2500.00],
        ]
    ],
    'Mundo Gamer' => [
        'Razer' => [
            ['nombre' => 'Mouse DeathAdder V3', 'precio' => 399.00, 'costo_base' => 250.00],
            ['nombre' => 'Teclado BlackWidow V4', 'precio' => 799.00, 'costo_base' => 550.00],
            ['nombre' => 'Audifonos Kraken V3', 'precio' => 459.00, 'costo_base' => 300.00],
            ['nombre' => 'Laptop Blade 15', 'precio' => 9500.00, 'costo_base' => 8000.00],
        ],
        'MSI' => [
            ['nombre' => 'Monitor Optix 27"', 'precio' => 1299.00, 'costo_base' => 900.00],
            ['nombre' => 'Laptop Stealth 16', 'precio' => 8500.00, 'costo_base' => 7000.00],
            ['nombre' => 'Placa Base MAG B650', 'precio' => 899.00, 'costo_base' => 650.00],
            ['nombre' => 'Tarjeta Grafica RTX 4070', 'precio' => 3200.00, 'costo_base' => 2700.00],
        ],
        'Logitech' => [
            ['nombre' => 'Mouse G Pro X Superlight', 'precio' => 599.00, 'costo_base' => 400.00],
            ['nombre' => 'Teclado G915 TKL', 'precio' => 899.00, 'costo_base' => 650.00],
            ['nombre' => 'Volante G923', 'precio' => 1499.00, 'costo_base' => 1100.00],
            ['nombre' => 'Audifonos G733', 'precio' => 549.00, 'costo_base' => 380.00],
        ]
    ],
    'Audio' => [
        'JBL' => [
            ['nombre' => 'Parlante Flip 6', 'precio' => 499.00, 'costo_base' => 350.00],
            ['nombre' => 'Parlante Charge 5', 'precio' => 699.00, 'costo_base' => 500.00],
            ['nombre' => 'Audifonos Tune 720BT', 'precio' => 299.00, 'costo_base' => 200.00],
            ['nombre' => 'PartyBox 310', 'precio' => 2100.00, 'costo_base' => 1600.00],
        ],
        'Sony' => [
            ['nombre' => 'Audifonos WH-1000XM5', 'precio' => 1299.00, 'costo_base' => 950.00],
            ['nombre' => 'Audifonos WF-1000XM5', 'precio' => 1099.00, 'costo_base' => 800.00],
            ['nombre' => 'Parlante SRS-XB13', 'precio' => 199.00, 'costo_base' => 130.00],
            ['nombre' => 'Soundbar HT-S400', 'precio' => 999.00, 'costo_base' => 700.00],
        ],
        'Bose' => [
            ['nombre' => 'QuietComfort 45', 'precio' => 1499.00, 'costo_base' => 1100.00],
            ['nombre' => 'SoundLink Flex', 'precio' => 699.00, 'costo_base' => 500.00],
            ['nombre' => 'Smart Soundbar 600', 'precio' => 2299.00, 'costo_base' => 1700.00],
            ['nombre' => 'QuietComfort Earbuds II', 'precio' => 1199.00, 'costo_base' => 900.00],
        ]
    ],
    'TV' => [
        'LG' => [
            ['nombre' => 'TV OLED Evo C3 55"', 'precio' => 5500.00, 'costo_base' => 4200.00],
            ['nombre' => 'TV QNED 65"', 'precio' => 3800.00, 'costo_base' => 2900.00],
            ['nombre' => 'TV UHD 4K 43"', 'precio' => 1200.00, 'costo_base' => 900.00],
            ['nombre' => 'TV OLED G3 65"', 'precio' => 8500.00, 'costo_base' => 6800.00],
        ],
        'Samsung' => [
            ['nombre' => 'TV Neo QLED 4K 55"', 'precio' => 4900.00, 'costo_base' => 3800.00],
            ['nombre' => 'TV The Frame 65"', 'precio' => 5200.00, 'costo_base' => 4000.00],
            ['nombre' => 'TV Crystal UHD 50"', 'precio' => 1499.00, 'costo_base' => 1100.00],
            ['nombre' => 'TV OLED S90C 65"', 'precio' => 6500.00, 'costo_base' => 5100.00],
        ],
        'Hisense' => [
            ['nombre' => 'TV ULED U7K 55"', 'precio' => 2500.00, 'costo_base' => 1800.00],
            ['nombre' => 'TV UHD 4K 50"', 'precio' => 1100.00, 'costo_base' => 850.00],
            ['nombre' => 'TV Laser 4K 100"', 'precio' => 12000.00, 'costo_base' => 9500.00],
            ['nombre' => 'TV Mini-LED U8K 65"', 'precio' => 3900.00, 'costo_base' => 3000.00],
        ]
    ],
    'Videojuegos' => [
        'Nintendo' => [
            ['nombre' => 'Switch OLED', 'precio' => 1599.00, 'costo_base' => 1200.00],
            ['nombre' => 'Switch Lite', 'precio' => 999.00, 'costo_base' => 750.00],
            ['nombre' => 'Juego Zelda TOTK', 'precio' => 259.00, 'costo_base' => 180.00],
            ['nombre' => 'Pro Controller', 'precio' => 299.00, 'costo_base' => 210.00],
        ],
        'PlayStation' => [
            ['nombre' => 'PS5 Slim Edicion Disco', 'precio' => 2499.00, 'costo_base' => 2000.00],
            ['nombre' => 'Control DualSense', 'precio' => 320.00, 'costo_base' => 240.00],
            ['nombre' => 'PS VR2', 'precio' => 2699.00, 'costo_base' => 2100.00],
            ['nombre' => 'Juego Spider-Man 2', 'precio' => 299.00, 'costo_base' => 210.00],
        ],
        'Xbox' => [
            ['nombre' => 'Series X 1TB', 'precio' => 2599.00, 'costo_base' => 2050.00],
            ['nombre' => 'Series S 512GB', 'precio' => 1399.00, 'costo_base' => 1050.00],
            ['nombre' => 'Control Inalambrico Xbox', 'precio' => 280.00, 'costo_base' => 200.00],
            ['nombre' => 'Audifonos Xbox Wireless', 'precio' => 450.00, 'costo_base' => 320.00],
        ]
    ],
    'Smartwatches' => [
        'Apple' => [
            ['nombre' => 'Watch Series 9', 'precio' => 1899.00, 'costo_base' => 1500.00],
            ['nombre' => 'Watch Ultra 2', 'precio' => 3899.00, 'costo_base' => 3100.00],
            ['nombre' => 'Watch SE 2da Gen', 'precio' => 1199.00, 'costo_base' => 900.00],
            ['nombre' => 'Correa Ocean Band', 'precio' => 450.00, 'costo_base' => 280.00],
        ],
        'Garmin' => [
            ['nombre' => 'Fenix 7 Pro', 'precio' => 3500.00, 'costo_base' => 2800.00],
            ['nombre' => 'Forerunner 265', 'precio' => 2100.00, 'costo_base' => 1650.00],
            ['nombre' => 'Venu 3', 'precio' => 1800.00, 'costo_base' => 1400.00],
            ['nombre' => 'Instinct 2 Solar', 'precio' => 1500.00, 'costo_base' => 1150.00],
        ],
        'Amazfit' => [
            ['nombre' => 'GTR 4', 'precio' => 850.00, 'costo_base' => 600.00],
            ['nombre' => 'GTS 4 Mini', 'precio' => 450.00, 'costo_base' => 300.00],
            ['nombre' => 'T-Rex Ultra', 'precio' => 1800.00, 'costo_base' => 1300.00],
            ['nombre' => 'Bip 5', 'precio' => 350.00, 'costo_base' => 220.00],
        ]
    ]
];

$allProductsVariants = [];

foreach ($catalogo as $catName => $marcas) {
    $catId = DB::table('categoria')->insertGetId([
        'nombre' => $catName, 'activa' => true,
        'created_at' => now(), 'updated_at' => now()
    ]);
    
    foreach ($marcas as $marcaName => $productos) {
        $marcaId = DB::table('marca')->where('nombre', $marcaName)->value('id');
        if (!$marcaId) {
            $marcaId = DB::table('marca')->insertGetId(['nombre' => $marcaName]);
        }
        
        foreach ($productos as $prodInfo) {
            $skuBase = strtoupper(substr($marcaName,0,3)) . '-' . rand(1000,9999);
            
            $pId = DB::table('producto')->insertGetId([
                'nombre' => $marcaName . ' ' . $prodInfo['nombre'],
                'slug' => Str::slug($marcaName . ' ' . $prodInfo['nombre']),
                'sku_base' => $skuBase,
                'descripcion' => 'Producto oficial de ' . $marcaName,
                'activo' => true,
                'marca_id' => $marcaId,
                'proveedor_id' => $provIds[array_rand($provIds)],
                'created_at' => now(), 'updated_at' => now()
            ]);
            
            DB::table('producto_categoria')->insert(['producto_id' => $pId, 'categoria_id' => $catId]);
            
            $vId = DB::table('variante')->insertGetId([
                'producto_id' => $pId, 'sku' => $skuBase . '-STD', 'precio' => $prodInfo['precio'], 'stock' => 0,
                'peso' => rand(2, 25) / 10, 'created_at' => now(), 'updated_at' => now()
            ]);
            
            $allProductsVariants[] = [
                'prod_id' => $pId,
                'var_id' => $vId,
                'costo_base' => $prodInfo['costo_base']
            ];
        }
    }
}

// Generar compras masivas aleatorias
$meses = ['2024-03-10', '2024-04-15', '2024-05-20', '2024-06-05', '2024-07-01'];

foreach ($meses as $fecha) {
    // 5 compras por mes
    for ($i=0; $i<5; $i++) {
        $provId = $provIds[array_rand($provIds)];
        $itemsCount = rand(5, 12); // compras más grandes
        $itemsToBuy = [];
        
        $selectedKeys = array_rand($allProductsVariants, $itemsCount);
        if (!is_array($selectedKeys)) $selectedKeys = [$selectedKeys];
        
        foreach ($selectedKeys as $key) {
            $prod = $allProductsVariants[$key];
            // Variacion de costo
            $variacion = (rand(-8, 8) / 100);
            $costoFinal = $prod['costo_base'] * (1 + $variacion);
            
            $itemsToBuy[] = [
                'prod_id' => $prod['prod_id'],
                'var_id' => $prod['var_id'],
                'cantidad' => rand(5, 50), // más stock
                'costo' => round($costoFinal, 2)
            ];
        }
        
        crearCompra($provId, $fecha, $itemsToBuy);
    }
}

echo "Base de datos sembrada MASIVAMENTE con 7 categorias, 20+ marcas, 84 productos y compras reales.\n";
