<?php
$categorias = DB::table('categoria')->get();
foreach($categorias as $c) {
    $hasProducts = DB::table('producto_categoria')->where('categoria_id', $c->id)->exists();
    if (!$hasProducts) {
        $skuBase = 'TEST-' . $c->id . '-' . rand(1000, 9999);
        $prodId = DB::table('producto')->insertGetId([
            'nombre' => 'Test ' . $c->nombre,
            'slug' => \Illuminate\Support\Str::slug('Test ' . $c->nombre . ' ' . rand(100,999)),
            'sku_base' => $skuBase,
            'descripcion' => 'Semilla',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('producto_categoria')->insert([
            'producto_id' => $prodId,
            'categoria_id' => $c->id
        ]);
        $varId = DB::table('variante')->insertGetId([
            'producto_id' => $prodId,
            'sku' => $skuBase,
            'precio' => 100,
            'stock' => 10,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $compraId = DB::table('compras')->insertGetId([
            'numero_orden' => 'OC-SEED-' . rand(1000, 9999),
            'proveedor_id' => 1,
            'total' => 500,
            'estado' => 'completado',
            'fecha_compra' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('compra_items')->insert([
            'compra_id' => $compraId,
            'producto_id' => $prodId,
            'variante_id' => $varId,
            'cantidad' => 5,
            'costo_unitario' => 100,
            'subtotal' => 500,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "Injected for category: " . $c->nombre . "\n";
    }
}
