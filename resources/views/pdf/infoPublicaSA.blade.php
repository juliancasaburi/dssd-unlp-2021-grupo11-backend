<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
</head>

<body>
    <h1>Nombre: {{ $nombre }}</h1>
    <h1>Fecha de creación: {{ $fechaCreacion }}</h1>
    <h1>Socios</h1>
    @foreach ($socios as $socio)
        <h2>Nombre {{ $socio->nombre }}</h2>
        <h2>Apellido {{ $socio->apellido }}</h2>
        <h2>Porcentaje {{ $socio->porcentaje }}</h2>
        <hr>
    @endforeach
</body>

</html>