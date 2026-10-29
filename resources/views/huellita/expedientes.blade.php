@extends('layouts.huellita')
@section('title','Expedientes clínicos')
@section('content')
<div class="page-heading"><div><span class="eyebrow">MEMORIA DE CADA CUIDADO</span><h1>Expedientes clínicos</h1><p>Un solo expediente por paciente, una historia que continúa.</p></div></div>
<form class="searchbar"><input name="buscar" aria-label="Buscar expediente" value="{{ request('buscar') }}" placeholder="Código, paciente o propietario…"><button class="button secondary">Buscar</button></form>
<section class="panel">@forelse($expedientes as $expediente)<a class="list-row" href="{{ route('expedientes.show',$expediente) }}"><span class="code">{{ $expediente->codigo }}</span><div><strong>{{ $expediente->mascota->nombre }}</strong><small>{{ $expediente->mascota->especie }} · {{ $expediente->mascota->propietario->nombre_completo }}</small></div><span class="push-right">Abrir →</span></a>@empty<div class="empty-state">Todavía no hay expedientes.<small>Se crean al registrar cada paciente.</small></div>@endforelse</section>{{ $expedientes->links() }}
@endsection