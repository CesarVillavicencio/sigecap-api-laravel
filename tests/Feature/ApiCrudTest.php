<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Participant;
use App\Mail\RegistrationConfirmationMail;
use App\Services\CertificatePdfService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PDO;
use PhpOffice\PhpSpreadsheet\IOFactory;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tfpdf\Fpdi;
use Tests\Support\SqliteMigrationConnection;
use Tests\TestCase;

class ApiCrudTest extends TestCase
{
    private array $identity = ['X-SIGBCS-User-Id' => '17', 'X-SIGBCS-Responsibility-Id' => '23'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::extend('sqlite', function ($config) {
            $pdo = new PDO('sqlite::memory:');
            $pdo->exec('PRAGMA foreign_keys = ON');

            return new SqliteMigrationConnection($pdo, ':memory:', '', $config);
        });
        // No se ejecutan las dos migraciones de users ni se accede a la base del usuario.
        foreach (glob(database_path('migrations/2026_10_16_*.php')) as $file) {
            if (! str_ends_with($file, 'create_users_table.php')) {
                (require $file)->up();
            }
        }
    }

    private function create(string $module, array $data): array
    {
        return $this->postJson('/api/v1/'.$module, $data, $this->identity)
            ->assertCreated()->assertHeader('Location')->json('data');
    }

    private function participant(string $curp = 'AAAA000101MBSXXX00'): array
    {
        return $this->create('participants', [
            'dependency_name' => 'Dependencia libre', 'first_name' => 'Ana',
            'first_last_name' => 'Perez', 'curp' => $curp,
            'institutional_email' => 'ana@example.org',
        ]);
    }

    private function edition(array $attributes = []): array
    {
        $course = $this->create('courses', ['name' => 'Curso']);

        return $this->create('course-editions', array_replace([
            'course_id' => $course['id'], 'start_date' => '2027-01-15',
            'status' => 'registration_open',
        ], $attributes));
    }

    private function registration(array $edition, ?array $participant = null): array
    {
        return $this->create('registrations', [
            'participant_id' => ($participant ?? $this->participant())['id'],
            'course_edition_id' => $edition['id'],
        ]);
    }

    public function test_course_registrations_download_as_excel(): void
    {
        $edition = $this->edition();
        $participant = $this->participant();
        Participant::findOrFail($participant['id'])->update([
            'first_name' => 'María', 'second_last_name' => 'López',
            'dependency_name' => '=1+1',
        ]);
        $this->registration($edition, $participant);
        $secondEdition = $this->edition(['course_id' => $edition['course_id']]);
        $dependency = $this->create('dependencies', ['name' => 'Educación & Cultura']);
        $catalogParticipant = $this->participant('BBBB000101MBSXXX00');
        Participant::findOrFail($catalogParticipant['id'])->update(['dependency_id' => $dependency['id']]);
        $cancelled = $this->registration($secondEdition, $catalogParticipant);
        $this->deleteJson('/api/v1/registrations/'.$cancelled['id'])->assertNoContent();
        $otherEdition = $this->edition();
        $this->registration($otherEdition, $participant);

        $response = $this->get('/api/v1/registrations/excel/'.$edition['course_id']);
        $response->assertOk()->assertDownload('registros-curso-'.$edition['course_id'].'.xlsx')
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $path = tempnam(sys_get_temp_dir(), 'registrations-test-');
        try {
            file_put_contents($path, $response->streamedContent());
            $workbook = IOFactory::load($path);
            $sheet = $workbook->getActiveSheet();
            $this->assertSame(5, $sheet->getHighestRow());
            $this->assertSame('D', $sheet->getHighestColumn());
            $this->assertSame('Curso', $sheet->getCell('A1')->getValue());
            $this->assertSame([['Folio', 'Estado', 'Nombre completo', 'Dependencia']], $sheet->rangeToArray('A3:D3'));
            $this->assertSame('María Perez López', $sheet->getCell('C4')->getValue());
            $this->assertSame('=1+1', $sheet->getCell('D4')->getValue());
            $this->assertSame('s', $sheet->getCell('D4')->getDataType());
            $this->assertSame('Educación & Cultura', $sheet->getCell('D5')->getValue());
            $this->assertSame('cancelled', $sheet->getCell('B5')->getValue());
            $workbook->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_course_registrations_excel_handles_empty_and_missing_courses(): void
    {
        $this->getJson('/api/v1/registrations/excel/999999')->assertNotFound();
        $course = $this->create('courses', ['name' => 'Sin registros']);
        $response = $this->get('/api/v1/registrations/excel/'.$course['id']);
        $response->assertOk()->assertDownload('registros-curso-'.$course['id'].'.xlsx');
        $path = tempnam(sys_get_temp_dir(), 'registrations-test-');
        try {
            file_put_contents($path, $response->streamedContent());
            $workbook = IOFactory::load($path);
            $this->assertSame(3, $workbook->getActiveSheet()->getHighestRow());
            $this->assertSame('Sin registros', $workbook->getActiveSheet()->getCell('A1')->getValue());
            $workbook->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_catalogs_create_update_and_deactivate_with_all_editable_fields(): void
    {
        $dependency = $this->create('dependencies', ['name' => 'Dependencia', 'acronym' => 'DEP', 'active' => true]);
        $this->patchJson('/api/v1/dependencies/'.$dependency['id'], ['name' => 'Otra', 'acronym' => null, 'active' => false])
            ->assertOk()->assertJsonPath('data.acronym', null)->assertJsonPath('data.active', false);
        $participant = $this->create('participants', [
            'dependency_id' => $dependency['id'], 'dependency_name' => null,
            'first_name' => 'Ana', 'first_last_name' => 'Perez', 'second_last_name' => 'Lopez',
            'curp' => 'aaaa000101mbsxxx00', 'area' => 'Area', 'position' => 'Puesto',
            'institutional_email' => 'ana@example.org', 'phone' => '123', 'active' => true,
        ]);
        $this->assertSame('AAAA000101MBSXXX00', $participant['curp']);
        $changes = [
            'dependency_id' => null, 'dependency_name' => 'Libre', 'first_name' => 'Maria',
            'first_last_name' => 'Garcia', 'second_last_name' => null, 'curp' => 'BBBB000101MBSXXX00',
            'area' => null, 'position' => 'Nuevo', 'institutional_email' => 'maria@example.org',
            'phone' => null, 'active' => false,
        ];
        $this->patchJson('/api/v1/participants/'.$participant['id'], $changes)->assertOk()->assertJson(['data' => $changes]);
        $this->patchJson('/api/v1/participants/'.$participant['id'], ['dependency_name' => null])->assertUnprocessable();
        $this->deleteJson('/api/v1/participants/'.$participant['id'])->assertNoContent();
        $this->deleteJson('/api/v1/dependencies/'.$dependency['id'])->assertNoContent();
        $this->assertDatabaseHas('participants', ['id' => $participant['id'], 'active' => false]);
        $this->assertDatabaseHas('dependencies', ['id' => $dependency['id'], 'active' => false]);
    }

    public function test_instructors_validate_update_and_delete(): void
    {
        $data = ['name' => 'Instructor', 'position' => 'Puesto', 'institution' => 'Instituto', 'email' => 'i@example.org', 'phone' => '123', 'active' => true];
        $instructor = $this->create('instructors', $data);
        $changes = ['name' => 'Otro', 'position' => null, 'institution' => null, 'email' => null, 'phone' => null, 'active' => false];
        $this->patchJson('/api/v1/instructors/'.$instructor['id'], $changes)->assertOk()->assertJson(['data' => $changes]);
        $this->patchJson('/api/v1/instructors/'.$instructor['id'], ['email' => 'invalid'])->assertUnprocessable();
        $this->patchJson('/api/v1/instructors/'.$instructor['id'], ['id' => 77])->assertUnprocessable();
        $this->patchJson('/api/v1/instructors/'.$instructor['id'], [])->assertUnprocessable();
        $this->deleteJson('/api/v1/instructors/'.$instructor['id'])->assertNoContent();
        $this->getJson('/api/v1/instructors/'.$instructor['id'])->assertNotFound();
    }

    public function test_courses_and_editions_edit_fields_validate_dates_and_delete(): void
    {
        $data = [
            'name' => 'Curso', 'activity' => 'Taller', 'description' => 'Descripcion',
            'objective' => 'Objetivo', 'target_audience' => 'Personal',
            'duration_hours' => 8.25, 'modality' => 'Presencial', 'active' => true,
        ];
        $course = $this->create('courses', $data);
        $this->assertEquals(17, $course['created_by']);
        $this->assertSame('8.25', $course['duration_hours']);
        $changes = [
            'name' => 'Curso editado', 'activity' => null, 'description' => null,
            'objective' => 'Nuevo', 'target_audience' => null,
            'duration_hours' => null, 'modality' => null, 'active' => false,
        ];
        $this->patchJson('/api/v1/courses/'.$course['id'], $changes)->assertOk()->assertJson(['data' => $changes]);
        $this->patchJson('/api/v1/courses/'.$course['id'], ['created_by' => 1])->assertUnprocessable();
        $this->patchJson('/api/v1/courses/'.$course['id'], ['duration_hours' => -1])->assertUnprocessable();
        $edition = $this->create('course-editions', [
            'course_id' => $course['id'], 'start_date' => '2027-01-15', 'end_date' => '2027-01-16',
            'start_time' => '09:00', 'end_time' => '12:00:00', 'location' => 'Sala',
            'max_capacity' => 10, 'participant_target' => 8,
            'registration_start' => '2027-01-01T00:00:00', 'registration_end' => '2027-01-14T23:59:59',
            'status' => 'draft',
        ]);
        $this->assertSame(64, strlen($edition['public_token']));
        $secondCourse = $this->create('courses', ['name' => 'Segundo']);
        $changes = [
            'course_id' => $secondCourse['id'], 'start_date' => '2027-02-01', 'end_date' => null,
            'start_time' => null, 'end_time' => '14:00:00', 'location' => null,
            'max_capacity' => null, 'participant_target' => 0,
            'registration_start' => null, 'registration_end' => null, 'status' => 'published',
        ];
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], $changes)->assertOk()
            ->assertJsonPath('data.public_token', $edition['public_token'])->assertJsonPath('data.max_capacity', null);
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], ['end_date' => '2026-01-01'])->assertUnprocessable();
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], ['registration_start' => '2027-03-01', 'registration_end' => '2027-02-01'])->assertUnprocessable();
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], ['status' => 'bad'])->assertUnprocessable();
        $this->deleteJson('/api/v1/course-editions/'.$edition['id'])->assertNoContent();
        $this->deleteJson('/api/v1/courses/'.$course['id'])->assertNoContent();
    }

    public function test_modules_and_instructor_assignments_preserve_edition_consistency(): void
    {
        $edition = $this->edition();
        $otherEdition = $this->edition();
        $module = $this->create('course-edition-modules', ['course_edition_id' => $edition['id'], 'name' => 'Tema', 'description' => 'Contenido', 'display_order' => 1]);
        $otherModule = $this->create('course-edition-modules', ['course_edition_id' => $otherEdition['id'], 'name' => 'Otro']);
        $instructor = $this->create('instructors', ['name' => 'Instructor']);
        $assignment = $this->create('course-edition-instructors', [
            'course_edition_id' => $edition['id'], 'instructor_id' => $instructor['id'],
            'module_id' => $module['id'], 'topic' => 'Anterior', 'display_order' => 1,
        ]);
        $this->deleteJson('/api/v1/instructors/'.$instructor['id'])->assertConflict();
        $this->patchJson('/api/v1/course-edition-modules/'.$module['id'], ['course_edition_id' => $otherEdition['id']])->assertConflict();
        $this->patchJson('/api/v1/course-edition-instructors/'.$assignment['id'], ['module_id' => $otherModule['id']])->assertConflict();
        $changes = ['name' => 'Editado', 'description' => null, 'display_order' => 2];
        $this->patchJson('/api/v1/course-edition-modules/'.$module['id'], $changes)->assertOk()->assertJson(['data' => $changes]);
        $this->patchJson('/api/v1/course-edition-instructors/'.$assignment['id'], ['topic' => null, 'display_order' => 2])->assertOk();
        $this->postJson('/api/v1/course-edition-instructors', [
            'course_edition_id' => $edition['id'], 'instructor_id' => $instructor['id'], 'module_id' => $module['id'],
        ])->assertConflict();
        $this->deleteJson('/api/v1/course-edition-instructors/'.$assignment['id'])->assertNoContent();
        $this->deleteJson('/api/v1/course-edition-modules/'.$module['id'])->assertNoContent();
        $this->deleteJson('/api/v1/instructors/'.$instructor['id'])->assertNoContent();
    }

    public function test_registration_capacity_cancellation_reactivation_and_history(): void
    {
        $edition = $this->edition(['max_capacity' => 1]);
        $participant = $this->participant();
        $registration = $this->registration($edition, $participant);
        $this->assertSame(64, strlen($registration['qr_token']));
        $this->assertMatchesRegularExpression('/^SIG-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}$/', $registration['folio']);
        $other = $this->participant('BBBB000101MBSXXX00');
        $this->postJson('/api/v1/registrations', ['participant_id' => $other['id'], 'course_edition_id' => $edition['id']])
            ->assertConflict()->assertJsonPath('error.code', 'CAPACITY_EXCEEDED');
        $this->postJson('/api/v1/registrations', ['participant_id' => $participant['id'], 'course_edition_id' => $edition['id']])->assertConflict();
        $this->deleteJson('/api/v1/course-editions/'.$edition['id'])->assertConflict();
        $this->deleteJson('/api/v1/courses/'.$edition['course_id'])->assertConflict();
        $this->deleteJson('/api/v1/registrations/'.$registration['id'])->assertNoContent();
        $this->assertDatabaseHas('registrations', ['id' => $registration['id'], 'status' => 'cancelled']);
        $this->patchJson('/api/v1/registrations/'.$registration['id'], ['status' => 'registered'])->assertOk();
        $this->patchJson('/api/v1/registrations/'.$registration['id'], ['participant_id' => $other['id']])->assertOk();
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], ['max_capacity' => 2])->assertOk();
        $this->registration($edition, $participant);
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], ['max_capacity' => 1])->assertConflict();
        $this->patchJson('/api/v1/registrations/'.$registration['id'], ['folio' => 'x'])->assertUnprocessable();
    }

    public function test_attendance_and_certificate_workflow_and_deletions(): void
    {
        $edition = $this->edition();
        $registration = $this->registration($edition);
        $this->postJson('/api/v1/certificates', ['registration_id' => $registration['id']])->assertConflict();
        $attendance = $this->create('attendances', [
            'registration_method' => 'qr', 'qr_token' => $registration['qr_token'],
            'course_edition_id' => $edition['id'], 'device' => 'Lector', 'notes' => 'Entrada',
        ]);
        $this->assertEquals(17, $attendance['registered_by']);
        $this->patchJson('/api/v1/attendances/'.$attendance['id'], ['device' => null, 'notes' => 'Corregido'])->assertOk();
        $this->patchJson('/api/v1/attendances/'.$attendance['id'], ['registration_method' => 'manual', 'registration_id' => $registration['id']])->assertOk();
        $this->postJson('/api/v1/attendances', ['registration_method' => 'folio', 'folio' => $registration['folio']], $this->identity)->assertConflict();
        $certificate = $this->create('certificates', ['registration_id' => $registration['id'], 'file_path' => 'constancias/1.pdf']);
        $this->assertSame(64, strlen($certificate['verification_token']));
        $this->patchJson('/api/v1/certificates/'.$certificate['id'], ['file_path' => null])->assertOk()->assertJsonPath('data.file_path', null);
        $this->postJson('/api/v1/certificates', ['registration_id' => $registration['id']])->assertConflict();
        $this->deleteJson('/api/v1/attendances/'.$attendance['id'])->assertConflict();
        $other = $this->participant('BBBB000101MBSXXX00');
        $this->patchJson('/api/v1/registrations/'.$registration['id'], ['participant_id' => $other['id']])->assertConflict();
        $this->deleteJson('/api/v1/certificates/'.$certificate['id'])->assertNoContent();
        $this->deleteJson('/api/v1/attendances/'.$attendance['id'])->assertNoContent();
        $this->assertSame(0, Attendance::count());
        $this->assertSame(0, Certificate::count());
    }

    public function test_attendance_identifiers_and_cancelled_registrations(): void
    {
        $edition = $this->edition();
        $otherEdition = $this->edition();
        $registration = $this->registration($edition);
        $this->postJson('/api/v1/attendances', ['registration_method' => 'manual'], $this->identity)->assertUnprocessable();
        $this->postJson('/api/v1/attendances', [
            'registration_method' => 'manual', 'registration_id' => $registration['id'], 'course_edition_id' => $otherEdition['id'],
        ], $this->identity)->assertConflict()->assertJsonPath('error.code', 'ATTENDANCE_EDITION_MISMATCH');
        $attendance = $this->create('attendances', ['registration_method' => 'folio', 'folio' => strtolower($registration['folio'])]);
        $this->deleteJson('/api/v1/attendances/'.$attendance['id'])->assertNoContent();
        $this->deleteJson('/api/v1/registrations/'.$registration['id'])->assertNoContent();
        $this->postJson('/api/v1/attendances', ['registration_method' => 'manual', 'registration_id' => $registration['id']], $this->identity)
            ->assertConflict()->assertJsonPath('error.code', 'REGISTRATION_CANCELLED');
    }

    public function test_lists_missing_records_and_identity_validation(): void
    {
        $this->postJson('/api/v1/courses', ['name' => 'Sin identidad'])->assertUnprocessable();
        $this->assertDatabaseCount('courses', 0);
        $this->create('dependencies', ['name' => 'Activa', 'active' => true]);
        $this->create('dependencies', ['name' => 'Inactiva', 'active' => false]);
        $this->getJson('/api/v1/dependencies?active=true&limit=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('pagination.limit', 1);
        $this->getJson('/api/v1/dependencies?unknown=1')->assertUnprocessable();
        $this->getJson('/api/v1/dependencies?limit=101')->assertUnprocessable();
        $this->patchJson('/api/v1/dependencies/999', ['name' => 'Nada'])->assertNotFound();
        $this->deleteJson('/api/v1/dependencies/999')->assertNotFound();
    }

    public function test_instructor_pdf_uses_assignment_instructor_module_and_edition_date(): void
    {
        $edition = $this->edition(['start_date' => '2026-10-19']);
        $module = $this->create('course-edition-modules', ['course_edition_id' => $edition['id'], 'name' => 'Educación financiera y ahorro']);
        $instructor = $this->create('instructors', ['name' => 'Ana Sofía Pérez Muñoz']);
        $assignment = $this->create('course-edition-instructors', [
            'course_edition_id' => $edition['id'], 'instructor_id' => $instructor['id'],
            'module_id' => $module['id'], 'topic' => 'Tema anterior',
        ]);
        $this->mock(CertificatePdfService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()
                ->with('Ana Sofía Pérez Muñoz', 'Educación financiera y ahorro', '19 de octubre de 2026')
                ->andReturn('%PDF-example');
            $mock->shouldReceive('generate')->once()
                ->with('Ana Sofía Pérez Muñoz', 'Tema anterior', '19 de octubre de 2026')
                ->andReturn('%PDF-legacy');
        });
        $url = '/api/v1/course-edition-instructors/'.$assignment['id'].'/pdf';
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="reconocimiento-instructor-'.$assignment['id'].'.pdf"')
            ->assertContent('%PDF-example');
        $this->patchJson('/api/v1/course-edition-instructors/'.$assignment['id'], ['module_id' => null])->assertOk();
        $this->get($url)->assertOk()->assertContent('%PDF-legacy');
        $this->patchJson('/api/v1/course-edition-instructors/'.$assignment['id'], ['topic' => null])->assertOk();
        $this->getJson($url)->assertConflict()->assertJsonPath('error.code', 'MODULE_REQUIRED');
        $this->getJson('/api/v1/course-edition-instructors/999/pdf')->assertNotFound();
    }

    public function test_certificate_pdf_download_and_cancelled_registration(): void
    {
        $edition = $this->edition();
        $registration = $this->registration($edition);
        $this->create('attendances', ['registration_method' => 'manual', 'registration_id' => $registration['id']]);
        $certificate = $this->create('certificates', ['registration_id' => $registration['id']]);
        $participant = Participant::findOrFail($registration['participant_id']);
        $participant->update(['first_name' => 'Ana Sofía', 'first_last_name' => 'Pérez', 'second_last_name' => 'Muñoz']);
        $url = '/api/v1/certificates/'.$certificate['id'].'/pdf';
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="reconocimiento-'.$certificate['id'].'.pdf"');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $parser = new Fpdi;
        $this->assertSame(1, $parser->setSourceFile(StreamReader::createByString($response->getContent())));
        $size = $parser->getTemplateSize($parser->importPage(1));
        $this->assertEqualsWithDelta(279.4, $size['width'], 0.1);
        $this->assertEqualsWithDelta(215.9, $size['height'], 0.1);
        $this->getJson('/api/v1/certificates/999/pdf')->assertNotFound();
        $this->deleteJson('/api/v1/registrations/'.$registration['id'])->assertNoContent();
        $this->getJson($url)->assertConflict()->assertJsonPath('error.code', 'REGISTRATION_CANCELLED');
    }

    public function test_public_pages_register_and_confirm_without_exposing_identity(): void
    {
        Mail::fake();
        $edition = $this->edition();
        $dependency = $this->create('dependencies', ['name' => 'Dependencia de prueba']);
        $this->get('/cursos')->assertOk()->assertSee('Cursos disponibles');
        $this->get('/cursos/'.$edition['course_id'])->assertOk()->assertSee('Confirmar inscripción');
        $this->get('/c/'.$edition['public_token'])->assertOk()->assertSee('Dependencia de prueba');
        $coursePage = $this->getJson('/cursos/'.$edition['course_id'])->assertOk()->json('data');
        $this->assertSame($edition['public_token'], $coursePage['editions'][0]['public_token']);
        $this->assertTrue($coursePage['editions'][0]['can_register']);
        $json = $this->getJson('/c/'.$edition['public_token'])->assertOk()->json('data');
        $this->assertArrayNotHasKey('created_by', $json['course']);
        $this->assertArrayNotHasKey('created_by', $json['editions'][0]);
        $data = [
            'dependency_id' => $dependency['id'], 'first_name' => 'Ana', 'first_last_name' => 'Perez',
            'curp' => 'AAAA000101MBSXXX00', 'institutional_email' => 'ana@example.org',
            'edition_id' => 999,
        ];
        $response = $this->post('/c/'.$edition['public_token'].'/inscripciones', $data)
            ->assertCreated()->assertSee('Tu inscripción está confirmada')->assertSee('Descargar mi QR');
        Mail::assertSent(RegistrationConfirmationMail::class);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $response->getContent());
        $this->assertDatabaseHas('registrations', ['course_edition_id' => $edition['id'], 'status' => 'registered']);
        $this->post('/c/'.$edition['public_token'].'/inscripciones', $data)->assertConflict()->assertSee('Ya existe una inscripción');
        $this->assertDatabaseCount('participants', 1);
        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_public_form_errors_closed_editions_and_existing_participants(): void
    {
        Mail::fake();
        $edition = $this->edition();
        $dependency = $this->create('dependencies', ['name' => 'Dependencia']);
        $url = '/cursos/'.$edition['course_id'].'/inscripciones';
        $data = [
            'dependency_id' => $dependency['id'], 'first_name' => 'Ana', 'first_last_name' => 'Perez',
            'curp' => 'AAAA000101MBSXXX00', 'institutional_email' => 'ana@example.org', 'edition_id' => $edition['id'],
        ];
        $this->post($url, ['first_name' => 'Ana'])->assertUnprocessable()->assertSee('No se pudo completar')->assertSee('value="Ana"', false);
        $this->post($url, array_replace($data, ['edition_id' => 999]))->assertUnprocessable();
        $this->assertDatabaseCount('participants', 0);
        $this->postJson($url, $data)->assertCreated()->assertJsonPath('data.email', 'sent')->assertJsonStructure(['data' => ['folio', 'qr_svg']]);
        Mail::assertSent(RegistrationConfirmationMail::class, 1);
        $second = $this->edition();
        $secondUrl = '/c/'.$second['public_token'].'/inscripciones';
        $this->postJson($secondUrl, array_replace($data, ['institutional_email' => 'otro@example.org']))->assertConflict();
        $this->postJson($secondUrl, array_replace($data, ['first_name' => 'Otro nombre']))->assertCreated();
        $this->assertDatabaseHas('participants', ['curp' => $data['curp'], 'first_name' => 'Ana']);
        $this->assertDatabaseCount('participants', 1);
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], ['status' => 'registration_closed'])->assertOk();
        $this->get('/c/'.$edition['public_token'])->assertOk()->assertDontSee('Confirmar inscripción');
        $this->postJson($url, array_replace($data, ['curp' => 'BBBB000101MBSXXX00']))->assertConflict();
        $this->assertDatabaseCount('participants', 1);
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], ['status' => 'draft'])->assertOk();
        $this->get('/c/'.$edition['public_token'])->assertNotFound();
        $this->get('/cursos/'.$edition['course_id'])->assertNotFound();
    }

    public function test_qr_endpoints_encode_the_documented_urls_and_attendance_token(): void
    {
        config(['sigecap.public_base_url' => 'https://capacitacion.example.org/portal/']);
        $edition = $this->edition();
        $registration = $this->registration($edition);
        $cases = [
            ['courses', $edition['course_id'], 'https://capacitacion.example.org/portal/cursos/'.$edition['course_id'], 'curso'],
            ['course-editions', $edition['id'], 'https://capacitacion.example.org/portal/c/'.$edition['public_token'], 'edicion'],
            ['registrations', $registration['id'], $registration['qr_token'], 'inscripcion'],
        ];
        foreach ($cases as [$module, $id, $content, $filename]) {
            $response = $this->get('/api/v1/'.$module.'/'.$id.'/qr.svg');
            $response->assertOk()->assertHeader('Content-Type', 'image/svg+xml')
                ->assertHeader('Content-Disposition', 'attachment; filename="'.$filename.'-'.$id.'.svg"');
            $expected = (new SvgWriter)->write(
                new QrCode(data: $content, size: 300, margin: 10)
            )->getString();
            $this->assertSame($expected, $response->getContent());
            $this->assertSame('svg', simplexml_load_string($response->getContent())->getName());
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
        $this->assertDatabaseHas('course_editions', ['id' => $edition['id'], 'public_token' => $edition['public_token']]);
        $this->assertDatabaseHas('registrations', ['id' => $registration['id'], 'qr_token' => $registration['qr_token']]);
    }

    public function test_public_qr_requires_configuration_but_attendance_qr_does_not(): void
    {
        $edition = $this->edition();
        $registration = $this->registration($edition);
        foreach ([null, '', 'invalid', 'javascript:alert(1)', 'https://example.org/?page=1', 'https://example.org/#section'] as $url) {
            config(['sigecap.public_base_url' => $url]);
            foreach (['courses' => $edition['course_id'], 'course-editions' => $edition['id']] as $module => $id) {
                $this->getJson('/api/v1/'.$module.'/'.$id.'/qr.svg')->assertStatus(503)
                    ->assertJsonPath('error.code', 'PUBLIC_BASE_URL_NOT_CONFIGURED');
            }
        }
        config(['sigecap.public_base_url' => null]);
        $this->get('/api/v1/registrations/'.$registration['id'].'/qr.svg')->assertOk();
        foreach (['courses', 'course-editions', 'registrations'] as $module) {
            $this->getJson('/api/v1/'.$module.'/999/qr.svg')->assertNotFound();
        }
    }

    public function test_reassignments_keep_certificates_backed_by_attendance(): void
    {
        $edition = $this->edition();
        $first = $this->registration($edition);
        $second = $this->registration($edition, $this->participant('BBBB000101MBSXXX00'));
        $attendance = $this->create('attendances', ['registration_method' => 'manual', 'registration_id' => $first['id']]);
        $certificate = $this->create('certificates', ['registration_id' => $first['id']]);
        $this->patchJson('/api/v1/attendances/'.$attendance['id'], ['registration_id' => $second['id']])->assertConflict();
        $this->patchJson('/api/v1/certificates/'.$certificate['id'], ['registration_id' => $second['id']])->assertConflict();
        $this->create('attendances', ['registration_method' => 'manual', 'registration_id' => $second['id']]);
        $updated = $this->patchJson('/api/v1/certificates/'.$certificate['id'], ['registration_id' => $second['id']])
            ->assertOk()->json('data');
        $this->assertNotSame($certificate['verification_token'], $updated['verification_token']);
        $this->deleteJson('/api/v1/attendances/'.$attendance['id'])->assertNoContent();
        $this->assertDatabaseHas('certificates', ['id' => $certificate['id'], 'registration_id' => $second['id']]);
    }

    public function test_course_deletion_cascades_modules_and_assignments_but_preserves_instructors(): void
    {
        $edition = $this->edition();
        $module = $this->create('course-edition-modules', ['course_edition_id' => $edition['id'], 'name' => 'Tema']);
        $instructor = $this->create('instructors', ['name' => 'Instructor']);
        $this->create('course-edition-instructors', [
            'course_edition_id' => $edition['id'], 'instructor_id' => $instructor['id'], 'module_id' => $module['id'],
        ]);
        $this->deleteJson('/api/v1/courses/'.$edition['course_id'])->assertNoContent();
        $this->assertDatabaseCount('course_editions', 0);
        $this->assertDatabaseCount('course_edition_modules', 0);
        $this->assertDatabaseCount('course_edition_instructors', 0);
        $this->assertDatabaseCount('instructors', 1);
    }

    public function test_registration_checks_activity_and_registration_period_on_reactivation(): void
    {
        $edition = $this->edition();
        $registration = $this->registration($edition);
        $this->deleteJson('/api/v1/registrations/'.$registration['id'])->assertNoContent();
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], ['status' => 'registration_closed'])->assertOk();
        $this->patchJson('/api/v1/registrations/'.$registration['id'], ['status' => 'registered'])->assertConflict();
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], [
            'status' => 'registration_open', 'registration_start' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertOk();
        $this->patchJson('/api/v1/registrations/'.$registration['id'], ['status' => 'registered'])->assertConflict();
        $this->patchJson('/api/v1/course-editions/'.$edition['id'], ['registration_start' => null])->assertOk();
        $this->deleteJson('/api/v1/participants/'.$registration['participant_id'])->assertNoContent();
        $this->patchJson('/api/v1/registrations/'.$registration['id'], ['status' => 'registered'])
            ->assertConflict()->assertJsonPath('error.code', 'INACTIVE_RESOURCE');
        $this->assertDatabaseHas('registrations', ['id' => $registration['id'], 'status' => 'cancelled']);
    }
}
