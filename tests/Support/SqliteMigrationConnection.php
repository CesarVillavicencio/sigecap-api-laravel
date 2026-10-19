<?php

namespace Tests\Support;

use Illuminate\Database\SQLiteConnection;

/**
 * Las migraciones reales definen tablas, claves e indices en SQLite.
 * Solo se omiten los ALTER ADD CONSTRAINT de PostgreSQL: estas pruebas
 * verifican las reglas de la API, no los CHECK ni bloqueos de PostgreSQL.
 */
class SqliteMigrationConnection extends SQLiteConnection
{
    public function statement($query, $bindings = [])
    {
        if (preg_match('/^ALTER TABLE \w+ ADD CONSTRAINT /i', $query)) {
            return true;
        }

        return parent::statement($query, $bindings);
    }
}
