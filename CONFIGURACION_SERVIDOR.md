# Configuración del Servidor

## Configuración de BASE_URL para diferentes entornos

Esta aplicación ahora usa la variable `BASE_URL` de forma dinámica para adaptarse a diferentes servidores. Ya no es necesario tener rutas hardcodeadas como `/project/`.

### Configuración Local (XAMPP, WAMP, etc.)

1. Edita el archivo `env` en la raíz del proyecto
2. Modifica la variable `APP_URL` según tu configuración:

```env
# Para desarrollo local con carpeta "project"
APP_URL=http://localhost/project

# Para desarrollo local con otro nombre de carpeta
APP_URL=http://localhost/mi-aplicacion

# Para desarrollo local en el root
APP_URL=http://localhost
```

### Configuración en Servidor de Producción

#### Servidor con dominio propio

```env
# Con HTTPS (recomendado)
APP_URL=https://www.midominio.com

# Sin carpeta adicional
APP_URL=https://miempresa.com

# En una subcarpeta
APP_URL=https://midominio.com/sistema
```

#### Servidor compartido o subdominio

```env
# Subdominio
APP_URL=https://sistema.midominio.com

# Carpeta pública en hosting compartido
APP_URL=https://midominio.com/public_html/sistema
```

### Configuración con IP del servidor

```env
# IP local (desarrollo)
APP_URL=http://192.168.1.100/project

# IP pública (servidor en red)
APP_URL=http://200.123.45.67/sistema
```

## Verificar la Configuración

1. Después de cambiar `APP_URL` en el archivo `env`, limpia la caché del navegador
2. Accede a la aplicación
3. Verifica que todos los recursos (CSS, JS, imágenes) se carguen correctamente
4. Prueba la navegación entre las diferentes páginas

## Solución de Problemas

### Las páginas no cargan correctamente

- Verifica que `APP_URL` en el archivo `env` coincida exactamente con la URL base de tu servidor
- Asegúrate de que no haya espacios al inicio o al final de la URL
- No incluyas la barra diagonal final: ❌ `http://localhost/project/` ✅ `http://localhost/project`

### Los recursos (CSS/JS/imágenes) no cargan

- Verifica los permisos de las carpetas `app/assets/`
- Revisa la configuración de `.htaccess` si usas Apache
- Verifica que el archivo `env` esté en la raíz del proyecto

### Errores 404 en las rutas

- Asegúrate de que el módulo `mod_rewrite` de Apache esté habilitado
- Verifica que el archivo `.htaccess` exista en la raíz
- Comprueba que `APP_URL` no tenga barras diagonales dobles

## Notas Importantes

- **NUNCA** subas el archivo `env` al repositorio de producción con datos sensibles
- Cambia las claves de seguridad (`JWT_SECRET`, `SESSION_SECRET`, etc.) en producción
- En producción, configura `APP_ENV=production` y `APP_DEBUG=false`

## Cambios Realizados

Se han actualizado los siguientes archivos para usar `BASE_URL`:

### PHP
- ✅ `public/header.php` - Todos los enlaces del menú y recursos
- ✅ `public/footer.php` - Scripts y recursos
- ✅ `app/views/login/*.php` - Páginas de login y recuperación
- ✅ `helpers/helpers.php` - Función `base_url()`
- ✅ Todas las vistas que tenían rutas hardcodeadas

### JavaScript
- ✅ `functions_resetpassword.js` - Función base_url con fallback
- ✅ `functions_resetpass.js` - Función base_url con fallback
- ✅ `functions_sueldos.js` - Constante base_url
- ✅ `shepherd-tours.js` - Selectores actualizados

### Configuración
- ✅ `config/config.php` - Define BASE_URL desde env
- ✅ `env` - Variable APP_URL configurada
