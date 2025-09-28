@extends('layouts/layoutMaster')

@section('title', 'GPS Tracker - Dashboard')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/leaflet/leaflet.scss'])
@endsection

@section('page-style')
@vite('resources/assets/vendor/scss/pages/gps-tracker.scss')
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/leaflet/leaflet.js'])
@endsection

@section('page-script')
@vite('resources/assets/js/gps-tracker-dashboard.js')
@endsection

@section('content')
<div class="row g-6">
    <!-- Page Header -->
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold py-3 mb-1">GPS Tracker Dashboard</h4>
                <p class="text-muted mb-0">Monitoreo de ubicación y velocidad en tiempo real</p>
            </div>
            <div class="d-flex gap-2">
                <a href="/pwa/" target="_blank" class="btn btn-primary">
                    <i class="icon-base ti tabler-device-mobile me-1"></i>
                    Abrir PWA
                </a>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="icon-base ti tabler-download me-1"></i>
                    Exportar
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">Total Ubicaciones</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ number_format($totalLocations) }}</h4>
                        </div>
                        <small class="text-muted">Registros de posición</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="icon-base ti tabler-map-pin ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">Total Eventos</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ number_format($totalEvents) }}</h4>
                        </div>
                        <small class="text-muted">Eventos detectados</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="icon-base ti tabler-alert-triangle ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">Velocidad Promedio</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $speedStats->avg_speed ?? 0 }} km/h</h4>
                        </div>
                        <small class="text-muted">Velocidad media registrada</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="icon-base ti tabler-speedometer ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">Velocidad Máxima</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $speedStats->max_speed ?? 0 }} km/h</h4>
                        </div>
                        <small class="text-muted">Velocidad más alta registrada</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="icon-base ti tabler-flame ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="col-xl-8 col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Velocidad por Hora</h5>
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="icon-base ti tabler-dots-vertical ti-sm"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="javascript:void(0);">Exportar</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Configurar</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div id="speedChart"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Eventos por Tipo</h5>
            </div>
            <div class="card-body">
                <div id="eventsChart"></div>
            </div>
        </div>
    </div>

    <!-- Events Table -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Eventos Recientes</h5>
                <div class="d-flex gap-2">
                    <div class="input-group" style="width: 300px;">
                        <span class="input-group-text"><i class="icon-base ti tabler-calendar"></i></span>
                        <input type="date" class="form-control" id="startDate" value="{{ $startDate->format('Y-m-d') }}">
                        <span class="input-group-text">a</span>
                        <input type="date" class="form-control" id="endDate" value="{{ $endDate->format('Y-m-d') }}">
                    </div>
                    <select class="form-select" id="eventTypeFilter" style="width: auto;">
                        <option value="">Todos los tipos</option>
                        <option value="speeding">Exceso de velocidad</option>
                        <option value="harsh_acceleration">Aceleración brusca</option>
                        <option value="harsh_braking">Frenada brusca</option>
                        <option value="crash">Posible choque</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover" id="eventsTable">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Fecha/Hora</th>
                            <th>Ubicación</th>
                            <th>Velocidad</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach($recentEvents as $event)
                        <tr>
                            <td>
                                <span class="badge bg-label-{{ $event->event_type === 'speeding' ? 'danger' : ($event->event_type === 'crash' ? 'danger' : 'warning') }} me-1">
                                    {{ ucfirst(str_replace('_', ' ', $event->event_type)) }}
                                </span>
                            </td>
                            <td>{{ $event->event_time->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <a href="https://maps.google.com/?q={{ $event->latitude }},{{ $event->longitude }}" target="_blank" class="text-primary">
                                    {{ number_format($event->latitude, 6) }}, {{ number_format($event->longitude, 6) }}
                                </a>
                            </td>
                            <td>
                                <strong>{{ $event->speed ? number_format($event->speed, 1) . ' km/h' : '-' }}</strong>
                            </td>
                            <td>{{ $event->description ?? 'Sin descripción' }}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);" onclick="viewEventDetails({{ $event->id }})">
                                            <i class="icon-base ti tabler-eye me-1"></i> Ver detalles
                                        </a>
                                        <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteEvent({{ $event->id }})">
                                            <i class="icon-base ti tabler-trash me-1"></i> Eliminar
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Map Card -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Mapa de Ubicaciones (Últimas 24 horas)</h5>
            </div>
            <div class="card-body">
                <div id="locationMap" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exportar Datos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" name="start_date" value="{{ $startDate->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" name="end_date" value="{{ $endDate->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tipo de Datos</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="data_type" value="locations" id="exportLocations" checked>
                                <label class="form-check-label" for="exportLocations">
                                    Datos de Ubicación
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="data_type" value="events" id="exportEvents">
                                <label class="form-check-label" for="exportEvents">
                                    Eventos
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="exportData()">Exportar CSV</button>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
// Pasar datos de PHP a JavaScript
window.chartData = @json($chartData);
window.mapLocations = @json($mapLocations);
window.csrfToken = '{{ csrf_token() }}';
</script>
@endsection