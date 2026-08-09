# Documentación de Autenticación y Renovación de Tokens (Admin API)

Esta documentación describe la implementación del mecanismo de autenticación y renovación de sesión (Refresh Token) para las rutas administrativas (`/api/admin/*`) del sistema **Aura**.

---

## 1. Arquitectura y Conceptos de Tokens

El sistema utiliza **Laravel Sanctum** con una arquitectura de **doble token** (Access Token + Refresh Token) basada en habilidades (*abilities*):

| Concepto | Nombre del Token | Habilidad (*Ability*) | Duración / Expiración | Propósito |
| :--- | :--- | :---: | :---: | :--- |
| **Access Token** | `admin-session` | `access-api` | **3 horas** (10,800 s) | Utilizado para autenticar todas las peticiones a endpoints protegidos de la API de administración. |
| **Refresh Token** | `admin-refresh` | `issue-access-token` | **30 días** (2,592,000 s) | Utilizado **únicamente** para solicitar un nuevo Access Token cuando el anterior expira. |

### ¿Por qué se utiliza este mecanismo?
1. **Seguridad Elevada:** Los tokens de acceso tienen una vida corta (3 horas). Si un Access Token es interceptado o expuesto, el riesgo se limita al tiempo restante de esa ventana.
2. **Experiencia de Usuario Continua (UX):** El administrador no necesita reingresar sus credenciales (usuario y contraseña) cada 3 horas. Su aplicación/frontend utiliza transparentemente el Refresh Token de 30 días para emitir un nuevo Access Token.
3. **Control de Capacidades:** El Refresh Token **no puede** ser utilizado para consumir recursos administrativos (como crear usuarios o ver reportes), únicamente sirve para llamar al endpoint `/api/admin/auth/refresh`.

---

## 2. Flujo de Trabajo en el Cliente (Frontend / Panel Web)

```
[ Cliente / Panel Web ]                                          [ Backend API ]
         |                                                             |
         |---------------- 1. POST /api/admin/auth/login ------------->|
         |<--- 2. Retorna { access_token, refresh_token, ... } --------|
         |                                                             |
         |=== ( Guardar tokens en almacenamiento seguro / memoria ) ===|
         |                                                             |
         |---------------- 3. GET /api/admin/incidents --------------->|
         |                   Authorization: Bearer <access_token>      |
         |<------------------ 4. 200 OK (Respuesta exitosa) -----------|
         |                                                             |
         |            ... 3 horas después (Access Token expira) ...    |
         |                                                             |
         |---------------- 5. GET /api/admin/incidents --------------->|
         |                   Authorization: Bearer <access_token>      |
         |<------------------ 6. 401 Unauthorized ---------------------|
         |                                                             |
         |---- 7. POST /api/admin/auth/refresh ----------------------->|
         |        Authorization: Bearer <refresh_token>                |
         |<--- 8. 200 OK { access_token (nuevo), expires_in: 10800 } --|
         |                                                             |
         |---- 9. Reintentar GET /api/admin/incidents ---------------->|
         |           Authorization: Bearer <nuevo_access_token>        |
         |<------------------ 10. 200 OK ------------------------------|
```

---

## 3. Endpoints de la API

### 3.1. Inicio de Sesión (`Login`)

Permite autenticar a un usuario administrativo mediante email/username y contraseña.

- **Ruta:** `/api/admin/auth/login`
- **Método HTTP:** `POST`
- **Autenticación requerida:** No

#### Cuerpo de la Petición (`Request Body`)
```json
{
  "login": "admin@aura.com",
  "password": "mi_password_segura"
}
```
*Nota: El campo `login` acepta tanto la dirección de correo electrónico como el nombre de usuario (`username`).*

#### Respuestas Exitosa (HTTP 200 OK)
```json
{
  "access_token": "1|Yt4eSuEspqQmInrysqxrPIjr2cqxdPKjbCrrZMT513285798",
  "refresh_token": "2|NVlp5tjntbAbwC1Cd6F8sAqpRWtCGfaN4ew1UNOEc2c5d72d",
  "token_type": "bearer",
  "expires_in": 10800,
  "user": {
    "id": 1,
    "name": "Administrador Principal",
    "username": "admin",
    "email": "admin@aura.com",
    "phone": "5551234567",
    "role_id": 1,
    "is_active": 1,
    "role": {
      "id": 1,
      "name": "admin",
      "hierarchy_level": 0
    }
  }
}
```

---

### 3.2. Renovación de Sesión (`Refresh Token`)

Genera un nuevo **Access Token** de 3 horas de vigencia presentando un **Refresh Token** válido.

- **Ruta:** `/api/admin/auth/refresh`
- **Método HTTP:** `POST`
- **Autenticación requerida:** Sí (Bearer Token con `refresh_token`)

#### Encabezados de la Petición (`Headers`)
```http
Authorization: Bearer <refresh_token>
Content-Type: application/json
Accept: application/json
```

#### Respuesta Exitosa (HTTP 200 OK)
```json
{
  "access_token": "3|BCiA5APvwf4VaycxNkf2hQIEu8jKecDbWkf2IULqaffa8436",
  "token_type": "bearer",
  "expires_in": 10800
}
```

---

## 4. Códigos de Estado HTTP y Manejo de Errores

| Código HTTP | Escenario / Razón | Ejemplo de Respuesta JSON |
| :---: | :--- | :--- |
| **200 OK** | Operación exitosa (Login o Renovación de token completada). | Ver ejemplos de respuesta en sección 3. |
| **401 Unauthorized** | Credenciales de login incorrectas o Access Token / Refresh Token expirado/inexistente. | `{"error": "Credenciales incorrectas"}` o `{"message": "Unauthenticated."}` |
| **403 Forbidden** | **1. Cuenta inactiva:**<br>`{"error": "Tu cuenta está inactiva. Por favor, contacta al administrador."}`<br><br>**2. No es administrador:**<br>`{"error": "No tienes permisos de administrador para acceder a este panel"}`<br><br>**3. Usuario baneado:**<br>`{"error": "Usuario baneado", "reason": "Motivo del ban"}`<br><br>**4. Intento de Refresh con Access Token:**<br>`{"error": "El token provisto no es válido para renovar sesión"}` |
| **422 Unprocessable Content** | Errores de validación en los campos requeridos (`login` o `password` faltantes). | `{"message": "The login field is required.", "errors": {"login": ["The login field is required."]}}` |

---

## 5. Resumen de Diferencias entre API Mobile y API Admin

- **Ruta de Login:**
  - Móvil: `/api/mobile/auth/login` (solo usuarios residentes/guardias)
  - Admin: `/api/admin/auth/login` (solo usuarios con rol admin/superadmin)
- **Ruta de Refresh:**
  - Móvil: `/api/mobile/auth/refresh`
  - Admin: `/api/admin/auth/refresh`
- Ambas arquitecturas comparten la misma durabilidad (Access Token: 3h, Refresh Token: 30d) y el sistema de permisos de token de Sanctum.
