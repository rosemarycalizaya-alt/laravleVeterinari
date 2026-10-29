@extends('layouts.huellita')
@section('title','Registro de paciente')
@section('content')
<div class="page-heading"><div><span class="eyebrow">DATOS DEL PACIENTE</span><h1>{{ $mascota->exists ? 'Editar paciente' : 'Un nuevo compañero' }}</h1><p>Al registrarlo, abrimos automáticamente su expediente único.</p></div><a class="text-link" href="{{ route('mascotas.index') }}">← Volver</a></div>
<form class="panel form-panel" method="POST" enctype="multipart/form-data" action="{{ $mascota->exists ? route('mascotas.update',$mascota) : route('mascotas.store') }}">@csrf @if($mascota->exists) @method('PUT') @endif
<div class="form-grid"><x-huellita-field name="propietario_id" label="Propietario" type="select" required :value="$mascota->propietario_id" :options="$propietarios->mapWithKeys(fn($p)=>[$p->id=>$p->nombre_completo.' · '.($p->documento_identidad ?: 'Sin documento')])->all()"/><a class="text-link" href="{{ route('propietarios.create') }}">+ Registrar propietario</a>
<x-huellita-field name="nombre" label="Nombre del paciente" required :value="$mascota->nombre"/>
<x-huellita-field name="especie" label="Especie (perro, gato u otra)" required :value="$mascota->especie"/>
<x-huellita-field name="raza" label="Raza" :value="$mascota->raza"/>
<x-huellita-field name="sexo" label="Sexo" type="select" required :value="$mascota->sexo ?? 'desconocido'" :options="['macho'=>'Macho','hembra'=>'Hembra','desconocido'=>'Desconocido']"/>
<x-huellita-field name="fecha_nacimiento" label="Fecha de nacimiento (si se conoce)" type="date" :value="$mascota->fecha_nacimiento?->format('Y-m-d')"/>
<x-huellita-field name="color" label="Color" :value="$mascota->color"/>
<x-huellita-field name="esterilizado" label="Esterilización" type="select" :value="$mascota->esterilizado === null ? null : (int)$mascota->esterilizado" :options="[1=>'Sí',0=>'No']"/>
<x-huellita-field name="estado" label="Estado" type="select" required :value="$mascota->estado ?? 'activa'" :options="['activa'=>'Activa','inactiva'=>'Inactiva','fallecida'=>'Fallecida']"/>
<x-huellita-field name="foto" label="Fotografía (JPG, PNG o WebP, hasta 4 MB)" type="file" accept=".jpg,.jpeg,.png,.webp"/>
<x-huellita-field name="observaciones" label="Observaciones" type="textarea" :value="$mascota->observaciones"/>
</div><div class="form-actions"><button class="button">Guardar paciente</button></div></form>
@endsection