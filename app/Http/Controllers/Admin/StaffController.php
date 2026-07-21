<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Usuario;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        \Log::info('StaffController index reached');
        $query = Usuario::query()->with('roles');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%")
                  ->orWhere('dni', 'like', "%{$buscar}%");
            });
        }

        // Solo mostrar usuarios que SÍ tienen algún rol que NO sea 'cliente' (es decir, trabajadores)
        $query->whereHas('roles', function ($q) {
            $q->where('nombre', '!=', 'cliente');
        });

        $clientes = $query->withCount('pedidos')
            ->orderBy('id', 'desc')
            ->paginate(12);

        return Inertia::render('Admin/Trabajadores/Index', [
            'trabajadores' => $clientes,
            'filtros' => $request->only('buscar'),
        ]);
    }

    public function create()
    {
        $roles = \App\Models\Rol::all();
        return Inertia::render('Admin/Trabajadores/Create', [
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'required|email|unique:usuario,email',
            'password' => 'required|string|min:6',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:rol,id',
            'dni' => 'nullable|string|max:20|unique:usuario,dni',
            'telefono' => 'nullable|string|max:30',
        ], [
            'dni.unique' => 'Este DNI ya está registrado en el sistema.',
            'email.unique' => 'Este correo electrónico ya está registrado.'
        ]);

        $usuario = Usuario::create([
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'email' => $validated['email'],
            'password_hash' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'dni' => $validated['dni'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'estado' => 'activo'
        ]);

        $usuario->roles()->attach($validated['roles']);

        \App\Models\ActividadLog::log('Creó un nuevo usuario', 'usuario', $usuario->id, $usuario->toArray());

        return redirect()->route('admin.trabajadores')->with('success', 'Trabajador creado correctamente.');
    }

    public function edit($id)
    {
        $usuario = Usuario::with('roles')->findOrFail($id);
        $roles = \App\Models\Rol::all();

        return Inertia::render('Admin/Trabajadores/Edit', [
            'trabajador' => $usuario,
            'roles' => $roles
        ]);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $validated = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'required|email|unique:usuario,email,' . $id,
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:rol,id',
            'dni' => 'nullable|string|max:20|unique:usuario,dni,' . $id,
            'telefono' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:6',
        ], [
            'dni.unique' => 'Este DNI ya está registrado en el sistema.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.'
        ]);


        $dataToUpdate = [
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'email' => $validated['email'],
            'dni' => $validated['dni'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $dataToUpdate['password_hash'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $usuario->update($dataToUpdate);

        $usuario->roles()->sync($validated['roles']);

        \App\Models\ActividadLog::log('Actualizó un usuario', 'usuario', $usuario->id, $usuario->toArray());

        return redirect()->route('admin.trabajadores')->with('success', 'Trabajador actualizado correctamente.');
    }

    public function destroy($id)
    {
        $usuario = Usuario::findOrFail($id);

        if ($usuario->id === auth('admin')->id()) {
            return redirect()->back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete(); // Soft delete
        
        \App\Models\ActividadLog::log('Eliminó un usuario (Soft Delete)', 'usuario', $usuario->id);

        return redirect()->route('admin.trabajadores')->with('success', 'Trabajador movido a la papelera.');
    }

    public function toggleBloqueo($id)
    {
        $usuario = Usuario::findOrFail($id);
        
        if ($usuario->id === auth('admin')->id()) {
            return redirect()->back()->with('error', 'No puedes bloquear tu propia cuenta.');
        }

        $usuario->estado = $usuario->estado === 'bloqueado' ? 'activo' : 'bloqueado';
        $usuario->save();

        $accion = $usuario->estado === 'bloqueado' ? 'bloqueada' : 'desbloqueada';
        \App\Models\ActividadLog::log("Cuenta de usuario $accion", 'usuario', $usuario->id);

        return redirect()->back()->with('success', "Cuenta de usuario $accion correctamente.");
    }

    public function resetPassword($id)
    {
        $usuario = Usuario::findOrFail($id);
        
        $newPassword = 'Novape' . date('Y') . '!';
        $usuario->password_hash = \Illuminate\Support\Facades\Hash::make($newPassword);
        $usuario->save();

        \App\Models\ActividadLog::log('Restableció contraseña de usuario', 'usuario', $usuario->id);

        return redirect()->back()->with('success', "Contraseña restablecida exitosamente. Nueva contraseña: {$newPassword}");
    }

    public function show($id)
    {
        // Cargar el trabajador sin limit en eager load para evitar problemas con MySQL ONLY_FULL_GROUP_BY
        $cliente = Usuario::with('roles')->findOrFail($id);

        // Cargar pedidos por separado para evitar window function issues con MySQL
        $pedidos = $cliente->pedidos()->orderBy('id', 'desc')->limit(10)->get();
        $cliente->setRelation('pedidos', $pedidos);

        $totalCompras = $cliente->pedidos()->where('estado', 'completado')->sum('total');
        $totalPedidos = $cliente->pedidos()->count();

        return Inertia::render('Admin/Trabajadores/Show', [
            'trabajador' => $cliente,
            'totalCompras' => (float) $totalCompras,
            'totalPedidos' => $totalPedidos,
        ]);
    }

    public function export()
    {
        $clientes = Usuario::whereHas('roles', function ($q) {
            $q->where('nombre', '!=', 'cliente');
        })->withCount('pedidos')->get();

        $csv = "ID,Nombres,Apellidos,Email,Teléfono,DNI,Estado,Pedidos,Registro\n";
        foreach ($clientes as $c) {
            $csv .= implode(',', [
                $c->id,
                '"' . $c->nombres . '"',
                '"' . $c->apellidos . '"',
                $c->email,
                $c->telefono,
                $c->dni,
                $c->estado,
                $c->pedidos_count,
                $c->created_at,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="trabajadores_' . date('Y-m-d') . '.csv"',
        ]);
    }
}
