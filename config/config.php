<?php


// Configuración de Email - Elegir una opción:

// OPCIÓN 1: Gmail (Configuración activa para enviar emails reales)
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USER = 'gilalejandro926@gmail.com'; // Tu email
const SMTP_PASS = 'actu ldpn dhrh jccu'; // Contraseña de aplicación de Gmail
const SMTP_SECURE = 'tls';
const FROM_EMAIL = 'gilalejandro926@gmail.com';
const FROM_NAME = 'Sistema Recuperadora';

// OPCIÓN 2: MailHog (Para desarrollo - captura emails sin enviar)
// const SMTP_HOST = 'localhost';
// const SMTP_PORT = 1025;
// const SMTP_USER = '';
// const SMTP_PASS = '';
// const SMTP_SECURE = '';
// const FROM_EMAIL = 'noreply@localhost.local';
// const FROM_NAME = 'Sistema Recuperadora';

// OPCIÓN 3: Mailtrap (Para testing)
// const SMTP_HOST = 'smtp.mailtrap.io';
// const SMTP_PORT = 2525;
// const SMTP_USER = 'tu-usuario-mailtrap';
// const SMTP_PASS = 'tu-password-mailtrap';
// const SMTP_SECURE = 'tls';
// const FROM_EMAIL = 'noreply@recuperadora.local';
// const FROM_NAME = 'Sistema Recuperadora';

?>