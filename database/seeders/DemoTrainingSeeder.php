<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseEdition;
use App\Models\CourseEditionInstructor;
use App\Models\CourseEditionModule;
use App\Models\Dependency;
use App\Models\Instructor;
use App\Models\Participant;
use App\Models\Registration;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoTrainingSeeder extends Seeder
{
    public function run(): void
    {
        if (! in_array(app()->environment(), ['local', 'testing'], true)) {
            throw new RuntimeException('Los datos de demostración solo se pueden cargar en local o testing.');
        }
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite'
            && ! in_array((string) $connection->getConfig('host'), ['localhost', '127.0.0.1', '::1'], true)) {
            throw new RuntimeException('El seeder de demostración requiere una base de datos local.');
        }

        DB::transaction(function (): void {
            $today = CarbonImmutable::now();
            $courses = [
                ['name' => 'Finanzas personales', 'topic' => 'Presupuesto y ahorro', 'hours' => 6, 'modality' => 'Presencial'],
                ['name' => 'Seguridad digital', 'topic' => 'Protección de datos', 'hours' => 8, 'modality' => 'Virtual'],
                ['name' => 'Atención ciudadana', 'topic' => 'Comunicación y servicio', 'hours' => 5, 'modality' => 'Presencial'],
                ['name' => 'Gestión de proyectos', 'topic' => 'Planeación y seguimiento', 'hours' => 10, 'modality' => 'Híbrida'],
                ['name' => 'Salud y bienestar laboral', 'topic' => 'Prevención y autocuidado', 'hours' => 6, 'modality' => 'Presencial'],
            ];

            $dependencies = [];
            foreach (['Administración', 'Servicios', 'Tecnología'] as $index => $name) {
                $dependencies[] = Dependency::firstOrCreate(
                    ['acronym' => 'SIGDEMO'.($index + 1)],
                    ['name' => '[DEMO] '.$name, 'active' => true]
                );
            }

            $instructors = [];
            foreach (['Ana Torres', 'Luis Romero', 'María Vega', 'Carlos Díaz',
                'Elena Ruiz', 'Jorge Medina', 'Sofía Castro', 'Pablo Navarro'] as $index => $name) {
                $number = $index + 1;
                $instructors[] = Instructor::firstOrCreate(
                    ['email' => sprintf('instructor%02d@sigecap-demo.invalid', $number)],
                    ['name' => '[DEMO] '.$name, 'position' => 'Ponente',
                        'institution' => 'Capacitación de demostración', 'active' => true]
                );
            }

            $participants = [];
            for ($number = 1; $number <= 30; $number++) {
                $letters = 'AA'.chr(ord('A') + $number - 1);
                if ($number > 26) {
                    $letters = 'AB'.chr(ord('A') + $number - 27);
                }
                $participants[] = Participant::firstOrCreate(
                    ['curp' => 'DEMO900101HBS'.$letters.sprintf('%02d', $number)],
                    ['dependency_id' => $dependencies[($number - 1) % count($dependencies)]->id,
                        'first_name' => 'Participante', 'first_last_name' => 'Demo',
                        'second_last_name' => sprintf('%02d', $number),
                        'institutional_email' => sprintf('participante%02d@sigecap-demo.invalid', $number),
                        'active' => true]
                );
            }

            foreach ($courses as $courseIndex => $item) {
                $courseNumber = $courseIndex + 1;
                $course = Course::firstOrCreate(
                    ['name' => '[DEMO] '.$item['name']],
                    ['created_by' => 0, 'created_by_responsibility_id' => 0,
                        'activity' => 'Curso de demostración',
                        'description' => 'Datos ficticios para probar el catálogo y las inscripciones.',
                        'objective' => 'Practicar '.$item['topic'].'.',
                        'target_audience' => 'Personal en capacitación',
                        'duration_hours' => $item['hours'], 'modality' => $item['modality'],
                        'active' => true]
                );

                for ($editionNumber = 1; $editionNumber <= 2; $editionNumber++) {
                    $isOpen = $editionNumber === 1;
                    $date = $isOpen ? $today->addDays(30 + $courseIndex * 7)
                        : $today->subDays(45 + $courseIndex * 7);
                    $edition = CourseEdition::firstOrCreate(
                        ['public_token' => hash('sha256', "sigecap-demo-edition-$courseNumber-$editionNumber")],
                        ['course_id' => $course->id, 'created_by' => 0,
                            'created_by_responsibility_id' => 0,
                            'start_date' => $date->toDateString(), 'end_date' => $date->toDateString(),
                            'start_time' => '09:00:00', 'end_time' => '12:00:00',
                            'location' => $isOpen ? 'Sala de capacitación [DEMO]' : 'Aula virtual [DEMO]',
                            'max_capacity' => 40, 'participant_target' => 25,
                            'registration_start' => ($isOpen ? $today->subDays(7) : $date->subDays(30))->toDateTimeString(),
                            'registration_end' => ($isOpen ? $date->subDay() : $date->subDays(2))->toDateTimeString(),
                            'status' => $isOpen ? 'registration_open' : 'finished']
                    );

                    foreach ([$item['topic'], 'Aplicación práctica'] as $moduleIndex => $moduleName) {
                        $order = $moduleIndex + 1;
                        $module = CourseEditionModule::firstOrCreate(
                            ['course_edition_id' => $edition->id, 'display_order' => $order],
                            ['name' => $moduleName, 'description' => 'Módulo de demostración.']
                        );
                        $instructor = $instructors[($courseIndex * 2 + $moduleIndex) % count($instructors)];
                        CourseEditionInstructor::firstOrCreate(
                            ['course_edition_id' => $edition->id, 'instructor_id' => $instructor->id,
                                'module_id' => $module->id],
                            ['topic' => $moduleName, 'display_order' => $order]
                        );
                    }

                    for ($slot = 0; $slot < 12; $slot++) {
                        $participant = $participants[($courseIndex * 6 + $editionNumber * 3 + $slot) % count($participants)];
                        Registration::firstOrCreate(
                            ['participant_id' => $participant->id, 'course_edition_id' => $edition->id],
                            ['folio' => sprintf('SIG-DEMO-%02d-%02d-%02d', $courseNumber, $editionNumber, $slot + 1),
                                'qr_token' => hash('sha256', "sigecap-demo-registration-$courseNumber-$editionNumber-$slot"),
                                'status' => $slot % 5 === 0 ? 'cancelled' : 'registered',
                                'registered_at' => ($isOpen ? $today->subDays(1 + $slot % 6)
                                    : $date->subDays(4 + $slot))->toDateTimeString()]
                        );
                    }
                }
            }
        });
    }
}
