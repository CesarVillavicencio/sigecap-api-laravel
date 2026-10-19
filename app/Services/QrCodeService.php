<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    public function publicUrl(string $path): string
    {
        $base = rtrim(trim((string) config('sigecap.public_base_url')), '/');
        $parts = parse_url($base);
        if (! $parts || ! filter_var($base, FILTER_VALIDATE_URL)
            || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || isset($parts['query']) || isset($parts['fragment'])
            || isset($parts['user']) || isset($parts['pass'])
            || strlen($base) > 2000) {
            throw new HttpResponseException(response()->json([
                'error' => [
                    'code' => 'PUBLIC_BASE_URL_NOT_CONFIGURED',
                    'message' => 'Configura PUBLIC_BASE_URL con la URL HTTP o HTTPS del sitio publico, sin query ni fragmento.',
                ],
            ], 503));
        }

        return $base.'/'.ltrim($path, '/');
    }

    public function download(string $content, string $filename): Response
    {
        $result = (new SvgWriter)->write(new QrCode(data: $content, size: 300, margin: 10));

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function png(string $content): string
    {
        $result = (new PngWriter)->write(
            new QrCode(
                data: $content,
                size: 300,
                margin: 10
            )
        );

        return $result->getString();
    }
}
