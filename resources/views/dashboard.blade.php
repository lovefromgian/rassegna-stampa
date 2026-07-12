@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <h1>Benvenuto, {{ auth()->user()->name }}</h1>
        <p>Gestionale rassegne stampa · ruolo: {{ auth()->user()->ruolo->etichetta() }}</p>
    </div>

    <div class="metrics">
        <div class="metric">
            <div class="label">Clienti attivi</div>
            <div class="value">{{ $clientiAttivi }}</div>
        </div>
        <div class="metric">
            <div class="label">Rassegne in raccolta</div>
            <div class="value">{{ $rassegneInRaccolta }}</div>
        </div>
        <div class="metric">
            <div class="label">Rassegne in revisione</div>
            <div class="value">{{ $rassegneInRevisione }}</div>
        </div>
    </div>

    <div class="card">
        <h2>Accessi rapidi</h2>
        <div class="actions">
            <a class="btn" href="{{ route('clienti.index') }}">Clienti</a>
            <a class="btn" href="{{ route('rassegne.index') }}">Rassegne</a>
        </div>
    </div>
@endsection
