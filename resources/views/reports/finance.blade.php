<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('reports.partials.styles')
</head>
<body>
    @include('reports.partials.header')

    <div class="section-title">Resumen del periodo</div>
    <table class="cards">
        <tr>
            <td>
                <div class="card-label">Ingresos del periodo</div>
                <div class="card-value positive">${{ number_format($income_range, 2) }}</div>
            </td>
            <td>
                <div class="card-label">Egresos del periodo</div>
                <div class="card-value negative">${{ number_format($expenses_range, 2) }}</div>
            </td>
            <td>
                <div class="card-label">Balance del periodo</div>
                <div class="card-value {{ $net_range >= 0 ? 'positive' : 'negative' }}">${{ number_format($net_range, 2) }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Totales históricos</div>
    <table class="cards">
        <tr>
            <td>
                <div class="card-label">Ingresos totales</div>
                <div class="card-value positive">${{ number_format($total_income, 2) }}</div>
            </td>
            <td>
                <div class="card-label">Egresos totales</div>
                <div class="card-value negative">${{ number_format($total_expenses, 2) }}</div>
            </td>
            <td>
                <div class="card-label">Fondo actual</div>
                <div class="card-value {{ $current_balance >= 0 ? 'positive' : 'negative' }}">${{ number_format($current_balance, 2) }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Proyección financiera (promedio últimos {{ count($prediction['series']) }} meses)</div>
    <table class="two-col">
        <tr>
            <td>
                <table class="aura-table">
                    <thead>
                        <tr><th>Mes</th><th>Ingresos</th><th>Egresos</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($prediction['series'] as $row)
                            <tr>
                                <td>{{ $row['month'] }}</td>
                                <td>${{ number_format($row['income'], 2) }}</td>
                                <td>${{ number_format($row['expense'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td>
                <table class="aura-table">
                    <thead>
                        <tr><th>Proyección próximo mes</th><th>Monto</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Ingresos estimados</td><td>${{ number_format($prediction['projected_next_month_income'], 2) }}</td></tr>
                        <tr><td>Egresos estimados</td><td>${{ number_format($prediction['projected_next_month_expense'], 2) }}</td></tr>
                        <tr>
                            <td>Balance estimado</td>
                            <td class="amount {{ $prediction['projected_next_month_net'] >= 0 ? 'positive' : 'negative' }}">
                                ${{ number_format($prediction['projected_next_month_net'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <table class="two-col">
        <tr>
            <td>
                <div class="section-title" style="margin-top: 0;">Egresos por categoría</div>
                @if (empty($expense_breakdown))
                    <div class="empty-state">Sin egresos en el periodo.</div>
                @else
                    <table class="aura-table">
                        <thead><tr><th>Categoría</th><th>Total</th><th>#</th></tr></thead>
                        <tbody>
                            @foreach ($expense_breakdown as $row)
                                <tr>
                                    <td>{{ \App\Services\Reports\ReportLabels::expenseCategory($row['category']) }}</td>
                                    <td>${{ number_format($row['total'], 2) }}</td>
                                    <td>{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </td>
            <td>
                <div class="section-title" style="margin-top: 0;">Ingresos por método de pago</div>
                @if (empty($income_breakdown))
                    <div class="empty-state">Sin ingresos en el periodo.</div>
                @else
                    <table class="aura-table">
                        <thead><tr><th>Método</th><th>Total</th><th>#</th></tr></thead>
                        <tbody>
                            @foreach ($income_breakdown as $row)
                                <tr>
                                    <td>{{ \App\Services\Reports\ReportLabels::paymentMethod($row['payment_method']) }}</td>
                                    <td>${{ number_format($row['total'], 2) }}</td>
                                    <td>{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    <div class="section-title">Transferencias pendientes de aprobación</div>
    @if ($pending_transfers->isEmpty())
        <div class="empty-state">No hay transferencias pendientes de aprobación.</div>
    @else
        <table class="aura-table">
            <thead>
                <tr><th>Fecha</th><th>Residente</th><th>Concepto</th><th>Monto</th></tr>
            </thead>
            <tbody>
                @foreach ($pending_transfers as $payment)
                    <tr>
                        <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $payment->user?->name ?? 'Residente' }}</td>
                        <td>{{ $payment->financialCharge?->contractedService?->service?->title ?? 'Cuota de mantenimiento' }}</td>
                        <td>${{ number_format($payment->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Movimientos del periodo ({{ count($movements) }})</div>
    @if (empty($movements))
        <div class="empty-state">No hay movimientos registrados en este periodo.</div>
    @else
        <table class="aura-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th>Residente / Proveedor</th>
                    <th>Tipo</th>
                    <th>Monto</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($movements as $movement)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</td>
                        <td>{{ $movement['concept'] }}</td>
                        <td>{{ $movement['counterpart'] }}</td>
                        <td>
                            <span class="badge badge-{{ $movement['type'] }}">
                                {{ $movement['type'] === 'income' ? 'Ingreso' : 'Egreso' }}
                            </span>
                        </td>
                        <td class="amount {{ $movement['type'] === 'income' ? 'positive' : 'negative' }}">
                            {{ $movement['type'] === 'income' ? '+' : '-' }}${{ number_format($movement['amount'], 2) }}
                        </td>
                        <td>
                            {{ $movement['type'] === 'income'
                                ? \App\Services\Reports\ReportLabels::paymentStatus($movement['status'])
                                : \App\Services\Reports\ReportLabels::expenseStatus($movement['status']) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
