# Documentación de APIs - Módulo de Pagos (Móvil / Residentes)

Documentación de los endpoints del módulo de pagos e integración de Stripe para residentes en la API Móvil (`api_mobile.php`) de Aura.

---

## Headers Requeridos
Todas las peticiones a endpoints protegidos deben incluir:
```http
Authorization: Bearer <token_sanctum>
Accept: application/json
```

---

## 1. Obtener Resumen de Pagos, Saldo Pendiente e Histórico

Permite al residente consultar su saldo total pendiente por pagar (incluyendo la cuota mensual obligatoria y servicios contratados), el desglose de conceptos pendientes y su historial de pagos paginado a **10 elementos por página**.

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
          "receipt_url": "https://pay.stripe.com/receipts/acct_xxx/ch_xxx/rcpt_xxx",
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

## 2. Crear Intent de Pago en Stripe (Android PaymentSheet)

Permite a la aplicación móvil Android inicializar el componente nativo de Stripe `PaymentSheet` obteniendo el `client_secret` y la clave pública requeridos.

- **Método**: `POST`
- **Ruta**: `/api/mobile/payments/stripe/create-intent`
- **Content-Type**: `application/json`
- **Payload Esperado**:
  - `items` (array, requerido, min: 1): Lista de elementos a pagar.
    - `type` (string, enum: `"financial_charge"`, `"contracted_service"`, `"monthly_fee"`).
    - `id` (integer, opcional para `"monthly_fee"`, requerido para los demás).
    - `residence_id` (integer, opcional).

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
  ]
}
```

### Respuesta de Éxito (200 OK):
```json
{
  "status": "success",
  "data": {
    "client_secret": "pi_3Pxxx_secret_yyy",
    "publishable_key": "pk_test_51Pxxx",
    "payment_intent_id": "pi_3Pxxx",
    "amount": "2700.00",
    "currency": "mxn"
  }
}
```

---

## 3. Procesar Pago de Deuda Seleccionada

Permite al residente confirmar y finalizar el pago de conceptos de deuda previamente seleccionados utilizando Stripe o métodos manuales/transferencia con comprobante.

- **Método**: `POST`
- **Ruta**: `/api/mobile/payments/pay`
- **Content-Type**: `application/json` o `multipart/form-data`
- **Payload Esperado**:
  - `items` (array, requerido, min: 1).
  - `payment_method` (string, requerido: `"stripe"`, `"transfer"`, `"cash"`).
  - `stripe_payment_intent_id` (string, opcional): ID `pi_xxx` retornado por `/create-intent` tras ser completado en Android.
  - `payment_method_id` (string, opcional): ID `pm_xxx` del SDK de Stripe para cobro directo.
  - `receipt` (file o string, opcional): Comprobante adjunto (en caso de transferencia).

### Ejemplo de Payload JSON (Stripe):
```json
{
  "items": [
    { "type": "financial_charge", "id": 15 }
  ],
  "payment_method": "stripe",
  "stripe_payment_intent_id": "pi_3Pxxx"
}
```

### Respuesta de Éxito - Aprobado (200 OK):
```json
{
  "status": "success",
  "message": "Pago procesado exitosamente.",
  "data": {
    "total_paid": "1500.00",
    "payment_method": "stripe",
    "payments": [
      {
        "payment_id": 4,
        "charge_id": 15,
        "amount": "1500.00",
        "receipt_url": "https://pay.stripe.com/receipts/acct_xxx/ch_xxx/rcpt_xxx",
        "status": "approved",
        "failure_code": null,
        "failure_reason": null
      }
    ]
  }
}
```

### Respuesta de Error - Pago Rechazado por Stripe (400 Bad Request):
```json
{
  "status": "error",
  "message": "La tarjeta no cuenta con fondos suficientes.",
  "decline_code": "insufficient_funds",
  "failure_reason": "Tarjeta rechazada por Stripe: Your card has insufficient funds.",
  "data": {
    "total_paid": "0.00",
    "payment_method": "stripe",
    "payments": [
      {
        "payment_id": 5,
        "charge_id": 15,
        "amount": "1500.00",
        "status": "refused",
        "failure_code": "insufficient_funds",
        "failure_reason": "Tarjeta rechazada por Stripe: Your card has insufficient funds."
      }
    ]
  }
}
```

---

## 4. Guía de Requerimientos para Android

Para integrar este flujo correctamente en Android con el SDK Oficial de Stripe (`com.stripe:stripe-android`):

1. **Inicialización**:
   - Inicializar la clave pública obtenida de la API o del entorno:
     `PaymentConfiguration.init(context, publishableKey)`
2. **Flujo Recomendado (PaymentSheet)**:
   - El usuario selecciona los conceptos a pagar en la app Android.
   - La app realiza una petición `POST /api/mobile/payments/stripe/create-intent` enviando la lista de `items`.
   - La app recibe `client_secret` y `payment_intent_id`.
   - Android presenta `PaymentSheet.presentWithPaymentIntent(clientSecret, configuration)`.
   - Una vez que la UI de Stripe responde con `PaymentSheetResult.Completed`, la app llama a `POST /api/mobile/payments/pay` pasando `stripe_payment_intent_id` e `items`.
   - En caso de `PaymentSheetResult.Failed(error)`, Android muestra el error nativo o notifica al backend si se requiere registrar el fallo.
