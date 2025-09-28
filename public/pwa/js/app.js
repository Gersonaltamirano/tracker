// GPS Tracker PWA - Aplicación principal
class GPSTracker {
    constructor() {
        this.watchId = null;
        this.isTracking = false;
        this.lastPosition = null;
        this.lastAcceleration = null;
        this.eventCounts = {
            speeding: 0,
            acceleration: 0,
            braking: 0,
            crash: 0
        };

        // Configuración por defecto
        this.config = {
            maxSpeed: 95, // km/h
            interval: 60000, // 60 segundos
            sensitivity: 'medium',
            notifications: true,
            autoStart: true
        };

        // Base URL de la API
        this.apiBaseUrl = 'https://gps-tracker.srv-sa.com/api';

        this.init();
    }

    async init() {
        console.log('Inicializando GPS Tracker...');

        // Inicializar base de datos
        await this.initDatabase();

        // Cargar configuración
        await this.loadSettings();

        // Configurar event listeners
        this.setupEventListeners();

        // Registrar service worker
        await this.registerServiceWorker();

        // Solicitar permisos de notificación
        if (this.config.notifications) {
            await this.requestNotificationPermission();
        }

        // Auto-iniciar si está configurado
        if (this.config.autoStart) {
            setTimeout(() => {
                this.startTracking();
            }, 2000);
        }

        // Iniciar sincronización periódica
        setInterval(() => {
            this.syncData();
        }, 300000); // Cada 5 minutos

        console.log('GPS Tracker inicializado');
    }

    async initDatabase() {
        // Inicializar Dexie.js
        this.db = new Dexie('GPSTrackerDB');

        this.db.version(1).stores({
            locationData: '++id, recorded_at, latitude, longitude, speed, synced',
            locationEvents: '++id, event_type, event_time, latitude, longitude, notified, synced'
        });

        console.log('Base de datos inicializada');
    }

    async loadSettings() {
        // Cargar configuración desde localStorage
        const savedConfig = localStorage.getItem('gpsTrackerConfig');
        if (savedConfig) {
            this.config = { ...this.config, ...JSON.parse(savedConfig) };
        }

        // Aplicar configuración a la UI
        this.applySettingsToUI();
    }

    applySettingsToUI() {
        document.getElementById('maxSpeed').value = this.config.maxSpeed;
        document.getElementById('interval').value = this.config.interval / 1000;
        document.getElementById('sensitivity').value = this.config.sensitivity;
        document.getElementById('notifications').checked = this.config.notifications;
        document.getElementById('autoStart').checked = this.config.autoStart;
    }

    setupEventListeners() {
        // Botones de control
        document.getElementById('startBtn').addEventListener('click', () => {
            this.startTracking();
        });

        document.getElementById('stopBtn').addEventListener('click', () => {
            this.stopTracking();
        });

        // Botón de configuración
        document.getElementById('settingsBtn').addEventListener('click', () => {
            this.showSettingsModal();
        });

        // Botón de guardar configuración
        document.getElementById('saveSettingsBtn').addEventListener('click', () => {
            this.saveSettings();
        });

        // Botón de limpiar eventos
        document.getElementById('clearEventsBtn').addEventListener('click', () => {
            this.clearEvents();
        });

        // Cerrar modal
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                this.hideSettingsModal();
            });
        });

        // Cerrar modal al hacer clic fuera
        document.getElementById('settingsModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('settingsModal')) {
                this.hideSettingsModal();
            }
        });

        // Manejar mensajes del service worker
        navigator.serviceWorker?.addEventListener('message', (event) => {
            this.handleServiceWorkerMessage(event.data);
        });
    }

    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/pwa/sw.js');
                console.log('Service Worker registrado:', registration);

                // Escuchar mensajes del service worker
                navigator.serviceWorker.addEventListener('message', (event) => {
                    this.handleServiceWorkerMessage(event.data);
                });
            } catch (error) {
                console.error('Error registrando Service Worker:', error);
            }
        }
    }

    async requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            const permission = await Notification.requestPermission();
            console.log('Permiso de notificación:', permission);
        }
    }

    async startTracking() {
        if (this.isTracking) return;

        console.log('Iniciando rastreo GPS...');

        // Verificar soporte de geolocalización
        if (!navigator.geolocation) {
            this.showError('Tu navegador no soporta geolocalización');
            return;
        }

        try {
            // Solicitar permiso de geolocalización
            const permission = await this.requestGeolocationPermission();

            if (permission !== 'granted') {
                this.showError('Permiso de geolocalización denegado');
                return;
            }

            // Iniciar watchPosition
            this.watchId = navigator.geolocation.watchPosition(
                (position) => this.handlePositionUpdate(position),
                (error) => this.handleGeolocationError(error),
                {
                    enableHighAccuracy: true,
                    maximumAge: 30000,
                    timeout: 27000
                }
            );

            this.isTracking = true;
            this.updateUI();

            // Iniciar sensores de movimiento si están disponibles
            this.startMotionSensors();

            console.log('Rastreo GPS iniciado');
        } catch (error) {
            console.error('Error iniciando rastreo:', error);
            this.showError('Error iniciando rastreo GPS');
        }
    }

    async requestGeolocationPermission() {
        return new Promise((resolve) => {
            navigator.permissions?.query({ name: 'geolocation' }).then((result) => {
                resolve(result.state);
            }).catch(() => {
                resolve('unknown');
            });
        });
    }

    stopTracking() {
        if (!this.isTracking) return;

        console.log('Deteniendo rastreo GPS...');

        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        this.stopMotionSensors();
        this.isTracking = false;
        this.updateUI();

        console.log('Rastreo GPS detenido');
    }

    handlePositionUpdate(position) {
        const { coords, timestamp } = position;
        const speedKmh = (coords.speed * 3.6) || 0; // Convertir m/s a km/h

        // Actualizar posición actual
        this.lastPosition = {
            latitude: coords.latitude,
            longitude: coords.longitude,
            speed: speedKmh,
            accuracy: coords.accuracy,
            altitude: coords.altitude,
            heading: coords.heading,
            recorded_at: new Date(timestamp)
        };

        // Actualizar UI
        this.updateLocationDisplay();

        // Verificar eventos
        this.checkForEvents(this.lastPosition);

        // Guardar en base de datos local
        this.saveLocationData(this.lastPosition);

        // Actualizar indicador GPS
        this.updateGPSIndicator(true);
    }

    handleGeolocationError(error) {
        console.error('Error de geolocalización:', error);
        this.updateGPSIndicator(false);

        switch (error.code) {
            case error.PERMISSION_DENIED:
                this.showError('Permiso de geolocalización denegado');
                break;
            case error.POSITION_UNAVAILABLE:
                this.showError('Ubicación no disponible');
                break;
            case error.TIMEOUT:
                this.showError('Timeout obteniendo ubicación');
                break;
            default:
                this.showError('Error obteniendo ubicación');
        }
    }

    updateLocationDisplay() {
        if (!this.lastPosition) return;

        document.getElementById('latitude').textContent = this.lastPosition.latitude.toFixed(6);
        document.getElementById('longitude').textContent = this.lastPosition.longitude.toFixed(6);
        document.getElementById('speed').textContent = `${Math.round(this.lastPosition.speed)} km/h`;
        document.getElementById('accuracy').textContent = `${Math.round(this.lastPosition.accuracy)} m`;

        // Actualizar mapa (simulado)
        this.updateMapDisplay();
    }

    updateMapDisplay() {
        // Aquí iría la integración con un mapa real (Leaflet, Google Maps, etc.)
        const mapElement = document.getElementById('map');
        if (this.lastPosition) {
            mapElement.innerHTML = `
                <div style="text-align: center; color: var(--primary-color);">
                    <div>📍</div>
                    <div>Lat: ${this.lastPosition.latitude.toFixed(4)}</div>
                    <div>Lng: ${this.lastPosition.longitude.toFixed(4)}</div>
                </div>
            `;
        }
    }

    checkForEvents(position) {
        const now = new Date();
        const timeDiff = this.lastPosition ?
            (now - this.lastPosition.recorded_at) / 1000 : 0;

        // Verificar exceso de velocidad
        if (position.speed > this.config.maxSpeed) {
            this.createEvent('speeding', {
                ...position,
                max_speed: this.config.maxSpeed,
                description: `Velocidad ${Math.round(position.speed)} km/h excede límite de ${this.config.maxSpeed} km/h`
            });
        }

        // Verificar aceleración brusca (si tenemos datos anteriores)
        if (this.lastPosition && timeDiff > 0) {
            const acceleration = Math.abs(position.speed - this.lastPosition.speed) / timeDiff; // m/s²

            if (acceleration > this.getAccelerationThreshold()) {
                if (position.speed > this.lastPosition.speed) {
                    this.createEvent('harsh_acceleration', {
                        ...position,
                        acceleration: acceleration,
                        description: `Aceleración brusca: ${acceleration.toFixed(2)} m/s²`
                    });
                } else {
                    this.createEvent('harsh_braking', {
                        ...position,
                        acceleration: acceleration,
                        description: `Frenada brusca: ${acceleration.toFixed(2)} m/s²`
                    });
                }
            }
        }

        // Verificar choque (impacto súbito)
        if (this.lastAcceleration && timeDiff > 0) {
            const deltaAcceleration = Math.abs(this.lastAcceleration - position.speed) / timeDiff;
            if (deltaAcceleration > 15) { // Umbral de choque
                this.createEvent('crash', {
                    ...position,
                    impact_force: deltaAcceleration,
                    description: `Posible choque detectado: ${deltaAcceleration.toFixed(2)} m/s²`
                });
            }
        }
    }

    getAccelerationThreshold() {
        const thresholds = {
            low: 3,
            medium: 5,
            high: 8
        };
        return thresholds[this.config.sensitivity] || 5;
    }

    async createEvent(eventType, data) {
        const eventData = {
            event_type: eventType,
            event_time: new Date(),
            latitude: data.latitude,
            longitude: data.longitude,
            speed: data.speed,
            max_speed: data.max_speed || null,
            acceleration: data.acceleration || null,
            impact_force: data.impact_force || null,
            description: data.description,
            event_data: data,
            notified: false,
            synced: false
        };

        // Guardar en base de datos local
        await this.db.locationEvents.add(eventData);

        // Actualizar contador
        this.eventCounts[eventType]++;
        this.updateEventCounts();

        // Agregar a la lista de eventos
        this.addEventToList(eventData);

        // Mostrar notificación si está habilitado
        if (this.config.notifications) {
            this.showNotification(eventType, data);
        }

        console.log('Evento creado:', eventType, eventData);
    }

    updateEventCounts() {
        document.getElementById('speedingCount').textContent = this.eventCounts.speeding;
        document.getElementById('accelerationCount').textContent = this.eventCounts.acceleration;
        document.getElementById('brakingCount').textContent = this.eventCounts.braking;
        document.getElementById('crashCount').textContent = this.eventCounts.crash;
    }

    addEventToList(eventData) {
        const eventsList = document.getElementById('eventsList');
        const eventElement = document.createElement('div');
        eventElement.className = `event-item ${eventData.event_type}`;

        const eventTime = new Date(eventData.event_time).toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });

        eventElement.innerHTML = `
            <div class="event-icon">
                ${this.getEventIcon(eventData.event_type)}
            </div>
            <div class="event-content">
                <h6 class="event-title">${this.getEventTitle(eventData.event_type)}</h6>
                <p class="event-time">${eventTime}</p>
            </div>
            <div class="event-value">
                ${eventData.speed ? Math.round(eventData.speed) + ' km/h' : ''}
            </div>
        `;

        // Insertar al inicio de la lista
        const firstEvent = eventsList.querySelector('.event-item');
        if (firstEvent) {
            eventsList.insertBefore(eventElement, firstEvent);
        } else {
            eventsList.appendChild(eventElement);
        }

        // Limitar a 50 eventos en la UI
        while (eventsList.children.length > 50) {
            eventsList.removeChild(eventsList.lastChild);
        }
    }

    getEventIcon(eventType) {
        const icons = {
            speeding: '🚗',
            harsh_acceleration: '⚡',
            harsh_braking: '🛑',
            crash: '💥'
        };
        return icons[eventType] || '📍';
    }

    getEventTitle(eventType) {
        const titles = {
            speeding: 'Exceso de Velocidad',
            harsh_acceleration: 'Aceleración Brusca',
            harsh_braking: 'Frenada Brusca',
            crash: 'Posible Choque'
        };
        return titles[eventType] || 'Evento';
    }

    showNotification(eventType, data) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const title = this.getEventTitle(eventType);
            const options = {
                body: data.description || 'Evento detectado',
                icon: '/pwa/icons/icon-192x192.png',
                badge: '/pwa/icons/icon-72x72.png',
                tag: eventType,
                requireInteraction: eventType === 'crash'
            };

            new Notification(title, options);
        }
    }

    async saveLocationData(position) {
        try {
            await this.db.locationData.add({
                recorded_at: position.recorded_at,
                latitude: position.latitude,
                longitude: position.longitude,
                speed: position.speed,
                accuracy: position.accuracy,
                altitude: position.altitude,
                heading: position.heading,
                device_info: {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    timestamp: Date.now()
                },
                synced: false
            });

            console.log('Datos de ubicación guardados localmente');
        } catch (error) {
            console.error('Error guardando datos de ubicación:', error);
        }
    }

    async syncData() {
        if (!navigator.onLine) {
            console.log('Offline, omitiendo sincronización');
            return;
        }

        console.log('Iniciando sincronización...');
        this.showLoading('Sincronizando datos...');

        try {
            // Sincronizar datos de ubicación
            await this.syncLocationData();

            // Sincronizar eventos
            await this.syncEvents();

            console.log('Sincronización completada');
        } catch (error) {
            console.error('Error en sincronización:', error);
        } finally {
            this.hideLoading();
        }
    }

    async syncLocationData() {
        const unsyncedData = await this.db.locationData.where('synced').equals(0).toArray();

        if (unsyncedData.length === 0) return;

        console.log(`Sincronizando ${unsyncedData.length} registros de ubicación`);

        try {
            const response = await fetch(`${this.apiBaseUrl}/location-data/batch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ locations: unsyncedData })
            });

            if (response.ok) {
                const result = await response.json();
                console.log('Ubicaciones sincronizadas:', result);

                // Marcar como sincronizados
                const ids = unsyncedData.map(item => item.id);
                await this.db.locationData.where('id').anyOf(ids).modify({ synced: true });

                // Limpiar datos antiguos (mantener solo últimos 1000)
                await this.cleanupOldData();
            }
        } catch (error) {
            console.error('Error sincronizando ubicaciones:', error);
        }
    }

    async syncEvents() {
        const unsyncedEvents = await this.db.locationEvents.where('synced').equals(0).toArray();

        if (unsyncedEvents.length === 0) return;

        console.log(`Sincronizando ${unsyncedEvents.length} eventos`);

        try {
            const response = await fetch(`${this.apiBaseUrl}/location-events/batch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ events: unsyncedEvents })
            });

            if (response.ok) {
                const result = await response.json();
                console.log('Eventos sincronizados:', result);

                // Marcar como sincronizados
                const ids = unsyncedEvents.map(item => item.id);
                await this.db.locationEvents.where('id').anyOf(ids).modify({ synced: true });
            }
        } catch (error) {
            console.error('Error sincronizando eventos:', error);
        }
    }

    async cleanupOldData() {
        // Eliminar datos de ubicación de más de 7 días
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - 7);

        await this.db.locationData.where('recorded_at').below(cutoffDate).delete();

        // Eliminar eventos de más de 30 días
        const eventsCutoffDate = new Date();
        eventsCutoffDate.setDate(eventsCutoffDate.getDate() - 30);

        await this.db.locationEvents.where('event_time').below(eventsCutoffDate).delete();

        console.log('Datos antiguos eliminados');
    }

    startMotionSensors() {
        if ('DeviceMotionEvent' in window) {
            window.addEventListener('devicemotion', (event) => {
                this.handleDeviceMotion(event);
            });
            console.log('Sensores de movimiento iniciados');
        }
    }

    stopMotionSensors() {
        if ('DeviceMotionEvent' in window) {
            window.removeEventListener('devicemotion', this.handleDeviceMotion);
            console.log('Sensores de movimiento detenidos');
        }
    }

    handleDeviceMotion(event) {
        const acceleration = event.accelerationIncludingGravity;
        if (acceleration) {
            // Calcular magnitud de aceleración
            const magnitude = Math.sqrt(
                acceleration.x * acceleration.x +
                acceleration.y * acceleration.y +
                acceleration.z * acceleration.z
            );

            this.lastAcceleration = magnitude;
        }
    }

    updateUI() {
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const statusText = document.getElementById('statusText');

        if (this.isTracking) {
            startBtn.style.display = 'none';
            stopBtn.style.display = 'inline-block';
            statusText.textContent = 'Rastreando ubicación...';
        } else {
            startBtn.style.display = 'inline-block';
            stopBtn.style.display = 'none';
            statusText.textContent = 'Detenido';
        }
    }

    updateGPSIndicator(active) {
        const indicator = document.getElementById('gpsIndicator');
        if (active) {
            indicator.classList.add('active');
        } else {
            indicator.classList.remove('active');
        }
    }

    showSettingsModal() {
        document.getElementById('settingsModal').style.display = 'flex';
    }

    hideSettingsModal() {
        document.getElementById('settingsModal').style.display = 'none';
    }

    saveSettings() {
        this.config.maxSpeed = parseInt(document.getElementById('maxSpeed').value);
        this.config.interval = parseInt(document.getElementById('interval').value) * 1000;
        this.config.sensitivity = document.getElementById('sensitivity').value;
        this.config.notifications = document.getElementById('notifications').checked;
        this.config.autoStart = document.getElementById('autoStart').checked;

        // Guardar en localStorage
        localStorage.setItem('gpsTrackerConfig', JSON.stringify(this.config));

        this.hideSettingsModal();
        console.log('Configuración guardada:', this.config);
    }

    async clearEvents() {
        await this.db.locationEvents.clear();
        document.getElementById('eventsList').innerHTML = '<p class="text-muted text-center">No hay eventos registrados</p>';

        // Resetear contadores
        this.eventCounts = {
            speeding: 0,
            acceleration: 0,
            braking: 0,
            crash: 0
        };
        this.updateEventCounts();

        console.log('Eventos eliminados');
    }

    showLoading(message = 'Cargando...') {
        const overlay = document.getElementById('loadingOverlay');
        overlay.querySelector('p').textContent = message;
        overlay.style.display = 'flex';
    }

    hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }

    showError(message) {
        // Mostrar error en la UI (puedes personalizar esto)
        console.error('Error:', message);

        // Mostrar notificación si está disponible
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('GPS Tracker - Error', {
                body: message,
                icon: '/pwa/icons/icon-192x192.png'
            });
        }
    }

    handleServiceWorkerMessage(data) {
        console.log('Mensaje del Service Worker:', data);

        switch (data.type) {
            case 'SYNC_START':
                this.showLoading('Sincronizando...');
                break;
            case 'OFFLINE_ACTION':
                console.log('Acción offline guardada para más tarde');
                break;
        }
    }
}

// Inicializar aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Verificar si es HTTPS o localhost
    if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        console.error('GPS Tracker requiere HTTPS para funcionar correctamente');
        document.getElementById('statusText').textContent = 'HTTPS requerido';
        return;
    }

    // Inicializar aplicación
    window.gpsTracker = new GPSTracker();
});

// Manejar visibilidad de la página
document.addEventListener('visibilitychange', () => {
    if (window.gpsTracker) {
        if (document.hidden) {
            console.log('Página oculta, continuando en segundo plano');
        } else {
            console.log('Página visible');
            // Actualizar datos cuando la página se vuelva visible
            window.gpsTracker.updateLocationDisplay();
        }
    }
});

// Manejar conexión online/offline
window.addEventListener('online', () => {
    console.log('Conexión restaurada');
    if (window.gpsTracker) {
        window.gpsTracker.syncData();
    }
});

window.addEventListener('offline', () => {
    console.log('Sin conexión');
});