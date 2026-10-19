@extends('public-courses.layout')
@section('title', $course['name'])
@section('content')
<article class="card">
    <h1>{{ $course['name'] }}</h1>
    <p class="muted">{{ $course['modality'] }} @if($course['duration_hours']) · {{ $course['duration_hours'] }} horas @endif</p>
    @foreach(['description' => 'Descripción', 'objective' => 'Objetivo', 'target_audience' => 'Dirigido a'] as $field => $label)
        @if($course[$field]) <h2>{{ $label }}</h2><p class="text">{{ $course[$field] }}</p> @endif
    @endforeach
</article>
@foreach($editions as $edition)
<section class="card">
    <h2>{{ $edition['start_date'] }} @if($edition['end_date']) — {{ $edition['end_date'] }} @endif</h2>
    <p><strong>Sede:</strong> {{ $edition['location'] ?: 'Por confirmar' }}</p>
    @if($edition['start_time'])<p><strong>Horario:</strong> {{ $edition['start_time'] }} @if($edition['end_time']) a {{ $edition['end_time'] }} @endif</p>@endif
    @if($edition['modules']->isNotEmpty())
    <h3>Temario</h3>
    <ul>@foreach($edition['modules'] as $module)
        <li><strong>{{ $module['name'] }}</strong>
            @if($module['description'])<p class="text">{{ $module['description'] }}</p>@endif
        </li>
    @endforeach</ul>
    @endif
    @if($edition['instructors']->isNotEmpty())
    <p><strong>Instructores:</strong> {{ $edition['instructors']->pluck('name')->unique()->implode(', ') }}</p>
    @endif
    <p class="notice">{{ $edition['can_register'] ? 'Inscripciones abiertas' : 'Esta edición no admite inscripciones en este momento.' }}</p>
</section>
@endforeach
@if($formErrors)
<div class="card error" role="alert">
    <h2>No se pudo completar la inscripción</h2>
    <ul>@foreach($formErrors as $message)<li>{{ $message }}</li>@endforeach</ul>
</div>
@endif
@php($available = $editions->where('can_register', true))
@if($available->isNotEmpty() && $dependencies->isNotEmpty())
<section class="card">
    <h2>Inscríbete</h2>
    <p class="muted">Completa tus datos. Los campos marcados con * son obligatorios.</p>
    <form method="POST" action="{{ $action }}">
        <label for="edition_id">Fecha / edición *</label>
        <select id="edition_id" name="edition_id" required>
            @foreach($available as $edition)
            <option value="{{ $edition['id'] }}" @selected(($input['edition_id'] ?? '') == $edition['id'])>{{ $edition['start_date'] }} · {{ $edition['location'] ?: 'Sede por confirmar' }}</option>
            @endforeach
        </select>
        <p><label for="dependency_id">Dependencia *</label>
        <select id="dependency_id" name="dependency_id" required>
            <option value="">Selecciona tu dependencia</option>
            @foreach($dependencies as $dependency)
            <option value="{{ $dependency->id }}" @selected(($input['dependency_id'] ?? '') == $dependency->id)>{{ $dependency->name }}</option>
            @endforeach
        </select></p>
        <div class="grid">
            @foreach([
                'first_name' => ['Nombre(s)', 'text', 100, true],
                'first_last_name' => ['Primer apellido', 'text', 100, true],
                'second_last_name' => ['Segundo apellido', 'text', 100, false],
                'curp' => ['CURP', 'text', 18, true],
                'institutional_email' => ['Correo institucional', 'email', 180, true],
                'phone' => ['Teléfono', 'tel', 30, false],
            ] as $field => [$label, $type, $max, $required])
            <label for="{{ $field }}">{{ $label }} {{ $required ? '*' : '' }}
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" maxlength="{{ $max }}" value="{{ is_scalar($input[$field] ?? '') ? ($input[$field] ?? '') : '' }}" @required($required) @if($field === 'curp') minlength="18" style="text-transform:uppercase" @endif>
            </label>
            @endforeach
        </div>
        <p><button type="submit">Confirmar inscripción</button></p>
    </form>
</section>
@elseif($available->isNotEmpty())
<p class="notice">No hay dependencias disponibles. Contacta al organizador para inscribirte.</p>
@endif
@endsection
