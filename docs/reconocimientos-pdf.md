# Reconocimientos con nombre

## Reconocimiento del instructor

`GET /api/v1/course-edition-instructors/{id}/pdf` genera el reconocimiento a partir del **ID de asignación**. Obtiene el nombre desde `instructor.name`, el tema desde `module.name` y la fecha desde `courseEdition.start_date`, en español (por ejemplo, `19 de octubre de 2026`). Si no hay módulo, admite `topic` para asignaciones anteriores; sin ninguno de los dos responde 409. Una asignación inexistente devuelve 404.

El nombre, tema y fecha se colocan en los tres espacios del PDF original. Las posiciones y tamaños están en `config/certificates.php`. La fecha usada es la de inicio de la edición porque los módulos no tienen fecha propia. No se requiere una inscripción o constancia de participante para descargar el reconocimiento del instructor.

Ejemplo con datos ficticios: `storage/app/reconocimiento-instructor-ejemplo.pdf`. El evento, lugar, firma y fecha impresa del pie siguen siendo parte fija de la plantilla.

## Descarga anterior por constancia de participante

`GET /api/v1/certificates/{id}/pdf` descarga el reconocimiento de una constancia existente. El ID corresponde a la constancia, no al participante. Requiere que la inscripción siga vigente y tenga asistencia, como la emisión de constancias actual; no agrega una comprobación de estado finished de la edición.

El nombre se obtiene de `first_name`, `first_last_name` y `second_last_name` del participante asociado. Se conservan acentos y ñ con una fuente Unicode. No se recibe el nombre por query ni se modifica la plantilla original. La descarga no escribe en `file_path` ni guarda copias por participante.

Se utiliza FPDI con tFPDF para importar directamente `resources/pdf/reconocimiento.pdf`, conservando su tamaño carta horizontal y diseño. Dompdf genera documentos desde HTML; no se necesita para esta superposición sobre un PDF existente.

La posición y tamaño del nombre se ajustan en `config/certificates.php`. Las coordenadas son puntos desde la esquina superior izquierda de la página. El nombre se centra en el espacio bajo el título, con reducción de fuente y salto de línea para nombres largos.

La plantilla proporcionada dice **participación como ponente**, menciona un evento específico y octubre de 2026. La descarga anterior por constancia agrega únicamente el nombre del participante; la nueva descarga por asignación de instructor completa también tema y fecha. Para constancias de participantes de otros cursos se debe sustituir o adaptar la plantilla.

Ejemplo generado con datos ficticios: `storage/app/reconocimiento-ejemplo.pdf`. El código de ejecución no depende de Python ni de herramientas externas de conversión de PDF.
