<?php

use Illuminate\Support\Facades\Schema;

// url e titolo arrivano dal feed esterno (URL di redirect Google News lunghi, titoli
// lunghi). Devono essere `text`: su PostgreSQL varchar(255) fa fallire l'inserimento
// (SQLSTATE 22001), come emerso in produzione. Guardia contro la regressione a varchar.

test('uscite.url è di tipo text (URL di redirect lunghi)', function () {
    expect(Schema::getColumnType('uscite', 'url'))->toBe('text');
});

test('uscite.titolo è di tipo text (titoli lunghi dal feed)', function () {
    expect(Schema::getColumnType('uscite', 'titolo'))->toBe('text');
});
