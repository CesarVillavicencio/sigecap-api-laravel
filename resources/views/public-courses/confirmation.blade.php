@extends('public-courses.layout')
@section('title', 'Inscripción confirmada')
@section('content')
<section class="card">
    <h1>¡Tu inscripción está confirmada!</h1>
    <p>{{ $course->name }}</p>
    <p>Tu folio es:</p>
    <p class="folio">{{ $folio }}</p>
    <p>Guarda este QR y preséntalo para registrar tu asistencia.</p>
    <div class="qr"><img src="data:image/svg+xml;base64,{{ base64_encode($qr_svg) }}" alt="QR de asistencia de la inscripción {{ $folio }}"></div>
    <a class="button" href="data:image/svg+xml;base64,{{ base64_encode($qr_svg) }}" download="{{ $folio }}.svg">Descargar mi QR</a>
    @if($email === 'sent')
    <p class="notice">Te enviamos el correo de confirmación a tu dirección institucional.</p>
    @elseif($email === 'failed')
    <p class="notice">No pudimos confirmar el envío del correo. Guarda tu folio y descarga el QR antes de cerrar esta página.</p>
    @else
    <p class="notice">Guarda tu folio y descarga el QR antes de cerrar esta página.</p>
    @endif
    <a href="/cursos">Volver a los cursos</a>
</section>
@endsection
