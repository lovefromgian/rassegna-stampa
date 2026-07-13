<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 32px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f1f1d; font-size: 12px; line-height: 1.5; }

        .accent { color: {{ $coloreAccento }}; }

        /* Copertina */
        .cover { border: 3px solid {{ $coloreAccento }}; padding: 48px 36px; text-align: center; height: 720px; }
        .cover .logo { max-height: 120px; max-width: 320px; margin-bottom: 28px; }
        .cover .kicker { text-transform: uppercase; letter-spacing: 3px; font-size: 13px; color: #66655f; }
        .cover h1 { font-size: 26px; margin: 18px 0 8px; }
        .cover .sub { font-size: 15px; color: #66655f; margin: 0 auto; max-width: 560px; }
        .cover .meta { margin-top: 32px; font-size: 13px; color: #66655f; }
        .cover .rule { width: 80px; height: 3px; background: {{ $coloreAccento }}; margin: 24px auto; }

        /* Indice */
        .page-break { page-break-before: always; }
        h2.section { font-size: 18px; border-bottom: 2px solid {{ $coloreAccento }}; padding-bottom: 6px; }
        table.index { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.index th { text-align: left; font-size: 11px; color: #66655f; border-bottom: 1px solid #cfcec8; padding: 6px 4px; }
        table.index td { padding: 7px 4px; border-bottom: 1px solid #e3e2dd; vertical-align: top; }
        .num { color: #93928b; width: 24px; }

        /* Pagina uscita */
        .uscita-head { border-left: 4px solid {{ $coloreAccento }}; padding-left: 12px; margin-bottom: 12px; }
        .uscita-head .testata { font-size: 17px; font-weight: bold; }
        .uscita-head .riga { font-size: 12px; color: #66655f; }
        .uscita-head .link { font-size: 11px; word-break: break-all; }
        .titolo-art { font-size: 14px; margin: 6px 0 12px; }
        .shot img { max-width: 100%; border: 1px solid #cfcec8; }
        .badge { display: inline-block; font-size: 10px; padding: 2px 8px; border: 1px solid #cfcec8; border-radius: 10px; color: #66655f; }
    </style>
</head>
<body>

{{-- 1. Copertina --}}
<div class="cover">
    @if ($logoDataUri)
        <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
    @else
        <div style="font-size:22px;font-weight:bold;margin-bottom:28px;">{{ $cliente->nome }}</div>
    @endif
    <div class="kicker">Rassegna stampa</div>
    <div class="rule"></div>
    <h1>{{ $rassegna->comunicato_titolo ?: $rassegna->titolo }}</h1>
    @if ($rassegna->comunicato_sottotitolo)
        <p class="sub">{{ $rassegna->comunicato_sottotitolo }}</p>
    @endif
    <div class="meta">
        {{ $cliente->nome }}<br>
        @if ($rassegna->comunicato_data)
            Comunicato del {{ $rassegna->comunicato_data->format('d/m/Y') }} ·
        @endif
        monitoraggio {{ $rassegna->monitoraggio_inizio->format('d/m/Y') }} – {{ $rassegna->monitoraggio_fine->format('d/m/Y') }}
    </div>
</div>

{{-- 2. Indice delle uscite --}}
<div class="page-break"></div>
<h2 class="section">Indice delle uscite</h2>
<table class="index">
    <thead>
        <tr><th class="num">#</th><th>Testata</th><th>Titolo</th><th>Data</th><th>Tipo</th></tr>
    </thead>
    <tbody>
        @foreach ($voci as $i => $voce)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $voce['uscita']->testata->nome }}</td>
                <td>{{ $voce['uscita']->titolo }}</td>
                <td>{{ $voce['uscita']->data_pubblicazione->format('d/m/Y') }}</td>
                <td><span class="badge">{{ $voce['uscita']->tipo_media->etichetta() }}</span></td>
            </tr>
        @endforeach
    </tbody>
</table>
<p style="margin-top:14px;color:#66655f;font-size:11px;">{{ count($voci) }} uscite · {{ $cliente->nome }}</p>

{{-- 3. Una pagina per uscita --}}
@foreach ($voci as $i => $voce)
    @php $u = $voce['uscita']; @endphp
    <div class="page-break"></div>
    <div class="uscita-head">
        <div class="testata">{{ $u->testata->nome }}@if ($u->pagina_giornale), {{ $u->pagina_giornale }}@endif</div>
        <div class="riga">{{ $u->data_pubblicazione->format('d/m/Y') }} · {{ $u->tipo_media->etichetta() }} · {{ $u->rilevanza?->etichetta() }}</div>
        @if ($u->url)
            <div class="link accent">{{ $u->url }}</div>
        @endif
    </div>
    <div class="titolo-art">{{ $u->titolo }}</div>
    <div class="shot">
        @if ($voce['immagine'])
            <img src="{{ $voce['immagine'] }}" alt="Cattura">
        @elseif ($u->file_caricato_path)
            <p style="color:#66655f;">Materiale allegato a parte: {{ basename($u->file_caricato_path) }}</p>
        @else
            <p style="color:#66655f;">Nessuna immagine disponibile.</p>
        @endif
    </div>
@endforeach

</body>
</html>
