# Operaciones administrativas

Cada módulo admite `GET /api/v1/{modulo}`, `GET /api/v1/{modulo}/{id}`, `POST /api/v1/{modulo}`, `PATCH /api/v1/{modulo}/{id}` y `DELETE /api/v1/{modulo}/{id}`.

Los controladores heredan las operaciones comunes de `CrudController`. Cada controlador define sus validaciones y reglas de negocio. POST devuelve 201 con `data` y `Location`; PATCH devuelve 200 con `data`; DELETE devuelve 204. Los errores de validación usan 422, los registros inexistentes 404 y los conflictos de negocio o integridad 409.

PATCH recibe solamente los campos que cambian y valida su combinación con los valores actuales. Se rechazan cuerpos vacíos y campos desconocidos. `null` solo se permite en las columnas opcionales indicadas por las reglas de cada controlador.

## Campos editables

| Módulo | Campos de entrada |
| --- | --- |
| dependencies | name, acronym, active |
| participants | dependency_id, dependency_name, first_name, first_last_name, second_last_name, curp, area, position, institutional_email, phone, active |
| instructors | name, position, institution, email, phone, active |
| courses | name, activity, description, objective, target_audience, duration_hours, modality, active |
| course-editions | course_id, start_date, end_date, start_time, end_time, location, max_capacity, participant_target, registration_start, registration_end, status |
| course-edition-modules | course_edition_id, name, description, display_order |
| course-edition-instructors | course_edition_id, instructor_id, module_id, topic, display_order |
| registrations | participant_id, course_edition_id, status |
| attendances | registration_method, registration_id (manual), qr_token (qr) o folio (folio), device, notes; course_edition_id es una comprobación opcional |
| certificates | registration_id, file_path |

Los IDs, fechas de auditoría, folios y tokens se generan en el servidor. Al crear cursos, ediciones y asistencias se requieren los encabezados `X-SIGBCS-User-Id` y `X-SIGBCS-Responsibility-Id`; no se aceptan sus columnas de auditoría en el cuerpo. El middleware de autenticación/autorización no forma parte de esta implementación: validar esos encabezados no autentica al consumidor.

Para cambiar el método o inscripción de una asistencia, enviar el método y exclusivamente su identificador correspondiente. Para modificar solo device o notes no hace falta reenviar el identificador. Reasignar una constancia exige asistencia en su inscripción de destino y regenera su token de verificación.

## Comportamiento de DELETE

| Módulo | Resultado |
| --- | --- |
| dependencies, participants | Desactiva con active=false; conserva el registro y sus relaciones. |
| registrations | Cambia status a cancelled; conserva historial. |
| courses | Borra el curso y sus ediciones, módulos y asignaciones únicamente si no hay inscripciones, incluidas las canceladas. |
| course-editions | Borra la edición, módulos y asignaciones únicamente si no tiene inscripciones. |
| instructors | Borra únicamente si no tiene asignaciones. |
| course-edition-modules | Borra el módulo y sus asignaciones de instructores. |
| course-edition-instructors | Borra la asignación. |
| attendances | Borra únicamente si su inscripción no tiene constancia. |
| certificates | Borra el registro de constancia; no elimina archivos del disco. |

No se permiten reasignaciones de inscripciones con asistencia o constancia, ni mover módulos con asignaciones a otra edición. Una asignación debe apuntar a un módulo de su propia edición.

## Verificación

`tests/Feature/ApiCrudTest.php` prueba los diez módulos sobre SQLite en memoria, utilizando las migraciones de negocio reales. El adaptador de pruebas omite exclusivamente los `ALTER TABLE ... ADD CONSTRAINT` específicos de PostgreSQL. Las pruebas verifican las reglas de la API, las claves foráneas y los índices; no comprueban los CHECK ni la concurrencia de PostgreSQL. No escriben en la base configurada del usuario.

Las migraciones existentes contienen dos archivos que crean `users`; este cambio no los modifica ni ejecuta migraciones en la base actual. Correo y autenticación siguen pendientes. La descarga `GET /api/v1/certificates/{id}/pdf` agrega el nombre a la plantilla de reconocimiento; ver [Reconocimientos PDF](reconocimientos-pdf.md).

## Códigos QR

Los siguientes GET generan y descargan SVG usando `endroid/qr-code`, sin guardar imágenes ni cambiar los tokens existentes:

| Endpoint | Contenido del QR |
| --- | --- |
| `/api/v1/courses/{id}/qr.svg` | `${PUBLIC_BASE_URL}/cursos/{id}` |
| `/api/v1/course-editions/{id}/qr.svg` | `${PUBLIC_BASE_URL}/c/{public_token}` |
| `/api/v1/registrations/{id}/qr.svg` | El `qr_token` de la inscripción, sin URL. |

Configura `PUBLIC_BASE_URL` en `.env` con el origen del frontend público, incluyendo puerto y subdirectorio si corresponden. Por ejemplo, `PUBLIC_BASE_URL=http://192.168.1.50:3000`. Usa la dirección real accesible desde el teléfono que escanea, no `0.0.0.0` ni `localhost`. Después de cambiarla, ejecuta `php artisan config:clear` si tienes configuración en caché.

Los QR de curso y edición devuelven 503 si falta esa URL o es inválida. Los tres devuelven 404 si el registro no existe. Se envía `Content-Type: image/svg+xml`, descarga con nombre de archivo y `Cache-Control: private, no-store`.

Las fichas públicas de prueba están disponibles en `/cursos`, `/cursos/{id}` y `/c/{public_token}`. Las vistas Blade están en `resources/views/public-courses`: `layout` contiene el estilo, `index` el catálogo, `show` la ficha y formulario, y `confirmation` el folio y QR descargable. No requieren compilar assets.

Solo se muestran cursos activos con ediciones distintas de draft/cancelled. El formulario requiere una dependencia activa y una edición con inscripciones abiertas, periodo vigente y cupo. El registro es transaccional; reutiliza participantes únicamente si coinciden CURP, correo y dependencia, y no modifica su ficha. La edición de una URL con token se resuelve en el servidor.

Los GET y POST admiten respuestas JSON con `Accept: application/json`. La confirmación HTML permite descargar el QR sin acceder a los endpoints administrativos. El correo no se envía en esta versión de prueba y se informa `email: not_configured`. La verificación pública de constancias y la protección con `X-API-Key` siguen pendientes.
