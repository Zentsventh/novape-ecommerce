<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;
use Carbon\Carbon;

class SitemapController extends Controller
{
    public function index()
    {
        $productos = Producto::where('activo', 1)->get();
        $categorias = Categoria::all();

        // XML declaration and urlset tag
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Home URL
        $xml .= $this->createUrlNode(url('/'), Carbon::now()->toAtomString(), '1.0', 'daily');

        // Static Pages
        $staticPages = ['/catalogo', '/nosotros', '/terminos', '/privacidad', '/devoluciones', '/faq'];
        foreach ($staticPages as $page) {
            $xml .= $this->createUrlNode(url($page), Carbon::now()->toAtomString(), '0.8', 'weekly');
        }

        // Categorias
        foreach ($categorias as $categoria) {
            $url = url('/catalogo?categoria=' . urlencode($categoria->nombre));
            $xml .= $this->createUrlNode($url, $categoria->updated_at ? $categoria->updated_at->toAtomString() : Carbon::now()->toAtomString(), '0.9', 'weekly');
        }

        // Productos
        foreach ($productos as $producto) {
            // Asume que usas slug o ID para la ruta del producto
            $url = url('/producto/' . ($producto->slug ? $producto->slug : $producto->id));
            $xml .= $this->createUrlNode($url, $producto->updated_at ? $producto->updated_at->toAtomString() : Carbon::now()->toAtomString(), '0.8', 'daily');
        }

        $xml .= '</urlset>';

        return response($xml)->header('Content-Type', 'text/xml');
    }

    private function createUrlNode($loc, $lastmod, $priority, $changefreq)
    {
        return '<url>' .
            '<loc>' . htmlspecialchars($loc) . '</loc>' .
            '<lastmod>' . $lastmod . '</lastmod>' .
            '<changefreq>' . $changefreq . '</changefreq>' .
            '<priority>' . $priority . '</priority>' .
        '</url>';
    }
}
