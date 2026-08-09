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
                <div class="card-label">Residentes registrados</div>
                <div class="card-value">{{ $residents_count }}</div>
            </td>
            <td>
                <div class="card-label">Incidencias en el periodo</div>
                <div class="card-value">{{ $incidents_total }}</div>
            </td>
            <td>
                <div class="card-label">Servicios pendientes</div>
                <div class="card-value">{{ $pending_services->count() }}</div>
            </td>
        </tr>
    </table>

    <table class="cards">
        <tr>
            <td>
                <div class="card-label">Servicios completados en el periodo</div>
                <div class="card-value positive">{{ $completed_services->count() }}</div>
            </td>
            <td>
                <div class="card-label">Accesos registrados</div>
                <div class="card-value">{{ $access_logs_total }}</div>
            </td>
            <td>
                <div class="card-label">Accesos QR vs. Placa</div>
                <div class="card-value" style="font-size: 13px;">
                    {{ $access_type_breakdown['qr'] }} QR / {{ $access_type_breakdown['plate'] }} Placa
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Incidencias por estatus</div>
    <table class="aura-table">
        <thead>
            <tr>
                <th>Estatus</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            <tr><td><span class="badge badge-cancelled">Abierto</span></td><td>{{ $incident_status_stats['open'] }}</td></tr>
            <tr><td><span class="badge badge-pending">Visto</span></td><td>{{ $incident_status_stats['viewed'] }}</td></tr>
            <tr><td><span class="badge badge-refunded">En progreso</span></td><td>{{ $incident_status_stats['in_progress'] }}</td></tr>
            <tr><td><span class="badge badge-completed">Atendido</span></td><td>{{ $incident_status_stats['attended'] }}</td></tr>
        </tbody>
    </table>

    <div class="section-title">Servicios pendientes</div>
    @if ($pending_services->isEmpty())
        <div class="empty-state">No hay servicios pendientes.</div>
    @else
        <table class="aura-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Servicio</th>
                    <th>Residente</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pending_services as $item)
                    <tr>
                        <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->service?->title ?? 'Sin servicio' }}</td>
                        <td>{{ $item->user?->name ?? 'Sin residente' }}</td>
                        <td>{{ \App\Services\Reports\ReportLabels::contractedServiceStatus($item->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Servicios completados en el periodo</div>
    @if ($completed_services->isEmpty())
        <div class="empty-state">No hay servicios completados en este periodo.</div>
    @else
        <table class="aura-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Servicio</th>
                    <th>Residente</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($completed_services as $item)
                    <tr>
                        <td>{{ $item->updated_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->service?->title ?? 'Sin servicio' }}</td>
                        <td>{{ $item->user?->name ?? 'Sin residente' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Bitácora de accesos ({{ $access_logs_total }})</div>
    @if ($access_logs->isEmpty())
        <div class="empty-state">No hay accesos registrados en este periodo.</div>
    @else
        <table class="aura-table">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Tipo</th>
                    <th>Destino</th>
                    <th>Placa</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($access_logs as $log)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($log->timestamp)->format('d/m/Y H:i') }}</td>
                        <td>{{ \App\Services\Reports\ReportLabels::accessType($log->access_type) }}</td>
                        <td>{{ $log->residence?->address?->name ?? 'Sin dirección' }}</td>
                        <td>{{ $log->vehicle?->plate ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($access_logs_total >= 300)
            <p class="muted">Se muestran los 300 accesos más recientes del periodo.</p>
        @endif
    @endif
</body>
</html>
