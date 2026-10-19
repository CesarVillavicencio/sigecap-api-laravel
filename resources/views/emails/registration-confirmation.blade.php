
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmación de inscripción - SIGECAP</title>
</head>

<body style="margin:0;padding:0;background:#f3f5f8;font-family:Arial,Helvetica,sans-serif;color:#273244;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#f3f5f8;padding:35px 12px;">
    <tr>
        <td align="center">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                   style="width:100%;max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;">

                <!-- ENCABEZADO -->
                <tr>
                    <td style="background:#18365b;padding:30px 25px;text-align:center;">
                        <h1 style="color:#ffffff;font-size:27px;margin:0;">
                            SIGECAP
                        </h1>
                        <p style="color:#dce7f4;font-size:13px;margin:8px 0 0;">
                            Sistema Integral de Gestión de Capacitación
                        </p>
                    </td>
                </tr>

                <!-- CONTENIDO -->
                <tr>
                    <td style="padding:35px 20px;">

                        <h2 style="font-size:22px;color:#18365b;margin:0 0 18px;">
                            ¡Inscripción confirmada!
                        </h2>

                        <p style="font-size:15px;line-height:1.7;">
                            Hola, <strong>{{ $participant->first_name }}
                            {{ $participant->first_last_name }}</strong>.
                        </p>

                        <p style="font-size:15px;line-height:1.7;">
                            Tu inscripción se ha registrado correctamente.
                            A continuación encontrarás los detalles del curso
                            y tu código QR personal para registrar tu asistencia.
                        </p>

                        <!-- DETALLES DEL CURSO -->
                        <table role="presentation" width="100%" cellpadding="0"
                               cellspacing="0"
                               style="background:#f4f7fb;border-radius:8px;margin:25px 0;">

                            <tr>
                                <td style="padding:22px;">

                                    <p style="font-size:12px;color:#697b90;margin:0 0 7px;">
                                        CURSO
                                    </p>

                                    <h3 style="font-size:19px;color:#18365b;margin:0 0 20px;">
                                        {{ $course->name }}
                                    </h3>

                                    <p style="font-size:14px;line-height:1.7;margin:8px 0;">
                                        <strong>Fecha:</strong>
                                        {{ \Carbon\Carbon::parse($edition->start_date)->format('d/m/Y') }}

                                        @if($edition->end_date)
                                            al {{ \Carbon\Carbon::parse($edition->end_date)->format('d/m/Y') }}
                                        @endif
                                    </p>

                                    @if($edition->start_time)
                                        <p style="font-size:14px;margin:8px 0;">
                                            <strong>Horario:</strong>
                                            {{ substr($edition->start_time, 0, 5) }}

                                            @if($edition->end_time)
                                                a {{ substr($edition->end_time, 0, 5) }}
                                            @endif
                                        </p>
                                    @endif

                                    <p style="font-size:14px;margin:8px 0;">
                                        <strong>Sede:</strong>
                                        {{ $edition->location ?: 'Por confirmar' }}
                                    </p>

                                    @if($course->modality)
                                        <p style="font-size:14px;margin:8px 0;">
                                            <strong>Modalidad:</strong>
                                            {{ $course->modality }}
                                        </p>
                                    @endif

                                </td>
                            </tr>
                        </table>

                        <!-- FOLIO -->
                        <div style="text-align:center;margin:30px 0 20px;">
                            <p style="font-size:13px;color:#697b90;margin:0 0 8px;">
                                TU FOLIO DE INSCRIPCIÓN
                            </p>

                            <p style="font-size:20px;font-weight:bold;letter-spacing:1px;color:#18365b;margin:0;overflow-wrap:anywhere;">
                                {{ $registration->folio }}
                            </p>
                        </div>

                        <!-- QR DE ASISTENCIA -->
                        <table role="presentation" width="100%" cellpadding="0"
                               cellspacing="0" style="margin:25px 0;">
                            <tr>
                                <td align="center" style="text-align:center;">

                                    <p style="font-size:15px;font-weight:bold;color:#18365b;margin:0 0 15px;">
                                        Código QR de asistencia
                                    </p>

                                    <img
                                        src="{{ $message->embedData($qrPng, 'codigo-qr.png', 'image/png') }}"
                                        width="190"
                                        height="190"
                                        alt="Código QR de asistencia"
                                        style="display:block;width:190px;height:190px;border:0;margin:0 auto;"
                                    >

                                    <p style="font-size:13px;color:#697b90;line-height:1.6;margin:18px 0 8px;">
                                        Presenta este código QR o tu folio
                                        al ingresar al curso.
                                    </p>

                                    <p style="font-size:12px;color:#697b90;line-height:1.6;margin:8px 0;">
                                        También encontrarás tu código QR adjunto
                                        en formato PNG (<strong>qr-asistencia.png</strong>).
                                    </p>

                                    <p style="font-size:12px;color:#697b90;line-height:1.6;margin:8px 0;">
                                        Si el código QR no aparece en tu correo,
                                        abre el archivo adjunto para visualizarlo.
                                    </p>

                                </td>
                            </tr>
                        </table>

                        <!-- AVISO -->
                        <div style="background:#edf4fc;border-left:4px solid #376ca6;padding:15px;margin-top:25px;">
                            <p style="font-size:13px;line-height:1.6;margin:0;">
                                <strong>Importante:</strong>
                                Este código QR es personal y está vinculado
                                a tu inscripción. Consérvalo para registrar
                                tu asistencia.
                            </p>
                        </div>

                        <p style="font-size:14px;margin-top:30px;line-height:1.6;">
                            Gracias por participar en nuestras actividades de capacitación.
                        </p>

                    </td>
                </tr>

                <!-- PIE -->
                <tr>
                    <td style="background:#edf1f5;padding:22px;text-align:center;">
                        <p style="font-size:13px;font-weight:bold;color:#18365b;margin:0 0 5px;">
                            SIGECAP
                        </p>

                        <p style="font-size:12px;color:#7b8795;margin:0;">
                            Este es un correo automático. Por favor, no respondas a este mensaje.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
