<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Novape - Tu tienda de electrodomésticos con los mejores precios y envío gratis a todo el Perú">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Novape') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon_novape .png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @routes
    <script>
        window.addEventListener('error', function(event) {
            fetch('/api/log-frontend-error', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: event.message, stack: event.error ? event.error.stack : null })
            });
        });
        window.addEventListener('unhandledrejection', function(event) {
            fetch('/api/log-frontend-error', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: event.reason, stack: event.reason ? event.reason.stack : null })
            });
        });
    </script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
