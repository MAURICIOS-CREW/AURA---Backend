# Documentación de APIs - Módulo de Servicios y Servicios Contratados

Documentación completa de los endpoints para el catálogo de servicios y la gestión de contrataciones en las APIs de **Administración (Web)** y **Móvil (Residentes)** de Aura.

---

## Headers Requeridos
Todas las peticiones a endpoints protegidos deben incluir:
```http
Authorization: Bearer <token_sanctum>
Accept: application/json
```

---

## 1. APIs de Administración (Web) (`api_admin.php`)

### 1.1 Listar Servicios
- **Método**: `GET`
- **Ruta**: `/api/admin/services`
- **Query Params (opcionales)**:
  - `is_active` (boolean): `true` o `false`
  - `search` (string): Busca en título y descripción
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Jardinería Profesional",
      "description": "Corte de césped y poda de arbustos",
      "price": "250.00",
      "images": [
        "services/xyz1.jpg",
        "services/xyz2.jpg"
      ],
      "image_urls": [
        "http://localhost/storage/services/xyz1.jpg",
        "http://localhost/storage/services/xyz2.jpg"
      ],
      "is_active": true,
      "created_at": "2026-08-03T18:00:00.000000Z",
      "updated_at": "2026-08-03T18:00:00.000000Z"
    }
  ]
}
```

---

### 1.2 Crear Servicio
- **Método**: `POST`
- **Ruta**: `/api/admin/services`
- **Content-Type**: `multipart/form-data`
- **Payload Esperado**:
  - `title` (string, requerido, max: 255): Título del servicio.
  - `description` (string, opcional): Descripción detallada.
  - `price` (numeric, requerido, min: 0): Precio del servicio.
  - `is_active` (boolean, opcional, default `true`): `1`/`true` o `0`/`false`.
  - **Archivos de Imagen (recursos binarios / `File`)**:
    - Para subir **múltiples imágenes**, envía los archivos en el campo `images[]` (ej. `images[] = archivo1.png`, `images[] = archivo2.jpg`).
    - Para subir **una sola imagen**, puedes enviarla tanto en `images[]` como en `images` o `image` (ej. `image = foto.jpg`).
    - Formatos soportados: `jpeg, png, jpg, webp`, máximo 5MB por imagen.
- **Respuesta de Éxito (201 Created)**:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Jardinería Profesional",
    "description": "Corte de pasto",
    "price": "250.00",
    "images": [
      "services/abc12345.jpg"
    ],
    "image_urls": [
      "http://localhost/storage/services/abc12345.jpg"
    ],
    "is_active": true,
    "created_at": "2026-08-03T18:00:00.000000Z",
    "updated_at": "2026-08-03T18:00:00.000000Z"
  }
}
```

---

### 1.3 Ver Detalle de un Servicio
- **Método**: `GET`
- **Ruta**: `/api/admin/services/{service_id}`
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Jardinería Profesional",
    "description": "Corte de pasto",
    "price": "250.00",
    "images": ["services/abc12345.jpg"],
    "image_urls": ["http://localhost/storage/services/abc12345.jpg"],
    "is_active": true
  }
}
```

---

### 1.4 Actualizar Servicio (Datos Generales)
- **Método**: `PUT` o `PATCH`
- **Ruta**: `/api/admin/services/{service_id}`
- **Content-Type**: `application/json`
- **Payload Esperado**:
  - `title` (string, opcional)
  - `description` (string, opcional)
  - `price` (numeric, opcional)
  - `is_active` (boolean, opcional)
- **Nota**: Este endpoint actualiza únicamente la información general del servicio. La gestión de imágenes se realiza mediante los endpoints independientes `/images`.
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "message": "Servicio actualizado correctamente.",
  "data": {
    "id": 1,
    "title": "Jardinería y Paisajismo",
    "description": "Servicio integral de jardín",
    "price": "300.00",
    "is_active": true
  }
}
```

---

### 1.5 Subir Imágenes a un Servicio
- **Método**: `POST`
- **Ruta**: `/api/admin/services/{service_id}/images`
- **Content-Type**: `multipart/form-data`
- **Payload Esperado**:
  - `images[]` o `images` o `image` (archivos binarios `File`, requerido): Uno o varios archivos de imagen (`jpeg, png, jpg, webp`, max 5MB).
- **Efecto**: Guarda los archivos en el almacenamiento y añade sus rutas a la lista de imágenes del servicio.
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "message": "Imágenes subidas y agregadas al servicio correctamente.",
  "data": {
    "id": 1,
    "title": "Jardinería Profesional",
    "images": [
      "services/foto1.jpg",
      "services/foto2.jpg"
    ],
    "image_urls": [
      "http://localhost/storage/services/foto1.jpg",
      "http://localhost/storage/services/foto2.jpg"
    ]
  }
}
```

---

### 1.6 Eliminar una Imagen de un Servicio
- **Método**: `DELETE`
- **Ruta**: `/api/admin/services/{service_id}/images`
- **Content-Type**: `application/json`
- **Payload Esperado**:
  - `image_path` (string, requerido): Ruta relativa exacta de la imagen a eliminar (ej. `"services/foto1.jpg"`).
- **Efecto**: Borra físicamente el archivo del almacenamiento y lo remueve de la lista de imágenes del servicio.
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "message": "Imagen eliminada correctamente del servicio.",
  "data": {
    "id": 1,
    "images": [
      "services/foto2.jpg"
    ],
    "image_urls": [
      "http://localhost/storage/services/foto2.jpg"
    ]
  }
}
```

---

### 1.7 Eliminar Servicio
- **Método**: `DELETE`
- **Ruta**: `/api/admin/services/{service_id}`
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "message": "Servicio eliminado correctamente."
}
```

---

### 1.6 Listar Servicios Contratados (Panel Admin)
- **Método**: `GET`
- **Ruta**: `/api/admin/contracted-services`
- **Query Params (opcionales)**:
  - `status` (string): Filtrar por enum (`created`, `scheduled`, `in_progress`, `completed`, `refunded`, `cancelled`).
  - `residence_id` (integer): Filtrar por ID de residencia.
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "service_id": 1,
      "user_id": 5,
      "residence_id": 2,
      "charge_id": 10,
      "preferred_date": "2026-08-05",
      "visit_time_from": "10:00:00",
      "visit_time_to": "14:00:00",
      "exact_scheduled_at": null,
      "amount": "250.00",
      "status": "created",
      "notes": "Tocar timbre principal",
      "payment_method": "stripe",
      "service": {
        "id": 1,
        "title": "Jardinería Profesional"
      },
      "user": {
        "id": 5,
        "name": "Juan Residente",
        "email": "juan@example.com",
        "phone": "3312345678"
      },
      "residence": {
        "id": 2,
        "block": 1,
        "number": "102"
      },
      "financial_charge": {
        "id": 10,
        "amount": "250.00",
        "status": "paid"
      },
      "access_code": null
    }
  ]
}
```

---

### 1.7 Programar Horario Exacto y Generar Código QR (Admin)
- **Método**: `POST`
- **Ruta**: `/api/admin/contracted-services/{contracted_service_id}/schedule`
- **Payload Esperado**:
  - `exact_scheduled_at` (date string, requerido, ej: `2026-08-05 11:30:00`): Fecha y hora exacta asignada por la administración.
  - `notes` (string, opcional): Notas para el usuario o asignación de personal.
- **Efecto**:
  - Cambia `contracted_services.status` a `'scheduled'`.
  - Genera un código QR en la tabla `access_codes` con `type = 'service'` y `contracted_service_id` enlazado.
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "message": "Servicio programado correctamente y código QR generado.",
  "data": {
    "id": 1,
    "status": "scheduled",
    "exact_scheduled_at": "2026-08-05T11:30:00.000000Z",
    "access_code": {
      "id": 15,
      "contracted_service_id": 1,
      "code": "a6b7c8d9...hash",
      "type": "service",
      "valid_from": "2026-08-05T10:30:00.000000Z",
      "valid_until": "2026-08-05T17:30:00.000000Z",
      "is_active": true
    }
  }
}
```

---

### 1.8 Cambiar Estado del Servicio Contratado (Admin)
- **Método**: `PATCH`
- **Ruta**: `/api/admin/contracted-services/{contracted_service_id}/status`
- **Payload Esperado**:
  - `status` (string, requerido, enum): `created`, `scheduled`, `in_progress`, `completed`, `refunded`, `cancelled`.
  - `notes` (string, opcional): Razón del cambio de estado o notas de reembolso.
- **Efecto**:
  - Si el estado se cambia a `refunded`, actualiza el registro en `financial_charges` a `refunded`.
  - Si el estado se cambia a `cancelled`, actualiza `financial_charges` a `cancelled`.
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "message": "Estado del servicio actualizado a 'refunded'.",
  "data": {
    "id": 1,
    "status": "refunded",
    "financial_charge": {
      "id": 10,
      "status": "refunded"
    }
  }
}
```

---

## 2. APIs Móvil / Residentes (`api_mobile.php`)

### 2.1 Ver Catálogo de Servicios Disponibles
- **Método**: `GET`
- **Ruta**: `/api/mobile/services`
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Jardinería Profesional",
      "description": "Corte de pasto",
      "price": "250.00",
      "image_urls": [
        "http://localhost/storage/services/xyz1.jpg"
      ],
      "is_active": true
    }
  ]
}
```

---

### 2.2 Ver Detalle de un Servicio (Móvil)
- **Método**: `GET`
- **Ruta**: `/api/mobile/services/{service_id}`
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Jardinería Profesional",
    "description": "Corte de pasto",
    "price": "250.00",
    "image_urls": ["http://localhost/storage/services/xyz1.jpg"]
  }
}
```

---

### 2.3 Contratar un Servicio
- **Método**: `POST`
- **Ruta**: `/api/mobile/services/{service_id}/contract`
- **Payload Esperado**:
  - `residence_id` (integer, requerido): ID de la residencia a la que pertenece el residente.
  - `preferred_date` (date string `YYYY-MM-DD`, requerido, fecha hoy o posterior): Día sugerido para la visita.
  - `visit_time_from` (time string `HH:MM`, requerido): Hora inicio disponible.
  - `visit_time_to` (time string `HH:MM`, requerido, posterior a `visit_time_from`): Hora fin disponible.
  - `is_recurrent` (boolean, opcional, default `false`): Indica si el servicio se contratará en modalidad recurrente.
  - `suggested_schedule` (array / json, opcional): Días u horarios sugeridos para las visitas recurrentes (ej. `["Lunes", "Miércoles"]`).
  - `notes` (string, opcional): Instrucciones para el prestador de servicio.
  - `payment_method` (string, opcional, default `'stripe'`): Método de pago (`"stripe"`, `"transfer"`, `"cash"`).
  - `stripe_payment_intent_id` (string, opcional): ID `pi_xxx` obtenido de Stripe tras PaymentSheet.
  - `payment_method_id` (string, opcional): ID `pm_xxx` del SDK de Stripe para cobro directo.
  - `receipt` (file o string, opcional): Comprobante adjunto (en caso de pago por transferencia).
- **Efecto**:
  - Procesa o verifica la transacción con Stripe o registra el comprobante de transferencia.
  - Si el pago en Stripe o transferencia es exitoso/aprobado, actualiza `financial_charges.status` a `'paid'` y registra `payments.status = 'approved'`.
  - Si el pago por Stripe es rechazado, registra `payments.status = 'refused'` con la razón del fallo, mantiene `financial_charges.status = 'pending'` y retorna un error HTTP 400.
  - Si `is_recurrent` es `true`, genera automáticamente un código QR reutilizable en la tabla `access_codes` (`type = 'service'`) con vigencia extendida de 1 año.

- **Respuesta de Éxito (201 Created)**:
```json
{
  "status": "success",
  "message": "Servicio contratado exitosamente. En espera de asignación de horario exacto por administración.",
  "data": {
    "id": 1,
    "service_id": 1,
    "residence_id": 2,
    "charge_id": 10,
    "preferred_date": "2026-08-05",
    "visit_time_from": "10:00:00",
    "visit_time_to": "14:00:00",
    "exact_scheduled_at": null,
    "amount": "250.00",
    "status": "created",
    "is_recurrent": true,
    "suggested_schedule": [
      "Lunes",
      "Miércoles"
    ],
    "notes": "Tocar timbre principal",
    "payment_method": "stripe",
    "financial_charge": {
      "id": 10,
      "amount": "250.00",
      "status": "paid"
    },
    "access_code": {
      "id": 20,
      "contracted_service_id": 1,
      "code": "83ffce8798212...",
      "type": "service",
      "valid_from": "2026-08-05T00:00:00.000000Z",
      "valid_until": "2027-08-05T23:59:59.000000Z",
      "max_uses": null,
      "active_days": ["Lunes", "Miércoles"],
      "is_active": true
    }
  }
}
```

- **Respuesta de Error - Rechazo de Pago en Stripe (400 Bad Request)**:
```json
{
  "status": "error",
  "message": "La tarjeta no cuenta con fondos suficientes.",
  "decline_code": "insufficient_funds",
  "failure_reason": "Tarjeta rechazada por Stripe: Your card has insufficient funds.",
  "payment": {
    "id": 15,
    "status": "refused",
    "failure_code": "insufficient_funds",
    "failure_reason": "Tarjeta rechazada por Stripe: Your card has insufficient funds."
  }
}
```

---

### 2.4 Ver Mis Servicios Contratados (Residente)
- **Método**: `GET`
- **Ruta**: `/api/mobile/contracted-services`
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "service": {
        "id": 1,
        "title": "Jardinería Profesional"
      },
      "preferred_date": "2026-08-05",
      "visit_time_from": "10:00:00",
      "visit_time_to": "14:00:00",
      "exact_scheduled_at": "2026-08-05T11:30:00.000000Z",
      "status": "scheduled",
      "access_code": {
        "id": 15,
        "code": "a6b7c8d9...hash",
        "type": "service",
        "valid_from": "2026-08-05T10:30:00.000000Z",
        "valid_until": "2026-08-05T17:30:00.000000Z"
      }
    }
  ]
}
```

---

### 2.5 Marcar Servicio como Finalizado (Residente en Android)
- **Método**: `PATCH`
- **Ruta**: `/api/mobile/contracted-services/{contracted_service_id}/complete`
- **Respuesta de Éxito (200 OK)**:
```json
{
  "status": "success",
  "message": "El servicio ha sido marcado como finalizado.",
  "data": {
    "id": 1,
    "status": "completed"
  }
}
```

---

## 3. Códigos de Error Previstos y Respuestas

| Código HTTP | Escenario | Respuesta JSON Esperada |
| :--- | :--- | :--- |
| **400 Bad Request** | Intentar finalizar un servicio ya completado/cancelado o solicitar un servicio inactivo | `{"status": "error", "message": "Este servicio no se encuentra activo actualmente."}` |
| **401 Unauthorized** | Token de autenticación Sanctum ausente, inválido o expirado | `{"message": "Unauthenticated."}` |
| **403 Forbidden** | El usuario no tiene rol de admin / residente no pertenece a la residencia | `{"status": "error", "message": "No tienes permisos para solicitar un servicio en esta residencia."}` |
| **404 Not Found** | El ID de servicio o servicio contratado no existe | `{"message": "No query results for model [App\\Models\\Service] 999"}` |
| **422 Unprocessable Entity** | Fallo en la validación de campos obligatorios (ej. precio negativo, fecha pasada, hora fin anterior a inicio) | `{"message": "The visit time to field must be a date after visit time from.", "errors": {"visit_time_to": ["The visit time to field must be a date after visit time from."]}}` |
| **500 Server Error** | Error interno de base de datos o servidor | `{"message": "Server Error"}` |
