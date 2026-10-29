@extends('layouts.huellita')
@section('title',$titulo)
@section('content')
<div class="page-heading"><div><span class="eyebrow">INFORMACIÓN DEL REGISTRO</span><h1>{{ $titulo }}</h1></div><a href="{{ route($recurso.'.index') }}" class="text-link">← Volver</a></div>
<form class="panel form-panel" method="POST" action="{{ $registro->exists ? route($recurso.'.update',$registro) : route($recurso.'.store') }}">@csrf @if($registro->exists) @method('PUT') @endif
@if(session('coincidencias'))<div class="notice"><strong>Encontramos posibles coincidencias:</strong><ul>@foreach(session('coincidencias') as $coincidencia)<li>{{ $coincidencia }}</li>@endforeach</ul><label class="checkbox-label"><input type="checkbox" name="confirmar_coincidencia" value="1"> Revisé las coincidencias y confirmo que es otra persona.</label></div>@endif
<div class="form-grid">@foreach($campos as $name=>$campo)<x-huellita-field :name="$name" :label="$campo[0]" :type="$campo[1]" :required="$campo[2] ?? false" :options="$campo[3] ?? []" :value="$registro->$name"/>@endforeach</div>
<div class="form-actions"><a href="{{ route($recurso.'.index') }}" class="button secondary">Cancelar</a><button class="button">Guardar registro</button></div></form>
@endsection
