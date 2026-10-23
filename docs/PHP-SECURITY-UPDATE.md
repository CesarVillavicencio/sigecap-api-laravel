# Actualización de seguridad de PHP (octubre de 2026)

Las vulnerabilidades [CVE-2026-109618 y CVE-2026-109619](https://www.php.net/archive/2026.php#2026-10-21-5) afectan al motor PHP que atiende peticiones web. Cambiar dependencias de Laravel no corrige el motor instalado en el servidor.

Versiones corregidas publicadas por PHP: **8.2.35, 8.3.36, 8.4.27 y 8.5.12**. `composer.json` exige al menos la versión corregida dentro de cada una de esas ramas.

## Despliegue

1. Identificar la versión y el SAPI utilizados por el servidor web, no solo por la terminal. Comprobar también el PHP usado por tareas programadas y trabajadores de cola.
2. Actualizar el PHP del servidor a una de las versiones corregidas o a una versión posterior de la misma rama. En Linux, usar el paquete de seguridad del proveedor si incorpora el parche.
3. Verificar que se conservaron `php.ini`, extensiones necesarias, certificados y ajustes de correo. Reiniciar Apache, PHP-FPM y trabajadores de cola según corresponda.
4. Ejecutar `composer install --no-dev --prefer-dist --no-interaction` con el PHP actualizado y comprobar `composer check-platform-reqs --no-dev`.
5. Confirmar la versión que responde a peticiones web y probar inscripción, correo, QR y descarga de constancias.

En Laragon local se instaló PHP **8.3.36 Thread Safe**, que corresponde a Apache con `mod_php`. El proyecto Java SIGECAP no ejecuta PHP y no requiere un cambio de versión por estas CVE.
