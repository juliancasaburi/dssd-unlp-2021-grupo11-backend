<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
</head>

<body>
    <h1>Información pública de la S.A.</h1>
    <hr>
    <div>
        <img src="data:image/png;base64, {{ $qr }}"/>
    <div>
    <h1>Nombre: {{ $nombre }}</h1>
    <h1>Fecha de creación: {{ $fechaCreacion->format('d/m/Y') }}</h1>
    <h1>Socios</h1>
    @foreach ($socios as $socio)
        @if ($socio->id === $apoderado_id)
            <h1>Apoderado</h1>
        @endif
        <h2>Nombre {{ $socio->nombre }}</h2>
        <h2>Apellido {{ $socio->apellido }}</h2>
        <h2>Porcentaje {{ $socio->porcentaje }}</h2>
        <hr>
    @endforeach
</body>

</html>