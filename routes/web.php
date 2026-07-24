<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnaliticasController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;

// Nuevos controladores de Admin
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SettingController;
use App\Models\ConfiguracionSitio;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/catalogo', [HomeController::class, 'catalogo'])->name('catalogo');
Route::get('/api/search/live', [HomeController::class, 'liveSearch'])->name('api.search.live');
Route::get('/producto/{slug}', [HomeController::class, 'producto'])->name('producto');
Route::get('/seguimiento', [HomeController::class, 'seguimiento'])->name('seguimiento');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/nosotros', [PageController::class, 'nosotros'])->name('nosotros');
Route::get('/trabaja-con-nosotros', [PageController::class, 'trabajaConNosotros'])->name('trabaja');
Route::get('/terminos', [PageController::class, 'terminos'])->name('terminos');
Route::get('/privacidad', [PageController::class, 'privacidad'])->name('privacidad');
Route::get('/ayuda', [PageController::class, 'ayuda'])->name('ayuda');
Route::get('/devoluciones', [PageController::class, 'devoluciones'])->name('devoluciones');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');

Route::get('/libro-de-reclamaciones', function () {
    return Inertia::render('LibroReclamaciones');
})->name('libro-reclamaciones');
Route::post('/libro-de-reclamaciones', function () {
    // Para simplificar, simularemos el guardado y retornaremos éxito.
    return back()->with('success', 'Su reclamo/queja ha sido registrado con éxito. Le enviaremos una copia a su correo electrónico.');
});
// Rutas de Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/registro', [AuthController::class, 'showRegister'])->name('registro');
Route::post('/registro', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Validación DNI/RUC
// (Movido a routes/api.php)

// Google OAuth
use App\Http\Controllers\Auth\GoogleAuthController;
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

// Rutas del carrito de compras
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Rutas de Pago y Checkout (Niubiz y FedEx)
use App\Http\Controllers\ShippingController;
Route::post('/api/shipping/calculate', [ShippingController::class, 'calculate'])->name('shipping.calculate');
Route::post('/api/shipping/validate-address', [ShippingController::class, 'validateAddress'])->name('shipping.validate-address');

// Rutas de Pago Stripe
use App\Http\Controllers\StripePaymentController;
Route::get('/checkout', [StripePaymentController::class, 'checkout'])->name('checkout');
Route::post('/api/checkout/stripe/intent', [StripePaymentController::class, 'createIntent']);
Route::post('/api/checkout/apply-coupon', [StripePaymentController::class, 'applyCoupon']);
Route::post('/webhook/stripe', [StripePaymentController::class, 'webhook'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/checkout/success', [StripePaymentController::class, 'success'])->name('checkout.success');

// Rutas del comparador
use App\Http\Controllers\CompareController;
Route::get('/comparador', [CompareController::class, 'index'])->name('comparador.index');

// Ruta Pública de Escaneo QR (Comprobantes)
Route::get('/comprobante/{codigo_ticket}', [\App\Http\Controllers\Admin\InvoiceController::class, 'verComprobantePublico'])->name('comprobante.publico');
Route::get('/comprobante/ecommerce/{codigo}', [\App\Http\Controllers\InvoiceController::class, 'verComprobanteEcommerce'])->name('comprobante.ecommerce.publico');
Route::post('/comparador/add', [CompareController::class, 'add'])->name('comparador.add');
Route::post('/comparador/remove', [CompareController::class, 'remove'])->name('comparador.remove');
Route::post('/comparador/clear', [CompareController::class, 'clear'])->name('comparador.clear');

Route::get('/test-permiso', function() {
    if (!auth()->check()) return response()->json(['error' => 'Not logged in']);
    $user = auth()->user();
    return response()->json([
        'email' => $user->email,
        'esAdmin' => $user->esAdmin(),
        'tiene_dashboard' => $user->tienePermiso('ver_dashboard'),
        'roles' => $user->roles->pluck('nombre'),
        'permisos' => $user->getAllPermisos()
    ]);
});

// Rutas de Usuario (Wishlist, Mi Cuenta, etc)
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ListaDeseoController;

Route::middleware('auth')->group(function () {
    
    // Perfil Mi Cuenta
    Route::get('/perfil', [ProfileController::class, 'index'])->name('perfil');
    Route::get('/perfil/compras/{codigo}', [ProfileController::class, 'showOrder'])->name('perfil.compras.show');
    Route::get('/factura/ecommerce/{id}/descargar', [\App\Http\Controllers\InvoiceController::class, 'descargarComprobante'])->name('factura.ecommerce.descargar');
    Route::post('/perfil/update', [ProfileController::class, 'update'])->name('perfil.update');
    Route::post('/perfil/celular/solicitar-codigo', [ProfileController::class, 'requestPhoneUpdateOtp'])->middleware('throttle:3,1')->name('perfil.celular.solicitar');
    Route::post('/perfil/celular/verificar-codigo', [ProfileController::class, 'verifyPhoneUpdateOtp'])->name('perfil.celular.verificar');
    Route::post('/perfil/password', [ProfileController::class, 'updatePassword'])->name('perfil.password');
    Route::post('/perfil/direccion', [ProfileController::class, 'storeDireccion'])->name('perfil.direccion.store');
    Route::post('/perfil/direccion/{id}/principal', [ProfileController::class, 'setPrincipalDireccion'])->name('perfil.direccion.principal');
    Route::delete('/perfil/direccion/{id}', [ProfileController::class, 'destroyDireccion'])->name('perfil.direccion.destroy');

    // Tarjetas, Reembolso, Sesiones, Cuenta
    Route::post('/perfil/tarjetas', [ProfileController::class, 'storeTarjeta'])->name('perfil.tarjetas.store');
    Route::delete('/perfil/tarjetas/{id}', [ProfileController::class, 'destroyTarjeta'])->name('perfil.tarjetas.destroy');
    Route::post('/perfil/reembolso', [ProfileController::class, 'updateDatosReembolso'])->name('perfil.reembolso.update');
    Route::delete('/perfil/sesiones/{id}', [ProfileController::class, 'destroySession'])->name('perfil.sesiones.destroy');
    Route::delete('/perfil/cuenta', [ProfileController::class, 'destroyAccount'])->name('perfil.cuenta.destroy');

    // Listas de deseos múltiples
    Route::get('/wishlist/lists', [ListaDeseoController::class, 'getLists'])->name('wishlist.lists');
    Route::post('/wishlist/toggle', [ListaDeseoController::class, 'toggleWishlist'])->name('wishlist.toggle');
    Route::post('/wishlist/sync', [ListaDeseoController::class, 'syncWishlists'])->name('wishlist.sync');
    Route::post('/perfil/listas', [ListaDeseoController::class, 'storeLista'])->name('perfil.listas.store');
    Route::delete('/perfil/listas/{id}', [ListaDeseoController::class, 'destroyLista'])->name('perfil.listas.destroy');
    Route::post('/perfil/listas/items', [ListaDeseoController::class, 'storeListaItem'])->name('perfil.listas.items.store');
    Route::delete('/perfil/listas/items/{id}', [ListaDeseoController::class, 'destroyListaItem'])->name('perfil.listas.items.destroy');

    // Seguimiento Shippo
    Route::get('/perfil/seguimiento', [ShippingController::class, 'trackPage'])->name('perfil.seguimiento');
});

// Rutas de Administración
use App\Http\Controllers\Admin\AdminAuthController;

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login')->middleware('guest:admin');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('guest:admin');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout')->middleware('auth:admin');

Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'dashboard'])->name('admin.dashboard')->middleware('permiso:ver_dashboard');
    Route::get('/pedidos/exportar-pdf', [DashboardController::class, 'exportarPdf'])->name('admin.pedidos.exportar_pdf')->middleware('permiso:ver_dashboard');
    
    // Búsqueda Global
    Route::get('/buscar', [DashboardController::class, 'globalSearch'])->name('admin.buscar');

    // Almacenes
    Route::get('/almacenes', [\App\Http\Controllers\Admin\AlmacenController::class, 'index'])->name('admin.almacenes.index');
    Route::post('/almacenes', [\App\Http\Controllers\Admin\AlmacenController::class, 'store'])->name('admin.almacenes.store');
    Route::post('/almacenes/transferir', [\App\Http\Controllers\Admin\AlmacenController::class, 'transferir'])->name('admin.almacenes.transferir');
    Route::get('/almacenes/{id}/kardex', [\App\Http\Controllers\Admin\AlmacenController::class, 'kardex'])->name('admin.almacenes.kardex');
    Route::delete('/almacenes/{id}', [\App\Http\Controllers\Admin\AlmacenController::class, 'destroy'])->name('admin.almacenes.destroy');
    // Gastos
    Route::get('/gastos', [\App\Http\Controllers\Admin\GastoController::class, 'index'])->name('admin.gastos.index');
    Route::post('/gastos', [\App\Http\Controllers\Admin\GastoController::class, 'store'])->name('admin.gastos.store');
    Route::put('/gastos/{id}', [\App\Http\Controllers\Admin\GastoController::class, 'update'])->name('admin.gastos.update');
    Route::delete('/gastos/{id}', [\App\Http\Controllers\Admin\GastoController::class, 'destroy'])->name('admin.gastos.destroy');
    // Productos
    Route::middleware('permiso:ver_productos')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('admin.products');
        Route::get('/products/{id}', [ProductController::class, 'show'])->name('admin.products.show')->where('id', '[0-9]+');
    });
    Route::middleware('permiso:crear_producto')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('admin.products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('admin.products.store');
    });
    Route::middleware('permiso:editar_producto')->group(function () {
        Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
        Route::put('/products/{id}', [ProductController::class, 'update'])->name('admin.products.update');
    });
    Route::middleware('permiso:eliminar_producto')->group(function () {
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
    });

    // Banners (Eliminado)

    // Categorías
    Route::middleware('permiso:gestionar_categorias')->group(function () {
        Route::get('/categorias', [CategoryController::class, 'index'])->name('admin.categorias');
        Route::get('/categorias/create', [CategoryController::class, 'create'])->name('admin.categorias.create');
        Route::post('/categorias', [CategoryController::class, 'store'])->name('admin.categorias.store');
        Route::post('/api/categorias', [CategoryController::class, 'storeApi'])->name('api.categorias.store');
        Route::get('/categorias/{id}/edit', [CategoryController::class, 'edit'])->name('admin.categorias.edit');
        Route::put('/categorias/{id}', [CategoryController::class, 'update'])->name('admin.categorias.update');
        Route::delete('/categorias/{id}', [CategoryController::class, 'destroy'])->name('admin.categorias.destroy');
    });

    // Reseñas (Eliminado)

    // POS
    Route::middleware('permiso:pos.vender')->group(function () {
        Route::get('/pos', [\App\Http\Controllers\Admin\PosController::class, 'index'])->name('admin.pos');
        Route::get('/pos/buscar-productos', [\App\Http\Controllers\Admin\PosController::class, 'buscarProductos'])->name('admin.pos.buscar_productos');
        Route::get('/pos/historial', [\App\Http\Controllers\Admin\PosController::class, 'historial'])->name('admin.pos.historial');
        Route::post('/pos/venta', [\App\Http\Controllers\Admin\PosController::class, 'registrarVenta'])->name('admin.pos.venta');
        Route::get('/pos/buscar-cliente', [\App\Http\Controllers\Admin\PosController::class, 'buscarCliente'])->name('admin.pos.buscar_cliente');
        Route::get('/pos/ticket/{id}', [\App\Http\Controllers\Admin\InvoiceController::class, 'generarFacturaPos'])->name('admin.pos.ticket');
        Route::get('/factura/{id}/descargar', [\App\Http\Controllers\Admin\InvoiceController::class, 'generarFacturaPos'])->name('admin.factura.descargar');
    });

    // Caja
    Route::post('/caja/aperturar', [\App\Http\Controllers\Admin\CajaController::class, 'aperturar'])->name('admin.caja.aperturar');
    Route::post('/caja/cerrar', [\App\Http\Controllers\Admin\CajaController::class, 'cerrar'])->name('admin.caja.cerrar');
    Route::post('/caja/movimiento', [\App\Http\Controllers\Admin\CajaController::class, 'movimiento'])->name('admin.caja.movimiento');

    // Compras
    Route::get('/compras', [\App\Http\Controllers\Admin\CompraController::class, 'index'])->name('admin.compras.index');
    Route::post('/compras', [\App\Http\Controllers\Admin\CompraController::class, 'store'])->name('admin.compras.store');
    Route::post('/compras/{id}/completar', [\App\Http\Controllers\Admin\CompraController::class, 'completar'])->name('admin.compras.completar');
    Route::get('/compras/{id}', [\App\Http\Controllers\Admin\CompraController::class, 'show'])->name('admin.compras.show');
    Route::delete('/compras/{id}', [\App\Http\Controllers\Admin\CompraController::class, 'destroy'])->name('admin.compras.destroy');

    // Inventario
    Route::get('/inventario', [\App\Http\Controllers\Admin\InventarioController::class, 'dashboard'])->name('admin.inventario');

    // Zonas
    Route::get('/zonas', [\App\Http\Controllers\Admin\ZonaController::class, 'index'])->name('admin.zonas.index');
    Route::post('/zonas', [\App\Http\Controllers\Admin\ZonaController::class, 'store'])->name('admin.zonas.store');
    Route::put('/zonas/{id}', [\App\Http\Controllers\Admin\ZonaController::class, 'update'])->name('admin.zonas.update');
    Route::delete('/zonas/{id}', [\App\Http\Controllers\Admin\ZonaController::class, 'destroy'])->name('admin.zonas.destroy');

    // Metodos Pago
    Route::get('/metodos-pago', [\App\Http\Controllers\Admin\MetodoPagoController::class, 'index'])->name('admin.metodospago.index');
    Route::post('/metodos-pago', [\App\Http\Controllers\Admin\MetodoPagoController::class, 'store'])->name('admin.metodospago.store');
    Route::put('/metodos-pago/{id}', [\App\Http\Controllers\Admin\MetodoPagoController::class, 'update'])->name('admin.metodospago.update');
    Route::delete('/metodos-pago/{id}', [\App\Http\Controllers\Admin\MetodoPagoController::class, 'destroy'])->name('admin.metodospago.destroy');
    
    // Marcas
    Route::middleware('permiso:gestionar_marcas')->group(function () {
        Route::get('/marcas', [BrandController::class, 'index'])->name('admin.marcas');
        Route::get('/marcas/create', [BrandController::class, 'create'])->name('admin.marcas.create');
        Route::post('/marcas', [BrandController::class, 'store'])->name('admin.marcas.store');
        Route::get('/marcas/{id}/edit', [BrandController::class, 'edit'])->name('admin.marcas.edit');
        Route::put('/marcas/{id}', [BrandController::class, 'update'])->name('admin.marcas.update');
        Route::delete('/marcas/{id}', [BrandController::class, 'destroy'])->name('admin.marcas.destroy');
    });

    // Promociones (Eliminado)

    // Proveedores
    Route::middleware('permiso:ver_productos')->group(function () {
        Route::resource('proveedores', \App\Http\Controllers\Admin\ProveedorController::class);
    });

    // Cupones
    Route::middleware('permiso:gestionar_cupones')->group(function () {
        Route::resource('cupones', \App\Http\Controllers\Admin\CuponController::class);
    });

    // Pedidos
    Route::middleware('permiso:ver_pedidos')->group(function () {
        Route::get('/pedidos', [OrderController::class, 'index'])->name('admin.pedidos');
        Route::get('/pedidos/{id}', [OrderController::class, 'show'])->name('admin.pedidos.show');
        Route::get('/pedidos/{id}/factura', [OrderController::class, 'facturaVista'])->name('admin.pedidos.factura');
    });
    Route::middleware('permiso:editar_pedido')->group(function () {
        Route::put('/pedidos/{id}/estado', [OrderController::class, 'updateEstado'])->name('admin.pedidos.update_estado');
        Route::post('/pedidos/{id}/reembolsar', [OrderController::class, 'reembolsar'])->name('admin.pedidos.reembolsar');
    });

    // Ajustes / Configuración / Permisos
    Route::middleware('permiso:gestionar_ajustes')->group(function () {
        Route::get('/ajustes', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('admin.ajustes');
        Route::post('/ajustes', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('admin.ajustes.update');
        
        // CMS Banners
        Route::get('/banners', [\App\Http\Controllers\Admin\BannerController::class, 'index'])->name('admin.banners.index');
        Route::post('/banners', [\App\Http\Controllers\Admin\BannerController::class, 'store'])->name('admin.banners.store');
        Route::post('/banners/{id}', [\App\Http\Controllers\Admin\BannerController::class, 'update'])->name('admin.banners.update');
        Route::delete('/banners/{id}', [\App\Http\Controllers\Admin\BannerController::class, 'destroy'])->name('admin.banners.destroy');
        
        Route::get('/ajustes/permisos', [SettingController::class, 'rolesIndex'])->name('admin.roles.index');
        Route::post('/ajustes/permisos/sync', [SettingController::class, 'rolesSyncPermisos'])->name('admin.roles.sync');
        Route::post('/ajustes/roles', [SettingController::class, 'storeRole'])->name('admin.roles.store');
        Route::delete('/ajustes/roles/{id}', [SettingController::class, 'destroyRole'])->name('admin.roles.destroy');
    });

    // Cupones (Eliminado)

    // Clientes
    Route::middleware('permiso:ver_usuarios')->group(function () {
        Route::get('/clientes', [CustomerController::class, 'index'])->name('admin.clientes');
    });
    
    Route::middleware('permiso:editar_usuario')->group(function () {
        Route::get('/clientes/create', [CustomerController::class, 'create'])->name('admin.clientes.create');
        Route::post('/clientes', [CustomerController::class, 'store'])->name('admin.clientes.store');
        Route::get('/clientes/{id}/edit', [CustomerController::class, 'edit'])->name('admin.clientes.edit');
        Route::put('/clientes/{id}', [CustomerController::class, 'update'])->name('admin.clientes.update');
        Route::delete('/clientes/{id}', [CustomerController::class, 'destroy'])->name('admin.clientes.destroy');
        Route::post('/clientes/{id}/bloquear', [CustomerController::class, 'toggleBloqueo'])->name('admin.clientes.bloquear');
        Route::post('/clientes/{id}/reset-password', [CustomerController::class, 'resetPassword'])->name('admin.clientes.reset_password');
        
        // Rutas para Notas CRM
        Route::post('/clientes/{id}/notas', [CustomerController::class, 'storeNota'])->name('admin.clientes.notas.store');
        Route::delete('/clientes/{id}/notas/{notaId}', [CustomerController::class, 'destroyNota'])->name('admin.clientes.notas.destroy');
    });

    // Trabajadores
    Route::middleware('permiso:ver_usuarios')->group(function () {
        Route::get('/trabajadores', [\App\Http\Controllers\Admin\StaffController::class, 'index'])->name('admin.trabajadores');
    });
    
    Route::middleware('permiso:editar_usuario')->group(function () {
        Route::get('/trabajadores/create', [\App\Http\Controllers\Admin\StaffController::class, 'create'])->name('admin.trabajadores.create');
        Route::post('/trabajadores', [\App\Http\Controllers\Admin\StaffController::class, 'store'])->name('admin.trabajadores.store');
        Route::get('/trabajadores/{id}/edit', [\App\Http\Controllers\Admin\StaffController::class, 'edit'])->name('admin.trabajadores.edit');
        Route::put('/trabajadores/{id}', [\App\Http\Controllers\Admin\StaffController::class, 'update'])->name('admin.trabajadores.update');
        Route::delete('/trabajadores/{id}', [\App\Http\Controllers\Admin\StaffController::class, 'destroy'])->name('admin.trabajadores.destroy');
        Route::post('/trabajadores/{id}/bloquear', [\App\Http\Controllers\Admin\StaffController::class, 'toggleBloqueo'])->name('admin.trabajadores.bloquear');
        Route::post('/trabajadores/{id}/reset-password', [\App\Http\Controllers\Admin\StaffController::class, 'resetPassword'])->name('admin.trabajadores.reset_password');
    });

    // Roles y Permisos
    Route::middleware('permiso:usuarios.gestionar')->group(function () {
        Route::get('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'index'])->name('admin.roles');
        Route::get('/roles/create', [\App\Http\Controllers\Admin\RoleController::class, 'create'])->name('admin.roles.create');
        Route::post('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'store'])->name('admin.roles.store');
        Route::get('/roles/{id}/edit', [\App\Http\Controllers\Admin\RoleController::class, 'edit'])->name('admin.roles.edit');
        Route::put('/roles/{id}', [\App\Http\Controllers\Admin\RoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('/roles/{id}', [\App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('admin.roles.destroy');
    });

    // Zonas de Envío
    Route::middleware('permiso:usuarios.gestionar')->group(function () {
        Route::get('/clientes/{id}', [CustomerController::class, 'show'])->name('admin.clientes.show');
        Route::get('/trabajadores/{id}', [\App\Http\Controllers\Admin\StaffController::class, 'show'])->name('admin.trabajadores.show');
    });

    // Exportar CSV
    Route::middleware('permiso:ver_productos')->group(function () {
        Route::get('/exportar/productos', [ProductController::class, 'export'])->name('admin.exportar.productos');
    });
    Route::middleware('permiso:ver_pedidos')->group(function () {
        Route::get('/exportar/pedidos', [OrderController::class, 'export'])->name('admin.exportar.pedidos');
    });
    Route::middleware('permiso:ver_usuarios')->group(function () {
        Route::get('/exportar/clientes', [CustomerController::class, 'export'])->name('admin.exportar.clientes');
        Route::get('/exportar/trabajadores', [\App\Http\Controllers\Admin\StaffController::class, 'export'])->name('admin.exportar.trabajadores');
    });
});
