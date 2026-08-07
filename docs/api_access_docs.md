# Documentación de la API de Acceso (Validación QR)

Esta API está diseñada para recibir la lectura de un código de acceso (como un código QR escaneado por un dispositivo) y validar si cumple con todas las reglas establecidas para permitir el acceso. Además, la API registra automáticamente cada intento de validación (exitoso o fallido) en el sistema de logs.

## Endpoint

- **Ruta:** `/api/access/qr`
- **Método HTTP:** `POST`
- **Controlador:** `ValidationController@validateAccess`

## Parámetros de Entrada (Body)

La petición debe enviar un objeto JSON o los datos en formato `form-data` con los siguientes campos:

| Campo | Tipo | Obligatorio | Descripción |
| :--- | :--- | :---: | :--- |
| `hash` | String | **Sí** | Es el código de acceso único que se desea validar. |<>
| `device_identifier` | String | No | Identificador del dispositivo o lector que está realizando la validación. Útil para el registro (log). |

*Ejemplo de petición JSON:*
```json
{
  "hash": "codigo-secreto-12345",
  "device_identifier": "Lector-Entrada-Principal"
}
```

## Proceso y Reglas de Validación

El controlador procesa la solicitud evaluando las siguientes reglas estrictamente en este orden. Si el código falla en alguna, se deniega el acceso inmediatamente:

1. **Existencia:** El código (`hash`) debe existir en la base de datos de `AccessCode`.
2. **Eliminación (Soft Delete):** El código no debe haber sido eliminado previamente.
3. **Estado Activo:** La propiedad `is_active` debe ser verdadera (no inhabilitado temporalmente).
4. **Vigencia (Fechas):** La fecha/hora actual debe estar dentro del rango definido por `valid_from` y `valid_until`.
5. **Límite de Usos:** 
   - El número de usos actual (`uses`) no debe superar el máximo permitido (`max_uses`).
   - Si el código es de tipo `temp` (temporal antiguo), se restringe a un máximo de **1 uso**.
6. **Días Autorizados:** Si el código tiene días específicos asignados (`active_days`), el día actual de la semana debe coincidir con ellos (Lunes = 1, Domingo = 7).
7. **Rango de Horarios:** Si se ha definido un horario de acceso (`start_time` a `end_time`), la hora actual del sistema debe estar comprendida dentro de ese rango.

*Si todas las reglas pasan:* El sistema incrementa el contador de usos (`uses`) y concede el acceso.

## Estructura de la Respuesta

La API siempre responde con un objeto JSON (independientemente de si el acceso es concedido o denegado) con la siguiente estructura:

```json
{
  "status": "granted" | "denied",
  "message": "Mensaje descriptivo del resultado",
  "data": {
    "guest_name": "Nombre del Invitado",
    "residence_id": 1
  } // "data" será null si el código no fue encontrado
}
```

### Códigos de Estado HTTP y Ejemplos

**✅ 200 OK - Acceso Concedido**
Ocurre cuando el código pasó todas las validaciones correctamente.
```json
{
  "status": "granted",
  "message": "Acceso permitido",
  "data": {
    "guest_name": "Juan Pérez",
    "residence_id": 45
  }
}
```

**❌ 403 Forbidden - Acceso Denegado**
Ocurre cuando el código existe pero incumple alguna regla (caducado, sin usos, día incorrecto, etc.).
```json
{
  "status": "denied",
  "message": "Límite de usos alcanzado, usos: 5, máximo: 5",
  "data": {
    "guest_name": "María Gómez",
    "residence_id": 45
  }
}
```

**🚫 404 Not Found - Código no encontrado**
Ocurre si el `hash` enviado no existe en la base de datos.
```json
{
  "status": "denied",
  "message": "Código no encontrado",
  "data": null
}
```

## Registro (Logging) Interno
Independientemente del resultado, justo antes de enviar la respuesta, el sistema utiliza el método `logAndRespond()` para guardar un nuevo registro en el modelo `AccessLog`. Este registro incluye:
- El ID del código de acceso y residencia (si se encontró).
- El código escaneado (`hash`).
- El estado resultante (`granted` o `denied`).
- El mensaje de error o éxito.
- El identificador del dispositivo (`device_identifier`).
- El tipo de acceso (`qr`) y método (`scan`).
