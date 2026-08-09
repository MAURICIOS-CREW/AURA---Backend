<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('reports.partials.styles')
</head>
<body>
    @include('reports.partials.header')

    <table class="cards">
        <tr>
            <td>
                <div class="card-label">Servicios contratados en el periodo</div>
                <div class="card-value">{{ $total_count }}</div>
            </td>
            <td>
                <div class="card-label">Ingresos por servicios (no cancelados)</div>
                <div class="card-value positive">${{ number_format($total_revenue, 2) }}</div>
            </td>
            <td>
                <div class="card-label">Tiempo promedio de finalización</div>
                <div class="card-value" style="font-size: 14px;">
                    @if ($avg_completion_hours === null)
                        <span class="muted">Sin datos</span>
                    @else
                        {{ number_format($avg_completion_hours, 1) }} hrs
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="two-col">
        <tr>
            <td>
                <div class="section-title" style="margin-top: 0;">Servicios por estatus</div>
                @if ($by_status->isEmpty())
                    <div class="empty-state">Sin datos en el periodo.</div>
                @else
                    <table class="aura-table">
                        <thead><tr><th>Estatus</th><th>Cantidad</th></tr></thead>
                        <tbody>
                            @foreach ($by_status as $status => $count)
                                <tr>
                                    <td>{{ \App\Services\Reports\ReportLabels::contractedServiceStatus($status) }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </td>
            <td>
                <div class="section-title" style="margin-top: 0;">Servicios más solicitados</div>
                @if ($by_service->isEmpty())
                    <div class="empty-state">Sin datos en el periodo.</div>
                @else
                    <table class="aura-table">
                        <thead><tr><th>Servicio</th><th>#</th><th>Ingresos</th></tr></thead>
                        <tbody>
                            @foreach ($by_service as $row)
                                <tr>
                                    <td>{{ $row['title'] }}</td>
                                    <td>{{ $row['count'] }}</td>
                                    <td>${{ number_format($row['revenue'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    <div class="section-title">Detalle de servicios contratados en el periodo</div>
    @if ($contracted_services->isEmpty())
        <div class="empty-state">No hay servicios contratados en este periodo.</div>
    @else
        <table class="aura-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Servicio</th>
                    <th>Residente</th>
                    <th>Domicilio</th>
                    <th>Monto</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($contracted_services as $item)
                    <tr>
                        <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->service?->title ?? 'Sin servicio' }}</td>
                        <td>{{ $item->user?->name ?? 'Sin residente' }}</td>
                        <td>{{ $item->residence?->address?->name ?? 'N/A' }}</td>
                        <td>${{ number_format($item->amount, 2) }}</td>
                        <td>{{ \App\Services\Reports\ReportLabels::contractedServiceStatus($item->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
