<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoDownloadController;
use App\Livewire\Archivio;
use App\Livewire\Audit;
use App\Livewire\Cestino;
use App\Livewire\Clienti;
use App\Livewire\Rassegne;
use App\Livewire\Statistiche;
use App\Livewire\Uscite;
use Illuminate\Support\Facades\Route;

// Ospiti: login
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// Autenticati
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    // Clienti — l'accesso in scrittura è filtrato dalle Policy lato server.
    Route::get('/clienti', Clienti\Elenco::class)->name('clienti.index');
    Route::get('/clienti/nuovo', Clienti\Modifica::class)->name('clienti.create');
    Route::get('/clienti/{cliente}', Clienti\Scheda::class)->name('clienti.show');
    Route::get('/clienti/{cliente}/modifica', Clienti\Modifica::class)->name('clienti.edit');

    // Rassegne
    Route::get('/rassegne', Rassegne\Elenco::class)->name('rassegne.index');
    Route::get('/rassegne/nuova', Rassegne\Modifica::class)->name('rassegne.create');
    Route::get('/rassegne/{rassegna}', Rassegne\Scheda::class)->name('rassegne.show');
    Route::get('/rassegne/{rassegna}/modifica', Rassegne\Modifica::class)->name('rassegne.edit');
    Route::get('/rassegne/{rassegna}/uscite', Uscite\Gestore::class)->name('rassegne.uscite');
    Route::get('/rassegne/{rassegna}/candidati', Rassegne\Candidati::class)->name('rassegne.candidati');
    Route::get('/rassegne/{rassegna}/revisione', Rassegne\Revisione::class)->name('rassegne.revisione');
    Route::get('/rassegne/{rassegna}/pdf', Rassegne\OrdinePdf::class)->name('rassegne.pdf');
    Route::get('/documenti/{documento}/download', DocumentoDownloadController::class)->name('documenti.download');

    // M5 — contorno
    Route::get('/log', Audit\Registro::class)->name('log.index');
    Route::get('/rassegne/{rassegna}/log', Audit\Registro::class)->name('rassegne.log');
    Route::get('/archivio', Archivio::class)->name('archivio.index');
    Route::get('/statistiche', Statistiche::class)->name('statistiche.index');
    Route::get('/cestino', Cestino::class)->name('cestino.index');
});
