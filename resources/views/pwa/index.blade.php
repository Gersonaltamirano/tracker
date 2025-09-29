<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GPS Tracker - Sistema de Monitoreo')</title>
    <meta name="description" content="@yield('description', 'Aplicación para monitorear velocidad y ubicación GPS')">
    <meta name="theme-color" content="#007bff">

    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ route('pwa.manifest') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/pwa/icons/icon-72x72.png">
    <link rel="apple-touch-icon" href="/pwa/icons/icon-192x192.png">

    <!-- Styles -->
    <link rel="stylesheet" href="/pwa/css/styles.css">

    <!-- Dexie.js -->
    <script src="https://unpkg.com/dexie@3.2.4/dist/dexie.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @livewireStyles
</head>
<body>
    @livewire('gps-tracker')

    @livewireScripts

    <!-- Scripts -->
    <script src="/pwa/js/app.js"></script>
</body>
</html>