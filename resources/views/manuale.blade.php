<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manuale d'uso — Rassegna Stampa</title>
</head>
<body>
@verbatim
<style>
  :root {
    --paper: #f7f7f5;
    --surface: #ffffff;
    --surface-alt: #fafaf8;
    --ink: #1f1f1d;
    --ink-2: #56554f;
    --ink-3: #86857d;
    --line: #e3e2dd;
    --line-strong: #cfcec8;
    --accent: #185fa5;
    --accent-soft: #e6f1fb;
    --ok: #1f7a4d;
    --ok-soft: #e6f4ec;
    --warn: #9a6b12;
    --warn-soft: #fbf1dd;
    --danger: #b23b3b;
    --danger-soft: #f8e7e7;
    --sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    --serif: "Iowan Old Style", "Palatino Linotype", Palatino, "Book Antiqua", Georgia, serif;
    --mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
    --maxw: 68ch;
    --radius: 10px;
  }
  @media (prefers-color-scheme: dark) {
    :root {
      --paper: #17181a;
      --surface: #1e1f22;
      --surface-alt: #232427;
      --ink: #e9e8e3;
      --ink-2: #b6b5ad;
      --ink-3: #8b8a82;
      --line: #33343880;
      --line-strong: #45464b;
      --accent: #6aa8e6;
      --accent-soft: #17324b;
      --ok: #6fca97;
      --ok-soft: #16321f;
      --warn: #e0b25a;
      --warn-soft: #322914;
      --danger: #e08585;
      --danger-soft: #3a1f1f;
    }
  }
  :root[data-theme="light"] {
    --paper: #f7f7f5; --surface: #ffffff; --surface-alt: #fafaf8;
    --ink: #1f1f1d; --ink-2: #56554f; --ink-3: #86857d;
    --line: #e3e2dd; --line-strong: #cfcec8;
    --accent: #185fa5; --accent-soft: #e6f1fb;
    --ok: #1f7a4d; --ok-soft: #e6f4ec; --warn: #9a6b12; --warn-soft: #fbf1dd;
    --danger: #b23b3b; --danger-soft: #f8e7e7;
  }
  :root[data-theme="dark"] {
    --paper: #17181a; --surface: #1e1f22; --surface-alt: #232427;
    --ink: #e9e8e3; --ink-2: #b6b5ad; --ink-3: #8b8a82;
    --line: #33343880; --line-strong: #45464b;
    --accent: #6aa8e6; --accent-soft: #17324b;
    --ok: #6fca97; --ok-soft: #16321f; --warn: #e0b25a; --warn-soft: #322914;
    --danger: #e08585; --danger-soft: #3a1f1f;
  }

  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body {
    background: var(--paper);
    color: var(--ink);
    font-family: var(--sans);
    font-size: 16px;
    line-height: 1.7;
    -webkit-font-smoothing: antialiased;
  }

  .appbar {
    position: sticky; top: 0; z-index: 20;
    background: color-mix(in srgb, var(--paper) 88%, transparent);
    backdrop-filter: saturate(1.4) blur(8px);
    border-bottom: 1px solid var(--line);
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 10px 24px;
  }
  .appbar a.back {
    color: var(--ink); text-decoration: none; font-weight: 600; font-size: 0.92rem;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .appbar a.back:hover { color: var(--accent); }
  .appbar .who { font-family: var(--mono); font-size: 0.74rem; color: var(--ink-3); }

  .wrap {
    max-width: 1120px;
    margin: 0 auto;
    padding: 0 24px 96px;
    display: grid;
    grid-template-columns: 264px minmax(0, 1fr);
    gap: 56px;
    align-items: start;
  }

  .masthead {
    grid-column: 1 / -1;
    border-bottom: 2px solid var(--ink);
    padding: 36px 0 20px;
    margin-bottom: 8px;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
  }
  .masthead .eyebrow {
    font-size: 0.72rem; letter-spacing: 0.18em; text-transform: uppercase;
    color: var(--accent); font-weight: 600; margin: 0 0 6px;
  }
  .masthead h1 {
    font-family: var(--serif); font-weight: 600;
    font-size: clamp(1.9rem, 4vw, 2.7rem); line-height: 1.05; margin: 0;
    text-wrap: balance; letter-spacing: -0.01em;
  }
  .masthead .sub { color: var(--ink-2); font-size: 0.95rem; margin: 8px 0 0; }
  .masthead .meta { font-family: var(--mono); font-size: 0.74rem; color: var(--ink-3); text-align: right; line-height: 1.6; }

  .side { position: sticky; top: 68px; }
  .side summary { display: none; }
  .toc-title {
    font-size: 0.72rem; letter-spacing: 0.16em; text-transform: uppercase;
    color: var(--ink-3); font-weight: 600; margin: 0 0 10px; padding-left: 12px;
  }
  .side nav { display: flex; flex-direction: column; }
  .side .group-label {
    font-family: var(--serif); font-size: 0.86rem; font-weight: 600;
    color: var(--ink-2); margin: 18px 0 4px; padding-left: 12px;
  }
  .side .group-label:first-of-type { margin-top: 0; }
  .side a {
    display: block; text-decoration: none; color: var(--ink-2);
    font-size: 0.9rem; line-height: 1.35; padding: 5px 12px;
    border-left: 2px solid transparent; border-radius: 0 6px 6px 0;
    transition: color .12s, background .12s, border-color .12s;
  }
  .side a:hover { color: var(--ink); background: var(--surface-alt); }
  .side a.active { color: var(--accent); border-left-color: var(--accent); background: var(--accent-soft); font-weight: 500; }

  main { min-width: 0; max-width: var(--maxw); }
  section { scroll-margin-top: 72px; padding-top: 8px; }
  section + section { margin-top: 12px; }
  section > h2 {
    font-family: var(--serif); font-weight: 600; font-size: 1.6rem;
    letter-spacing: -0.01em; margin: 40px 0 4px; padding-top: 20px;
    border-top: 1px solid var(--line); text-wrap: balance;
  }
  section:first-of-type > h2 { border-top: none; margin-top: 8px; padding-top: 0; }
  .lead { color: var(--ink-2); font-size: 1.02rem; margin: 6px 0 18px; }
  h3 { font-family: var(--sans); font-weight: 650; font-size: 1.06rem; margin: 26px 0 6px; color: var(--ink); }
  p { margin: 0 0 14px; }
  main a { color: var(--accent); text-decoration: none; border-bottom: 1px solid color-mix(in srgb, var(--accent) 35%, transparent); }
  main a:hover { border-bottom-color: var(--accent); }
  strong { font-weight: 650; }
  ul, ol { margin: 0 0 16px; padding-left: 22px; }
  li { margin: 4px 0; }
  li::marker { color: var(--ink-3); }

  .role {
    display: inline-block; font-family: var(--mono); font-size: 0.72rem;
    padding: 1px 7px; border-radius: 20px; border: 1px solid var(--line-strong);
    color: var(--ink-2); white-space: nowrap; vertical-align: middle;
  }
  .role.sup { color: var(--accent); border-color: color-mix(in srgb, var(--accent) 45%, transparent); background: var(--accent-soft); }
  .role.op { color: var(--ok); border-color: color-mix(in srgb, var(--ok) 45%, transparent); background: var(--ok-soft); }

  .note {
    border: 1px solid var(--line); border-left: 3px solid var(--accent);
    background: var(--surface); border-radius: var(--radius);
    padding: 12px 16px; margin: 16px 0; font-size: 0.95rem;
  }
  .note.warn { border-left-color: var(--warn); }
  .note.tip { border-left-color: var(--ok); }
  .note .k { font-weight: 650; font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--ink-3); display: block; margin-bottom: 2px; }

  .flow { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin: 4px 0 20px; }
  .flow .chip { font-family: var(--mono); font-size: 0.78rem; background: var(--surface); border: 1px solid var(--line-strong); padding: 4px 10px; border-radius: 20px; color: var(--ink); }
  .flow .arrow { color: var(--ink-3); font-size: 0.9rem; }

  .pill { font-family: var(--mono); font-size: 0.76rem; padding: 2px 9px; border-radius: 20px; white-space: nowrap; display: inline-block; }
  .pill.n { background: var(--surface-alt); border: 1px solid var(--line-strong); color: var(--ink-2); }
  .pill.a { background: var(--accent-soft); color: var(--accent); }
  .pill.g { background: var(--ok-soft); color: var(--ok); }
  .pill.w { background: var(--warn-soft); color: var(--warn); }
  .pill.d { background: var(--danger-soft); color: var(--danger); }

  .defs { border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden; margin: 16px 0; background: var(--surface); }
  .defs .row { display: grid; grid-template-columns: 190px 1fr; gap: 16px; padding: 11px 16px; border-top: 1px solid var(--line); }
  .defs .row:first-child { border-top: none; }
  .defs .row > div:first-child { font-weight: 600; }
  .defs .row .d { color: var(--ink-2); font-size: 0.95rem; }
  @media (max-width: 520px) { .defs .row { grid-template-columns: 1fr; gap: 2px; } }

  .backtop { display: inline-block; margin-top: 14px; font-size: 0.82rem; color: var(--ink-3); text-decoration: none; border: none; }
  .backtop:hover { color: var(--accent); }

  footer.end { grid-column: 1 / -1; border-top: 1px solid var(--line); margin-top: 40px; padding-top: 18px; color: var(--ink-3); font-size: 0.85rem; }

  a:focus-visible, summary:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 4px; }
  html { scroll-behavior: smooth; }
  @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } * { transition: none !important; } }

  @media (max-width: 860px) {
    .wrap { grid-template-columns: 1fr; gap: 8px; }
    .side { position: static; margin-bottom: 8px; }
    .side details { border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); padding: 6px 10px; }
    .side summary { display: block; cursor: pointer; font-weight: 600; font-size: 0.95rem; padding: 6px 4px; list-style: none; }
    .side summary::after { content: "  ▾"; color: var(--ink-3); }
    .side details[open] summary::after { content: "  ▴"; }
    .toc-title { display: none; }
    main { max-width: none; }
  }
</style>

<div class="appbar">
  <a class="back" href="/">← Torna al gestionale</a>
  <span class="who">Manuale d'uso · uso interno</span>
</div>

<div class="wrap" id="top">

  <header class="masthead">
    <div>
      <p class="eyebrow">Manuale d'uso</p>
      <h1>Rassegna Stampa</h1>
      <p class="sub">Gestionale per la produzione delle rassegne stampa dell'agenzia — dalla ricerca degli articoli al PDF da consegnare.</p>
    </div>
    <div class="meta">
      uso interno<br>
      ruoli: supervisore · operatore<br>
      v1
    </div>
  </header>

  <aside class="side">
    <details open>
      <summary>Indice</summary>
      <p class="toc-title">Indice</p>
      <nav id="toc">
        <span class="group-label">Per iniziare</span>
        <a href="#panoramica">Cos'è e com'è organizzato</a>
        <a href="#ruoli">Accesso e ruoli</a>

        <span class="group-label">Menu principale</span>
        <a href="#clienti">Clienti</a>
        <a href="#rassegne">Rassegne</a>
        <a href="#archivio">Archivio</a>
        <a href="#statistiche">Statistiche</a>
        <a href="#log">Log</a>
        <a href="#utenti">Utenti</a>
        <a href="#cestino">Cestino</a>

        <span class="group-label">Il flusso di lavoro</span>
        <a href="#flusso">Le fasi di una rassegna</a>
        <a href="#candidati">1 · Candidati</a>
        <a href="#revisione">2 · Revisione</a>
        <a href="#pdf">3 · Ordine e PDF</a>

        <span class="group-label">Riferimento</span>
        <a href="#stati">Stati e termini</a>
        <a href="#faq">Problemi frequenti</a>
      </nav>
    </details>
  </aside>

  <main>
    <section id="panoramica">
      <h2>Cos'è e com'è organizzato</h2>
      <p class="lead">Il gestionale, data una campagna di monitoraggio, cerca automaticamente sul web gli articoli che parlano di un cliente, li propone all'operatore che li conferma o scarta, ne cattura screenshot e testo, e infine genera un PDF impaginato della rassegna da scaricare e consegnare.</p>
      <p>I <strong>clienti finali non accedono</strong> al sistema: ricevono soltanto il PDF finito.</p>

      <h3>Come sono organizzati i dati</h3>
      <p>Tutto ruota attorno a tre livelli, uno dentro l'altro:</p>
      <div class="flow">
        <span class="chip">Cliente</span><span class="arrow">→</span>
        <span class="chip">Rassegna</span><span class="arrow">→</span>
        <span class="chip">Uscite</span>
      </div>
      <ul>
        <li><strong>Cliente</strong> — l'azienda o l'ente per cui si lavora. Ha un logo e un colore d'accento che personalizzano il PDF.</li>
        <li><strong>Rassegna</strong> — una campagna di monitoraggio per un cliente, con un periodo e delle parole chiave. È qui che si lavora.</li>
        <li><strong>Uscita</strong> — il singolo articolo (o servizio radio/TV, ritaglio di giornale…) raccolto dentro una rassegna.</li>
      </ul>
      <p>Il <strong>PDF appartiene alla rassegna</strong> ed è <strong>versionato</strong>: ogni generazione crea una nuova versione, senza mai sovrascrivere le precedenti.</p>
    </section>

    <section id="ruoli">
      <h2>Accesso e ruoli</h2>
      <p class="lead">Si accede con email e password. Esistono due ruoli, con permessi diversi.</p>
      <div class="defs">
        <div class="row">
          <div><span class="role sup">Supervisore</span></div>
          <div class="d">Può fare tutto: anagrafica clienti e impostazioni, eliminazioni, riapertura di una rassegna chiusa, e <strong>gestione degli utenti</strong>.</div>
        </div>
        <div class="row">
          <div><span class="role op">Operatore</span></div>
          <div class="d">Crea rassegne, conferma o scarta i candidati, revisiona le catture e genera il PDF. Non tocca l'anagrafica clienti, non elimina, non gestisce utenti.</div>
        </div>
      </div>
      <p>Non c'è divisione per cliente: ogni utente vede tutti i clienti dell'agenzia. Un account <strong>disattivato non può più accedere</strong>; per riattivarlo serve un supervisore.</p>
      <div class="note"><span class="k">Nota</span> Nel manuale i permessi riservati sono segnalati con l'etichetta <span class="role sup">Supervisore</span>. Dove non è indicato, l'azione è disponibile anche all'operatore.</div>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="clienti">
      <h2>Clienti</h2>
      <p class="lead">L'anagrafica di tutti i clienti dell'agenzia. È il punto di partenza: ogni rassegna appartiene a un cliente.</p>
      <ul>
        <li><strong>Elenco e ricerca</strong> — cerca per nome; ogni riga mostra quante rassegne ha il cliente.</li>
        <li><strong>Scheda cliente</strong> — dati, impostazioni e le rassegne collegate.</li>
        <li><strong>Nuovo / Modifica</strong> <span class="role sup">Supervisore</span> — nome, logo e <strong>colore d'accento</strong>: logo e colore sono gli unici elementi che personalizzano il PDF consegnato.</li>
        <li><strong>Elimina</strong> <span class="role sup">Supervisore</span> — sposta il cliente nel <a href="#cestino">Cestino</a> (è recuperabile, non viene cancellato subito).</li>
      </ul>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="rassegne">
      <h2>Rassegne</h2>
      <p class="lead">Il cuore del lavoro. Una rassegna è una campagna di monitoraggio con un periodo, delle parole chiave e le uscite raccolte.</p>

      <h3>Creare una rassegna</h3>
      <ul>
        <li><strong>Cliente e titolo</strong> della rassegna.</li>
        <li><strong>Periodo di monitoraggio</strong> (inizio e fine). Se c'è un <strong>comunicato</strong>, il sistema precompila: inizio = data del comunicato, fine = +14 giorni. Se è una rassegna di periodo (senza comunicato), le date le imposti tu.</li>
        <li><strong>Parole chiave</strong> — una lista di termini <strong>richiesti</strong> e una di termini da <strong>escludere</strong>. Gli esclusi servono a tagliare i falsi positivi (es. "Grado" ne genera molti).</li>
      </ul>
      <p>Alla creazione parte già una prima <strong>scansione automatica</strong> e si atterra sui candidati trovati.</p>

      <h3>Le scansioni</h3>
      <p>Ogni rassegna con periodo attivo viene scansionata <strong>una volta al giorno</strong> in automatico. In qualsiasi momento puoi lanciare una scansione manuale con <strong>«Scansiona ora»</strong> dalla schermata Candidati.</p>

      <h3>Stati della rassegna</h3>
      <div class="flow">
        <span class="chip">in raccolta</span><span class="arrow">→</span>
        <span class="chip">in revisione</span><span class="arrow">→</span>
        <span class="chip">chiusa / inviata</span><span class="arrow">→</span>
        <span class="chip">riaperta</span>
      </div>
      <p>La <strong>riapertura</strong> di una rassegna chiusa è riservata al <span class="role sup">Supervisore</span> e non cancella il PDF già generato: si aggiungono le nuove uscite e si genera una <strong>nuova versione</strong>, così lo storico degli invii resta intero.</p>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="archivio">
      <h2>Archivio</h2>
      <p class="lead">Una ricerca a testo pieno su <strong>tutte le uscite mai raccolte</strong>, trasversale ai clienti e agli anni.</p>
      <p>Scrivi un termine e trovi ogni articolo il cui testo catturato lo contiene. Puoi restringere per <strong>cliente</strong> e per <strong>testata</strong>. È lo strumento per rispondere a domande come «quando abbiamo già parlato di questo tema?» senza riaprire rassegna per rassegna.</p>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="statistiche">
      <h2>Statistiche</h2>
      <p class="lead">Una vista d'insieme del lavoro prodotto.</p>
      <ul>
        <li><strong>Uscite per cliente</strong> — quanto materiale è stato raccolto per ognuno.</li>
        <li><strong>Uscite per tipo di media</strong> — la ripartizione tra online, carta, radio, TV, ecc.</li>
        <li><strong>Testate più presenti</strong> — le fonti che ricorrono di più.</li>
      </ul>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="log">
      <h2>Log</h2>
      <p class="lead">Il registro di controllo: chi ha fatto cosa e quando.</p>
      <p>Ogni azione rilevante viene registrata — conferma e scarto di un candidato, approvazione, generazione e download del PDF, modifiche all'anagrafica, gestione utenti. Il log è consultabile in forma <strong>globale</strong> e <strong>per singola rassegna</strong>.</p>
      <div class="note"><span class="k">Importante</span> Il log è <strong>immutabile</strong>: non si modifica né si cancella, nemmeno da supervisore. È la memoria certa di ciò che è successo.</div>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="utenti">
      <h2>Utenti <span class="role sup">Supervisore</span></h2>
      <p class="lead">La gestione delle persone che accedono al programma. Visibile solo al supervisore.</p>
      <ul>
        <li><strong>Elenco</strong> — nome, email, ruolo e stato (attivo/disattivato) di ogni utente.</li>
        <li><strong>Nuovo / Modifica</strong> — crea un utente o cambiane nome, email, ruolo e password. In modifica, la password si cambia solo se compili il campo (altrimenti resta quella attuale).</li>
        <li><strong>Disattiva / Attiva</strong> — sospende o ripristina l'accesso. Non puoi disattivare te stesso.</li>
      </ul>
      <div class="note"><span class="k">Perché non si eliminano</span> Gli utenti <strong>non si cancellano, si disattivano</strong>. Così il log di audit resta integro: le azioni passate restano attribuite a una persona che esiste ancora. Chi dimentica la password la fa reimpostare da un supervisore.</div>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="cestino">
      <h2>Cestino <span class="role sup">Supervisore</span></h2>
      <p class="lead">Tutto ciò che viene eliminato (clienti, rassegne, uscite) finisce qui, ed è recuperabile.</p>
      <ul>
        <li><strong>Ripristina</strong> — riporta il record al suo posto.</li>
        <li><strong>Selezione multipla</strong> — agisci su più record insieme, o usa <strong>«Svuota cestino»</strong>.</li>
        <li><strong>Elimina definitivamente</strong> — cancellazione fisica e <strong>irreversibile</strong>, con conferma. Su un cliente agisce a cascata (cliente → rassegne → uscite) e rimuove anche i file.</li>
      </ul>
      <div class="note warn"><span class="k">Attenzione</span> La cancellazione definitiva non si può annullare e, non essendoci ancora un backup dei file, i dati eliminati non si recuperano. Usala solo quando sei certo.</div>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="flusso">
      <h2>Le fasi di una rassegna</h2>
      <p class="lead">Dentro una rassegna, il lavoro segue sempre lo stesso percorso. In alto trovi uno <strong>stepper</strong> con le fasi: è anche la navigazione tra le schermate.</p>
      <div class="flow">
        <span class="chip">Candidati</span><span class="arrow">→</span>
        <span class="chip">Revisione</span><span class="arrow">→</span>
        <span class="chip">Approvate</span><span class="arrow">→</span>
        <span class="chip">Ordine / PDF</span><span class="arrow">→</span>
        <span class="chip">Scartate</span>
      </div>
      <p>Ogni voce mostra il proprio conteggio. <strong>Approvate</strong> e <strong>Scartate</strong> aprono l'elenco filtrato delle uscite in quello stato. Le tre fasi operative sono descritte qui sotto.</p>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="candidati">
      <h2>1 · Candidati</h2>
      <p class="lead">Gli articoli che la scansione ha trovato e propone. Qui decidi cosa entra nella rassegna.</p>
      <p>Ogni candidato mostra testata, data, titolo, un estratto e un <strong>punteggio di corrispondenza</strong> con le parole chiave (alta / media / debole). Un falso positivo non viene escluso da solo: appare come «debole», sta a te scartarlo.</p>
      <ul>
        <li><strong>Selezione</strong> — clicca <strong>un punto qualsiasi della riga</strong> per selezionarla (oppure la casella). «Seleziona tutti» per agire in blocco.</li>
        <li><strong>Conferma selezionati</strong> — porta i candidati alla fase successiva e <strong>avvia la cattura</strong> automatica (screenshot + testo).</li>
        <li><strong>Scarta selezionati</strong> — li mette da parte (restano archiviati e recuperabili).</li>
        <li><strong>Scansiona ora</strong> — lancia subito una nuova ricerca.</li>
        <li><strong>Aggiungi a mano</strong> — per ciò che la scansione non trova o non è online (carta, radio, TV): inserisci l'URL o carica un ritaglio.</li>
      </ul>
      <div class="note tip"><span class="k">Deduplica</span> Un URL già presente nella rassegna non viene riproposto. Solo il tipo <strong>online</strong> è cercabile in automatico; gli altri media si inseriscono a mano.</div>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="revisione">
      <h2>2 · Revisione</h2>
      <p class="lead">Un'uscita alla volta: verifichi la cattura, la classifichi e decidi se approvarla o scartarla.</p>
      <p>Per ogni uscita catturata vedi l'<strong>anteprima</strong> dello screenshot, i dati (testata, titolo, data, link) e i campi da compilare:</p>
      <ul>
        <li><strong>Tipo di media</strong> e <strong>Rilevanza</strong> (principale · secondaria · citazione). La rilevanza si assegna <strong>qui</strong>, non alla conferma: determina l'ordine proposto nel PDF.</li>
        <li><strong>Note interne</strong> — visibili solo al team.</li>
      </ul>

      <h3>Navigare tra le uscite</h3>
      <p>In alto a destra ci sono <strong>‹ Precedente</strong> e <strong>Successiva ›</strong>: puoi sfogliare le uscite senza essere obbligato a decidere. Le scelte in corso (tipo, rilevanza, note) restano salvate come bozza, così tornando indietro non perdi il lavoro.</p>

      <h3>Se la cattura non è pulita</h3>
      <ul>
        <li><strong>Ricattura</strong> — rifà lo screenshot dal web. Durante l'operazione il riquadro mostra <em>«Acquisizione in corso…»</em> e si aggiorna da solo appena la nuova cattura è pronta.</li>
        <li><strong>Sostituisci il file</strong> — carichi a mano uno screenshot o un ritaglio (jpg, png, pdf), utile se un banner cookie o un paywall rovinano la pagina. Il file caricato diventa il materiale ufficiale dell'uscita.</li>
      </ul>

      <h3>Decidere</h3>
      <p><strong>Approva</strong> manda l'uscita tra le approvate e passa alla successiva; <strong>Scarta</strong> la mette da parte (resta recuperabile). Se altre catture sono ancora in coda, un avviso <em>«N in acquisizione…»</em> ti segnala che ne stanno arrivando altre.</p>
      <div class="note warn"><span class="k">Se non compare nulla</span> Appena confermi un candidato, la cattura viene messa in coda: l'uscita compare in Revisione solo <strong>quando è pronta</strong>. Se vedi «in acquisizione», aspetta qualche istante: si aggiorna da solo.</div>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="pdf">
      <h2>3 · Ordine delle uscite e generazione</h2>
      <p class="lead">L'ultima fase: metti le uscite approvate nell'ordine giusto e generi il PDF da consegnare.</p>
      <ul>
        <li><strong>Ordine</strong> — la proposta è per rilevanza, poi data. Con le frecce ▲▼ riordini a mano: l'ordine manuale prevale ed è la scelta editoriale della rassegna.</li>
        <li><strong>Elimina</strong> <span class="role sup">Supervisore</span> — puoi togliere un'uscita direttamente da qui (va nel Cestino): non comparirà nel PDF che genererai. Le versioni già generate restano intatte.</li>
        <li><strong>Genera PDF</strong> — crea una <strong>nuova versione</strong>. Il sistema registra chi l'ha generata e quando. Il PDF si scarica e si invia a mano al cliente.</li>
        <li><strong>Versioni</strong> — l'elenco di tutte le versioni prodotte, ognuna scaricabile. Non si sovrascrive mai: si aggiunge.</li>
      </ul>
      <div class="note warn"><span class="k">Quando la generazione è bloccata</span> Il PDF non si genera se restano candidati non ancora decisi, o se un'uscita approvata non ha uno screenshot valido. La schermata elenca i motivi: risolvili (decidi i candidati, ricattura o carica i file mancanti) e riprova.</div>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="stati">
      <h2>Stati e termini</h2>
      <p class="lead">Il vocabolario del sistema, in breve.</p>

      <h3>Stati di un'uscita</h3>
      <div class="defs">
        <div class="row"><div><span class="pill n">candidato</span></div><div class="d">Trovato dalla scansione, in attesa di decisione.</div></div>
        <div class="row"><div><span class="pill a">confermato</span></div><div class="d">Accettato: la cattura è stata avviata.</div></div>
        <div class="row"><div><span class="pill a">catturato</span></div><div class="d">Screenshot e testo pronti: è in revisione.</div></div>
        <div class="row"><div><span class="pill g">approvato</span></div><div class="d">Validato: entra nel PDF.</div></div>
        <div class="row"><div><span class="pill d">scartato</span></div><div class="d">Messo da parte, ma archiviato e recuperabile.</div></div>
      </div>

      <h3>Stati di una rassegna</h3>
      <div class="defs">
        <div class="row"><div><span class="pill n">in raccolta</span></div><div class="d">Scansioni attive, si raccolgono candidati.</div></div>
        <div class="row"><div><span class="pill a">in revisione</span></div><div class="d">Si verificano e approvano le uscite.</div></div>
        <div class="row"><div><span class="pill g">chiusa / inviata</span></div><div class="d">PDF generato e consegnato.</div></div>
        <div class="row"><div><span class="pill w">riaperta</span></div><div class="d">Riaperta dal supervisore per aggiunte; porterà a una nuova versione.</div></div>
      </div>

      <h3>Tipo di media</h3>
      <p><span class="pill n">online</span> <span class="pill n">carta stampata</span> <span class="pill n">radio</span> <span class="pill n">TV</span> <span class="pill n">agenzia di stampa</span> <span class="pill n">social / blog</span></p>
      <p>Solo <strong>online</strong> è cercabile in automatico; gli altri richiedono l'inserimento manuale.</p>

      <h3>Rilevanza</h3>
      <p><span class="pill g">principale</span> <span class="pill a">secondaria</span> <span class="pill n">citazione</span> — la assegna l'operatore in revisione e determina l'ordine proposto nel PDF.</p>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <section id="faq">
      <h2>Problemi frequenti</h2>

      <h3>«Confermo un candidato ma non lo trovo in revisione»</h3>
      <p>La cattura è messa in coda e richiede qualche istante. L'uscita compare in Revisione appena è pronta; nel frattempo vedi l'avviso «in acquisizione», che si aggiorna da solo.</p>

      <h3>Lo screenshot ha un banner cookie o un paywall davanti</h3>
      <p>Il sistema prova a chiudere i banner da solo, ma non sempre ci riesce. In Revisione usa <strong>Ricattura</strong>, oppure <strong>Sostituisci il file</strong> caricando uno screenshot tuo.</p>

      <h3>Una cattura è andata in errore</h3>
      <p>Compare il messaggio d'errore sull'uscita. Risolvi con <strong>Ricattura</strong> o con il caricamento manuale; in alternativa scartala. Le catture in errore non si ignorano: vanno risolte prima di generare il PDF.</p>

      <h3>Il PDF non si genera</h3>
      <p>Restano candidati non decisi, oppure un'uscita approvata è senza screenshot valido. La schermata <a href="#pdf">Ordine e generazione</a> elenca i motivi esatti.</p>

      <h3>Ho eliminato qualcosa per sbaglio</h3>
      <p>Clienti, rassegne e uscite eliminati finiscono nel <a href="#cestino">Cestino</a> e si ripristinano <span class="role sup">Supervisore</span>. Fa eccezione la cancellazione definitiva dal cestino, che è irreversibile.</p>
      <a class="backtop" href="#top">↑ Torna all'indice</a>
    </section>

    <footer class="end">
      Manuale d'uso di <strong>Rassegna Stampa</strong> · uso interno dell'agenzia. Le schermate possono evolvere: in caso di dubbio, la logica descritta qui resta il riferimento.
    </footer>
  </main>
</div>

<script>
  (function () {
    var links = Array.prototype.slice.call(document.querySelectorAll('#toc a'));
    var byId = {};
    links.forEach(function (a) { byId[a.getAttribute('href').slice(1)] = a; });
    var sections = Array.prototype.slice.call(document.querySelectorAll('main section[id]'));

    function setActive(id) {
      links.forEach(function (a) { a.classList.remove('active'); });
      if (byId[id]) byId[id].classList.add('active');
    }

    if ('IntersectionObserver' in window) {
      var visible = {};
      var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) visible[e.target.id] = e.intersectionRatio;
          else delete visible[e.target.id];
        });
        var best = null, bestTop = Infinity;
        sections.forEach(function (s) {
          if (visible[s.id] !== undefined) {
            var top = s.getBoundingClientRect().top;
            if (top < bestTop) { bestTop = top; best = s.id; }
          }
        });
        if (best) setActive(best);
      }, { rootMargin: '-10% 0px -70% 0px', threshold: [0, 0.25, 0.5, 1] });
      sections.forEach(function (s) { obs.observe(s); });
    }

    var det = document.querySelector('.side details');
    links.forEach(function (a) {
      a.addEventListener('click', function () {
        if (det && window.matchMedia('(max-width: 860px)').matches) det.open = false;
      });
    });
  })();
</script>
@endverbatim
</body>
</html>
