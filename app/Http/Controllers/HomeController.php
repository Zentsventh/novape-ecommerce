<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ConfiguracionSitio;
use App\Models\Pedido;

class HomeController extends Controller
{
    public function index()
    {
        // Obtener categorías padre junto con 10 productos activos, usando Caché
        $categoriaProductos = \Illuminate\Support\Facades\Cache::remember('home_categorias', 3600, function () {
            $categorias = Categoria::whereNull('categoria_padre_id')
                ->where(function ($q) {
                    $q->whereNotIn('slug', ['cyber-bombas', 'retiro-inmediato'])->orWhereNull('slug');
                })
                ->with(['subcategorias', 'productos' => function ($query) {
                    $query->where('producto.activo', 1)
                          ->with(['marca', 'variantes', 'imagenes']);
                }])
                ->get();

            return $categorias->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'nombre' => $cat->nombre,
                    'descripcion' => $cat->descripcion,
                    'subcategorias' => $cat->subcategorias->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'nombre' => $sub->nombre
                        ];
                    }),
                    'productos' => $cat->productos->take(10)->map(function ($prod) {
                        return $this->formatProducto($prod);
                    }),
                ];
            });
        });

        // Obtener productos de "Lo mejor de la semana" (últimos productos activos)
        $mejorSemana = \Illuminate\Support\Facades\Cache::remember('home_mejor_semana', 3600, function () {
            $productos = Producto::where('activo', 1)
                ->with(['marca', 'variantes', 'imagenes'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            return $productos->map(function ($prod) {
                return $this->formatProducto($prod);
            });
        });

        $now = now()->toDateTimeString();
        $banners = \Illuminate\Support\Facades\DB::table('banners')
            ->where('activo', 1)
            ->where('posicion', 'hero')
            ->where(function($q) use ($now) {
                $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $now);
            })
            ->where(function($q) use ($now) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $now);
            })
            ->orderBy('orden')
            ->get();

        // Obtener logo desde configuración
        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        return Inertia::render('Home', [
            'appName' => config('app.name'),
            'categoriaProductos' => $categoriaProductos,
            'mejorSemana' => $mejorSemana,
            'banners' => $banners,
            'logoUrl' => $logoUrl,
        ]);
    }

    private static $ecommerceStocksMemo = [];
    private static $almacenEcommerceIdMemo = null;

    private function preloadStocks($varianteIds)
    {
        if (self::$almacenEcommerceIdMemo === null) {
            self::$almacenEcommerceIdMemo = \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
        }
        $toLoad = array_diff($varianteIds, array_keys(self::$ecommerceStocksMemo));
        if (!empty($toLoad)) {
            $stocks = \Illuminate\Support\Facades\DB::table('stock_almacen')
                ->where('almacen_id', self::$almacenEcommerceIdMemo)
                ->whereIn('variante_id', $toLoad)
                ->pluck('cantidad', 'variante_id')
                ->toArray();
            foreach($toLoad as $id) {
                self::$ecommerceStocksMemo[$id] = $stocks[$id] ?? 0;
            }
        }
    }

    /**
     * Formatea un producto para la vista, calculando precios, descuentos e imagen.
     * Asume que las relaciones (marca, variantes, imagenes, historialPrecios) ya están precargadas.
     */
    private function formatProducto(Producto $prod)
    {
        // Variante activa (ya filtradas en el eager load)
        $variante = $prod->variantes->first();
        $precio_actual = $variante ? (float) $variante->precio : 0;

        // Imagen principal
        $imagen = $prod->imagenes->first();
        $imagen_url = $imagen ? $imagen->url : null;

        // Precio anterior (deshabilitado ya que se simplificó la base de datos)
        $precio_anterior = null;

        // Calcular descuento
        $descuento = 0;
        
        $isBombaCyber = $prod->categorias && $prod->categorias->contains('slug', 'cyber-bombas');
        $isRetiro = $prod->categorias && $prod->categorias->contains('slug', 'retiro-inmediato');
        
        if ($isBombaCyber || $isRetiro) {
            $descuento = 15 + ($prod->id % 45); // Descuento determinístico entre 15% y 59%
            $precio_anterior = round($precio_actual / (1 - ($descuento / 100)), 2);
        }

        // Obtener stock E-commerce
        $stock = 0;
        if ($variante) {
            $vId = $variante->id;
            if (self::$almacenEcommerceIdMemo === null) {
                self::$almacenEcommerceIdMemo = \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
            }
            if (!array_key_exists($vId, self::$ecommerceStocksMemo)) {
                $stockRow = \Illuminate\Support\Facades\DB::table('stock_almacen')
                    ->where('almacen_id', self::$almacenEcommerceIdMemo)
                    ->where('variante_id', $vId)
                    ->first();
                self::$ecommerceStocksMemo[$vId] = $stockRow ? (int)$stockRow->cantidad : 0;
            }
            $stock = self::$ecommerceStocksMemo[$vId];
        }

        return (object)[
            'id' => $prod->id,
            'nombre' => $prod->nombre,
            'slug' => $prod->slug ?? null,
            'descripcion' => $prod->descripcion,
            'garantias' => $prod->garantias,
            'marca' => $prod->marca ? $prod->marca->nombre : null,
            'marca_id' => $prod->marca_id,
            'precio_actual' => $precio_actual,
            'imagen' => $imagen_url,
            'precio_anterior' => $precio_anterior,
            'descuento' => $descuento,
            'stock' => $stock,
            'categorias' => $prod->categorias ? $prod->categorias->pluck('slug')->toArray() : [],
        ];
    }

    /**
     * Aplica el algoritmo de búsqueda inteligente NLP (Amazon/Falabella style).
     */
    private function applySmartSearch($query, string $search)
    {
        // 1. Limpieza inicial
        $search = mb_strtolower(trim($search), 'UTF-8');
        if (empty($search)) return $query;

        // 2. Diccionario de Stop Words (Ruido gramatical)
        $stopWords = [' de ', ' para ', ' con ', ' el ', ' la ', ' los ', ' las ', ' un ', ' una ', ' unos ', ' unas ', ' en '];
        $cleanSearch = str_replace($stopWords, ' ', ' ' . $search . ' ');
        $cleanSearch = trim(preg_replace('/\s+/', ' ', $cleanSearch));

        // 3. Tokenización
        $rawTerms = array_filter(explode(' ', $cleanSearch));

        // 4. Diccionario de Sinónimos (Expansión)
        $synonyms = [
            'celular' => ['smartphone', 'movil', 'teléfono', 'telefono', 'iphone'],
            'smartphone' => ['celular', 'movil', 'teléfono', 'telefono', 'iphone'],
            'laptop' => ['portatil', 'portátil', 'notebook', 'computadora', 'pc', 'macbook'],
            'audifono' => ['auricular', 'headset', 'casco', 'earpod', 'airpod'],
            'auricular' => ['audifono', 'headset', 'casco', 'earpod', 'airpod'],
            'nevera' => ['refrigeradora', 'refrigerador', 'frigorifico'],
            'refrigeradora' => ['nevera', 'refrigerador', 'frigorifico'],
            'televisor' => ['pantalla', 'smart tv', 'televisión'],
            'televisores' => ['pantalla', 'smart tv', 'televisión'],
            'pc' => ['computadora', 'laptop', 'desktop', 'ordenador']
        ];

        $expandedTermsGroup = [];

        // 5. Lematización y Expansión por cada término
        foreach ($rawTerms as $term) {
            $variations = [$term];

            // Singularización básica (si termina en s o es, quitamos)
            if (strlen($term) > 3) {
                if (substr($term, -2) === 'es') {
                    $variations[] = substr($term, 0, -2);
                } elseif (substr($term, -1) === 's') {
                    $variations[] = substr($term, 0, -1);
                }
            }
            
            // Añadir sinónimos
            foreach ($variations as $var) {
                if (isset($synonyms[$var])) {
                    $variations = array_merge($variations, $synonyms[$var]);
                }
            }

            // Quitar duplicados
            $expandedTermsGroup[] = array_unique($variations);
        }

        // 6. Matriz cruzada (AND para cada grupo de términos, OR para sus variaciones)
        $query->where(function($q) use ($expandedTermsGroup) {
            foreach ($expandedTermsGroup as $variations) {
                $q->where(function($subQ) use ($variations) {
                    foreach ($variations as $var) {
                        $subQ->orWhere('producto.nombre', 'like', '%' . $var . '%')
                             ->orWhere('producto.descripcion', 'like', '%' . $var . '%')
                             ->orWhereHas('marca', function ($m) use ($var) {
                                 $m->where('nombre', 'like', '%' . $var . '%');
                             })
                             ->orWhereHas('categorias', function ($c) use ($var) {
                                 $c->where('categoria.nombre', 'like', '%' . $var . '%');
                             });
                    }
                });
            }
        });

        $escapedSearch = \Illuminate\Support\Facades\DB::getPdo()->quote('%' . $search . '%');
        $exactSearch = \Illuminate\Support\Facades\DB::getPdo()->quote($search);
        
        $query->orderByRaw("CASE 
            WHEN producto.nombre = {$exactSearch} THEN 1
            WHEN producto.nombre LIKE {$escapedSearch} THEN 2
            ELSE 3
        END ASC");

        return $query;
    }

    /**
     * Búsqueda predictiva AJAX ultrarrápida e Inteligente
     */
    public function liveSearch(\Illuminate\Http\Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['productos' => [], 'marcas' => [], 'categorias' => [], 'sugerencias' => []]);
        }

        $query = Producto::where('activo', 1)->with(['marca', 'variantes', 'imagenes', 'categorias']);
        
        $this->applySmartSearch($query, $q);

        // Obtenemos hasta 40 productos para extraer una mejor variedad de marcas y categorias
        $productos = $query->limit(40)->get();

        $formateados = $productos->take(6)->map(function ($p) {
            return $this->formatProducto($p);
        });

        // Extraer marcas únicas
        $marcas = $productos->pluck('marca.nombre')->filter()->unique()->values();

        // Completar con las marcas globales más populares si hay menos de 3
        if ($marcas->count() < 3) {
            $topMarcas = \App\Models\Marca::withCount('productos')
                ->orderBy('productos_count', 'desc')
                ->limit(4)
                ->pluck('nombre');
            $marcas = $marcas->merge($topMarcas)->unique()->values();
        }
        $marcas = $marcas->take(4);

        // Extraer categorias únicas
        $categorias = $productos->pluck('categorias')->flatten()->pluck('nombre')->filter()->unique()->values();

        // Completar con las categorías globales más populares si hay menos de 3
        if ($categorias->count() < 3) {
            $topCategorias = \App\Models\Categoria::whereNull('categoria_padre_id')
                ->where(function ($q) {
                    $q->whereNotIn('slug', ['cyber-bombas', 'retiro-inmediato'])->orWhereNull('slug');
                })
                ->withCount('productos')
                ->orderBy('productos_count', 'desc')
                ->limit(4)
                ->pluck('nombre');
            $categorias = $categorias->merge($topCategorias)->unique()->values();
        }
        $categorias = $categorias->take(4);

        // Búsquedas sugeridas
        $sugerencias = [];
        if ($categorias->count() > 0) {
            $sugerencias[] = $q . ' en ' . $categorias->first();
        }
        if ($marcas->count() > 0) {
            $sugerencias[] = $marcas->first() . ' ' . $q;
        }

        return response()->json([
            'productos' => $formateados,
            'marcas' => $marcas,
            'categorias' => $categorias,
            'sugerencias' => $sugerencias
        ]);
    }

    /**
     * Catálogo público con filtros por categoría, subcategoría, marca y rango de precio.
     */
    public function catalogo(\Illuminate\Http\Request $request)
    {
        $categoriaParam = $request->query('categoria');
        $subcategoriaParam = $request->query('subcategoria');
        $marcaFilter = $request->query('marca');
        $precioMin = $request->query('precio_min');
        $precioMax = $request->query('precio_max');
        $searchQuery = $request->query('q');
        $sort = $request->query('sort', 'relevancia');

        // Obtener categorías padre para el breadcrumb/sidebar
        $categorias = Categoria::whereNull('categoria_padre_id')
            ->where(function ($q) {
                $q->whereNotIn('slug', ['cyber-bombas', 'retiro-inmediato'])->orWhereNull('slug');
            })
            ->with('subcategorias')
            ->get();

        // Construir query de productos
        $query = Producto::where('activo', 1)
            ->with(['marca', 'variantes', 'imagenes', 'categorias']);

        // Filtrar por categoría o subcategoría
        $categoriaActiva = null;
        $subcategoriaActiva = null;

        if ($subcategoriaParam && $categoriaParam) {
            $catPadre = Categoria::where('nombre', $categoriaParam)->whereNull('categoria_padre_id')->first();
            if ($catPadre) {
                $subcat = Categoria::where('nombre', $subcategoriaParam)->where('categoria_padre_id', $catPadre->id)->first();
                if ($subcat) {
                    $subcategoriaActiva = $subcat;
                    $categoriaActiva = $catPadre;
                    $query->whereHas('categorias', function($q) use ($subcat) {
                        $q->where('categoria.id', $subcat->id);
                    });
                }
            }
        } elseif ($subcategoriaParam) {
            $subcat = Categoria::where('nombre', $subcategoriaParam)->first();
            if ($subcat) {
                $subcategoriaActiva = $subcat;
                $categoriaActiva = $subcat->padre;
                $query->whereHas('categorias', function($q) use ($subcat) {
                    $q->where('categoria.id', $subcat->id);
                });
            }
        } elseif ($categoriaParam) {
            $cat = Categoria::where('nombre', $categoriaParam)->whereNull('categoria_padre_id')->first();
            if ($cat) {
                $categoriaActiva = $cat;
                // Incluir productos de esta categoría padre O cualquiera de sus subcategorías
                $catIds = $cat->subcategorias->pluck('id')->push($cat->id)->toArray();
                $query->whereHas('categorias', function($q) use ($catIds) {
                    $q->whereIn('categoria.id', $catIds);
                });
            }
        }

        // Implicit category from search query for UI highlighting
        if (!$categoriaActiva && !$subcategoriaActiva && $searchQuery) {
            $matchedCat = Categoria::where('nombre', 'like', $searchQuery)->first();
            if ($matchedCat) {
                if (is_null($matchedCat->categoria_padre_id)) {
                    $categoriaActiva = $matchedCat;
                } else {
                    $subcategoriaActiva = $matchedCat;
                    $categoriaActiva = $matchedCat->padre;
                }
            }
        }

        // Filtrar por búsqueda
        if ($searchQuery) {
            $this->applySmartSearch($query, $searchQuery);
        }

        // Obtener todas las marcas disponibles para los productos encontrados HASTA AHORA (sin el filtro de marca)
        $productosParaMarcas = (clone $query)->get();
        $marcasDisponibles = $productosParaMarcas->groupBy(fn($p) => $p->marca ? $p->marca->nombre : 'Sin marca')
            ->map(fn($items, $nombre) => ['nombre' => $nombre, 'count' => $items->count()])
            ->values()
            ->sortByDesc('count')
            ->values();

        // Filtrar por marca
        if ($marcaFilter) {
            $query->whereHas('marca', function($q) use ($marcaFilter) {
                $q->where('nombre', $marcaFilter);
            });
        }

        // Obtener todos los productos para calcular rango de precios y marcas disponibles
        $productos = $query->get();

        // Preload para evitar N+1
        $varianteIds = $productos->map(fn($p) => $p->variantes->first()?->id)->filter()->toArray();
        $this->preloadStocks($varianteIds);

        // Formatear productos
        $productosFormateados = $productos->map(function($prod) {
            return $this->formatProducto($prod);
        });

        // Filtrar por precio (post-query, ya que el precio viene de variante)
        if ($precioMin !== null && $precioMin !== '') {
            $productosFormateados = $productosFormateados->filter(fn($p) => $p->precio_actual >= (float)$precioMin);
        }
        if ($precioMax !== null && $precioMax !== '') {
            $productosFormateados = $productosFormateados->filter(fn($p) => $p->precio_actual <= (float)$precioMax);
        }

        // Sort collection
        if ($sort === 'precio_asc') {
            $productosFormateados = $productosFormateados->sortBy('precio_actual');
        } elseif ($sort === 'precio_desc') {
            $productosFormateados = $productosFormateados->sortByDesc('precio_actual');
        } elseif ($sort === 'descuento') {
            $productosFormateados = $productosFormateados->sortByDesc('descuento');
        } else {
            // Relevance (default)
            if (!$searchQuery) {
                $productosFormateados = $productosFormateados->sortByDesc('id');
            }
            // If there IS a searchQuery, keep the order from the database (applySmartSearch already sorted it)
        }

        // Logo
        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        // Banners Laterales
        $now = now()->toDateTimeString();
        $lateralBanners = \Illuminate\Support\Facades\DB::table('banners')
            ->where('activo', 1)
            ->where('posicion', 'lateral')
            ->where(function($q) use ($now) {
                $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $now);
            })
            ->where(function($q) use ($now) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $now);
            })
            ->orderBy('orden')
            ->get();

        return Inertia::render('Catalogo', [
            'productos' => $productosFormateados->values(),
            'categorias' => $categorias->map(function($cat) {
                return [
                    'id' => $cat->id,
                    'nombre' => $cat->nombre,
                    'subcategorias' => $cat->subcategorias->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre]),
                ];
            }),
            'marcasDisponibles' => $marcasDisponibles,
            'categoriaActiva' => $categoriaActiva ? $categoriaActiva->nombre : null,
            'subcategoriaActiva' => $subcategoriaActiva ? $subcategoriaActiva->nombre : null,
            'filtros' => [
                'marca' => $marcaFilter,
                'precio_min' => $precioMin,
                'precio_max' => $precioMax,
                'q' => $searchQuery,
                'sort' => $sort,
            ],
            'totalProductos' => $productosFormateados->count(),
            'logoUrl' => $logoUrl,
            'lateralBanners' => $lateralBanners,
        ]);
    }

    /**
     * Seguimiento público de pedidos por código.
     */
    public function seguimiento(\Illuminate\Http\Request $request)
    {
        $codigo = trim((string) $request->query('codigo', ''));
        $pedido = null;
        $error = null;
        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        if ($codigo !== '') {
            $query = Pedido::with(['envio']);

            if (ctype_digit($codigo)) {
                $query->where('id', (int) $codigo)->orWhere('codigo', $codigo);
            } else {
                $query->where('codigo', $codigo);
            }

            $pedido = $query->first();

            if (!$pedido) {
                $error = 'No se encontro ningun pedido con ese codigo.';
            }
        }

        return Inertia::render('Seguimiento', [
            'codigo' => $codigo,
            'pedido' => $pedido ? [
                'id' => $pedido->id,
                'codigo' => $pedido->codigo,
                'estado' => $pedido->estado,
                'total' => (float) $pedido->total,
                'fecha' => $pedido->created_at ? $pedido->created_at->toDateTimeString() : null,
                'envio' => $pedido->envio ? [
                    'estado' => $pedido->envio->estado,
                    'tracking' => $pedido->envio->tracking,
                ] : null,
            ] : null,
            'error' => $error,
            'logoUrl' => $logoUrl,
        ]);
    }

    public function producto($slugOrId)
    {
        $producto = Producto::with([
                'marca', 
                'variantes', 
                'imagenes', 
                'productoEspecificaciones',
                'categorias'
            ])
            ->where('activo', true)
            ->where(function($query) use ($slugOrId) {
                $query->where('slug', $slugOrId)
                      ->orWhere('id', $slugOrId);
            })
            ->firstOrFail();

        // Obtener reviews aprobadas (Vacío ya que se eliminó la tabla)
        $reviews = collect([]);

        $promedioEstrellas = 0;

        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        // Cross-selling: Productos recomendados basados en la misma categoría
        $categoriasIds = $producto->categorias->pluck('id')->toArray();
        $recomendados = collect();
        if (!empty($categoriasIds)) {
            $recomendadosQuery = Producto::where('activo', 1)
                ->where('id', '!=', $producto->id)
                ->whereHas('categorias', function($q) use ($categoriasIds) {
                    $q->whereIn('categoria.id', $categoriasIds);
                })
                ->with(['marca', 'variantes', 'imagenes'])
                ->inRandomOrder()
                ->limit(4)
                ->get();
            
            $recomendados = $recomendadosQuery->map(function($p) {
                return $this->formatProducto($p);
            });
        }

        $categorias = \App\Models\Categoria::whereNull('categoria_padre_id')
            ->where(function ($q) {
                $q->whereNotIn('slug', ['cyber-bombas', 'retiro-inmediato'])->orWhereNull('slug');
            })
            ->with('subcategorias')
            ->get();

        return Inertia::render('Producto', [
            'categorias' => $categorias->map(function($cat) {
                return [
                    'id' => $cat->id,
                    'nombre' => $cat->nombre,
                    'subcategorias' => $cat->subcategorias->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre]),
                ];
            }),
            'producto' => $this->formatProducto($producto),
            'detalles' => [
                'especificaciones' => $producto->productoEspecificaciones->map(function($pe) {
                    return [
                        'nombre' => $pe->clave,
                        'valor' => $pe->valor
                    ];
                }),
                'todas_imagenes' => $producto->imagenes->pluck('url')
            ],
            'reviews' => $reviews->map(function($r) {
                return [
                    'id' => $r->id,
                    'calificacion' => $r->calificacion,
                    'comentario' => $r->comentario,
                    'usuario' => $r->usuario->nombres . ' ' . substr($r->usuario->apellidos, 0, 1) . '.',
                    'fecha' => $r->created_at->format('d/m/Y')
                ];
            }),
            'promedioEstrellas' => $promedioEstrellas,
            'totalReviews' => $reviews->count(),
            'logoUrl' => $logoUrl,
            'recomendados' => $recomendados
        ]);
    }
}
