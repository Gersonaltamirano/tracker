// GPS Tracker Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Inicializar gráficos
    initCharts();

    // Inicializar mapa
    initMap();

    // Configurar filtros
    setupFilters();

    // Configurar exportación
    setupExport();

    console.log('GPS Tracker Dashboard inicializado');
});

/**
 * Inicializar gráficos con ApexCharts
 */
function initCharts() {
    // Gráfico de velocidad por hora
    if (document.getElementById('speedChart') && window.chartData.speedByHour) {
        const speedOptions = {
            series: [{
                name: 'Velocidad Promedio',
                data: window.chartData.speedByHour.map(item => ({
                    x: `${item.hour}:00`,
                    y: Math.round(item.avg_speed * 10) / 10
                }))
            }, {
                name: 'Velocidad Máxima',
                data: window.chartData.speedByHour.map(item => ({
                    x: `${item.hour}:00`,
                    y: Math.round(item.max_speed * 10) / 10
                }))
            }],
            chart: {
                height: 300,
                type: 'area',
                toolbar: {
                    show: false
                }
            },
            colors: ['#007bff', '#dc3545'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3
                }
            },
            xaxis: {
                type: 'category',
                categories: Array.from({length: 24}, (_, i) => `${i}:00`)
            },
            yaxis: {
                title: {
                    text: 'Velocidad (km/h)'
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' km/h';
                    }
                }
            }
        };

        new ApexCharts(document.getElementById('speedChart'), speedOptions).render();
    }

    // Gráfico de eventos por tipo
    if (document.getElementById('eventsChart') && window.chartData.eventsByType) {
        const eventsOptions = {
            series: window.chartData.eventsByType.map(item => item.count),
            chart: {
                height: 300,
                type: 'donut'
            },
            labels: window.chartData.eventsByType.map(item => {
                const labels = {
                    'speeding': 'Exceso de Velocidad',
                    'harsh_acceleration': 'Aceleración Brusca',
                    'harsh_braking': 'Frenada Brusca',
                    'crash': 'Posible Choque'
                };
                return labels[item.event_type] || item.event_type;
            }),
            colors: ['#dc3545', '#ffc107', '#17a2b8', '#fd7e14'],
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return Math.round(val) + '%';
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%'
                    }
                }
            },
            legend: {
                position: 'bottom'
            }
        };

        new ApexCharts(document.getElementById('eventsChart'), eventsOptions).render();
    }
}

/**
 * Inicializar mapa con Leaflet
 */
function initMap() {
    if (!document.getElementById('locationMap') || !window.mapLocations || window.mapLocations.length === 0) {
        return;
    }

    // Inicializar mapa
    const map = L.map('locationMap').setView([
        window.mapLocations[0].latitude,
        window.mapLocations[0].longitude
    ], 15);

    // Agregar capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(map);

    // Crear array de coordenadas para la polilínea
    const coordinates = window.mapLocations.map(loc => [loc.latitude, loc.longitude]);

    // Agregar polilínea del trayecto
    const polyline = L.polyline(coordinates, {
        color: '#007bff',
        weight: 3,
        opacity: 0.7
    }).addTo(map);

    // Ajustar mapa al trayecto
    map.fitBounds(polyline.getBounds(), { padding: [20, 20] });

    // Agregar marcadores para eventos
    window.mapLocations.forEach((location, index) => {
        if (location.speed > 95) { // Solo mostrar ubicaciones con exceso de velocidad
            const marker = L.circleMarker([location.latitude, location.longitude], {
                color: '#dc3545',
                fillColor: '#dc3545',
                fillOpacity: 0.8,
                radius: 6
            }).addTo(map);

            marker.bindPopup(`
                <strong>Velocidad: ${Math.round(location.speed)} km/h</strong><br>
                <small>${new Date(location.recorded_at).toLocaleString('es-ES')}</small>
            `);
        }
    });

    console.log('Mapa inicializado con', coordinates.length, 'puntos');
}

/**
 * Configurar filtros de fecha y tipo de evento
 */
function setupFilters() {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const eventTypeFilter = document.getElementById('eventTypeFilter');

    function applyFilters() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const eventType = eventTypeFilter.value;

        // Actualizar URL con parámetros
        const url = new URL(window.location);
        url.searchParams.set('start_date', startDate);
        url.searchParams.set('end_date', endDate);
        if (eventType) {
            url.searchParams.set('event_type', eventType);
        } else {
            url.searchParams.delete('event_type');
        }

        // Recargar página con nuevos parámetros
        window.location.href = url.toString();
    }

    startDateInput?.addEventListener('change', applyFilters);
    endDateInput?.addEventListener('change', applyFilters);
    eventTypeFilter?.addEventListener('change', applyFilters);
}

/**
 * Configurar funcionalidad de exportación
 */
function setupExport() {
    // Los elementos del modal ya están configurados en el HTML
    // Solo necesitamos la función exportData() que está en el HTML
}

/**
 * Ver detalles de un evento
 */
function viewEventDetails(eventId) {
    // Mostrar modal de detalles
    const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
    const content = document.getElementById('eventDetailsContent');

    // Cargar detalles del evento via AJAX
    fetch(`/api/location-events/${eventId}`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const event = data.data;
            content.innerHTML = `
                <div class="row g-3">
                    <div class="col-12">
                        <h6>Información General</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Tipo:</strong></td>
                                <td>${getEventTypeLabel(event.event_type)}</td>
                            </tr>
                            <tr>
                                <td><strong>Fecha/Hora:</strong></td>
                                <td>${new Date(event.event_time).toLocaleString('es-ES')}</td>
                            </tr>
                            <tr>
                                <td><strong>Ubicación:</strong></td>
                                <td>
                                    <a href="https://maps.google.com/?q=${event.latitude},${event.longitude}" target="_blank">
                                        ${event.latitude}, ${event.longitude}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Velocidad:</strong></td>
                                <td>${event.speed ? Math.round(event.speed) + ' km/h' : 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Descripción:</strong></td>
                                <td>${event.description || 'Sin descripción'}</td>
                            </tr>
                        </table>
                    </div>
                    ${event.event_data ? `
                    <div class="col-12">
                        <h6>Datos Adicionales</h6>
                        <pre class="bg-light p-3 rounded"><code>${JSON.stringify(event.event_data, null, 2)}</code></pre>
                    </div>
                    ` : ''}
                </div>
            `;
            modal.show();
        } else {
            throw new Error('Error cargando detalles del evento');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = '<div class="alert alert-danger">Error cargando detalles del evento</div>';
        modal.show();
    });
}

/**
 * Eliminar un evento
 */
function deleteEvent(eventId) {
    if (confirm('¿Estás seguro de que quieres eliminar este evento?')) {
        fetch(`/api/location-events/${eventId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar página para actualizar datos
                window.location.reload();
            } else {
                throw new Error('Error eliminando evento');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error eliminando evento');
        });
    }
}

/**
 * Exportar datos a CSV
 */
function exportData() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);

    const params = new URLSearchParams();
    formData.forEach((value, key) => {
        params.append(key, value);
    });

    const url = `/gps-tracker/export?${params.toString()}`;
    window.open(url, '_blank');
}

/**
 * Obtener etiqueta para tipo de evento
 */
function getEventTypeLabel(eventType) {
    const labels = {
        'speeding': 'Exceso de Velocidad',
        'harsh_acceleration': 'Aceleración Brusca',
        'harsh_braking': 'Frenada Brusca',
        'crash': 'Posible Choque'
    };
    return labels[eventType] || eventType;
}

/**
 * Actualizar estadísticas en tiempo real
 */
function updateStatistics() {
    fetch('/gps-tracker/statistics', {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar contadores en la página
            updateStatsDisplay(data.data);
        }
    })
    .catch(error => {
        console.error('Error actualizando estadísticas:', error);
    });
}

/**
 * Actualizar display de estadísticas
 */
function updateStatsDisplay(stats) {
    // Esta función puede ser expandida para actualizar los contadores en tiempo real
    console.log('Estadísticas actualizadas:', stats);
}

// Actualizar estadísticas cada 30 segundos
setInterval(updateStatistics, 30000);

// Funcionalidad para actualizar gráficos cuando cambian los filtros
function refreshCharts() {
    // Esta función puede ser implementada para actualizar gráficos dinámicamente
    // sin recargar la página completa
    console.log('Refrescando gráficos...');
}