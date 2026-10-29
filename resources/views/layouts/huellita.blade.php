<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'Panel') · Huellita</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="huellita">
<div class="app-shell">
<aside class="sidebar"><a href="{{ route('dashboard') }}" class="brand"><span class="brand-icon">✚</span> huellita<span class="brand-dot">.</span></a><p class="sidebar-caption">CUIDADO QUE DEJA HUELLA</p>
<nav aria-label="Navegación principal">
<a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">◈ <span>Panel principal</span></a>
@if(in_array(auth()->user()->rol,['administrador','secretaria']))<a href="{{ route('propietarios.index') }}">◎ <span>Propietarios</span></a>@endif
<a href="{{ route('mascotas.index') }}">♡ <span>Pacientes</span></a>
@if(auth()->user()->rol !== 'secretaria')<a href="{{ route('expedientes.index') }}">▤ <span>Expedientes clínicos</span></a><a href="{{ route('medicamentos.index') }}">✚ <span>Medicamentos</span></a>@endif
<a href="{{ route('citas.index') }}">▦ <span>Agenda de citas</span></a><a href="{{ route('atenciones.index') }}">◷ <span>Atenciones</span></a>
@if(auth()->user()->rol === 'administrador')<a href="{{ route('usuarios.index') }}">⚙ <span>Personal y permisos</span></a>@endif
</nav><div class="sidebar-footer"><p>Un historial, toda una vida.</p><a href="{{ route('home') }}">Ver portal público ↗</a></div></aside>
<div class="main-shell"><header class="topbar"><span>Clínica veterinaria <strong>Huellita</strong></span><div class="user-menu"><span>{{ auth()->user()->name }} <small>{{ ucfirst(auth()->user()->rol) }}</small></span><form method="POST" action="{{ route('logout') }}">@csrf<button class="button subtle">Salir</button></form></div></header>
<main class="workspace">
@if(session('status'))<div role="status" class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div role="alert" class="error-box"><strong>Revisa los siguientes datos</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main><footer class="main-footer">Huellita · Gestión veterinaria <span>{{ now()->format('d/m/Y') }}</span></footer></div></div>
</body></html>