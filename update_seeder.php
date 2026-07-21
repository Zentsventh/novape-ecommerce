<?php
$file = 'c:\\Users\\eduar\\Music\\PROYECTO\\Laravel\\proyecto\\database\\seeders\\ProfessionalStoreSeeder.php';
$content = file_get_contents($file);

$newProductosData = <<<EOT
        \$productosData = [
            // Celulares
            [
                'nombre' => 'iPhone 15 Pro Max 256GB',
                'marca' => 'Apple', 'cat' => ['Celulares', 'Apple'],
                'precio' => 5999.00, 'stock' => 100,
                'desc' => "El iPhone 15 Pro Max es el modelo más avanzado de Apple hasta la fecha. Forjado en titanio de calidad aeroespacial, es increíblemente resistente y ligero. Equipado con el revolucionario chip A17 Pro, ofrece un rendimiento gráfico y de procesamiento sin precedentes, ideal para juegos AAA y multitarea intensiva. Su sistema de cámaras pro incluye un teleobjetivo de 5x, permitiendo capturas a larga distancia con una nitidez asombrosa. Además, cuenta con el nuevo botón de Acción personalizable y conectividad USB-C con velocidades USB 3 para transferencias ultra rápidas. Descubre un mundo de posibilidades fotográficas y de entretenimiento con su brillante pantalla Super Retina XDR de 6.7 pulgadas con ProMotion a 120Hz.",
                'img' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'Super Retina XDR OLED 6.7"',
                    'Procesador' => 'Apple A17 Pro (3 nm)',
                    'Memoria RAM' => '8GB',
                    'Almacenamiento' => '256GB NVMe',
                    'Cámara Principal' => '48 MP f/1.8 + 12 MP (teleobjetivo 5x) + 12 MP (ultrawide)',
                    'Cámara Frontal' => '12 MP f/1.9',
                    'Batería' => '4441 mAh',
                    'Sistema Operativo' => 'iOS 17',
                    'Material' => 'Titanio',
                    'Conectividad' => '5G, Wi-Fi 6E, Bluetooth 5.3, USB-C'
                ]
            ],
            [
                'nombre' => 'Samsung Galaxy S24 Ultra 512GB',
                'marca' => 'Samsung', 'cat' => ['Celulares', 'Samsung'],
                'precio' => 5499.00, 'stock' => 100,
                'desc' => "El Samsung Galaxy S24 Ultra redefine la experiencia de un smartphone con la integración total de Galaxy AI, impulsando la creatividad, la productividad y las posibilidades móviles. Construido con un exterior de titanio resistente y elegante, aloja el poderoso procesador Snapdragon 8 Gen 3 for Galaxy, optimizado para un rendimiento extremo. Su sistema de cámaras de 200MP captura detalles inigualables en cualquier condición de luz gracias al Nightography mejorado. Disfruta de una experiencia visual inmersiva en su pantalla plana Dynamic AMOLED 2X de 6.8 pulgadas con un brillo máximo asombroso, y aumenta tu eficiencia con el S Pen integrado.",
                'img' => 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'Dynamic AMOLED 2X 6.8" 120Hz',
                    'Procesador' => 'Qualcomm Snapdragon 8 Gen 3 for Galaxy',
                    'Memoria RAM' => '12GB LPDDR5X',
                    'Almacenamiento' => '512GB UFS 4.0',
                    'Cámara Principal' => '200 MP + 50 MP (tele 5x) + 10 MP (tele 3x) + 12 MP (ultrawide)',
                    'Cámara Frontal' => '12 MP',
                    'Batería' => '5000 mAh, Carga rápida 45W',
                    'Sistema Operativo' => 'Android 14, One UI 6.1',
                    'Características Especiales' => 'S Pen integrado, Galaxy AI',
                    'Conectividad' => '5G, Wi-Fi 7, Bluetooth 5.3'
                ]
            ],
            [
                'nombre' => 'Xiaomi 14 Pro 256GB',
                'marca' => 'Xiaomi', 'cat' => ['Celulares', 'Xiaomi'],
                'precio' => 3200.00, 'stock' => 100,
                'desc' => "El Xiaomi 14 Pro combina tecnología de vanguardia con un diseño exquisito. Desarrollado en colaboración con Leica, su sistema de cámaras cuenta con lentes ópticos Summilux que ofrecen imágenes impresionantes con colores precisos y un gran rango dinámico. Está impulsado por la plataforma móvil Snapdragon 8 Gen 3, garantizando una velocidad y eficiencia sobresalientes para cualquier tarea exigente. Su pantalla AMOLED de 6.73 pulgadas soporta un brillo extraordinario y una tasa de refresco adaptativa, mientras que la batería de alta capacidad con carga ultrarrápida asegura que nunca te quedes desconectado.",
                'img' => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'LTPO AMOLED 6.73" 120Hz 3000 nits',
                    'Procesador' => 'Qualcomm Snapdragon 8 Gen 3',
                    'Memoria RAM' => '12GB',
                    'Almacenamiento' => '256GB UFS 4.0',
                    'Cámara Principal' => '50 MP + 50 MP (tele) + 50 MP (ultrawide) - Óptica Leica',
                    'Cámara Frontal' => '32 MP',
                    'Batería' => '4880 mAh, Carga rápida 120W',
                    'Sistema Operativo' => 'HyperOS (Android 14)',
                    'Seguridad' => 'Lector de huellas bajo pantalla óptico',
                    'Resistencia' => 'IP68 polvo y agua'
                ]
            ],

            // Cómputo
            [
                'nombre' => 'MacBook Pro M3 Max 14"',
                'marca' => 'Apple', 'cat' => ['Cómputo', 'MacBook', 'Laptops'],
                'precio' => 8500.00, 'stock' => 100,
                'desc' => "La MacBook Pro de 14 pulgadas da un salto gigantesco con el chip M3 Max de Apple. Construida para los profesionales más exigentes, ofrece un rendimiento gráfico brutal con trazado de rayos por aceleración de hardware, perfecto para modelado 3D, edición de video 8K y desarrollo de software pesado. La pantalla Liquid Retina XDR revela detalles asombrosos en las sombras y colores vibrantes, mientras que la duración de la batería líder en la industria te permite trabajar todo el día. Con un conjunto completo de puertos pro y un chasis de aluminio 100% reciclado, es la estación de trabajo portátil definitiva.",
                'img' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => '14.2" Liquid Retina XDR, ProMotion 120Hz',
                    'Procesador' => 'Apple M3 Max (14 núcleos CPU, 30 núcleos GPU)',
                    'Memoria RAM' => '36GB Memoria Unificada',
                    'Almacenamiento' => '1TB SSD ultrarrápido',
                    'Batería' => 'Hasta 18 horas de autonomía',
                    'Teclado' => 'Magic Keyboard retroiluminado con Touch ID',
                    'Puertos' => '3x Thunderbolt 4, HDMI, SDXC, MagSafe 3',
                    'Audio' => 'Sistema de 6 parlantes con audio espacial',
                    'Cámara' => 'FaceTime HD 1080p',
                    'Sistema Operativo' => 'macOS Sonoma'
                ]
            ],
            [
                'nombre' => 'Lenovo Legion Pro 5i Gen 8',
                'marca' => 'Lenovo', 'cat' => ['Cómputo', 'Lenovo', 'Laptops', 'Mundo Gamer', 'Laptops Gamer'],
                'precio' => 6200.00, 'stock' => 100,
                'desc' => "Domina el campo de batalla con la Lenovo Legion Pro 5i Gen 8. Equipado con un procesador Intel Core i7 de 13ª generación y una tarjeta gráfica NVIDIA GeForce RTX 4070, este equipo ofrece velocidades de fotogramas altísimas en los títulos más populares. Su revolucionario sistema de refrigeración Legion Coldfront 5.0 mantiene la máquina fresca y silenciosa incluso bajo cargas extremas. Sumérgete en el juego con la pantalla PureSight Gaming WQXGA de 16 pulgadas, con tasas de refresco competitivas y colores precisos respaldados por la calibración de fábrica X-Rite Pantone.",
                'img' => 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => '16" WQXGA (2560x1600) IPS, 165Hz',
                    'Procesador' => 'Intel Core i7-13700HX (16 Núcleos)',
                    'Tarjeta Gráfica' => 'NVIDIA GeForce RTX 4070 8GB GDDR6 (140W)',
                    'Memoria RAM' => '16GB DDR5 4800MHz',
                    'Almacenamiento' => '1TB PCIe Gen4 NVMe TLC M.2 SSD',
                    'Refrigeración' => 'Legion Coldfront 5.0',
                    'Teclado' => 'Legion TrueStrike RGB 4 zonas',
                    'Audio' => 'Nahimic Audio por SteelSeries',
                    'Conectividad' => 'Wi-Fi 6E, Bluetooth 5.1, RJ-45, USB-C',
                    'Sistema Operativo' => 'Windows 11 Home'
                ]
            ],
            [
                'nombre' => 'Asus ROG Zephyrus G14',
                'marca' => 'Asus', 'cat' => ['Cómputo', 'Asus', 'Laptops', 'Mundo Gamer', 'Laptops Gamer'],
                'precio' => 5800.00, 'stock' => 100,
                'desc' => "La Asus ROG Zephyrus G14 combina a la perfección potencia pura con un formato ultra portátil. Diseñada para creadores y gamers en movimiento, incluye un potente procesador AMD Ryzen 9 y una gráfica NVIDIA GeForce RTX 4060 en un chasis asombrosamente ligero. Su exclusiva pantalla Nebula HDR Mini-LED de 14 pulgadas ofrece un contraste profundo y colores espectaculares que dan vida a cada escena. Además, el panel AniMe Matrix en la tapa permite personalizar el equipo con animaciones y mensajes, haciendo de la Zephyrus G14 una laptop tan única como tú.",
                'img' => 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => '14" ROG Nebula Display QHD+ 165Hz',
                    'Procesador' => 'AMD Ryzen 9 7940HS',
                    'Tarjeta Gráfica' => 'NVIDIA GeForce RTX 4060 8GB GDDR6',
                    'Memoria RAM' => '16GB DDR5 4800MHz',
                    'Almacenamiento' => '512GB PCIe 4.0 NVMe M.2 SSD',
                    'Diseño' => 'Panel AniMe Matrix (tapa personalizable)',
                    'Peso' => '1.65 kg',
                    'Teclado' => 'Retroiluminado 1 zona RGB',
                    'Audio' => 'Sistema de 4 altavoces con Smart Amp, Dolby Atmos',
                    'Sistema Operativo' => 'Windows 11 Home'
                ]
            ],

            // Mundo Gamer & Consolas
            [
                'nombre' => 'PlayStation 5 Slim (Edición Disco)',
                'marca' => 'Sony', 'cat' => ['Videojuegos', 'Play Station', 'Consolas'],
                'precio' => 2499.00, 'stock' => 100,
                'desc' => "Experimenta tiempos de carga ultrarrápidos, inmersión más profunda y una nueva generación de increíbles juegos de PlayStation con la nueva PS5 Slim. Su diseño más compacto reduce el volumen un 30% respecto a su predecesora, pero mantiene todo el rendimiento que necesitas para el gaming en 4K. Incluye un SSD de 1TB integrado para que tengas tus títulos favoritos listos para jugar. El mando inalámbrico DualSense ofrece retroalimentación háptica y gatillos adaptativos dinámicos, haciéndote sentir los efectos y el impacto de tus acciones en el juego de manera visceral.",
                'img' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Almacenamiento' => '1TB SSD personalizado ultra veloz',
                    'Resolución Soportada' => 'Hasta 4K a 120 FPS',
                    'Tecnología Gráfica' => 'Ray Tracing, HDR',
                    'Unidad Óptica' => 'Ultra HD Blu-ray',
                    'Controlador' => '1x DualSense Inalámbrico Haptic',
                    'Audio' => 'Tempest 3D AudioTech',
                    'Puertos Frontales' => '1x USB-C SuperSpeed, 1x USB-C Hi-Speed',
                    'Puertos Traseros' => '2x USB-A SuperSpeed, HDMI 2.1, Ethernet',
                    'Dimensiones' => '358 x 96 x 216 mm (aprox)',
                    'Peso' => '3.2 kg'
                ]
            ],
            [
                'nombre' => 'Nintendo Switch OLED Neon',
                'marca' => 'Nintendo', 'cat' => ['Videojuegos', 'Nintendo', 'Consolas'],
                'precio' => 1499.00, 'stock' => 100,
                'desc' => "Juega en casa frente a la televisión o llévalo contigo en modo portátil con el Nintendo Switch OLED. Su nueva pantalla OLED de 7 pulgadas ofrece colores vibrantes y un contraste nítido, lo que hace que los juegos se vean mejor que nunca. El soporte ancho ajustable brinda comodidad para el modo semiportátil, y la base incluye un puerto LAN integrado para una conexión en línea estable cuando juegas en la TV. Disfruta del sonido mejorado de los parlantes integrados del sistema y guárdalo todo en los 64 GB de almacenamiento interno.",
                'img' => 'https://images.unsplash.com/photo-1617096200347-cb04ae810b1d?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Pantalla' => 'OLED táctil capacitiva 7.0" (1280x720)',
                    'Modos de Juego' => 'TV, Semiportátil, Portátil',
                    'Almacenamiento' => '64GB (expandible vía microSDHC/microSDXC)',
                    'Controles' => 'Joy-Con (L)/(R) color Neón',
                    'Batería' => 'Aprox. 4.5 a 9 horas de autonomía',
                    'Audio' => 'Altavoces estéreo mejorados',
                    'Base (Dock)' => 'Incluye puerto LAN por cable',
                    'Procesador' => 'NVIDIA Custom Tegra',
                    'Conectividad' => 'Wi-Fi (IEEE 802.11 a/b/g/n/ac), Bluetooth 4.1',
                    'Peso' => 'Aprox 320g (420g con Joy-Con acoplados)'
                ]
            ],

            // Audio & TV
            [
                'nombre' => 'Sony WH-1000XM5 Audífonos Inalámbricos',
                'marca' => 'Sony', 'cat' => ['Audio', 'Sony', 'Audífonos'],
                'precio' => 1299.00, 'stock' => 100,
                'desc' => "Los auriculares Sony WH-1000XM5 reescriben las reglas para escuchar sin distracciones y lograr la máxima claridad en las llamadas. Equipados con dos procesadores que controlan 8 micrófonos, ofrecen una cancelación de ruido sin precedentes y una calidad de llamada excepcional en todas las condiciones. La unidad de diafragma especial de 30 mm, construida con fibra de carbono ligera, mejora la sensibilidad a las altas frecuencias para un sonido natural. El elegante diseño continuo y ligero se ajusta cómodamente a la cabeza, permitiendo horas de escucha ininterrumpida.",
                'img' => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Tipo' => 'Cerrados, dinámicos, Over-Ear',
                    'Cancelación de Ruido' => 'Procesador V1 + QN1, 8 micrófonos, Auto NC Optimizer',
                    'Unidad de Diafragma' => '30 mm especial de fibra de carbono',
                    'Autonomía' => 'Hasta 30 horas (con NC activado)',
                    'Carga Rápida' => '3 minutos de carga = 3 horas de reproducción',
                    'Conectividad' => 'Bluetooth 5.2, LDAC, conexión multipunto',
                    'Audio' => 'Hi-Res Audio Wireless, DSEE Extreme',
                    'Controles' => 'Panel táctil en el auricular',
                    'Peso' => 'Aprox 250g',
                    'Características Extra' => 'Speak-to-Chat, Detección de uso'
                ]
            ],
            [
                'nombre' => 'AirPods Pro (2da Generación)',
                'marca' => 'Apple', 'cat' => ['Audio', 'Airpods', 'Audífonos'],
                'precio' => 1099.00, 'stock' => 100,
                'desc' => "Los AirPods Pro (2da generación) con estuche de carga MagSafe (USB-C) ofrecen una experiencia de audio completamente inmersiva gracias a su Cancelación Activa de Ruido hasta dos veces mejor y a su audio espacial personalizado. El chip H2 diseñado por Apple mejora la acústica, los bajos y el rendimiento general. El Modo Ambiente adaptable reduce inteligentemente el ruido de ruidos fuertes como sirenas o taladros. Los controles táctiles te permiten ajustar el volumen directamente desde el vástago. Incluyen almohadillas de silicona en cuatro tamaños para el ajuste perfecto.",
                'img' => 'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Procesador' => 'Chip H2 en auriculares, Chip U1 en el estuche',
                    'Cancelación de Ruido' => 'Cancelación Activa de Ruido, Modo Ambiente adaptable',
                    'Audio' => 'Audio espacial personalizado con seguimiento dinámico',
                    'Controles' => 'Control táctil en la parte inferior',
                    'Autonomía (Auriculares)' => 'Hasta 6 horas de escucha con NC activado',
                    'Autonomía (Con estuche)' => 'Hasta 30 horas de escucha con NC activado',
                    'Resistencia' => 'IP54 resistente al polvo, sudor y agua',
                    'Estuche de Carga' => 'MagSafe con USB-C, parlante y orificio para correa',
                    'Conectividad' => 'Bluetooth 5.3',
                    'Almohadillas' => 'Silicona, 4 tamaños (XS, S, M, L)'
                ]
            ],
            [
                'nombre' => 'TV LG OLED C3 55"',
                'marca' => 'LG', 'cat' => ['TV', 'LG', 'Televisores'],
                'precio' => 4500.00, 'stock' => 100,
                'desc' => "Disfruta de una evolución en la tecnología de visualización con el TV LG OLED evo C3. Sus píxeles autoiluminados, ahora más brillantes que nunca gracias al Brightness Booster, entregan negros perfectos y un contraste infinito para revelar detalles ocultos tanto en las sombras más oscuras como en las luces más brillantes. Potenciado por el procesador α9 AI 4K Gen6, el televisor optimiza automáticamente la imagen y el sonido basándose en lo que estás viendo. Con su diseño ultradelgado, se integra perfectamente a cualquier espacio, mientras que webOS 23 y el Magic Remote facilitan el acceso a todo tu contenido favorito.",
                'img' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Tipo de Pantalla' => 'OLED evo de 55 pulgadas, resolución 4K (3840 x 2160)',
                    'Tasa de Refresco' => '120Hz nativos',
                    'Procesador' => 'α9 AI Processor 4K Gen6',
                    'Calidad de Imagen' => 'Dolby Vision IQ, HDR10 Pro, HLG',
                    'Calidad de Sonido' => 'Dolby Atmos, 40W (2.2 canales)',
                    'Gaming' => 'G-Sync, FreeSync Premium, VRR, ALLM, 0.1ms de respuesta',
                    'Sistema Inteligente' => 'webOS 23',
                    'Control Remoto' => 'Magic Remote incluido',
                    'Conectividad' => '4x HDMI 2.1, 3x USB, Wi-Fi 5, Bluetooth 5.0',
                    'Diseño' => 'Ultra Slim con biseles mínimos'
                ]
            ],
            [
                'nombre' => 'TV Samsung Neo QLED 4K 65"',
                'marca' => 'Samsung', 'cat' => ['TV', 'Samsung', 'Televisores'],
                'precio' => 5200.00, 'stock' => 100,
                'desc' => "Vive la excelencia visual con el Samsung Neo QLED 4K QN90C. Gracias a la tecnología Quantum Matrix y sus Quantum Mini LEDs exclusivos, el televisor controla de forma ultradelgada y precisa la luz, entregando un contraste extremo y colores 100% reales. El Procesador Neural Quantum 4K utiliza la inteligencia artificial para optimizar cada escena, mientras que la pantalla antideslumbrante asegura que disfrutes de tu contenido sin molestos reflejos desde cualquier ángulo de visión. Además, con sonido Dolby Atmos integrado, la experiencia cinematográfica está garantizada en la comodidad de tu hogar.",
                'img' => 'https://images.unsplash.com/photo-1593784991095-a205069470b6?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Tipo de Pantalla' => 'Neo QLED de 65 pulgadas, resolución 4K (3840 x 2160)',
                    'Tasa de Refresco' => '120Hz (Hasta 144Hz en PC)',
                    'Tecnología de Imagen' => 'Quantum Matrix Technology, Neo Quantum HDR+',
                    'Procesador' => 'Neural Quantum Processor 4K',
                    'Calidad de Sonido' => 'Dolby Atmos, OTS+ (Object Tracking Sound+), Q-Symphony',
                    'Salida de Sonido' => '60W (4.2.2 canales)',
                    'Gaming' => 'Motion Xcelerator Turbo+, FreeSync Premium Pro',
                    'Sistema Inteligente' => 'Tizen OS con SmartThings Hub',
                    'Diseño' => 'NeoSlim Design',
                    'Conectividad' => '4x HDMI (120Hz/144Hz soportados), 2x USB, Wi-Fi 5, BT 5.2'
                ]
            ]
        ];
EOT;

$content = preg_replace('/\$productosData\s*=\s*\[.*?\];\s*(foreach \(\$productosData as \$idx => \$p\))/s', $newProductosData . "\n\n        \$1", $content);

$specsInsertCode = <<<EOT
            // Crear Especificaciones Técnicas
            if (isset(\$p['specs'])) {
                foreach (\$p['specs'] as \$clave => \$valor) {
                    \App\Models\ProductoEspecificacion::create([
                        'producto_id' => \$prod->id,
                        'clave' => \$clave,
                        'valor' => \$valor
                    ]);
                }
            }
EOT;

$content = preg_replace('/(\/\/ Crear Variante Principal)/s', $specsInsertCode . "\n\n            \$1", $content);

file_put_contents($file, $content);

echo "Success";
