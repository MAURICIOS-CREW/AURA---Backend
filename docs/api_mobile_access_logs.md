# Documentación de la API: Historial de Accesos del Residente (`GET /api/mobile/access-logs`)

Esta API permite a los usuarios móviles con rol de residente consultar el historial de registros de acceso (`AccessLog`) correspondientes a las residencias que tienen asociadas.

---

## 1. Información General de la Ruta

- **Método HTTP**: `GET`
- **URL**: `/api/mobile/access-logs`
- **Autenticación**: Requerida (`auth:api` vía OAuth2 / Passport Bearer Token)
- **Middlewares aplicados**: `auth:api`, `mobile`, `not.banned`

---

## 2. Parámetros de Consulta (Query Parameters)

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
| :--- | :--- | :--- | :--- | :--- |
| `page` | `integer` | No | Número de página para la paginación (por defecto `1`). | `?page=2` |
| `per_page` | `integer` | No | Cantidad de registros por página (por defecto `15`). | `?per_page=20` |

---

## 3. Respuestas y Códigos de Estado

### 3.1. Respuesta Exitosa (`200 OK`)

Retorna la lista paginada de registros de acceso ordenados de manera descendente por la fecha/hora de evento (`timestamp`).

```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 106,
        "access_type": "entry",
        "method": "license_plate",
        "residence_id": 1,
        "vehicle_id": 5,
        "access_code_id": null,
        "guard_user_id": null,
        "status": "granted",
        "ai_confidence": 0.98,
        "timestamp": "2026-08-05 04:00:00",
        "message": "Acceso automático concedido por lectura de placa ABC-123",
        "device_identifier": "CAM_NORTH_01",
        "scanned_code": "ABC-123",
        "created_at": "2026-08-05T04:00:00.000000Z",
        "updated_at": "2026-08-05T04:00:00.000000Z",
        "access_code": null,
        "residence": {
          "id": 1,
          "block": 1,
          "number": "101"
        },
        "vehicle": {
          "id": 5,
          "residence_id": 1,
          "plate": "ABC-123",
          "brand": "Toyota",
          "color": "Negro",
          "created_at": "2026-08-01T10:00:00.000000Z",
          "updated_at": "2026-08-01T10:00:00.000000Z",
          "deleted_at": null
        }
      },
      {
        "id": 105,
        "access_type": "qr",
        "method": "scan",
        "residence_id": 1,
        "vehicle_id": null,
        "access_code_id": 12,
        "guard_user_id": 4,
        "status": "granted",
        "ai_confidence": null,
        "timestamp": "2026-08-05 03:45:00",
        "message": "Acceso permitido",
        "device_identifier": "GATE_NORTH_01",
        "scanned_code": "a9f8b7c6...",
        "created_at": "2026-08-05T03:45:00.000000Z",
        "updated_at": "2026-08-05T03:45:00.000000Z",
        "access_code": {
          "id": 12,
          "residence_id": 1,
          "guest_name": "Juan Pérez (Invitado)",
          "code": "a9f8b7c6...",
          "type": "custom",
          "is_active": true
        },
        "residence": {
          "id": 1,
          "block": 1,
          "number": "101"
        },
        "vehicle": null
      }
    ],
    "first_page_url": "http://localhost/api/mobile/access-logs?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost/api/mobile/access-logs?page=1",
    "links": [
      {
        "url": null,
        "label": "&laquo; Anterior",
        "active": false
      },
      {
        "url": "http://localhost/api/mobile/access-logs?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": null,
        "label": "Siguiente &raquo;",
        "active": false
      }
    ],
    "next_page_url": null,
    "path": "http://localhost/api/mobile/access-logs",
    "per_page": 15,
    "prev_page_url": null,
    "to": 2,
    "total": 2
  }
}
```

---

### 3.2. Respuestas de Error

#### 401 Unauthorized (No Autenticado)
Ocurre si no se envía el header `Authorization: Bearer <token>` o si el token expiró/es inválido.

```json
{
  "message": "Unauthenticated."
}
```

#### 403 Forbidden (No Autorizado)
Ocurre si el usuario autenticado no tiene un rol válido para usar la app móvil (`resident`, `guard`) o si su usuario está baneado (`not.banned`).

```json
{
  "status": "error",
  "message": "Forbidden access"
}
```

#### 500 Internal Server Error (Error Inesperado del Servidor)
Ocurre en caso de algún fallo interno no controlado en el servidor o en la base de datos.

```json
{
  "status": "error",
  "message": "Server Error"
}
```
