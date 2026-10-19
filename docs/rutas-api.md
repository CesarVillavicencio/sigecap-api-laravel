# Rutas de SIGECAP API

Inventario del código al 19 de octubre de 2026. Las rutas administrativas reciben el prefijo `/api` de Laravel y el prefijo `/v1` de `routes/api.php`. Las rutas públicas declaradas en `bootstrap/app.php` no llevan `/api`.

`{id}` es un entero. `{public_token}` y `{token}` son cadenas hexadecimales de 64 caracteres. Las rutas de listado aceptan `page` y `limit` (máximo 100), además de los filtros indicados abajo.

## Operaciones administrativas

Cada fila enumera las cinco rutas reales de su recurso. `GET` de colección lista registros; `GET` con `{id}` obtiene uno; `POST` crea; `PATCH` actualiza; `DELETE` ejecuta el comportamiento de eliminación definido por el controlador.

| Recurso | Método | Ruta |
| --- | --- | --- |
| Dependencias | GET | `/api/v1/dependencies` |
| Dependencias | GET | `/api/v1/dependencies/{id}` |
| Dependencias | POST | `/api/v1/dependencies` |
| Dependencias | PATCH | `/api/v1/dependencies/{id}` |
| Dependencias | DELETE | `/api/v1/dependencies/{id}` |
| Cursos | GET | `/api/v1/courses` |
| Cursos | GET | `/api/v1/courses/{id}` |
| Cursos | POST | `/api/v1/courses` |
| Cursos | PATCH | `/api/v1/courses/{id}` |
| Cursos | DELETE | `/api/v1/courses/{id}` |
| Participantes | GET | `/api/v1/participants` |
| Participantes | GET | `/api/v1/participants/{id}` |
| Participantes | POST | `/api/v1/participants` |
| Participantes | PATCH | `/api/v1/participants/{id}` |
| Participantes | DELETE | `/api/v1/participants/{id}` |
| Ediciones de curso | GET | `/api/v1/course-editions` |
| Ediciones de curso | GET | `/api/v1/course-editions/{id}` |
| Ediciones de curso | POST | `/api/v1/course-editions` |
| Ediciones de curso | PATCH | `/api/v1/course-editions/{id}` |
| Ediciones de curso | DELETE | `/api/v1/course-editions/{id}` |
| Inscripciones | GET | `/api/v1/registrations` |
| Inscripciones | GET | `/api/v1/registrations/{id}` |
| Inscripciones | POST | `/api/v1/registrations` |
| Inscripciones | PATCH | `/api/v1/registrations/{id}` |
| Inscripciones | DELETE | `/api/v1/registrations/{id}` |
| Instructores | GET | `/api/v1/instructors` |
| Instructores | GET | `/api/v1/instructors/{id}` |
| Instructores | POST | `/api/v1/instructors` |
| Instructores | PATCH | `/api/v1/instructors/{id}` |
| Instructores | DELETE | `/api/v1/instructors/{id}` |
| Asignaciones de instructores | GET | `/api/v1/course-edition-instructors` |
| Asignaciones de instructores | GET | `/api/v1/course-edition-instructors/{id}` |
| Asignaciones de instructores | POST | `/api/v1/course-edition-instructors` |
| Asignaciones de instructores | PATCH | `/api/v1/course-edition-instructors/{id}` |
| Asignaciones de instructores | DELETE | `/api/v1/course-edition-instructors/{id}` |
| Módulos de edición | GET | `/api/v1/course-edition-modules` |
| Módulos de edición | GET | `/api/v1/course-edition-modules/{id}` |
| Módulos de edición | POST | `/api/v1/course-edition-modules` |
| Módulos de edición | PATCH | `/api/v1/course-edition-modules/{id}` |
| Módulos de edición | DELETE | `/api/v1/course-edition-modules/{id}` |
| Asistencias | GET | `/api/v1/attendances` |
| Asistencias | GET | `/api/v1/attendances/{id}` |
| Asistencias | POST | `/api/v1/attendances` |
| Asistencias | PATCH | `/api/v1/attendances/{id}` |
| Asistencias | DELETE | `/api/v1/attendances/{id}` |
| Constancias de participantes | GET | `/api/v1/certificates` |
| Constancias de participantes | GET | `/api/v1/certificates/{id}` |
| Constancias de participantes | POST | `/api/v1/certificates` |
| Constancias de participantes | PATCH | `/api/v1/certificates/{id}` |
| Constancias de participantes | DELETE | `/api/v1/certificates/{id}` |

### Descargas administrativas

| Método | Ruta | Resultado |
| --- | --- | --- |
| GET | `/api/v1/courses/{id}/qr.svg` | QR SVG del curso |
| GET | `/api/v1/course-editions/{id}/qr.svg` | QR SVG de la edición |
| GET | `/api/v1/registrations/{id}/qr.svg` | QR SVG de la inscripción |
| GET | `/api/v1/course-edition-instructors/{id}/pdf` | PDF de ponencia; `{id}` es el ID de asignación, no el del instructor |
| GET | `/api/v1/certificates/{id}/pdf` | PDF de constancia; `{id}` es el ID de la constancia |

### Filtros de listados

| Ruta de colección | Filtros adicionales |
| --- | --- |
| `/api/v1/dependencies` | `active` |
| `/api/v1/courses` | `active` |
| `/api/v1/participants` | `active`, `dependency_id` |
| `/api/v1/course-editions` | `course_id`, `status` |
| `/api/v1/registrations` | `participant_id`, `course_edition_id`, `status` |
| `/api/v1/instructors` | `active` |
| `/api/v1/course-edition-instructors` | `course_edition_id`, `instructor_id` |
| `/api/v1/course-edition-modules` | `course_edition_id` |
| `/api/v1/attendances` | `registration_id`, `registered_by` |
| `/api/v1/certificates` | `registration_id` |

## Rutas públicas sin prefijo `/api`

| Método | Ruta | Resultado |
| --- | --- | --- |
| GET | `/cursos` | Catálogo de cursos activos, HTML o JSON |
| GET | `/cursos/{id}` | Página o datos de un curso |
| POST | `/cursos/{id}/inscripciones` | Inscripción pública a un curso |
| GET | `/c/{public_token}` | Página o datos de una edición pública |
| POST | `/c/{public_token}/inscripciones` | Inscripción pública a una edición |
| GET | `/constancias/verificar/{token}` | Verificación pública de constancia; actualmente responde `501 NOT_IMPLEMENTED` |
| GET | `/health` | Salud pública; actualmente responde `501 NOT_IMPLEMENTED` |

## Rutas publicas para el frontend Java bajo `/api/v1/public`

| Metodo | Ruta | Resultado |
| --- | --- | --- |
| GET | `/api/v1/public/cursos` | Catalogo publico en JSON |
| GET | `/api/v1/public/cursos/{id}` | Datos publicos del curso en JSON |
| POST | `/api/v1/public/cursos/{id}/inscripciones` | Inscripcion a una edicion del curso |
| GET | `/api/v1/public/c/{public_token}` | Datos publicos de la edicion en JSON |
| POST | `/api/v1/public/c/{public_token}/inscripciones` | Inscripcion a la edicion |

## Salud interna y pruebas bajo `/api`

| Método | Ruta | Estado |
| --- | --- | --- |
| GET | `/api/v1/health/ready` | Actualmente responde `501 NOT_IMPLEMENTED` |
| GET | `/api/test/test` | Actualmente responde `501 NOT_IMPLEMENTED` |
| GET | `/api/test/pdfTest` | Actualmente responde `501 NOT_IMPLEMENTED` |

## Rutas de Laravel fuera de la API de negocio

| Método | Ruta | Origen |
| --- | --- | --- |
| GET | `/up` | Comprobación de salud configurada por Laravel en `bootstrap/app.php` |
| GET | `/` | Página `welcome` definida en `routes/web.php` |

Las rutas administrativas de este inventario no declaran middleware de autenticación en `routes/api.php` ni en sus archivos de grupo. El cliente Java envía `X-API-Key`, pero la API aún no lo valida en estas rutas. Un proxy externo podría imponer controles adicionales; este documento describe el código del repositorio.

Fuentes: `bootstrap/app.php`, `routes/api.php`, `routes/api/*.php` y `routes/web.php`. Para campos y reglas de negocio, consultar [api-crud.md](api-crud.md); para los PDF, [reconocimientos-pdf.md](reconocimientos-pdf.md).
