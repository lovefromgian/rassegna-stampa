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

-

## Risolti

_(Sposta qui con la data quando il debito è saldato.)_

-
