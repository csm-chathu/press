<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ \App\Models\Branch::first()?->name ?? 'POS System' }}</title>
    @php $logoPath = \App\Models\Branch::first()?->logo_path @endphp
    @if($logoPath)
        <link rel="icon" href="{{ asset('storage/' . $logoPath) }}" />
    @else
        <link rel="icon" href="/favicon.ico" />
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div id="app"></div>
</body>
</html>
