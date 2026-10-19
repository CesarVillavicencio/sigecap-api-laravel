<?php

namespace Database\Seeders;

use App\Models\Dependency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DependencySeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'API' => 'ADMINISTRACIÓN PORTUARIA INTEGRAL DE BAJA CALIFORNIA SUR',
            'BENU' => 'BENEMÉRITA ESCUELA NORMAL URBANA "PROF. DOMINGO CARBALLO FELIX',
            'CCL' => 'CENTRO DE CONCILIACIÓN LABORAL',
            'COBACH' => 'COLEGIO DE BACHILLERES DE BAJA CALIFORNIA SUR',
            'CONALEP' => 'COLEGIO DE EDUCACIÓN PROFESIONAL TÉCNICA DEL ESTADO',
            'CECYTE' => 'COLEGIO DE ESTUDIOS CIENTÍFICOS Y TECNOLÓGICOS DEL ESTADO',
            'COCAMBCS' => 'COMISIÓN DE MEDIACIÓN, CONCILIACIÓN Y ARBITRAJE MÉDICO',
            'CEA' => 'COMISIÓN ESTATAL DEL AGUA',
            'COSCYT' => 'CONSEJO SUDCALIFORNIANO DE CIENCIA Y TECNOLOGÍA',
            'CG' => 'CONTRALORÍA GENERAL',
            'ENS' => 'ESCUELA NORMAL SUPERIOR DE BCS "PROF. ENRIQUE ESTRADA LUCERO"',
            'FOSDE' => 'FONDO DE DESARROLLO SOCIAL DE BAJA CALIFORNIA SUR',
            'FONMAR' => 'FONDO PARA LA PROTECCIÓN DE RECURSOS MARINOS DE B.C.S.',
            'ICATEBCS' => 'INSTITUTO DE CAPACITACIÓN A LOS TRABAJADORES DEL ESTADO',
            'INVI' => 'INSTITUTO DE VIVIENDA',
            'IEEA' => 'INSTITUTO ESTATAL DE EDUCACIÓN PARA ADULTOS',
            'IERT' => 'INSTITUTO ESTATAL DE RADIO Y TELEVISIÓN',
            'ISC' => 'INSTITUTO SUDCALIFORNIANO DE CULTURA',
            'INSIPD' => 'INSTITUTO SUDCALIFORNIANO DE INCLUSIÓN A PERSONAS CON DISCAPACIDAD DE B.C.S.',
            'ISIFE' => 'INSTITUTO SUDCALIFORNIANO DE LA INFRAESTRUCTURA FÍSICA EDUCATIVA',
            'ISJ' => 'INSTITUTO SUDCALIFORNIANO DE LA JUVENTUD',
            'ISM' => 'INSTITUTO SUDCALIFORNIANO DE LAS MUJERES',
            'INSUDE' => 'INSTITUTO SUDCALIFORNIANO DEL DEPORTE',
            'ITESCABO' => 'INSTITUTO TECNOLÓGICO DE ESTUDIOS SUPERIORES DE LOS CABOS',
            'ITESCC' => 'INSTITUTO TECNOLÓGICO SUPERIOR DE CD. CONSTITUCIÓN',
            'ITESME' => 'INSTITUTO TECNOLÓGICO SUPERIOR DE MULEGÉ',
            'JEC' => 'JUNTA ESTATAL DE CAMINOS',
            'PEPJMMP' => 'PATRONATO DE LA ESCUELA PREPARATORIA JOSÉ MARÍA MORELOS Y PAVÓN',
            'PES' => 'PATRONATO DEL ESTUDIANTE SUDCALIFORNIANO',
            'PGJE' => 'PROCURADURÍA GENERAL DE JUSTICIA DEL ESTADO',
            'SEP' => 'SECRETARÍA DE EDUCACIÓN PÚBLICA',
            'SEPUIMM' => 'SECRETARÍA DE PLANEACIÓN URBANA, INFRAESTRUCTURA, MOVILIDAD, MEDIO AMBIENTE Y RECURSOS NATURALES',
            'SSA' => 'SECRETARÍA DE SALUD E INSTITUTO DE SERVICIOS DE SALUD DE BAJA CALIFORNIA SUR',
            'SSP' => 'SECRETARÍA DE SEGURIDAD PÚBLICA',
            'STBYDS' => 'SECRETARÍA DE TRABAJO, BIENESTAR Y DESARROLLO SOCIAL',
            'SETUES' => 'SECRETARÍA DE TURISMO Y ECONOMÍA',
            'SEPADA' => 'SECRETARÍA DE PESCA, ACUACULTURA Y DESARROLLO AGROPECUARIO',
            'SGG' => 'SECRETARÍA GENERAL DE GOBIERNO',
            'SEDIF' => 'SISTEMA ESTATAL PARA EL DESARROLLO INTEGRAL DE LA FAMILIA',
            'UPN' => 'UNIVERSIDAD PEDAGÓGICA NACIONAL',
            'UTLP' => 'UNIVERSIDAD TECNOLÓGICA DE LA PAZ',
            'PRUEBA' => 'PRESENTACIÒN SECI',
            'SFYA' => 'SECRETARÍA DE FINANZAS Y ADMINISTRACIÓN',
            '90' => 'SOPORTE INFORMATICA',
            'SESEA' => 'SECRETARÍA EJECUTIVA DEL SISTEMA ESTATAL ANTICORRUPCIÓN',
            'FITUPAZ' => 'FIDEICOMISO DE TURISMO DE LA PAZ',
            'ESCUFI' => 'ESCUELA SUPERIOR DE CULTURA FÍSICA',
        ];

        DB::transaction(function () use ($catalog): void {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('LOCK TABLE dependencies IN SHARE ROW EXCLUSIVE MODE');
            }

            foreach ($catalog as $acronym => $name) {
                // Conservar los registros existentes, incluso si sus siglas tienen espacios o minusculas.
                $exists = Dependency::whereRaw('UPPER(TRIM(acronym)) = ?', [(string) $acronym])->exists();

                if (! $exists) {
                    Dependency::create([
                        'acronym' => (string) $acronym,
                        'name' => $name,
                    ]);
                }
            }
        });
    }
}
