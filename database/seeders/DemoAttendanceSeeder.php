<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Registration;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // DemoTrainingSeeder impide ejecutar datos ficticios fuera de una base local.
        $this->call(DemoTrainingSeeder::class);

        DB::transaction(function (): void {
            $registrations = Registration::query()
                ->where('status', 'registered')
                ->where('folio', 'like', 'SIG-DEMO-%')
                ->whereHas('courseEdition', function ($query): void {
                    $query->where('status', 'finished')
                        ->whereHas('course', function ($courseQuery): void {
                            $courseQuery->where('name', 'like', '[DEMO] %');
                        });
                })
                ->with('courseEdition')
                ->orderBy('course_edition_id')
                ->orderBy('folio')
                ->get();

            $methodByIndex = ['qr', 'folio', 'manual'];
            $attendedByEdition = [];

            foreach ($registrations as $registration) {
                $slot = (int) substr($registration->folio, -2);
                if ($slot % 3 === 0) {
                    continue; // Algunas inscripciones quedan sin asistencia para probar los reportes.
                }

                $edition = $registration->courseEdition;
                $index = $attendedByEdition[$edition->id] ?? 0;
                $attendedByEdition[$edition->id] = $index + 1;
                $method = $methodByIndex[$index % count($methodByIndex)];
                $checkInAt = CarbonImmutable::parse(
                    $edition->start_date->format('Y-m-d').' '.$edition->start_time
                )->addMinutes(5 + $index * 4);

                Attendance::firstOrCreate(
                    ['registration_id' => $registration->id],
                    [
                        'registered_by' => 900001,
                        'registered_by_responsibility_id' => 900001,
                        'check_in_at' => $checkInAt,
                        'registration_method' => $method,
                        'device' => $method === 'manual' ? 'Captura de prueba' : 'Lector de prueba',
                        'notes' => $method === 'manual' ? 'Asistencia ficticia registrada manualmente.' : null,
                    ]
                );
            }
        });
    }
}
