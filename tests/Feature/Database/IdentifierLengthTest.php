<?php

use Illuminate\Support\Facades\Schema;

// Tests run on SQLite, which accepts any length; MariaDB in production stops at 64.
it('garde les noms d’index et de clés étrangères sous la limite de MariaDB', function () {
    $tooLong = [];

    foreach (Schema::getTableListing(schemaQualified: false) as $table) {
        $names = array_column(Schema::getIndexes($table), 'name');

        // SQLite does not keep foreign key names: rebuild Laravel's default one.
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            $names[] = $foreignKey['name'] ?? $table.'_'.implode('_', $foreignKey['columns']).'_foreign';
        }

        $tooLong = [...$tooLong, ...array_filter($names, fn (string $name) => strlen($name) > 64)];
    }

    expect($tooLong)->toBe([]);
});
