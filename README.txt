GESTOR DE ARTÍCULOS — PHP + MySQL

FUNCIONES
- Registrar artículos con nombre, marca, unidades y ubicación.
- Consultar el inventario y buscar por nombre, marca o ubicación.
- Editar y eliminar artículos.
- Ver el total de artículos y la suma de unidades.
- Crear automáticamente la tabla `articulos` si no existe.

INSTALACIÓN
1. Descomprime la carpeta en el directorio público de tu hosting (por ejemplo, htdocs o www).
2. En config.php están configurados los datos de conexión indicados para la base de datos.
3. Asegúrate de que la base de datos `andry_articulosgit` exista en el panel de hosting y que el usuario tenga permisos para crear tablas, consultar, insertar, actualizar y eliminar.
4. Abre en el navegador la dirección donde publicaste la carpeta, por ejemplo: https://tu-dominio/gestor_articulos_php_mysql/
5. La tabla se crea automáticamente al abrir la aplicación.

REQUISITOS
- PHP 8.0 o superior recomendado.
- Extensiones PDO y pdo_mysql habilitadas.
- MySQL/MariaDB.

SEGURIDAD
- No publiques config.php en repositorios públicos.
- Si las credenciales son reales y se han compartido en mensajes o capturas, cambia la contraseña desde el panel de hosting.
- Para un sitio público, configura HTTPS y limita el acceso al panel si corresponde.
