@extends('layouts.huellita')
@section('title','Registrar llegada')
@section('content')
<div class="page-heading"><div><span class="eyebrow">BIENVENIDOS AL CONSULTORIO</span><h1>Registrar llegada</h1><p>Puede ser una visita espontánea o una cita programada.</p></div></div>
<form class="panel form-panel" action="{{ route('atenciones.store') }}" method="POST">@csrf
@php($citaSeleccionada=$citas->firstWhere('id',$seleccionada))
<div class="form-grid"><x-huellita-field name="cita_id" label="Cita (opcional, dejar vacío para visita sin cita)" type="select" :value="$seleccionada ?: null" :options="$citas->mapWithKeys(fn($c)=>[$c->id=>$c->mascota->nombre.' · '.$c->inicio->format('d/m H:i')])->all()"/><x-huellita-field name="mascota_id" label="Paciente y propietario" type="select" required :value="$citaSeleccionada?->mascota_id" :options="$mascotas->mapWithKeys(fn($m)=>[$m->id=>$m->nombre.' · '.$m->propietario->nombre_completo])->all()"/><x-huellita-field name="motivo_ingreso" label="Motivo de ingreso" :value="$citaSeleccionada?->motivo"/></div>
<p class="muted">En visitas con cita, selecciona el mismo paciente de la cita.</p><div class="form-actions"><a class="button secondary" href="{{ route('atenciones.index') }}">Cancelar</a><button class="button">Registrar y poner en espera →</button></div></form>
@endsection