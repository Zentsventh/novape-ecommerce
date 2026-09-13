<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Autorizar el canal privado de Jarvis para los administradores
Broadcast::channel('admin.jarvis', function ($user) {
    // Si estás autenticado, puedes escuchar a Jarvis en el panel
    return true; 
});
