<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;
use Illuminate\Support\Facades\File;

class BannerController extends Controller
{
    public function index()
    {
        $banners = DB::table('banners')->orderBy('orden', 'asc')->get();
        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        return Inertia::render('Admin/Banners/Index', [
            'banners' => $banners,
            'logoUrl' => $logoUrl
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'imagen' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'enlace_url' => 'nullable|string|max:500',
            'fecha_inicio' => 'nullable|date|after_or_equal:today',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $imagenUrl = '';
        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = public_path('images/banners');
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
            $file->move($path, $filename);
            $imagenUrl = '/images/banners/' . $filename;
        }

        $orden = DB::table('banners')->where('posicion', 'hero')->max('orden') + 1;

        DB::table('banners')->insert([
            'titulo' => $request->titulo,
            'subtitulo' => $request->subtitulo,
            'imagen_url' => $imagenUrl,
            'enlace_url' => $request->enlace_url,
            'posicion' => 'hero',
            'orden' => $orden,
            'activo' => true,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Banner creado exitosamente.');
    }

    public function update(Request $request, $id)
    {
        // Toggle de activo
        if ($request->has('activo') && !$request->has('titulo')) {
            DB::table('banners')->where('id', $id)->update([
                'activo' => $request->activo,
                'updated_at' => now(),
            ]);
            return redirect()->back();
        }

        $request->validate([
            'titulo' => 'required|string|max:255',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $updateData = [
            'titulo' => $request->titulo,
            'subtitulo' => $request->subtitulo,
            'enlace_url' => $request->enlace_url,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'updated_at' => now(),
        ];

        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = public_path('images/banners');
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
            $file->move($path, $filename);
            $updateData['imagen_url'] = '/images/banners/' . $filename;
        }

        DB::table('banners')->where('id', $id)->update($updateData);

        return redirect()->back()->with('success', 'Banner actualizado.');
    }

    public function destroy($id)
    {
        DB::table('banners')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Banner eliminado.');
    }
}
