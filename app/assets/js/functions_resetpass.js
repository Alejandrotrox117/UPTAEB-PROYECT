(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const formNuevaPassword = document.getElementById('formNuevaPassword');
        
        if (formNuevaPassword) {
            formNuevaPassword.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const password = document.getElementById('txtPassword').value;
                const confirmPassword = document.getElementById('txtConfirmPassword').value;
            
            // Validar que las contraseñas coincidan
            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Contraseñas no coinciden',
                    html: `
                        <div style="text-align: left;">
                            <p>Las contraseñas ingresadas no son idénticas.</p>
                            <div style="margin-top: 10px; padding: 10px; background: #fef3c7; border-radius: 5px;">
                                <strong>💡 Sugerencia:</strong>
                                <ul style="margin: 5px 0; padding-left: 20px;">
                                    <li>Verifica que no tengas <strong>Caps Lock</strong> activado</li>
                                    <li>Asegúrate de escribir exactamente la misma contraseña</li>
                                    <li>Revisa espacios adicionales al inicio o final</li>
                                </ul>
                            </div>
                        </div>
                    `,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Corregir',
                    didOpen: () => {
                        // Enfocar el campo de contraseña
                        document.getElementById('txtPassword').focus();
                    }
                });
                return;
            }
            
            // Validar longitud mínima
            if (password.length < 6) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña muy corta',
                    html: `
                        <div style="text-align: left;">
                            <p>La contraseña debe tener al menos <strong>6 caracteres</strong>.</p>
                            <div style="margin-top: 10px; padding: 10px; background: #f0f9ff; border-radius: 5px;">
                                <strong>🔒 Recomendaciones de seguridad:</strong>
                                <ul style="margin: 5px 0; padding-left: 20px;">
                                    <li>Usa al menos <strong>8 caracteres</strong></li>
                                    <li>Combina <strong>letras y números</strong></li>
                                    <li>Incluye <strong>mayúsculas y minúsculas</strong></li>
                                    <li>Agrega <strong>símbolos especiales</strong> (@, #, $, etc.)</li>
                                </ul>
                            </div>
                        </div>
                    `,
                    confirmButtonColor: '#f59e0b',
                    confirmButtonText: 'Mejorar contraseña',
                    didOpen: () => {
                        document.getElementById('txtPassword').focus();
                    }
                });
                return;
            }
            
            const btn = document.getElementById('btnActualizar');
            const originalText = btn.innerHTML;
            
            // Deshabilitar botón y mostrar loading
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...';
            
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
                        title: '🎉 ¡Contraseña Actualizada!',
                        html: `
                            <div style="text-align: center;">
                                <div style="margin: 20px 0;">
                                    <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 10px; border: 2px solid #22c55e;">
                                        <i class="fas fa-check-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                                        <p style="margin: 0; font-weight: bold;">Tu contraseña ha sido actualizada exitosamente</p>
                                    </div>
                                </div>
                                <div style="background: #f0f9ff; padding: 15px; border-radius: 5px; border-left: 4px solid #3b82f6;">
                                    <p style="margin: 0;"><strong>✅ Ahora puedes:</strong></p>
                                    <ul style="margin: 10px 0; padding-left: 20px; text-align: left;">
                                        <li>Iniciar sesión con tu nueva contraseña</li>
                                        <li>Acceder a todas las funciones del sistema</li>
                                        <li>Tu sesión anterior ha sido cerrada por seguridad</li>
                                    </ul>
                                </div>
                            </div>
                        `,
                        confirmButtonColor: '#10b981',
                        confirmButtonText: '🚀 Ir al Login',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        timer: 5000,
                        timerProgressBar: true,
                        didOpen: () => {
                            // Mostrar countdown
                            const timerInterval = setInterval(() => {
                                const timerLeft = Swal.getTimerLeft();
                                if (timerLeft) {
                                    const seconds = Math.ceil(timerLeft / 1000);
                                    Swal.update({
                                        footer: `<small>Redirección automática en ${seconds} segundos...</small>`
                                    });
                                }
                            }, 100);
                            
                            Swal.getConfirmButton().addEventListener('click', () => {
                                clearInterval(timerInterval);
                            });
                        }
                    }).then(() => {
                        window.location.href = base_url() + '/login';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al actualizar',
                        text: data.msg,
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'Intentar nuevamente',
                        footer: '<small>Si el problema persiste, solicita un nuevo enlace de recuperación</small>'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    html: `
                        <p>No se pudo procesar la solicitud.</p>
                        <div style="margin-top: 10px; padding: 10px; background: #fee2e2; border-radius: 5px;">
                            <strong>⚠️ Importante:</strong>
                            <ul style="margin: 5px 0; padding-left: 20px; text-align: left;">
                                <li>Tu enlace de recuperación sigue siendo válido</li>
                                <li>Puedes intentar nuevamente en unos momentos</li>
                                <li>Si el problema persiste, contacta al administrador</li>
                            </ul>
                        </div>
                    `,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Reintentar',
                    showCancelButton: true,
                    cancelButtonText: 'Solicitar nuevo enlace',
                    cancelButtonColor: '#6b7280'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        window.location.href = base_url() + '/login/resetPassword';
                    }
                });
            })
            .finally(() => {
                // Restaurar botón
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
        
        // Validación en tiempo real para confirmar contraseña
        const txtPassword = document.getElementById('txtPassword');
        const txtConfirmPassword = document.getElementById('txtConfirmPassword');
        const errorConfirm = document.getElementById('error-confirm');
        
        if (txtConfirmPassword && txtPassword && errorConfirm) {
            txtConfirmPassword.addEventListener('input', function() {
                const password = txtPassword.value;
                const confirmPassword = this.value;
                
                if (confirmPassword.length > 0) {
                    if (password !== confirmPassword) {
                        this.classList.add('border-red-500');
                        this.classList.remove('border-gray-300');
                        errorConfirm.textContent = 'Las contraseñas no coinciden';
                        errorConfirm.classList.remove('hidden');
                    } else {
                        this.classList.remove('border-red-500');
                        this.classList.add('border-green-500');
                        errorConfirm.classList.add('hidden');
                    }
                } else {
                    this.classList.remove('border-red-500', 'border-green-500');
                    this.classList.add('border-gray-300');
                    errorConfirm.classList.add('hidden');
                }
            });
        }
        
        // Validación de longitud mínima para password
        const errorPassword = document.getElementById('error-password');
        
        if (txtPassword && errorPassword) {
            txtPassword.addEventListener('input', function() {
                const password = this.value;
                
                if (password.length > 0 && password.length < 6) {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-gray-300');
                    errorPassword.textContent = 'La contraseña debe tener al menos 6 caracteres';
                    errorPassword.classList.remove('hidden');
                } else if (password.length >= 6) {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-green-500');
                    errorPassword.classList.add('hidden');
                } else {
                    this.classList.remove('border-red-500', 'border-green-500');
                    this.classList.add('border-gray-300');
                    errorPassword.classList.add('hidden');
                }
            });
        }
        } // Cierre del if (formNuevaPassword)
    }); // Cierre del DOMContentLoaded

})(); // Fin de IIFE - Código protegido
