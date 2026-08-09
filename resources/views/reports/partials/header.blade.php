<header>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: bottom;">
                <div class="report-header">
                    <div class="brand">AURA</div>
                    <div class="subtitle">{{ $title }}</div>
                </div>
            </td>
            <td style="vertical-align: bottom; text-align: right;">
                <div class="report-header meta">
                    Generado el {{ $generated_at->format('d/m/Y H:i') }}<br>
                    Periodo: {{ $range->label }}
                </div>
            </td>
        </tr>
    </table>
</header>
<footer></footer>
