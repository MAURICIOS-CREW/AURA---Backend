# Documentación de APIs - Módulo de Pagos (Móvil / Residentes)

Documentación de los endpoints del módulo de pagos para residentes en la API Móvil (`api_mobile.php`) de Aura.

---

## Headers Requeridos
Todas las peticiones a endpoints protegidos deben incluir:
```http
Authorization: Bearer <token_sanctum>
Accept: application/json
```

---

## 1. Obtener Resumen de Pagos, Saldo Pendiente e Histórico

Permite al residente consultar su saldo total pendiente por pagar (incluyendo el pago mensual obligatorio recurrente del mes actual y cualquier servicio pendiente), el desglose de conceptos pendientes y su historial de pagos paginado a **10 elementos por página**.

- **Método**: `GET`
- **Ruta**: `/api/mobile/payments`
- **Query Params (opcionales para paginación)**:
  - `page` (integer): Número de página para el historial (default `1`).

### Respuesta de Éxito (200 OK):
```json
{
  "status": "success",
  "data": {
    "saldo_pendiente": "2700.00",
    "pagos_pendientes": [
      {
        "id": null,
        "type": "monthly_fee",
        "title": "Mantenimiento Mensual - Agosto 2026",
        "amount": "1500.00",
        "month": 8,
        "year": 2026,
        "status": "pending",
        "is_recurrent": true,
        "contracted_service_id": null,
        "created_at": "2026-08-01T00:00:00.000000Z"
      },
      {
        "id": 15,
        "type": "contracted_service",
        "title": "Jardinería Profesional",
        "amount": "1200.00",
        "month": 8,
        "year": 2026,
        "status": "pending",
        "is_recurrent": false,
        "contracted_service_id": 5,
        "created_at": "2026-08-05T10:00:00.000000Z"
      }
    ],
    "historico_pagos": {
      "current_page": 1,
      "data": [
        {
          "id": 10,
          "payment_id": 3,
          "title": "Mantenimiento Mensual - 7/2026",
          "amount": "1500.00",
          "payment_method": "stripe",
          "receipt_url": "http://localhost/storage/receipts/comprobante123.pdf",
          "status": "paid",
          "month": 7,
          "year": 2026,
          "date": "2026-07-05T14:30:00.000000Z"
        }
      ],
      "first_page_url": "http://localhost/api/mobile/payments?page=1",
      "from": 1,
      "last_page": 1,
      "last_page_url": "http://localhost/api/mobile/payments?page=1",
      "next_page_url": null,
      "path": "http://localhost/api/mobile/payments",
      "per_page": 10,
      "prev_page_url": null,
      "to": 1,
      "total": 1
    }
  }
}
```

---

## 2. Procesar Pago de Deuda Seleccionada

Permite al residente realizar el pago de uno o varios conceptos específicos de su saldo pendiente seleccionados desde la aplicación (por ejemplo, únicamente la cuota mensual del mes actual o uno de los servicios contratados).

- **Método**: `POST`
- **Ruta**: `/api/mobile/payments/pay`
- **Content-Type**: `application/json` o `multipart/form-data` (si incluye archivo de recibo)
- **Payload Esperado**:
  - `items` (array, requerido, min: 1): Lista de elementos a pagar. Cada objeto contiene:
    - `type` (string, enum: `"financial_charge"`, `"contracted_service"`, `"monthly_fee"`, requerido).
    - `id` (integer, opcional para `"monthly_fee"`, requerido para `"financial_charge"` y `"contracted_service"`).
    - `residence_id` (integer, opcional).
  - `payment_method` (string, requerido, ej. `"stripe"`, `"card"`, `"transfer"`).
  - `receipt` (file o string, opcional): Comprobante adjunto.

### Ejemplo de Payload JSON:
```json
{
  "items": [
    {
      "type": "monthly_fee",
      "id": null
    },
    {
      "type": "financial_charge",
      "id": 15
    }
  ],
  "payment_method": "stripe"
}
```

### Respuesta de Éxito (200 OK):
```json
{
  "status": "success",
  "message": "Pago procesado exitosamente.",
  "data": {
    "total_paid": "2700.00",
    "payment_method": "stripe",
    "payments": [
      {
        "payment_id": 4,
        "charge_id": 18,
        "amount": "1500.00",
        "status": "approved"
      },
      {
        "payment_id": 5,
        "charge_id": 15,
        "amount": "1200.00",
        "status": "approved"
      }
    ]
  }
}
```

---

## 3. Códigos de Error

| Código HTTP | Escenario | Respuesta JSON |
| :--- | :--- | :--- |
| **400 Bad Request** | El residente no posee residencias asociadas | `{"status": "error", "message": "El usuario no tiene residencias asociadas."}` |
| **401 Unauthorized** | Token ausente o expirado | `{"message": "Unauthenticated."}` |
| **422 Unprocessable Entity** | Fallo en validación de items o payment_method | `{"message": "The items field is required.", "errors": {"items": ["The items field is required."]}}` |
