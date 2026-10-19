@extends('public-courses.layout')
@section('title', 'Catálogo de cursos')
@section('content')
<h1>Cursos disponibles</h1>
<p class="muted">Consulta las fechas y los requisitos para inscribirte.</p>
@forelse($courses as $course)
<article class="card">
    <h2>{{ $course->name }}</h2>
    <p class="text">{{ $course->description }}</p>
    <p class="muted">{{ $course->modality }} @if($course->duration_hours) · {{ $course->duration_hours }} horas @endif</p>
    <a class="button" href="/cursos/{{ $course->id }}">Ver curso</a>
</article>
@empty
<p class="notice">Por el momento no hay cursos publicados.</p>
@endforelse
@endsection
