<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use setasign\Fpdi\Tfpdf\Fpdi;

class CertificatePdfService
{
    public function generate(string $name, ?string $topic = null, ?string $date = null): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'No se ha indicado el nombre.']);
        }

        $pdf = new Fpdi('L', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->setSourceFile(config('certificates.template'));
        $page = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($page);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($page);
        $pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
        $pdf->SetTextColor(0, 0, 0);

        $this->writeField($pdf, $name, 'name');
        if ($topic !== null) {
            $this->writeField($pdf, $topic, 'topic');
        }
        if ($date !== null) {
            $this->writeField($pdf, $date, 'date');
        }

        return $pdf->Output('S');
    }

    private function writeField(Fpdi $pdf, string $text, string $field): void
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        $box = config('certificates.'.$field);
        $fits = false;
        for ($fontSize = $box['font_size']; $fontSize >= $box['min_font_size']; $fontSize--) {
            $pdf->SetFont('DejaVu', 'B', $fontSize);
            $lines = $this->wrap($pdf, $text, $box['width'] - 8);
            $lineHeight = $fontSize * 1.2;
            if (count($lines) * $lineHeight <= $box['height']) {
                $fits = true;
                break;
            }
        }
        if (! $fits) {
            throw ValidationException::withMessages([$field => 'El texto es demasiado largo para el espacio de la plantilla.']);
        }
        $y = $box['y'] + ($box['height'] - count($lines) * $lineHeight) / 2;
        foreach ($lines as $line) {
            $pdf->SetXY($box['x'], $y);
            $pdf->Cell($box['width'], $lineHeight, $line, 0, 0, 'C');
            $y += $lineHeight;
        }
    }

    private function wrap(Fpdi $pdf, string $name, float $width): array
    {
        $lines = [];
        $line = '';
        foreach (explode(' ', $name) as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            if ($pdf->GetStringWidth($candidate) <= $width) {
                $line = $candidate;

                continue;
            }
            if ($line !== '') {
                $lines[] = $line;
            }
            $line = '';
            foreach (mb_str_split($word) as $character) {
                if ($line !== '' && $pdf->GetStringWidth($line.$character) > $width) {
                    $lines[] = $line;
                    $line = '';
                }
                $line .= $character;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }
}
