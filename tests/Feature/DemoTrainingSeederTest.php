<?php

namespace Tests\Feature;

use Database\Seeders\DemoTrainingSeeder;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\Support\SqliteMigrationConnection;
use Tests\TestCase;

class DemoTrainingSeederTest extends TestCase
{
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

        foreach (glob(database_path('migrations/2026_10_16_*.php')) as $file) {
            (require $file)->up();
        }
    }

    public function test_it_creates_related_demo_data_without_duplicating_it_on_a_second_run(): void
    {
        $this->seed(DemoTrainingSeeder::class);
        $this->seed(DemoTrainingSeeder::class);

        $this->assertDatabaseCount('dependencies', 3);
        $this->assertDatabaseCount('courses', 5);
        $this->assertDatabaseCount('course_editions', 10);
        $this->assertDatabaseCount('course_edition_modules', 20);
        $this->assertDatabaseCount('instructors', 8);
        $this->assertDatabaseCount('course_edition_instructors', 20);
        $this->assertDatabaseCount('participants', 30);
        $this->assertDatabaseCount('registrations', 120);

        $this->assertSame(5, DB::table('course_editions')->where('status', 'registration_open')->count());
        $this->assertSame(5, DB::table('course_editions')->where('status', 'finished')->count());
        $this->assertSame(90, DB::table('registrations')->where('status', 'registered')->count());
        $this->assertSame(30, DB::table('registrations')->where('status', 'cancelled')->count());
        $this->assertSame(0, DB::table('course_edition_instructors as assignment')
            ->join('course_edition_modules as module', 'module.id', '=', 'assignment.module_id')
            ->whereColumn('assignment.course_edition_id', '!=', 'module.course_edition_id')->count());
    }
}
