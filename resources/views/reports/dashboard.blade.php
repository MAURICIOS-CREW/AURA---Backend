```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <title>{{ $title }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #334155;
        }

        h1 {
            color: #195491;
        }

        .date {
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <h1>{{ $title }}</h1>

    <p class="date">
        Generado el:
        {{ $generated_at->format('d/m/Y H:i') }}
    </p>

    <p>
        Este es el reporte del Dashboard de AURA.
    </p>

</body>
</html>
```
