<style>
    @page {
        margin: 125px 32px 65px 32px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        color: #334155;
        font-size: 11px;
        line-height: 1.5;
    }

    h1, h2, h3 {
        margin: 0;
        color: #195491;
    }

    header {
        position: fixed;
        top: -105px;
        left: 0;
        right: 0;
        height: 90px;
    }

    footer {
        position: fixed;
        bottom: -50px;
        left: 0;
        right: 0;
        height: 35px;
        text-align: center;
        color: #94a3b8;
        font-size: 9px;
        border-top: 1px solid #e2e8f0;
        padding-top: 8px;
    }

    footer:after {
        content: "Página " counter(page) " de " counter(pages);
    }

    .report-header {
        border-bottom: 3px solid #2E6DB4;
        padding-bottom: 10px;
    }

    .report-header .brand {
        font-size: 20px;
        font-weight: 700;
        color: #195491;
    }

    .report-header .subtitle {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }

    .report-header .meta {
        font-size: 10px;
        color: #94a3b8;
        text-align: right;
    }

    .section-title {
        font-size: 13px;
        font-weight: 700;
        color: #195491;
        text-transform: uppercase;
        letter-spacing: .4px;
        border-left: 4px solid #2E6DB4;
        padding-left: 8px;
        margin: 22px 0 10px 0;
    }

    .cards {
        width: 100%;
        border-collapse: separate;
        border-spacing: 8px 0;
        margin-bottom: 6px;
    }

    .cards td {
        width: 33.33%;
        background: #f8fbff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 12px;
        vertical-align: top;
    }

    .cards .card-label {
        font-size: 9px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .cards .card-value {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin-top: 3px;
    }

    .cards .card-value.positive { color: #15803d; }
    .cards .card-value.negative { color: #dc2626; }

    table.aura-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 4px;
    }

    table.aura-table th {
        background: #f8fbff;
        color: #195491;
        font-weight: 700;
        text-align: left;
        padding: 6px 8px;
        border-bottom: 1px solid #cbd5e1;
        font-size: 9.5px;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    table.aura-table td {
        padding: 6px 8px;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
        font-size: 10px;
    }

    table.aura-table tr:nth-child(even) td {
        background: #fafcff;
    }

    .badge {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-income, .badge-paid, .badge-approved, .badge-completed { background: #dcfce7; color: #15803d; }
    .badge-expense, .badge-cancelled, .badge-refused { background: #fee2e2; color: #dc2626; }
    .badge-pending { background: #fef3c7; color: #b45309; }
    .badge-refunded, .badge-in_progress { background: #dbeafe; color: #195491; }
    .badge-neutral { background: #ede9fe; color: #6d28d9; }

    .amount.positive { color: #15803d; font-weight: 700; }
    .amount.negative { color: #dc2626; font-weight: 700; }

    .muted { color: #94a3b8; font-style: italic; }

    .empty-state {
        padding: 14px;
        text-align: center;
        color: #94a3b8;
        font-style: italic;
        border: 1px dashed #cbd5e1;
        border-radius: 6px;
    }

    .two-col {
        width: 100%;
        border-collapse: separate;
        border-spacing: 10px 0;
    }

    .two-col > tr > td {
        width: 50%;
        vertical-align: top;
    }
</style>
