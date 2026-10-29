@extends('layouts.huellita')
@section('title','Consulta médica')
@section('content')
<div class="page-heading"><div><span class="eyebrow">ATENCIÓN #{{ $atencion->id }}</span><h1>Consulta · {{ $atencion->mascota->nombre }}</h1><p>Registra los hallazgos y las indicaciones del profesional.</p></div><a class="text-link" href="{{ route('expedientes.show',$atencion->mascota->expediente) }}">← Ver antecedentes</a></div>
<form class="panel form-panel" method="POST" action="{{ $consulta->exists ? route('consultas.update',$consulta) : route('consultas.store') }}">@csrf @if($consulta->exists) @method('PUT') @endif
<input type="hidden" name="atencion_id" value="{{ $atencion->id }}"><input type="hidden" name="expediente_id" value="{{ $atencion->mascota->expediente->id }}">
<div class="form-grid"><x-huellita-field name="motivo_consulta" label="Motivo de consulta" required :value="$consulta->motivo_consulta ?? $atencion->motivo_ingreso"/><x-huellita-field name="proximo_control" label="Próximo control" type="date" :value="$consulta->proximo_control?->format('Y-m-d')"/><x-huellita-field name="peso_kg" label="Peso (kg)" type="number" step="0.01" min="0.01" :value="$consulta->peso_kg"/><x-huellita-field name="temperatura_c" label="Temperatura (°C)" type="number" step="0.1" min="0" max="60" :value="$consulta->temperatura_c"/>
@foreach(['sintomas'=>'Síntomas','examen_fisico'=>'Examen físico','diagnostico'=>'Diagnóstico','tratamiento'=>'Tratamiento','recomendaciones'=>'Recomendaciones'] as $name=>$label)<x-huellita-field :name="$name" :label="$label" type="textarea" :value="$consulta->$name"/>@endforeach</div>
<div class="form-actions"><button class="button">Guardar consulta y continuar →</button></div></form>
@endsection