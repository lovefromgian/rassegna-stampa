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

### TD-004 — Google News/RSS restituisce URL di redirect, non l'articolo
- **Motivo:** il feed di Google News dà link `news.google.com/rss/articles/…` che rimandano
  all'articolo reale solo aprendoli. L'URL salvato su `uscite.url` è quindi il redirect, non
  l'URL canonico dell'editore. La cattura Playwright segue il redirect (lo screenshot è
  corretto), ma la deduplica lavora sul link di Google: lo stesso articolo raggiunto con due
  redirect diversi potrebbe sfuggire alla dedup, e l'URL mostrato non è quello dell'editore.
- **Rischio:** **basso/medio** — dedup imperfetta tra scansioni, URL poco leggibile nel PDF.
- **Azione prevista:** risolvere il redirect al `urlFinale` dopo la cattura e salvarlo come
  `url` canonico (la cattura già conosce `urlFinale`); oppure passare a una fonte che
  restituisce l'URL diretto (API a pagamento), essendo la fonte dietro interfaccia.
- **File:** `app/Support/Discovery/GoogleNewsRss.php`, `app/Jobs/CatturaUscita.php`.

### TD-006 — Ticket UX residuo (UX-06)
- **Motivo:** la revisione UX di Cowork (`REVISIONE-UX.md`) elenca 6 interventi. Fatti i
  cinque a maggior impatto (UX-01 prossimo passo, UX-02 metriche, UX-04 stepper, UX-03
  alleggerimento scheda, UX-05 utility CSS). Resta **UX-06**: la riga del gestore uscite è
  troppo densa — azioni secondarie da spostare dietro un menu "⋯", riga essenziale.
- **Rischio:** **basso** — solo UI/UX; funzionalità e permessi invariati.
- **Azione prevista:** implementare UX-06 in una sessione successiva (dettagli in
  `REVISIONE-UX.md`).
- **File:** `resources/views/livewire/uscite/gestore.blade.php`.

### TD-005 — Snippet di scoperta salvato in testo_estratto
- **Motivo:** in scansione lo snippet del feed è salvato provvisoriamente in
  `uscite.testo_estratto` (per mostrarlo tra i candidati). La cattura lo sovrascrive col
  testo completo, ma finché il candidato non è catturato l'indice full-text (M5) contiene
  solo lo snippet.
- **Rischio:** **basso** — la ricerca d'archivio (M5, ancora da fare) su candidati non
  catturati troverebbe solo lo snippet.
- **Azione prevista:** valutare una colonna dedicata `estratto_scoperta`, oppure indicizzare
  solo le uscite `catturato`/`approvato`.
- **File:** `app/Services/ScansioneRassegna.php`.

-

### TD-007 — Cancellazione definitiva: deroga a "nulla si cancella fisicamente"
- **Motivo:** su richiesta esplicita del committente è stata abilitata la cancellazione
  definitiva (fisica) dei record dal cestino (solo supervisore, con conferma), in deroga alla
  regola storica del progetto. Agisce a cascata (cliente → rassegne → uscite) e rimuove anche
  i file su disco.
- **Rischio:** **alto** — irreversibile e, senza backup dei file (TD-001), i dati eliminati
  non si recuperano. Aggravato dalla cascata: eliminare un cliente distrugge tutto il suo
  storico.
- **Azione prevista:** prima della produzione, mettere in sicurezza con (a) backup/versioning
  dello storage (vedi TD-001) e possibilmente (b) una "trattenuta" (es. cancellazione
  definitiva consentita solo dopo N giorni nel cestino) o un doppio conferma/2FA. Valutare se
  restringere ulteriormente (es. solo record senza PDF consegnati).
- **File:** `app/Services/EliminazioneDefinitiva.php`, `app/Livewire/Cestino.php`,
  Policy `forceDelete` di Cliente/Rassegna/Uscita.

### TD-008 — Produzione su PostgreSQL invece di MySQL (deviazione dalla spec)
- **Motivo:** la spec firmata (`docs/`, CLAUDE §2) indica MySQL 8/MariaDB, ma il server di
  produtione (Hetzner, `rs.fulviabenussi.com`) è standardizzato su **PostgreSQL** (già usato
  dagli altri progetti sul box) e non ha `pdo_mysql`. Su richiesta del committente si è scelto
  di usare il Postgres esistente anziché installare un secondo motore DB su una VPS condivisa
  da 3.7 GB. Dev/test restano su SQLite.
- **Rischio:** **basso** — l'app è ORM-first (Eloquent), l'unico punto driver-specifico è la
  **ricerca full-text dell'archivio**: su MySQL usa l'indice `FULLTEXT`, su PostgreSQL degrada
  a uno scan `ILIKE '%termine%'` (nessun indice full-text, niente ranking). Accettabile ai
  volumi di un'agenzia; da rivedere se l'archivio cresce molto.
- **Azione prevista:** se le ricerche diventano lente, adottare il full-text nativo di
  PostgreSQL (`tsvector`/`tsquery` + indice GIN) nel ramo `pgsql` di `Archivio`. Nessuna
  migrazione dati necessaria oggi.
- **File:** `app/Livewire/Archivio.php` (ramo pgsql → ILIKE),
  `database/migrations/2026_07_12_000004_create_uscite_table.php` (indice FULLTEXT solo su
  mysql), `config/database.php`, `.env` di produzione.

### TD-009 — Nessun backup automatico in produzione (DB + file)
- **Motivo:** il deploy su `rs.fulviabenussi.com` (Hetzner, 15 lug 2026) è andato in produzione
  senza backup schedulati né del database PostgreSQL (`rassegna_db`) né della cartella file
  (`storage/app`). Estende TD-001 (che riguardava solo i file) ora che esistono dati vivi e un
  DB di produzione.
- **Rischio:** **alto** — un guasto o un errore azzera clienti, rassegne, uscite e l'archivio
  storico. Aggravato dall'esistenza della cancellazione definitiva (TD-007).
- **Azione prevista:** cron di `pg_dump rassegna_db` giornaliero + copia di `storage/app` su
  storage esterno (o object storage, vedi TD-001), con ritenzione. Verificare periodicamente il
  ripristino, non solo il dump.
- **File:** infrastruttura server (cron/systemd-timer), non nel repo.

## Risolti

_(Sposta qui con la data quando il debito è saldato.)_

-
