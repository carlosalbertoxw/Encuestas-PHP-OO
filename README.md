# PHP-OO-Template
Plantilla PHP OO para desarrollar aplicaciones web

Antes de ejecutar el código por favor de hacer lo siguiente:

Asegurarse de tener habilitado el mod_rewrite en el servidor.

Asegurarse de que los permisos de los directorios sean 775 o (rwxrwxr-x) y el de los archivos 664 o (rw-rw-r).

Archivo /.htaccess

Configurar ruta de: ErrorDocument 404
Configurar ruta de: ErrorDocument 403

Archivo /application/config/constants.php

Configurar: RELEASED
Configurar: TRANSFER_PROTOCOL
Configurar: WEB_PATH
Configurar: WEB_SITE_NAME

Archivo /application/db/connection.php

Configurar parametros de conexión a base de datos en el método open_connection