<?php
// The welcome e-mail offered for packages that use this module.
Configure::set('Openplex.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thank you for choosing us for your hosting!

Your OpenPlex account is ready:

Control panel: you can open it from your client area (Log in to OpenPlex), or at the address we gave you
Username: {service.openplex_username}
Password: {service.openplex_password}
Website: {service.openplex_domain}

Thank you for your business!',
        'html' => '<p>Thank you for choosing us for your hosting!</p>
<p>Your OpenPlex account is ready:</p>
<p>Control panel: you can open it from your client area (<em>Log in to OpenPlex</em>), or at the address we gave you<br />Username: {service.openplex_username}<br />Password: {service.openplex_password}<br />Website: {service.openplex_domain}</p>
<p>Thank you for your business!</p>'
    ],
    'es_es' => [
        'lang' => 'es_es',
        'text' => '¡Gracias por confiar en nosotros para tu alojamiento!

Tu cuenta de OpenPlex está lista:

Panel de control: puedes abrirlo desde tu área de cliente (Entrar en OpenPlex), o en la dirección que te indicamos
Usuario: {service.openplex_username}
Contraseña: {service.openplex_password}
Sitio web: {service.openplex_domain}

¡Gracias!',
        'html' => '<p>¡Gracias por confiar en nosotros para tu alojamiento!</p>
<p>Tu cuenta de OpenPlex está lista:</p>
<p>Panel de control: puedes abrirlo desde tu área de cliente (<em>Entrar en OpenPlex</em>), o en la dirección que te indicamos<br />Usuario: {service.openplex_username}<br />Contraseña: {service.openplex_password}<br />Sitio web: {service.openplex_domain}</p>
<p>¡Gracias!</p>'
    ],
]);
