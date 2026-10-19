<?php

return [
    'template' => resource_path('pdf/reconocimiento.pdf'),
    // Coordenadas en puntos (72 puntos = 1 pulgada), desde la esquina superior izquierda.
    'name' => [
        'x' => 80,
        'y' => 302,
        'width' => 632,
        'height' => 46,
        'font_size' => 28,
        'min_font_size' => 8,
    ],
    'topic' => [
        'x' => 83,
        'y' => 373,
        'width' => 303,
        'height' => 17,
        'font_size' => 12,
        'min_font_size' => 6,
    ],
    'date' => [
        'x' => 512,
        'y' => 394,
        'width' => 137,
        'height' => 16,
        'font_size' => 11,
        'min_font_size' => 7,
    ],
];
