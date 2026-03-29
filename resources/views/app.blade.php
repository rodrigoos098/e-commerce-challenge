<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'E-Commerce') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <meta name="description" content="Curadoria premium de artesanato e design. Celebramos a beleza nas imperfeições.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name', 'E-Commerce') }}">
    <meta property="og:title" content="{{ config('app.name', 'E-Commerce') }}">
    <meta property="og:description" content="Curadoria premium de artesanato e design. Celebramos a beleza nas imperfeições.">
    <meta property="og:image" content="{{ url('/shopsugi_og_image_1_1.jpg') }}">
    <meta property="og:image:secure_url" content="{{ url('/shopsugi_og_image_1_1.jpg') }}">
    <meta property="og:image:alt" content="Shopsugi - Presentes que contam histórias">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name', 'E-Commerce') }}">
    <meta name="twitter:description" content="Curadoria premium de artesanato e design. Celebramos a beleza nas imperfeições.">
    <meta name="twitter:image" content="{{ url('/shopsugi_og_image_1_1.jpg') }}">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
