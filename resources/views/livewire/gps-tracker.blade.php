<div
    x-data="gpsTrackerComponent()"
    x-init="init()"
    class="gps-tracker-container"
>
    <!-- Pantalla de Permisos Inicial -->
    <div x-show="showPermissionsScreen" x-transition class="permissions-screen">
        <div class="permissions-container">
            <div class="permissions-icon">📍</div>
            <h2>Permisos de Ubicación</h2>
            <p class="permissions-description">
                Para que GPS Tracker funcione correctamente, necesitamos acceso a tu ubicación GPS.
                Esto nos permite registrar tu velocidad y posición en tiempo real.
            </p>

            <div class="permissions-steps">
                <div class="permission-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Habilitar GPS</h4>
                        <p>Asegúrate de que el GPS de tu dispositivo esté encendido</p>
                    </div>
                </div>
                <div class="permission-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Permitir Acceso</h4>
                        <p>Concede permisos de ubicación cuando el navegador lo solicite</p>
                    </div>
                </div>
                <div class="permission-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Seleccionar "Preciso"</h4>
                        <p>Elige la opción de ubicación precisa para mejor accuracy</p>
                    </div>
                </div>
            </div>

            <div class="permissions-status" id="permissionsStatus">
                <div class="status-item">
                    <span class="status-icon" id="gpsStatus">⏳</span>
                    <span class="status-text">Verificando GPS...</span>
                </div>
                <div class="status-item">
                    <span class="status-icon" id="locationStatus">⏳</span>
                    <span class="status-text">Verificando permisos de ubicación...</span>
                </div>
                <div class="status-item">
                    <span class="status-icon" id="notificationStatus">⏳</span>
                    <span class="status-text">Verificando permisos de notificaciones...</span>
                </div>
            </div>

            <button
                @click="requestPermissions()"
                :disabled="requestingPermissions"
                class="btn btn-primary btn-lg"
                x-text="requestingPermissions ? 'Solicitando...' : 'Solicitar Permisos'"
            ></button>

            <div class="permissions-help">
                <p><strong>¿Problemas?</strong></p>
                <ul>
                    <li>Ve a Configuración > Aplicaciones > GPS Tracker > Permisos</li>
                    <li>Asegúrate de que el GPS esté activado en tu dispositivo</li>
                    <li>Permite la ubicación "Todo el tiempo" para funcionamiento en segundo plano</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Banner de Instalación Manual (para Android) -->
    <div x-show="showInstallBanner" x-transition class="install-banner">
        <div class="install-banner-content">
            <div class="install-banner-icon">📱</div>
            <div class="install-banner-text">
                <h4>Instalar GPS Tracker</h4>
                <p>Agrega esta aplicación a tu pantalla principal para un acceso rápido</p>
            </div>
            <div class="install-banner-actions">
                <button @click="installPwa()" class="btn btn-primary btn-sm">Instalar</button>
                <button @click="dismissInstallBanner()" class="btn btn-secondary btn-sm">Más tarde</button>
            </div>
        </div>
    </div>

    <!-- App Principal -->
    <div x-show="!showPermissionsScreen" x-transition x-cloak>
        <!-- Header -->
        <header class="app-header">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="gps-indicator" :class="{ 'active': isTracking }"></div>
                        <div>
                            <h1 class="app-title">GPS Tracker</h1>
                            <p class="app-subtitle" x-text="isTracking ? 'Rastreando ubicación...' : 'Detenido'"></p>
                        </div>
                    </div>
                    <div class="header-controls">
                        <button
                            @click="startTracking()"
                            x-show="!isTracking"
                            class="btn btn-primary"
                        >Iniciar</button>
                        <button
                            @click="stopTracking()"
                            x-show="isTracking"
                            class="btn btn-danger"
                        >Detener</button>
                        <button @click="showSettingsModal = true" class="btn btn-secondary">⚙️</button>
                        <button @click="diagnoseStorage()" class="btn btn-info" title="Diagnosticar problemas de almacenamiento">🔍</button>
                        <button @click="checkForUpdates()" class="btn btn-warning" title="Verificar actualizaciones">🔄</button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="app-main">
            <div class="container">
                <!-- Current Location Card -->
                <div class="card location-card">
                    <div class="card-header">
                        <h5>Ubicación Actual</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="location-info">
                                    <div class="info-item">
                                        <span class="label">Latitud:</span>
                                        <span x-text="currentLocation ? currentLocation.latitude.toFixed(6) : '-'">-</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Longitud:</span>
                                        <span x-text="currentLocation ? currentLocation.longitude.toFixed(6) : '-'">-</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Velocidad:</span>
                                        <span x-text="currentLocation ? Math.round(currentLocation.speed) + ' km/h' : '-'">-</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Precisión:</span>
                                        <span x-text="currentLocation ? Math.round(currentLocation.accuracy) + ' m' : '-'">-</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="map" class="location-map">
                                    <div x-show="currentLocation" style="text-align: center; color: var(--primary-color);">
                                        <div>📍</div>
                                        <div x-text="`Lat: ${currentLocation.latitude.toFixed(4)}`"></div>
                                        <div x-text="`Lng: ${currentLocation.longitude.toFixed(4)}`"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <div class="stats-icon speeding">🚗</div>
                                <h3 x-text="eventCounts.speeding">0</h3>
                                <p>Excesos de Velocidad</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <div class="stats-icon acceleration">⚡</div>
                                <h3 x-text="eventCounts.acceleration">0</h3>
                                <p>Aceleraciones Bruscas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <div class="stats-icon braking">🛑</div>
                                <h3 x-text="eventCounts.braking">0</h3>
                                <p>Frenadas Bruscas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <div class="stats-icon crash">💥</div>
                                <h3 x-text="eventCounts.crash">0</h3>
                                <p>Choques</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Events Log -->
                <div class="card events-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Eventos Recientes</h5>
                        <button @click="clearEvents()" class="btn btn-sm btn-outline-secondary">Limpiar</button>
                    </div>
                    <div class="card-body">
                        <div class="events-list">
                            <template x-for="event in recentEvents" :key="event.id">
                                <div class="event-item" :class="event.event_type">
                                    <div class="event-icon" x-text="getEventIcon(event.event_type)"></div>
                                    <div class="event-content">
                                        <h6 class="event-title" x-text="getEventTitle(event.event_type)"></h6>
                                        <p class="event-time" x-text="formatEventTime(event.event_time)"></p>
                                    </div>
                                    <div class="event-value" x-text="event.speed ? Math.round(event.speed) + ' km/h' : ''"></div>
                                </div>
                            </template>
                            <p x-show="recentEvents.length === 0" class="text-muted text-center">No hay eventos registrados</p>
                        </div>
                    </div>
                </div>

                <!-- Settings Modal -->
                <div x-show="showSettingsModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="modal">
                    <div @click.away="showSettingsModal = false" class="modal-content">
                        <div class="modal-header">
                            <h5>Configuración</h5>
                            <button @click="showSettingsModal = false" class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="maxSpeed">Velocidad Máxima (km/h):</label>
                                <input type="number" x-model="config.maxSpeed" class="form-control" min="1" max="200">
                            </div>
                            <div class="form-group">
                                <label for="interval">Intervalo de Registro (segundos):</label>
                                <input type="number" x-model="config.interval" class="form-control" min="5" max="300">
                            </div>
                            <div class="form-group">
                                <label for="sensitivity">Sensibilidad de Eventos:</label>
                                <select x-model="config.sensitivity" class="form-control">
                                    <option value="low">Baja</option>
                                    <option value="medium">Media</option>
                                    <option value="high">Alta</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" x-model="config.notifications" class="form-check-input">
                                    <label class="form-check-label">Notificaciones de Eventos</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" x-model="config.autoStart" class="form-check-input">
                                    <label class="form-check-label">Inicio Automático</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <button @click="recheckPermissions()" class="btn btn-outline-info">
                                    Verificar Permisos Nuevamente
                                </button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button @click="saveSettings()" class="btn btn-primary">Guardar</button>
                            <button @click="showSettingsModal = false" class="btn btn-secondary">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Loading Overlay -->
        <div x-show="loading" x-transition class="loading-overlay">
            <div class="loading-spinner"></div>
            <p x-text="loadingMessage"></p>
        </div>
    </div>

    <script>
        function gpsTrackerComponent() {
            return {
                // Estado de la aplicación
                showPermissionsScreen: true,
                isTracking: false,
                currentLocation: null,
                eventCounts: @js($eventCounts),
                recentEvents: @js($recentEvents),
                showSettingsModal: false,
                loading: false,
                loadingMessage: 'Cargando...',
                requestingPermissions: false,
                showInstallBanner: false,
                deferredPrompt: null,

                // Configuración
                config: @js($config),

                // Livewire events
                init() {
                    // Escuchar eventos de Livewire
                    Livewire.on('location-updated', (data) => {
                        this.currentLocation = data[0];
                        this.updateMapDisplay();
                    });

                    Livewire.on('event-created', (data) => {
                        this.recentEvents.unshift(data[0]);
                        if (this.recentEvents.length > 50) {
                            this.recentEvents = this.recentEvents.slice(0, 50);
                        }
                    });

                    Livewire.on('events-cleared', () => {
                        this.recentEvents = [];
                    });

                    Livewire.on('config-saved', (data) => {
                        this.config = data[0];
                        this.showSettingsModal = false;
                    });

                    Livewire.on('error-occurred', (message) => {
                        alert('Error: ' + message[0]);
                    });

                    // Inicializar GPS Tracker JS
                    this.initializeGpsTracker();

                    // Configurar instalación PWA
                    this.setupPwaInstallation();
                },

                async initializeGpsTracker() {
                    // Aquí va la lógica de inicialización del GPS
                    console.log('Inicializando GPS Tracker con Livewire...');

                    // Verificar permisos existentes
                    await this.checkExistingPermissions();
                },

                async requestPermissions() {
                    this.requestingPermissions = true;

                    try {
                        // Solicitar permisos de geolocalización
                        await this.requestLocationPermission();

                        // Solicitar permisos de notificación
                        if (this.config.notifications) {
                            await this.requestNotificationPermission();
                        }

                        // Verificar si todos los permisos están concedidos
                        const allGranted = await this.checkExistingPermissions();

                        if (allGranted) {
                            this.showPermissionsScreen = false;
                            if (this.config.autoStart) {
                                setTimeout(() => this.startTracking(), 1000);
                            }
                        } else {
                            alert('Algunos permisos no fueron concedidos. Verifica la configuración del navegador.');
                        }
                    } catch (error) {
                        console.error('Error solicitando permisos:', error);
                        alert('Error solicitando permisos. Inténtalo de nuevo.');
                    } finally {
                        this.requestingPermissions = false;
                    }
                },

                async checkExistingPermissions() {
                    try {
                        // Verificar permisos de geolocalización
                        if (navigator.permissions) {
                            const locationPermission = await navigator.permissions.query({ name: 'geolocation' });
                            this.updateLocationStatus(locationPermission.state);

                            if (locationPermission.state === 'granted') {
                                // Verificar si podemos obtener la ubicación actual
                                return new Promise((resolve) => {
                                    navigator.geolocation.getCurrentPosition(
                                        () => {
                                            this.updateGPSStatus('active');
                                            resolve(true);
                                        },
                                        () => {
                                            this.updateGPSStatus('inactive');
                                            resolve(false);
                                        },
                                        { timeout: 5000 }
                                    );
                                });
                            } else {
                                return false;
                            }
                        }
                        return false;
                    } catch (error) {
                        console.error('Error verificando permisos:', error);
                        return false;
                    }
                },

                async requestLocationPermission() {
                    return new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(
                            () => {
                                this.updateLocationStatus('granted');
                                this.updateGPSStatus('active');
                                resolve();
                            },
                            (error) => {
                                this.updateLocationStatus('denied');
                                if (error.code === error.PERMISSION_DENIED) {
                                    reject(new Error('Permisos de ubicación denegados'));
                                } else {
                                    reject(new Error('Error obteniendo ubicación'));
                                }
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 300000
                            }
                        );
                    });
                },

                async requestNotificationPermission() {
                    if ('Notification' in window && Notification.permission === 'default') {
                        const permission = await Notification.requestPermission();
                        this.updateNotificationStatus(permission);
                    }
                },

                updateLocationStatus(state) {
                    const statusIcon = document.getElementById('locationStatus');
                    const statusText = statusIcon.nextElementSibling;

                    switch (state) {
                        case 'granted':
                            statusIcon.textContent = '✅';
                            statusText.textContent = 'Permisos de ubicación concedidos';
                            break;
                        case 'denied':
                            statusIcon.textContent = '❌';
                            statusText.textContent = 'Permisos de ubicación denegados';
                            break;
                        case 'prompt':
                            statusIcon.textContent = '⏳';
                            statusText.textContent = 'Solicitando permisos de ubicación...';
                            break;
                    }
                },

                updateNotificationStatus(state) {
                    const statusIcon = document.getElementById('notificationStatus');
                    const statusText = statusIcon.nextElementSibling;

                    switch (state) {
                        case 'granted':
                            statusIcon.textContent = '✅';
                            statusText.textContent = 'Permisos de notificaciones concedidos';
                            break;
                        case 'denied':
                            statusIcon.textContent = '❌';
                            statusText.textContent = 'Permisos de notificaciones denegados';
                            break;
                        case 'default':
                            statusIcon.textContent = '⏳';
                            statusText.textContent = 'Permisos de notificaciones no solicitados';
                            break;
                    }
                },

                updateGPSStatus(state) {
                    const statusIcon = document.getElementById('gpsStatus');
                    const statusText = statusIcon.nextElementSibling;

                    switch (state) {
                        case 'active':
                            statusIcon.textContent = '✅';
                            statusText.textContent = 'GPS activo y funcionando';
                            break;
                        case 'inactive':
                            statusIcon.textContent = '❌';
                            statusText.textContent = 'GPS inactivo o no disponible';
                            break;
                    }
                },

                async startTracking() {
                    this.isTracking = true;
                    this.showLoading('Iniciando rastreo GPS...');

                    try {
                        // Aquí se integraría con la API de geolocalización
                        // Por ahora simulamos el inicio
                        setTimeout(() => {
                            this.hideLoading();
                            console.log('Rastreo GPS iniciado');
                        }, 1000);
                    } catch (error) {
                        this.hideLoading();
                        alert('Error iniciando rastreo GPS');
                    }
                },

                stopTracking() {
                    this.isTracking = false;
                    console.log('Rastreo GPS detenido');
                },

                saveSettings() {
                    @this.saveConfiguration(this.config);
                },

                clearEvents() {
                    if (confirm('¿Estás seguro de que quieres limpiar todos los eventos?')) {
                        @this.clearEvents();
                    }
                },

                getEventIcon(eventType) {
                    const icons = {
                        'speeding': '🚗',
                        'harsh_acceleration': '⚡',
                        'harsh_braking': '🛑',
                        'crash': '💥'
                    };
                    return icons[eventType] || '📍';
                },

                getEventTitle(eventType) {
                    const titles = {
                        'speeding': 'Exceso de Velocidad',
                        'harsh_acceleration': 'Aceleración Brusca',
                        'harsh_braking': 'Frenada Brusca',
                        'crash': 'Posible Choque'
                    };
                    return titles[eventType] || 'Evento';
                },

                formatEventTime(eventTime) {
                    return new Date(eventTime).toLocaleTimeString('es-ES', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                updateMapDisplay() {
                    // Simulación de actualización del mapa
                    console.log('Mapa actualizado');
                },

                showLoading(message = 'Cargando...') {
                    this.loading = true;
                    this.loadingMessage = message;
                },

                hideLoading() {
                    this.loading = false;
                },

                async diagnoseStorage() {
                    console.log('=== DIAGNÓSTICO DE ALMACENAMIENTO ===');

                    // Verificar localStorage
                    const configTest = 'test_config_' + Date.now();
                    localStorage.setItem(configTest, 'test_value');
                    const retrievedConfig = localStorage.getItem(configTest);
                    if (retrievedConfig === 'test_value') {
                        console.log('✅ localStorage funcionando correctamente');
                        localStorage.removeItem(configTest);
                    } else {
                        console.log('❌ localStorage no funcionando');
                    }

                    // Verificar espacio disponible
                    if ('storage' in navigator && 'estimate' in navigator.storage) {
                        const estimate = await navigator.storage.estimate();
                        console.log('💾 Espacio de almacenamiento:');
                        console.log(`   - Usado: ${Math.round(estimate.usage / 1024 / 1024)} MB`);
                        console.log(`   - Disponible: ${Math.round(estimate.quota / 1024 / 1024)} MB`);
                    }

                    alert('Diagnóstico completado. Revisa la consola (F12) para ver los resultados.');
                },

                async checkForUpdates() {
                    this.showLoading('Verificando actualizaciones...');
                    setTimeout(() => {
                        this.hideLoading();
                        alert('Verificación completada. Revisa la consola para ver si hay actualizaciones.');
                    }, 2000);
                },

                async recheckPermissions() {
                    const hasPermissions = await this.checkExistingPermissions();
                    if (hasPermissions) {
                        alert('✅ Todos los permisos están concedidos correctamente');
                    } else {
                        alert('❌ Algunos permisos no están concedidos. Ve a la configuración del navegador.');
                    }
                },

                setupPwaInstallation() {
                    // Escuchar evento beforeinstallprompt de Android
                    window.addEventListener('beforeinstallprompt', (e) => {
                        console.log('PWA: beforeinstallprompt event fired');
                        e.preventDefault();
                        this.deferredPrompt = e;

                        // Verificar si el usuario ya desechó el banner recientemente
                        const dismissedTime = localStorage.getItem('pwa-install-dismissed');
                        if (dismissedTime) {
                            const hoursSinceDismissed = (Date.now() - parseInt(dismissedTime)) / (1000 * 60 * 60);
                            if (hoursSinceDismissed < 24) {
                                console.log('PWA: Install banner dismissed recently');
                                return;
                            }
                        }

                        this.showInstallBanner = true;
                    });

                    // Escuchar evento de instalación exitosa
                    window.addEventListener('appinstalled', () => {
                        console.log('PWA: App installed successfully');
                        this.showInstallBanner = false;
                        this.deferredPrompt = null;
                        alert('¡Aplicación instalada exitosamente! 🎉');
                    });

                    // Verificar si ya está instalada
                    if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
                        console.log('PWA: Already installed');
                        this.showInstallBanner = false;
                    } else {
                        // Mostrar banner después de 30 segundos si no aparece el prompt automático
                        setTimeout(() => {
                            const dismissedTime = localStorage.getItem('pwa-install-dismissed');
                            if (dismissedTime) {
                                const hoursSinceDismissed = (Date.now() - parseInt(dismissedTime)) / (1000 * 60 * 60);
                                if (hoursSinceDismissed < 24) return;
                            }

                            if (!this.deferredPrompt && !this.showInstallBanner) {
                                this.showInstallBanner = true;
                            }
                        }, 30000);
                    }
                },

                async installPwa() {
                    if (this.deferredPrompt) {
                        // Usar el prompt automático de Android
                        this.deferredPrompt.prompt();
                        const { outcome } = await this.deferredPrompt.userChoice;

                        if (outcome === 'accepted') {
                            console.log('PWA: User accepted the install prompt');
                            this.deferredPrompt = null;
                            this.showInstallBanner = false;
                        }
                    } else {
                        // Fallback: instrucciones manuales
                        this.showInstallInstructions();
                    }
                },

                showInstallInstructions() {
                    const isAndroid = /Android/i.test(navigator.userAgent);
                    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

                    let message = 'Para instalar la aplicación:\n\n';

                    if (isAndroid) {
                        message += '1. Toca el botón de menú (⋮) en Chrome\n';
                        message += '2. Selecciona "Instalar aplicación" o "Agregar a pantalla principal"\n';
                        message += '3. Confirma la instalación';
                    } else if (isIOS) {
                        message += '1. Toca el botón de compartir (□) en Safari\n';
                        message += '2. Selecciona "Agregar a pantalla principal"\n';
                        message += '3. Confirma para crear el ícono';
                    } else {
                        message += '1. Usa el botón de instalar del navegador\n';
                        message += '2. O agrega manualmente a la pantalla principal';
                    }

                    alert(message);
                },

                dismissInstallBanner() {
                    this.showInstallBanner = false;
                    // Recordar que el usuario desechó el banner por 24 horas
                    localStorage.setItem('pwa-install-dismissed', Date.now().toString());
                }
            }
        }
    </script>

    <style>
        /* Banner de instalación PWA */
        .install-banner {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
            z-index: 1000;
            animation: slideUp 0.3s ease-out;
        }

        .install-banner-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .install-banner-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        .install-banner-text {
            flex: 1;
        }

        .install-banner-text h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 600;
        }

        .install-banner-text p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .install-banner-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .install-banner .btn {
            padding: 8px 16px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .install-banner .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .install-banner .btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .install-banner .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .install-banner .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (min-width: 768px) {
            .install-banner {
                max-width: 400px;
                left: auto;
                right: 20px;
            }
        }
    </style>
</div>