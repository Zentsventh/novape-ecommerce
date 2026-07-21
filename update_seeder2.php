<?php
$file = 'c:\\Users\\eduar\\Music\\PROYECTO\\Laravel\\proyecto\\database\\seeders\\ProfessionalStoreSeeder.php';
$content = file_get_contents($file);

$newProductosData = <<<EOT
        \$productosData = [];

        // Generando 10 productos de Apple
        for(\$i=1; \$i<=10; \$i++) {
            \$productosData[] = [
                'nombre' => 'iPhone ' . (15 - (\$i % 3)) . ' Pro ' . (\$i * 128) . 'GB Edition ' . \$i,
                'marca' => 'Apple', 'cat' => ['Celulares', 'Apple'],
                'precio' => 4000.00 + (\$i * 100), 'stock' => 100,
                'desc' => "Descubre el poder del ecosistema Apple con este increíble iPhone. Equipado con el chip más avanzado de la industria, pantalla Super Retina XDR y un diseño elegante. Su sistema de cámaras profesional captura cada detalle con precisión, mientras que su batería de larga duración te acompaña todo el día.",
                'img' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'Super Retina XDR OLED 6.1" - 6.7"',
                    'Procesador' => 'Apple A-Series Bionic/Pro',
                    'Memoria RAM' => '6GB / 8GB',
                    'Almacenamiento' => (\$i * 128) . 'GB NVMe',
                    'Cámara Principal' => '48 MP + 12 MP (teleobjetivo) + 12 MP (ultrawide)',
                    'Cámara Frontal' => '12 MP f/1.9',
                    'Batería' => 'Larga duración, Carga Rápida',
                    'Sistema Operativo' => 'iOS 17',
                    'Conectividad' => '5G, Wi-Fi 6, Bluetooth 5.3',
                    'Seguridad' => 'Face ID'
                ]
            ];
        }

        // Generando 10 productos de Samsung
        for(\$i=1; \$i<=10; \$i++) {
            \$productosData[] = [
                'nombre' => 'Samsung Galaxy S' . (24 - (\$i % 3)) . ' Ultra ' . (\$i * 128) . 'GB Variante ' . \$i,
                'marca' => 'Samsung', 'cat' => ['Celulares', 'Samsung'],
                'precio' => 3500.00 + (\$i * 80), 'stock' => 100,
                'desc' => "Vive la experiencia definitiva con el Galaxy S Ultra. Diseñado para ofrecer la mejor cámara, el rendimiento más rápido con Snapdragon for Galaxy y la icónica S Pen. Su diseño robusto y pantalla inmersiva lo convierten en la herramienta perfecta para creadores y profesionales.",
                'img' => 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'Dynamic AMOLED 2X 6.8" 120Hz',
                    'Procesador' => 'Snapdragon 8 Series',
                    'Memoria RAM' => '8GB / 12GB',
                    'Almacenamiento' => (\$i * 128) . 'GB UFS 4.0',
                    'Cámara Principal' => '200 MP + 50 MP (tele) + 12 MP (ultrawide)',
                    'Cámara Frontal' => '12 MP',
                    'Batería' => '5000 mAh',
                    'Sistema Operativo' => 'Android 14, One UI 6',
                    'Soporte S Pen' => 'Sí, incluido',
                    'Conectividad' => '5G, Wi-Fi 7, Bluetooth 5.3'
                ]
            ];
        }

        // Generando 10 productos de Xiaomi
        for(\$i=1; \$i<=10; \$i++) {
            \$productosData[] = [
                'nombre' => 'Xiaomi ' . (14 - (\$i % 2)) . ' Pro ' . (\$i * 64) . 'GB Modelo ' . \$i,
                'marca' => 'Xiaomi', 'cat' => ['Celulares', 'Xiaomi'],
                'precio' => 2500.00 + (\$i * 50), 'stock' => 100,
                'desc' => "Xiaomi vuelve a sorprender con un equipo de alto rendimiento a un precio inigualable. Con carga hiper rápida, cámaras desarrolladas con expertos en fotografía óptica y una pantalla vibrante, este dispositivo te dará la máxima productividad y entretenimiento en tu día a día.",
                'img' => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'AMOLED 6.7" 120Hz',
                    'Procesador' => 'Snapdragon 8 Gen Series',
                    'Memoria RAM' => '8GB / 12GB',
                    'Almacenamiento' => (\$i * 64) . 'GB',
                    'Cámara Principal' => '50 MP + 50 MP + 50 MP',
                    'Cámara Frontal' => '32 MP',
                    'Batería' => '4800 mAh',
                    'Carga Rápida' => '120W HyperCharge',
                    'Sistema Operativo' => 'HyperOS',
                    'Resistencia' => 'IP68'
                ]
            ];
        }

        // Generando 10 Laptops (5 Lenovo, 5 Asus)
        for(\$i=1; \$i<=5; \$i++) {
            \$productosData[] = [
                'nombre' => 'Lenovo Legion ' . (5 + \$i) . 'i Pro Gen ' . \$i,
                'marca' => 'Lenovo', 'cat' => ['Cómputo', 'Lenovo', 'Laptops', 'Laptops Gamer'],
                'precio' => 4500.00 + (\$i * 200), 'stock' => 100,
                'desc' => "Domina todos los juegos de última generación con la poderosa Legion. Equipada con procesadores y tarjetas gráficas de última línea, refrigeración Coldfront avanzada y una pantalla inmersiva para que no te pierdas ningún detalle de la acción.",
                'img' => 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => '16" WQXGA 165Hz IPS',
                    'Procesador' => 'Intel Core i7/i9 HX',
                    'Tarjeta Gráfica' => 'NVIDIA RTX 40' . (50 + (\$i * 10)),
                    'Memoria RAM' => '16GB / 32GB DDR5',
                    'Almacenamiento' => '1TB SSD M.2',
                    'Refrigeración' => 'Legion Coldfront',
                    'Teclado' => 'TrueStrike RGB',
                    'Conectividad' => 'Wi-Fi 6E, Bluetooth 5.1',
                    'Batería' => '80Wh',
                    'Sistema Operativo' => 'Windows 11 Home'
                ]
            ];
            \$productosData[] = [
                'nombre' => 'Asus ROG Zephyrus G' . (14 + (\$i % 2)*2) . ' Modelo ' . \$i,
                'marca' => 'Asus', 'cat' => ['Cómputo', 'Asus', 'Laptops', 'Laptops Gamer'],
                'precio' => 5000.00 + (\$i * 150), 'stock' => 100,
                'desc' => "Ultraportabilidad y rendimiento gaming se unen en la Asus ROG Zephyrus. Con pantalla Nebula Display para colores perfectos y un diseño ligero con AniMe Matrix, esta laptop te permite jugar y crear contenido estés donde estés.",
                'img' => 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'ROG Nebula Display QHD+',
                    'Procesador' => 'AMD Ryzen 9',
                    'Tarjeta Gráfica' => 'NVIDIA RTX 40' . (60 + (\$i * 10)),
                    'Memoria RAM' => '16GB / 32GB DDR5',
                    'Almacenamiento' => '1TB SSD M.2',
                    'Diseño' => 'AniMe Matrix o ROG Slash',
                    'Peso' => '1.65 kg',
                    'Audio' => 'Dolby Atmos, 4 altavoces',
                    'Conectividad' => 'Wi-Fi 6E, Bluetooth 5.2',
                    'Sistema Operativo' => 'Windows 11 Home'
                ]
            ];
        }

        // Generando 10 Consolas / Videojuegos (5 Sony, 5 Nintendo)
        for(\$i=1; \$i<=5; \$i++) {
            \$productosData[] = [
                'nombre' => 'PlayStation 5 Edición Especial ' . \$i,
                'marca' => 'Sony', 'cat' => ['Videojuegos', 'Play Station', 'Consolas'],
                'precio' => 2400.00 + (\$i * 50), 'stock' => 100,
                'desc' => "Disfruta de la nueva generación con esta edición especial de PS5. Tiempos de carga ultrarrápidos, retroalimentación háptica, audio 3D y gatillos adaptativos te sumergen por completo en mundos increíbles y detallados a 4K.",
                'img' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Resolución Máxima' => '8K (soporte) / 4K nativo',
                    'Procesador' => 'AMD Ryzen Zen 2 personalizado',
                    'Gráficos' => 'RDNA 2, 10.28 TFLOPs',
                    'Memoria RAM' => '16GB GDDR6',
                    'Almacenamiento' => '825GB / 1TB SSD',
                    'Audio' => 'Tempest 3D AudioTech',
                    'Mando Incluido' => 'DualSense Wireless Controller',
                    'Unidad Óptica' => 'Ultra HD Blu-ray',
                    'Conectividad' => 'Wi-Fi 6, Ethernet, Bluetooth 5.1',
                    'Retrocompatibilidad' => 'Juegos de PS4 soportados'
                ]
            ];
            \$productosData[] = [
                'nombre' => 'Nintendo Switch OLED Bundle ' . \$i,
                'marca' => 'Nintendo', 'cat' => ['Videojuegos', 'Nintendo', 'Consolas'],
                'precio' => 1500.00 + (\$i * 40), 'stock' => 100,
                'desc' => "Juega en la tele, en la mesa o en tus manos con la versatilidad inigualable de Nintendo Switch OLED. Esta edición incluye colores especiales y pantalla de 7 pulgadas con contraste espectacular para disfrutar de la vasta biblioteca de juegos de Nintendo.",
                'img' => 'https://images.unsplash.com/photo-1617096200347-cb04ae810b1d?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'OLED táctil 7.0"',
                    'Resolución' => '1080p en TV, 720p Portátil',
                    'Almacenamiento' => '64GB Interno',
                    'Mandos' => 'Joy-Con L/R',
                    'Batería' => '4.5 a 9 horas',
                    'Audio' => 'Estéreo mejorado',
                    'Dock' => 'Incluye puerto LAN Ethernet',
                    'Procesador' => 'NVIDIA Custom Tegra',
                    'Conectividad' => 'Wi-Fi, Bluetooth',
                    'Peso' => '420g (con Joy-Cons)'
                ]
            ];
        }

        // Generando 10 TV (5 LG, 5 Samsung)
        for(\$i=1; \$i<=5; \$i++) {
            \$productosData[] = [
                'nombre' => 'TV LG OLED ' . (55 + (\$i * 10)) . '" Serie C' . \$i,
                'marca' => 'LG', 'cat' => ['TV', 'LG', 'Televisores'],
                'precio' => 4000.00 + (\$i * 500), 'stock' => 100,
                'desc' => "Un panel OLED evo que revoluciona lo que ves. Los píxeles autoiluminados producen negros perfectos e imágenes de contraste infinito. Su procesador con IA optimiza el sonido y la imagen para ofrecerte la experiencia del cine en tu sala.",
                'img' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'OLED evo 4K UHD',
                    'Tamaño' => (55 + (\$i * 10)) . ' pulgadas',
                    'Tasa de Refresco' => '120Hz nativos',
                    'Procesador' => 'α9 AI Processor 4K Gen6',
                    'Formatos HDR' => 'Dolby Vision, HDR10, HLG',
                    'Audio' => 'Dolby Atmos, 40W 2.2ch',
                    'Sistema Operativo' => 'webOS 23',
                    'Gaming' => 'G-Sync, FreeSync, VRR, ALLM',
                    'Asistente Inteligente' => 'ThinQ AI, Alexa integrado',
                    'Conectividad' => '4x HDMI 2.1, Wi-Fi 5, Bluetooth 5.0'
                ]
            ];
            \$productosData[] = [
                'nombre' => 'TV Samsung Neo QLED 8K ' . (65 + (\$i * 10)) . '" QN' . (\$i * 100),
                'marca' => 'Samsung', 'cat' => ['TV', 'Samsung', 'Televisores'],
                'precio' => 6000.00 + (\$i * 600), 'stock' => 100,
                'desc' => "Experimenta una claridad superior con el TV Neo QLED de Samsung. La tecnología Quantum Matrix y los Mini LEDs ofrecen brillo y contraste extremos, mientras que su procesador inteligente escala cualquier contenido a resolución increíble. Diseño delgado y elegante.",
                'img' => 'https://images.unsplash.com/photo-1593784991095-a205069470b6?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'Neo QLED (Mini LED) 8K o 4K',
                    'Tamaño' => (65 + (\$i * 10)) . ' pulgadas',
                    'Tasa de Refresco' => '120Hz (Hasta 144Hz PC)',
                    'Procesador' => 'Neural Quantum Processor',
                    'Formatos HDR' => 'Neo Quantum HDR+',
                    'Audio' => 'Object Tracking Sound (OTS+), Q-Symphony',
                    'Sistema Operativo' => 'Tizen OS',
                    'Gaming' => 'Motion Xcelerator Turbo+',
                    'Diseño' => 'Infinity One / NeoSlim',
                    'Conectividad' => '4x HDMI 2.1, Wi-Fi 6E, Bluetooth 5.2'
                ]
            ];
        }

EOT;

$content = preg_replace('/\$productosData\s*=\s*\[.*?\];\s*(foreach \(\$productosData as \$idx => \$p\))/s', $newProductosData . "\n        \$1", $content);

file_put_contents($file, $content);

echo "Success";
