<form method="POST" action="{{ route('integraciones.verificar', $integracion) }}" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-sm btn-primary">
        <i class="bi bi-activity me-1"></i> Verificar ahora
    </button>
</form>
