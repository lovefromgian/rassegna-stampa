# TECH-DEBT — Registro autoritativo del debito tecnico

> Ogni scostamento noto e non risolvibile subito va qui, non lasciato implicito nel
> codice. Claude Code aggiunge una voce quando prende una scorciatoia o quando un
> vincolo di specifica resta applicato solo in UI (aggirabile via API). Si aggiorna
> nello stesso commit che risolve il debito. Non è lo stato del lavoro (PROGRESS.md)
> né un elenco di bug attivi.

Formato voce: `TD-xxx` · titolo · motivo · rischio · azione prevista · file tracciati.

## Aperti

### TD-001 — Nessun backup dell'archivio file
- **Motivo:** in questa fase i file (screenshot, PDF pagina, ritagli, rassegne generate)
  stanno in una cartella del progetto sul VPS Hetzner. Scelta voluta per partire semplici,
  ma non c'è alcuna strategia di backup.
- **Rischio:** **alto** — l'archivio storico delle rassegne è il valore accumulato del
  sistema. Un guasto al disco o una cancellazione accidentale lo azzera. Il database si
  ripristina, i file no.
- **Azione prevista:** prima della produzione seria, o backup periodico della cartella su
  storage esterno, o migrazione a storage object (S3 / Cloudflare R2 / Backblaze). La
  migrazione è già preparata: lo storage passa dal disco Laravel, quindi è cambio di
  configurazione, non di codice.
- **File:** `config/filesystems.php`, cartella storage del progetto.

### TD-002 — Chiusura cookie banner euristica (cattura)
- **Motivo:** `scripts/capture.cjs` chiude i consent manager con una lista di selettori noti
  (Iubenda, OneTrust, generici "Accetta"/"Accept"). I consent manager non in lista, o quelli
  dentro iframe/shadow DOM, possono NON venire chiusi: lo screenshot esce con mezzo articolo
  coperto dal banner. La regola (regole-business.md §4) dice che uno screenshot così **non è
  valido**.
- **Rischio:** **medio** — non blocca il flusso (l'operatore vede il problema in revisione e
  ricattura o carica il file a mano), ma peggiora la resa su alcune testate e aumenta il
  lavoro manuale.
- **Azione prevista:** ampliare la lista di selettori sulle testate FVG effettivamente usate;
  valutare l'integrazione di una libreria di block-list consenso (es. `@cliqz/adblocker` o
  regole EasyList/“I don't care about cookies”) nel contesto Playwright.
- **File:** `scripts/capture.cjs` (array `SELETTORI_CONSENSO`).

### TD-003 — Riordino uscite con frecce, non drag&drop
- **Motivo:** la specifica (regole-business.md §6, mockup 08) prevede il riordino delle
  uscite nel PDF via **drag&drop**. È implementato con pulsanti su/giù (`OrdinePdf`): il
  requisito funzionale — riordino manuale che persiste in `posizione_pdf` e prevale sulla
  proposta — è pienamente soddisfatto e testato; manca solo l'affordance del trascinamento.
- **Rischio:** **basso** — solo ergonomia. Con molte uscite il riordino a frecce è più lento.
- **Azione prevista:** integrare una libreria sortable (es. SortableJS + Alpine) collegata a
  un metodo Livewire che riceve il nuovo ordine e riscrive `posizione_pdf` in blocco.
- **File:** `app/Livewire/Rassegne/OrdinePdf.php`, `resources/views/livewire/rassegne/ordine-pdf.blade.php`.

-

## Risolti

_(Sposta qui con la data quando il debito è saldato.)_

-
