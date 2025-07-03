document.addEventListener('DOMContentLoaded', function() {
    const formResetPass = document.getElementById('formResetPass');
    
    if (formResetPass) {
        formResetPass.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('txtEmailReset').value.trim();
            
            // Validación básica
            if (!email) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'El correo electrónico es obligatorio',
                    confirmButtonColor: '#3b82f6',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            
            // Validar formato de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Email inválido',
                    text: 'Por favor, ingresa un formato de correo electrónico válido',
                    confirmButtonColor: '#3b82f6',
                    confirmButtonText: 'Corregir',
                    footer: '<small>Ejemplo: usuario@dominio.com</small>'
                });
                return;
            }
            
            const btn = document.getElementById('btnResetPass');
            const originalText = btn.innerHTML;
            
            // Deshabilitar botón y mostrar loading
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Enviando...';
            
            let formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.status) {
                    Swal.fire({
                        icon: 'success',
                        title: '📧 ¡Email enviado!',
                        html: `
                            <div style="text-align: left;">
                                <p><strong>Se ha enviado un enlace de recuperación a:</strong></p>
                                <p style="background: #f0f9ff; padding: 10px; border-radius: 5px; color: #0369a1;">
                                    <i class="fas fa-envelope"></i> ${email}
                                </p>
                                <div style="margin-top: 15px; padding: 10px; background: #fef3c7; border-radius: 5px; border-left: 4px solid #f59e0b;">
                                    <strong>⚠️ Importante:</strong>
                                    <ul style="margin: 5px 0; padding-left: 20px;">
                                        <li>Revisa tu <strong>bandeja de entrada</strong></li>
                                        <li>Verifica la carpeta de <strong>spam</strong></li>
                                        <li>El enlace expira en <strong>1 hora</strong></li>
                                    </ul>
                                </div>
                            </div>
                        `,
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'Ir a mi correo',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showCancelButton: true,
                        cancelButtonText: 'Ir al login',
                        cancelButtonColor: '#6b7280'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Intentar abrir Gmail
                            window.open('https://mail.google.com', '_blank');
                            setTimeout(() => {
                                window.location.href = base_url() + '/login';
                            }, 1000);
                        } else {
                            window.location.href = base_url() + '/login';
                        }
                    });
                } else {
                    // Determinar el tipo de error para mostrar mensaje apropiado
                    let errorIcon = 'error';
                    let errorTitle = 'Error';
                    let errorHtml = '';
                    let showActions = false;

                    // Error: No se encontró el email
                    if (data.msg.includes('No encontramos una cuenta asociada')) {
                        errorIcon = 'warning';
                        errorTitle = '📧 Email no encontrado';
                        errorHtml = `
                            <div style="text-align: left;">
                                <p>No encontramos una cuenta asociada al correo:</p>
                                <p style="background: #fee2e2; padding: 10px; border-radius: 5px; color: #b91c1c; font-weight: bold;">
                                    <i class="fas fa-envelope"></i> ${email}
                                </p>
                                <div style="margin-top: 15px; padding: 10px; background: #fef3c7; border-radius: 5px;">
                                    <strong>💡 ¿Qué puedes hacer?</strong>
                                    <ul style="margin: 5px 0; padding-left: 20px;">
                                        <li>Verifica que hayas escrito el <strong>correo correcto</strong></li>
                                        <li>Revisa si tienes <strong>otra cuenta</strong> con un email diferente</li>
                                        <li>Contacta al <strong>administrador</strong> si crees que es un error</li>
                                        <li>Si no tienes cuenta, solicita que te <strong>registren</strong></li>
                                    </ul>
                                </div>
                            </div>
                        `;
                        showActions = true;
                    }
                    // Error: Cuenta inactiva
                    else if (data.msg.includes('cuenta se encuentra inactiva')) {
                        errorIcon = 'warning';
                        errorTitle = '⚠️ Cuenta Inactiva';
                        errorHtml = `
                            <div style="text-align: left;">
                                <p>Tu cuenta está temporalmente <strong>inactiva</strong>:</p>
                                <p style="background: #fee2e2; padding: 10px; border-radius: 5px; color: #b91c1c;">
                                    <i class="fas fa-user-slash"></i> ${email}
                                </p>
                                <div style="margin-top: 15px; padding: 10px; background: #f0f9ff; border-radius: 5px;">
                                    <strong>🔧 Para reactivar tu cuenta:</strong>
                                    <ul style="margin: 5px 0; padding-left: 20px;">
                                        <li>Contacta al <strong>administrador del sistema</strong></li>
                                        <li>Proporciona tu <strong>correo electrónico</strong></li>
                                        <li>Explica que necesitas <strong>reactivar tu cuenta</strong></li>
                                        <li>Solicita confirmación cuando esté <strong>activa nuevamente</strong></li>
                                    </ul>
                                </div>
                            </div>
                        `;
                        showActions = true;
                    }
                    // Error: Problema al enviar correo
                    else if (data.msg.includes('No pudimos enviar')) {
                        errorIcon = 'error';
                        errorTitle = '📧 Error de Envío';
                        errorHtml = `
                            <div style="text-align: left;">
                                <p>Hubo un problema técnico al enviar el correo:</p>
                                <div style="background: #fee2e2; padding: 10px; border-radius: 5px; margin: 10px 0;">
                                    <strong>Error:</strong> ${data.msg}
                                </div>
                                <div style="background: #f0f9ff; padding: 10px; border-radius: 5px;">
                                    <strong>🔧 Posibles soluciones:</strong>
                                    <ul style="margin: 5px 0; padding-left: 20px;">
                                        <li>Intenta nuevamente en <strong>unos minutos</strong></li>
                                        <li>Verifica tu <strong>conexión a internet</strong></li>
                                        <li>Contacta al <strong>soporte técnico</strong> si persiste</li>
                                    </ul>
                                </div>
                            </div>
                        `;
                    }
                    // Error genérico
                    else {
                        errorIcon = 'error';
                        errorTitle = 'Error inesperado';
                        errorHtml = `
                            <div style="text-align: left;">
                                <p>${data.msg}</p>
                                <div style="margin-top: 10px; padding: 10px; background: #fee2e2; border-radius: 5px;">
                                    <strong>Si el problema persiste:</strong>
                                    <ul style="margin: 5px 0; padding-left: 20px;">
                                        <li>Contacta al administrador del sistema</li>
                                        <li>Proporciona detalles del error</li>
                                    </ul>
                                </div>
                            </div>
                        `;
                    }

                    // Configurar botones según el tipo de error
                    let swalConfig = {
                        icon: errorIcon,
                        title: errorTitle,
                        html: errorHtml,
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'Intentar nuevamente'
                    };

                    if (showActions) {
                        swalConfig.showCancelButton = true;
                        swalConfig.cancelButtonText = 'Ir al login';
                        swalConfig.cancelButtonColor = '#6b7280';
                        swalConfig.footer = '<small>Si necesitas ayuda, contacta al administrador</small>';
                    }

                    Swal.fire(swalConfig).then((result) => {
                        if (showActions && !result.isConfirmed) {
                            window.location.href = base_url() + '/login';
                        }
                        // Si confirma "Intentar nuevamente", se queda en la página
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    html: `
                        <p>No se pudo enviar la solicitud.</p>
                        <div style="margin-top: 10px; padding: 10px; background: #fee2e2; border-radius: 5px;">
                            <strong>Posibles causas:</strong>
                            <ul style="margin: 5px 0; padding-left: 20px; text-align: left;">
                                <li>Problema de conexión a internet</li>
                                <li>Error temporal del servidor</li>
                                <li>Configuración de email incorrecta</li>
                            </ul>
                        </div>
                    `,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Reintentar',
                    showCancelButton: true,
                    cancelButtonText: 'Volver al login',
                    cancelButtonColor: '#6b7280'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        window.location.href = base_url() + '/login';
                    }
                });
            })
            .finally(() => {
                // Restaurar botón
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
        
        // Validación en tiempo real para email
        const txtEmailReset = document.getElementById('txtEmailReset');
        const errorEmail = document.getElementById('error-email');
        
        if (txtEmailReset && errorEmail) {
            txtEmailReset.addEventListener('input', function() {
                const email = this.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (email.length > 0) {
                    if (!emailRegex.test(email)) {
                        this.classList.add('border-red-500');
                        this.classList.remove('border-gray-300', 'border-green-500');
                        errorEmail.textContent = 'Formato de correo inválido';
                        errorEmail.classList.remove('hidden');
                    } else {
                        this.classList.remove('border-red-500');
                        this.classList.add('border-green-500');
                        this.classList.remove('border-gray-300');
                        errorEmail.classList.add('hidden');
                    }
                } else {
                    this.classList.remove('border-red-500', 'border-green-500');
                    this.classList.add('border-gray-300');
                    errorEmail.classList.add('hidden');
                }
            });
        }
    }
});

// Función auxiliar para obtener base_url
function base_url() {
    return window.location.protocol + "//" + window.location.host + "/project";
}
