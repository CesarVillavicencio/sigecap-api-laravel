<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Certificate;
use Illuminate\Support\Facades\DB;
use PDO;
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
