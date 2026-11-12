@php
    $etiquetas = [
        'verde' => 'Saludable',
        'amarillo' => 'Con problemas',
        'rojo' => 'Crítica',
    ];
@endphp
<span class="badge-salud {{ $estado }}">
    <span class="punto"></span>
    {{ $etiquetas[$estado] ?? ucfirst($estado) }}
</span>
