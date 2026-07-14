<div>
    <div class="page-head">
        <h1 class="mt-0">Statistiche</h1>
        <p>Copertura raccolta, trasversale a rassegne e anni.</p>
    </div>

    <div class="metrics">
        <div class="metric"><div class="label">Clienti</div><div class="value">{{ $totali['clienti'] }}</div></div>
        <div class="metric"><div class="label">Rassegne</div><div class="value">{{ $totali['rassegne'] }}</div></div>
        <div class="metric"><div class="label">Uscite totali</div><div class="value">{{ $totali['uscite'] }}</div></div>
        <div class="metric"><div class="label">Approvate</div><div class="value">{{ $totali['approvate'] }}</div></div>
        <div class="metric"><div class="label">PDF generati</div><div class="value">{{ $totali['pdf'] }}</div></div>
    </div>

    {{-- Due colonne indipendenti: a sinistra "Uscite per cliente" + subito sotto
         "Per tipo di media" (niente spazio vuoto); a destra "Testate più presenti". --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;">
        <div class="stack">
            <div class="card">
                <h2>Uscite per cliente</h2>
                <table class="data">
                    @forelse ($perCliente as $c)
                        <tr><td>{{ $c->nome }}</td><td>{{ $c->uscite_totali }} uscite · {{ $c->rassegne_count }} rassegne</td></tr>
                    @empty
                        <tr><td colspan="2" class="muted">Nessun cliente.</td></tr>
                    @endforelse
                </table>
            </div>

            <div class="card">
                <h2>Per tipo di media</h2>
                <table class="data">
                    @foreach (\App\Enums\TipoMedia::cases() as $tipo)
                        <tr><td>{{ $tipo->etichetta() }}</td><td>{{ $perTipo[$tipo->value] ?? 0 }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Testate più presenti</h2>
            <table class="data">
                @forelse ($perTestata as $t)
                    <tr><td>{{ $t->nome }}</td><td>{{ $t->uscite_count }} uscite</td></tr>
                @empty
                    <tr><td colspan="2" class="muted">Nessuna testata.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
