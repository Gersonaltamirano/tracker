# GPS Tracker - Sistema de Monitoreo PWA

Un sistema completo de rastreo GPS con Progressive Web App (PWA) que permite monitorear velocidad, ubicación y eventos de conducción en tiempo real.

## 🚀 Características

### PWA (Aplicación Web Progresiva)
- ✅ Funciona completamente offline
- ✅ Se instala como app nativa en móviles
- ✅ Funciona en segundo plano
- ✅ Notificaciones push
- ✅ Auto-inicio configurable
- ✅ Sincronización automática online/offline

### Funcionalidades de Rastreo
- 📍 Geolocalización precisa con GPS
- 🏎️ Monitoreo de velocidad en tiempo real
- 📊 Detección de eventos:
  - Exceso de velocidad
  - Aceleraciones bruscas
  - Frenadas bruscas
  - Posibles choques
- 💾 Almacenamiento local con IndexedDB
- 🔄 Sincronización con servidor Laravel

### Dashboard Web
- 📈 Gráficos interactivos con ApexCharts
- 🗺️ Mapa con Leaflet
- 📋 Tabla de eventos con filtros
- 📤 Exportación a CSV
- 🎛️ Configuración de parámetros

## 🛠️ Instalación y Configuración

### 1. Requisitos Previos
- PHP 8.2+
- Laravel 12+
- Composer
- Node.js y npm
- Servidor web (Apache/Nginx)
- SSL configurado (HTTPS requerido)

### 2. Instalación del Backend

```bash
# Clonar o copiar los archivos del proyecto
# Las migraciones ya están creadas

# Ejecutar migraciones
php artisan migrate

# Instalar dependencias de Composer (si es necesario)
composer install

# Compilar assets (si usas Vite)
npm install
npm run build
```

### 3. Configuración de Variables de Entorno

Agregar al archivo `.env`:

```env
# Configuración GPS Tracker
GPS_MAX_SPEED=95
GPS_INTERVAL=60
GPS_SENSITIVITY=medium

# URL de la aplicación (para PWA)
APP_URL=https://gps-tracker.srv-sa.com
```

### 4. Configuración del Servidor

Asegurar que el directorio `/public` sea la raíz web y que las rutas API estén disponibles:

```apache
# .htaccess (Laravel por defecto)
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### 5. Acceso a la PWA

1. **Desde móvil**: Abrir `https://gps-tracker.srv-sa.com/pwa/` en el navegador
2. **Instalar PWA**: Usar el botón "Instalar" del navegador o "Agregar a pantalla de inicio"
3. **Dashboard web**: Acceder a `https://gps-tracker.srv-sa.com/gps-tracker`

## 📱 Uso de la PWA

### Instalación en Móvil
1. Abrir la URL en el navegador móvil
2. Aceptar permisos de ubicación y notificaciones
3. Instalar como app nativa
4. Configurar parámetros según preferencias

### Configuración de Parámetros
- **Velocidad máxima**: 95 km/h (configurable)
- **Intervalo de registro**: 60 segundos (configurable)
- **Sensibilidad**: Media (Baja/Media/Alta)
- **Notificaciones**: Habilitadas
- **Auto-inicio**: Habilitado

### Funcionamiento Offline
- Los datos se almacenan localmente en IndexedDB
- La app funciona sin conexión a internet
- Sincronización automática cuando hay conexión
- Notificaciones de eventos incluso offline

## 🔐 Configuración de Permisos

### Primera Ejecución
La PWA incluye una pantalla de configuración de permisos que te guía paso a paso:

**Paso 1: Verificar GPS**
- La app verifica automáticamente si el GPS está encendido
- Muestra el estado del GPS en tiempo real

**Paso 2: Conceder permisos de ubicación**
- El navegador solicitará permisos de geolocalización
- Selecciona "**Permitir todo el tiempo**" para funcionamiento en segundo plano
- Elige la opción de ubicación "**Precisa**" para mejor accuracy

**Paso 3: Configurar notificaciones (opcional)**
- La app solicitará permisos para notificaciones de eventos
- Puedes habilitar/deshabilitar en configuración

### Si hay problemas con permisos:
- Ve a **Configuración del navegador > Privacidad y seguridad > Ubicación**
- Asegúrate de que la app tenga permisos "**Permitido**"
- Usa el botón "**Verificar Permisos Nuevamente**" en configuración
- Reinstala la PWA si es necesario

### Permisos necesarios:
- ✅ **Geolocalización precisa**: Para obtener ubicación GPS
- ✅ **Notificaciones**: Para alertas de eventos (opcional)
- ✅ **Almacenamiento**: Para guardar datos offline

## 🖥️ Dashboard Web

### Acceso
- URL: `https://gps-tracker.srv-sa.com/gps-tracker`
- Requiere autenticación de Laravel

### Funcionalidades
- **Estadísticas generales**: Total ubicaciones, eventos, velocidad promedio
- **Gráficos interactivos**: Velocidad por hora, eventos por tipo
- **Mapa de trayecto**: Visualización de ruta con eventos marcados
- **Tabla de eventos**: Filtrable por fecha y tipo
- **Exportación**: Datos en formato CSV

## 🔧 API Endpoints

### Datos de Ubicación
```http
GET    /api/location-data         # Listar ubicaciones
POST   /api/location-data         # Crear ubicación
POST   /api/location-data/batch   # Batch insert
GET    /api/location-data/unsynced # Datos no sincronizados
```

### Eventos
```http
GET    /api/location-events           # Listar eventos
POST   /api/location-events           # Crear evento
POST   /api/location-events/batch     # Batch insert
GET    /api/location-events/statistics # Estadísticas
GET    /api/location-events/type/{type} # Eventos por tipo
```

## 📊 Base de Datos

### Tabla: location_data
```sql
- id: BIGINT PRIMARY KEY
- recorded_at: TIMESTAMP
- latitude: DECIMAL(10,8)
- longitude: DECIMAL(11,8)
- speed: DECIMAL(8,2) NULLABLE
- accuracy: DECIMAL(8,2) NULLABLE
- altitude: DECIMAL(8,2) NULLABLE
- heading: DECIMAL(5,2) NULLABLE
- device_info: JSON NULLABLE
- synced: BOOLEAN DEFAULT FALSE
- timestamps
```

### Tabla: location_events
```sql
- id: BIGINT PRIMARY KEY
- event_type: VARCHAR (speeding, harsh_acceleration, harsh_braking, crash)
- event_time: TIMESTAMP
- latitude: DECIMAL(10,8)
- longitude: DECIMAL(11,8)
- speed: DECIMAL(8,2) NULLABLE
- max_speed: DECIMAL(8,2) NULLABLE
- acceleration: DECIMAL(8,2) NULLABLE
- impact_force: DECIMAL(8,2) NULLABLE
- description: TEXT NULLABLE
- event_data: JSON NULLABLE
- notified: BOOLEAN DEFAULT FALSE
- synced: BOOLEAN DEFAULT FALSE
- timestamps
```

## 🔒 Seguridad y Privacidad

### Permisos Requeridos
- **Geolocalización**: Acceso preciso a GPS
- **Notificaciones**: Para alertas de eventos
- **Almacenamiento**: IndexedDB para datos offline

### Consideraciones de Privacidad
- Datos almacenados localmente primero
- Sincronización solo cuando hay conexión
- Configuración de retención de datos
- Posibilidad de exportar y eliminar datos

## 🚨 Eventos Detectados

### Exceso de Velocidad
- Se activa cuando velocidad > límite configurado (95 km/h)
- Registra ubicación, velocidad y timestamp
- Genera notificación push

### Aceleración Brusca
- Detecta cambios rápidos de velocidad
- Configurable por sensibilidad (Baja: 3m/s², Media: 5m/s², Alta: 8m/s²)
- Diferencia entre aceleración y frenada

### Choque Potencial
- Detecta desaceleraciones súbitas (>15 m/s²)
- Usa sensores de movimiento del dispositivo
- Marca como evento crítico

## 🔄 Sincronización

### Modo Online
- Envío inmediato de datos a la API
- Sincronización en tiempo real
- Backup automático

### Modo Offline
- Almacenamiento en IndexedDB
- Cola de sincronización
- Reintento automático cuando hay conexión
- Límite de 1000 registros por tipo

### Estrategias de Sincronización
- **Network First**: Para APIs críticas
- **Cache First**: Para recursos estáticos
- **Offline First**: Para funcionalidad básica

## 📱 Compatibilidad

### Navegadores Soportados
- ✅ Chrome 80+
- ✅ Firefox 75+
- ✅ Safari 13+
- ✅ Edge 80+
- ✅ Android Browser 81+

### Dispositivos
- 📱 Móviles y tablets
- 💻 Computadoras de escritorio
- 🌐 Cualquier dispositivo con navegador moderno

## 🐛 Solución de Problemas

### Problemas Comunes

**La PWA no se instala:**
- Verificar HTTPS
- Aceptar permisos de ubicación
- Limpiar caché del navegador

**No detecta ubicación:**
- Verificar permisos de GPS
- Comprobar conexión GPS/WiFi
- Reiniciar la aplicación

**No sincroniza datos:**
- Verificar conexión a internet
- Comprobar URL de la API
- Revisar logs del servidor

**Batería se agota rápido:**
- Aumentar intervalo de registro
- Deshabilitar GPS de alta precisión
- Reducir frecuencia de sincronización

## 📝 Logs y Debug

### Service Worker Logs
```javascript
// En DevTools > Application > Service Workers
// Verificar estado del service worker
```

### IndexedDB
```javascript
// En DevTools > Application > IndexedDB
// Inspeccionar datos almacenados
```

### Network Tab
```http
// Monitorear requests a la API
// Verificar sincronización de datos
```

## 🔄 Actualizaciones

### Actualizar PWA
1. La PWA se actualiza automáticamente
2. Service Worker maneja cache versioning
3. Datos se preservan durante actualizaciones

### Forzar Actualización
```javascript
// En DevTools > Application > Service Workers
// Click "Update" o "Skip Waiting"
```

## 📞 Soporte

Para soporte técnico o reportar bugs:
- Revisar logs del navegador
- Verificar configuración del servidor
- Comprobar permisos de geolocalización
- Testear en diferentes dispositivos

## 🔐 Licencia

Este proyecto es para uso personal del desarrollador. No distribuir sin autorización.

---

**Desarrollado para monitoreo personal de velocidad de conducción** 🚗💨