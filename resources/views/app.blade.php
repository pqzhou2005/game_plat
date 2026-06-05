<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', '602游戏平台') }}</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-gray-50">
    @inertia
</body>
</html>
