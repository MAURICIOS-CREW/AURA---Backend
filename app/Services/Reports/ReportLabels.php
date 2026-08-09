<?php

namespace App\Services\Reports;

/**
 * Traducciones de estatus/categorías a español, compartidas por las tres
 * vistas Blade de reportes. Espeja los mapas get*Label() del frontend
 * (dashboard.ts, finance.ts, list-of-services.ts) para que el PDF use el
 * mismo lenguaje que la pantalla.
 */
class ReportLabels
{
    public static function paymentStatus(?string $status): string
    {
        return match ($status) {
            'approved' => 'Aprobado',
            'pending' => 'Pendiente',
            'refused' => 'Rechazado',
            'refunded' => 'Reembolsado',
            'cancelled' => 'Cancelado',
            default => 'Sin estado',
        };
    }

    public static function expenseStatus(?string $status): string
    {
        return match ($status) {
            'paid' => 'Pagado',
            'pending' => 'Pendiente',
            'cancelled' => 'Cancelado',
            default => 'Sin estado',
        };
    }

    public static function expenseCategory(?string $category): string
    {
        return match ($category) {
            'maintenance' => 'Mantenimiento',
            'security' => 'Seguridad',
            'utilities' => 'Servicios (luz, agua, etc.)',
            'salaries' => 'Nómina',
            'services' => 'Servicios contratados',
            'supplies' => 'Insumos',
            'other' => 'Otro',
            default => 'Sin categoría',
        };
    }

    public static function paymentMethod(?string $method): string
    {
        return match ($method) {
            'stripe' => 'Tarjeta (Stripe)',
            'transfer' => 'Transferencia',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'check' => 'Cheque',
            default => 'Sin especificar',
        };
    }

    public static function contractedServiceStatus(?string $status): string
    {
        return match ($status) {
            'created' => 'Creado',
            'scheduled' => 'Programado',
            'in_progress' => 'En progreso',
            'completed' => 'Completado',
            'refunded' => 'Reembolsado',
            'cancelled' => 'Cancelado',
            default => 'Sin estado',
        };
    }

    public static function incidentStatus(?string $status): string
    {
        return match ($status) {
            'open' => 'Abierto',
            'viewed' => 'Visto',
            'in_progress' => 'En progreso',
            'attended' => 'Atendido',
            default => 'Sin estado',
        };
    }

    public static function accessType(?string $type): string
    {
        return match ($type) {
            'qr' => 'Por QR',
            'plate' => 'Por placa',
            default => 'Desconocido',
        };
    }
}
