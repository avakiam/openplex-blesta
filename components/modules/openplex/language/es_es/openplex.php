<?php
// Básico
$lang['Openplex.name'] = 'OpenPlex';
$lang['Openplex.description'] = 'OpenPlex es un panel de control de alojamiento de código abierto para servidores Linux: webs, bases de datos, correo, DNS y copias de seguridad, con cuentas de cliente y de revendedor.';
$lang['Openplex.module_row'] = 'Servidor';
$lang['Openplex.module_row_plural'] = 'Servidores';
$lang['Openplex.module_group'] = 'Grupo de servidores';
$lang['Openplex.order_options.first'] = 'Primer servidor';
$lang['Openplex.tab_login'] = 'Entrar en OpenPlex';

// Servidores
$lang['Openplex.add_module_row'] = 'Añadir servidor';
$lang['Openplex.add_module_group'] = 'Añadir grupo de servidores';
$lang['Openplex.back_to_manage'] = 'Volver';
$lang['Openplex.manage.tab_rows'] = 'Servidores';
$lang['Openplex.manage.tab_groups'] = 'Grupos de servidores';
$lang['Openplex.manage.rows.name'] = 'Nombre';
$lang['Openplex.manage.rows.host'] = 'Host';
$lang['Openplex.manage.rows.port'] = 'Puerto';
$lang['Openplex.manage.rows.options'] = 'Opciones';
$lang['Openplex.manage.rows.none'] = 'No hay servidores.';
$lang['Openplex.manage.rows.confirm_delete'] = '¿Seguro que quieres eliminar este servidor?';
$lang['Openplex.manage.groups.name'] = 'Grupo';
$lang['Openplex.manage.groups.servers'] = 'Servidores';
$lang['Openplex.manage.groups.none'] = 'No hay grupos de servidores.';
$lang['Openplex.manage.groups.confirm_delete'] = '¿Seguro que quieres eliminar este grupo de servidores?';
$lang['Openplex.manage.edit'] = 'Editar';
$lang['Openplex.manage.delete'] = 'Eliminar';
$lang['Openplex.add_row.box_title'] = 'Añadir servidor OpenPlex';
$lang['Openplex.add_row.submit'] = 'Añadir servidor';
$lang['Openplex.edit_row.box_title'] = 'Editar servidor OpenPlex';
$lang['Openplex.edit_row.submit'] = 'Guardar';
$lang['Openplex.row.server_name'] = 'Nombre';
$lang['Openplex.row.host_name'] = 'Host';
$lang['Openplex.row.host_name.hint'] = 'El dominio del panel (por ejemplo panel.example.com) o su dirección IP.';
$lang['Openplex.row.use_ssl'] = 'Usar HTTPS (recomendado: el panel tiene un dominio con certificado)';
$lang['Openplex.row.port'] = 'Puerto';
$lang['Openplex.row.port.hint'] = '443 con HTTPS. Sin él, 7080: mantén ese puerto cerrado a internet salvo para Blesta.';
$lang['Openplex.row.api_key'] = 'Clave de API';
$lang['Openplex.row.api_key.hint'] = 'Créala en OpenPlex, en Claves de API, como administrador (cualquier tipo de cuenta) o como revendedor (las cuentas serán sus clientes). Se comprueba al guardar.';

// Paquetes
$lang['Openplex.package.role'] = 'Tipo de cuenta';
$lang['Openplex.package.role.customer'] = 'Cliente';
$lang['Openplex.package.role.reseller'] = 'Revendedor';
$lang['Openplex.package.role.tooltip'] = 'Un revendedor gestiona sus propios clientes.';
$lang['Openplex.package.max_domains'] = 'Sitios web';
$lang['Openplex.package.max_domains.tooltip'] = 'Cuántos sitios web puede tener la cuenta; 0 = sin límite.';
$lang['Openplex.package.max_databases'] = 'Bases de datos';
$lang['Openplex.package.max_databases.tooltip'] = 'Cuántas bases de datos puede tener la cuenta; 0 = sin límite.';
$lang['Openplex.package.max_db_size_mb'] = 'Tamaño de base de datos (MB)';
$lang['Openplex.package.max_db_size_mb.tooltip'] = 'Límite de tamaño por defecto de cada base de datos; 0 = sin límite.';
$lang['Openplex.package.create_website'] = 'Crear el sitio web';
$lang['Openplex.package.create_website.tooltip'] = 'Crea un sitio web para el dominio del servicio (el dominio pasa a ser obligatorio).';
$lang['Openplex.package.runtime'] = 'Entorno del sitio web';
$lang['Openplex.package.runtime_version'] = 'Versión del entorno';
$lang['Openplex.package.runtime_version.tooltip'] = 'Por ejemplo 8.3; vacío = la versión por defecto.';
$lang['Openplex.package.locale'] = 'Idioma del panel';
$lang['Openplex.package.locale.browser'] = 'El del navegador del cliente';

// Servicios
$lang['Openplex.service_field.domain'] = 'Dominio';
$lang['Openplex.service_field.domain.tooltip'] = 'El sitio web que se crea para la cuenta, como example.com.';
$lang['Openplex.service_field.domain.edit_tooltip'] = 'Solo se guarda aquí: el sitio web en sí se cambia en el panel.';
$lang['Openplex.service_field.username'] = 'Usuario';
$lang['Openplex.service_field.username.tooltip'] = 'Opcional: de 3 a 32 letras minúsculas, números, "_", "." o "-", empezando por una letra. Vacío = a partir del dominio.';
$lang['Openplex.service_field.password'] = 'Contraseña';
$lang['Openplex.service_field.password.tooltip'] = 'Opcional: al menos 10 caracteres con letras y números. Vacío = una generada (o, al editar, sin cambios).';
$lang['Openplex.service_field.id'] = 'ID de la cuenta de OpenPlex';
$lang['Openplex.service_field.id.tooltip'] = 'Solo para una cuenta que ya existe, añadida sin aprovisionarla.';
$lang['Openplex.service_field.id.edit_tooltip'] = 'La cuenta del panel que controla este servicio.';
$lang['Openplex.service_info.account'] = 'Cuenta';
$lang['Openplex.service_info.panel'] = 'Panel';
$lang['Openplex.service_info.login'] = 'Entrar en OpenPlex';
$lang['Openplex.service_info.login_admin'] = 'Entrar como este cliente';
$lang['Openplex.tab_login.intro'] = 'Abre el panel de OpenPlex, con la sesión ya iniciada, en una pestaña nueva. El enlace funciona una vez y dura dos minutos.';
$lang['Openplex.tab_login.button'] = 'Entrar en OpenPlex';

// Errores
$lang['Openplex.!error.server_name.empty'] = 'Escribe un nombre para el servidor.';
$lang['Openplex.!error.host_name.valid'] = 'Escribe un host o una dirección IP válidos.';
$lang['Openplex.!error.port.valid'] = 'Escribe un puerto válido.';
$lang['Openplex.!error.api_key.empty'] = 'Escribe una clave de API de OpenPlex.';
$lang['Openplex.!error.api_key.connection'] = 'No se puede conectar con OpenPlex con estos datos, o la clave de API es incorrecta o es de una cuenta de cliente: usa la de un administrador o un revendedor.';
$lang['Openplex.!error.meta[max_domains].valid'] = 'El número de sitios web debe ser un número entero (0 = sin límite).';
$lang['Openplex.!error.meta[max_databases].valid'] = 'El número de bases de datos debe ser un número entero (0 = sin límite).';
$lang['Openplex.!error.meta[max_db_size_mb].valid'] = 'El tamaño de base de datos debe ser un número entero de MB (0 = sin límite).';
$lang['Openplex.!error.meta[role].valid'] = 'Elige un tipo de cuenta.';
$lang['Openplex.!error.meta[runtime].valid'] = 'Elige un entorno para el sitio web.';
$lang['Openplex.!error.meta[runtime_version].valid'] = 'La versión del entorno no es válida.';
$lang['Openplex.!error.meta[locale].valid'] = 'Elige un idioma para el panel.';
$lang['Openplex.!error.openplex_domain.format'] = 'Escribe un dominio válido, como example.com.';
$lang['Openplex.!error.openplex_username.format'] = 'El usuario debe tener de 3 a 32 letras minúsculas, números, "_", "." o "-", empezando por una letra.';
$lang['Openplex.!error.openplex_password.format'] = 'La contraseña necesita al menos 10 caracteres con letras y números.';
$lang['Openplex.!error.openplex_id.format'] = 'El ID de la cuenta debe ser un número.';
$lang['Openplex.!error.module_row.missing'] = 'No hay ningún servidor OpenPlex disponible para este paquete.';
$lang['Openplex.!error.no_account'] = 'El servicio no tiene cuenta de OpenPlex.';
$lang['Openplex.!error.login.inactive'] = 'El servicio no está activo.';
