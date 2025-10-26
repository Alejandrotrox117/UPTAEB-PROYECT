<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Romana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'text-primary': '#111827',
                        'text-secondary': '#6b7280',
                    },
                    fontFamily: {
                        display: ["'Roboto Mono'", "monospace"],
                        sans: ["'Inter'", "sans-serif"],
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': {
                                opacity: '0.5',
                                transform: 'translateY(10px) scale(0.98)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0) scale(1)'
                            },
                        },
                        pulseIndicator: {
                            '0%, 100%': {
                                opacity: 1
                            },
                            '50%': {
                                opacity: 0.5
                            },
                        }
                    },
                    animation: {
                        fadeInUp: 'fadeInUp 0.3s ease-out forwards',
                        pulseIndicator: 'pulseIndicator 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                },
            },
        };
    </script>
    <style>
        .animate-weight-change {
            animation: fadeInUp 0.3s ease-out;
        }
        body {
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-white font-sans text-text-primary antialiased">
    <div class="flex flex-col items-center justify-center min-h-screen p-4 sm:p-6 lg:p-8 overflow-hidden">
        <header class="w-full max-w-6xl mx-auto flex justify-between items-start text-text-secondary mb-16 md:mb-24">
            <div class="text-left">
                <p class="text-base sm:text-lg" id="date-display">-</p>
                <p class="font-mono text-base sm:text-lg" id="time-display">-</p>
            </div>
            <div class="flex items-center gap-2 pt-1">
                <div class="w-2.5 h-2.5 rounded-full bg-slate-300 animate-pulseIndicator" id="status-indicator"></div>
                <span class="text-sm sm:text-base" id="status-text">Conectando...</span>
            </div>
        </header>

        <main class="text-center flex-grow flex flex-col items-center justify-center">
            <div class="relative">
                <p class="font-display font-bold text-8xl sm:text-9xl md:text-[12rem] lg:text-[15rem] leading-none text-text-primary tracking-tighter" id="weight-display">
                    0.00
                </p>
                <span class="absolute -right-10 sm:-right-14 md:-right-20 lg:-right-24 bottom-2 sm:bottom-4 md:bottom-8 lg:bottom-10 font-sans font-medium text-2xl sm:text-3xl md:text-5xl text-text-secondary">kg</span>
            </div>
        </main>

        <footer class="w-full max-w-6xl mx-auto text-center py-8">
            <div class="flex flex-col items-center gap-6">
                <img src="./app/assets/img/LOGO-COMPLETO.svg" alt="Logo Empresa" class="mt-12 h-48 sm:h-56 md:h-64 lg:h-80 xl:h-96 opacity-60 hover:opacity-80 transition-opacity duration-300">
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const weightDisplay = document.getElementById('weight-display');
            const timeDisplay = document.getElementById('time-display');
            const dateDisplay = document.getElementById('date-display');
            const statusIndicator = document.getElementById('status-indicator');
            const statusText = document.getElementById('status-text');
            let currentWeight = 0.00;
            let lastSavedWeight = null;
            let lastFetchTime = null;
            let pollInterval = 2000; // Intervalo base de 2 segundos
            let consecutiveNoChanges = 0;

            function updateDateTime() {
                const now = new Date();
                const timeOptions = {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                };
                const dateOptions = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                timeDisplay.textContent = now.toLocaleTimeString('es-ES', timeOptions);
                dateDisplay.textContent = now.toLocaleDateString('es-ES', dateOptions);
            }

            setInterval(updateDateTime, 1000);
            updateDateTime();

            async function saveWeightToDatabase() {
                try {
                    const response = await fetch('./Peso/guardarPesoRomana', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });
                    const result = await response.json();
                    
                    if (result.status) {
                        console.log('Peso guardado en BD:', result);
                        lastSavedWeight = currentWeight;
                    }
                } catch (error) {
                    console.error('Error al guardar peso en BD:', error);
                }
            }

            async function fetchWeight() {
                try {
                    const response = await fetch('./Peso/getUltimoPeso');
                    const data = await response.json();
                    
                    if (data.status && data.data && data.data.peso !== undefined) {
                        const newWeight = parseFloat(data.data.peso);
                        
                        if (!isNaN(newWeight) && newWeight !== currentWeight) {
                            currentWeight = newWeight;
                            weightDisplay.textContent = newWeight.toFixed(2);
                            weightDisplay.classList.add('animate-weight-change');
                            setTimeout(() => {
                                weightDisplay.classList.remove('animate-weight-change');
                            }, 300);

                            // Reiniciar contador de cambios y reducir intervalo
                            consecutiveNoChanges = 0;
                            pollInterval = 2000; // Volver a 2 segundos cuando hay cambios

                            // Guardar en BD si el peso es diferente al último guardado y es mayor a 1kg
                            if (newWeight > 1 && (lastSavedWeight === null || Math.abs(newWeight - lastSavedWeight) > 0.5)) {
                                saveWeightToDatabase();
                            }
                        } else {
                            // Incrementar contador si no hay cambios
                            consecutiveNoChanges++;
                            
                            // Aumentar gradualmente el intervalo si no hay cambios (máximo 10 segundos)
                            if (consecutiveNoChanges > 5) {
                                pollInterval = Math.min(10000, pollInterval + 1000);
                            }
                        }

                        // Update status to connected
                        if (statusIndicator.classList.contains('bg-slate-300')) {
                            statusIndicator.classList.remove('bg-slate-300', 'animate-pulseIndicator');
                            statusIndicator.classList.add('bg-green-500');
                            statusText.textContent = 'Conectado';
                        }
                    }
                } catch (error) {
                    console.error('Error al obtener peso:', error);
                    statusIndicator.classList.remove('bg-green-500');
                    statusIndicator.classList.add('bg-red-500');
                    statusText.textContent = 'Error de conexión';
                }
            }

            // Sistema de polling adaptativo
            async function continuousPolling() {
                await fetchWeight();
                setTimeout(continuousPolling, pollInterval);
            }

            // Iniciar polling inmediatamente
            continuousPolling();
        });
    </script>
</body>
</html>
