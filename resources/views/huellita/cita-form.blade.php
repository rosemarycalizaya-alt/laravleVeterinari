@extends('layouts.huellita')
@section('title','Programar cita')
@section('content')
<div class="page-heading"><div><span class="eyebrow">AGENDA MANUAL</span><h1>{{ $cita->exists ? 'Editar cita':'Programar una visita' }}</h1><p>Comprobamos la disponibilidad del veterinario al guardar.</p></div></div>
<form class="panel form-panel" method="POST" action="{{ $cita->exists ? route('citas.update',$cita) : route('citas.store') }}">@csrf @if($cita->exists) @method('PUT') @endif<div class="form-grid">
<x-huellita-field name="mascota_id" label="Paciente y propietario" type="select" required :value="$cita->mascota_id" :options="$mascotas->mapWithKeys(fn($m)=>[$m->id=>$m->nombre.' · '.$m->propietario->nombre_completo])->all()"/>
<x-huellita-field name="veterinario_id" label="Veterinario" type="select" required :value="$cita->veterinario_id" :options="$veterinarios->pluck('name','id')->all()"/>
<x-huellita-field name="inicio" label="Inicio" type="datetime-local" required :value="$cita->inicio?->format('Y-m-d\TH:i')"/>
<x-huellita-field name="fin" label="Fin" type="datetime-local" required :value="$cita->fin?->format('Y-m-d\TH:i')"/>
<x-huellita-field name="motivo" label="Motivo" :value="$cita->motivo"/>
<x-huellita-field name="canal" label="Canal de contacto" type="select" required :value="$cita->canal ?? 'presencial'" :options="['whatsapp'=>'WhatsApp','telefono'=>'Teléfono','presencial'=>'Presencial','otro'=>'Otro']"/>
<x-huellita-field name="estado" label="Estado" type="select" required :value="$cita->estado ?? 'programada'" :options="['programada'=>'Programada','no_asistio'=>'No asistió','cancelada'=>'Cancelada']"/>
<x-huellita-field name="observaciones" label="Observaciones" type="textarea" :value="$cita->observaciones"/></div><div class="form-actions"><a class="button secondary" href="{{ route('citas.index') }}">Cancelar</a><button class="button">Guardar cita</button></div></form>
@endsection