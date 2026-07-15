# PROGRESS — Stato vivo del lavoro

> Dove siamo: cosa è fatto, in corso, prossimi passi, decisioni da ricordare.
> Convenzioni e setup → CLAUDE.md. Debito tecnico → TECH-DEBT.md. Qui solo lo **stato**.

**Ultimo aggiornamento:** scope v1 + revisione UX + eliminazione/cestino + gestione utenti + deploy prod · 15 lug 2026 · 132 test verdi (352 asserzioni)

## ▶ RIPRENDI DA QUI

> Blocco di ripresa rapida: aggiornato dopo **ogni sotto-passo** (non solo a fine
> milestone), per riprendere senza perdite se una sessione si interrompe (es. soglia
> token). Se leggi questo all'avvio: fai `git log --oneline -5`, poi continua da qui.

- **Stato:** **SCOPE v1 COMPLETO (M1–M5).** Il gestionale gira end-to-end: clienti/rassegne
  → scoperta automatica → cattura → revisione → PDF impaginato versionato → contorno (log,
  archivio, statistiche, chiusura/riapertura). **132 test verdi (352 asserzioni)** (comprese
  le aggiunte post-M5: UX revisione, eliminazione/cestino, gestione utenti, deploy prod).
- **M5 (Contorno) completata:** log di audit consultabile globale e per rassegna
  (`Audit\Registro`, immutabile); archivio con ricerca full-text sul testo estratto
  (`Archivio`, MySQL fulltext / LIKE su SQLite); statistiche per cliente e testata
  (`Statistiche`); chiusura raccolta/rassegna e **riapertura solo supervisore** con
  versionamento PDF.
- **Verifica reale M5:** ciclo di vita completo — v1 generata → chiusa → riaperta → uscita
  tardiva aggiunta → v2 generata, **entrambe le versioni conservate** (§9).
- **In produzione:** l'app è online su **https://rs.fulviabenussi.com** (deploy 15 lug 2026 —
  vedi sezione dedicata in fondo al blocco). Aggiornamenti futuri: `git pull` + `composer install`
  + `npm run build` + `php artisan migrate --force` + `php artisan optimize` + restart worker.
- **Prossimo passo concreto:** nessuna milestone né feature residua richiesta. Priorità di
  hardening ora che c'è produzione: **backup DB+file** (TD-009, alto), poi resto del debito
  (TD-002 cookie banner, TD-004 URL Google News, TD-005 snippet, TD-006 UX-06) e il **reset
  password self-service** (oggi la resetta il supervisore).
- **Decisioni M5:** log consultabile ma immutabile (nessuna UI di modifica); riapertura
  gated a supervisore lato server (Policy); chiusura rassegna richiede un PDF generato (§9).
- **Aggiunte/fix post-M5 (collaudo con l'utente):**
  - **Scansione automatica alla creazione** rassegna (job `ScansionaRassegna` accodato) +
    atterraggio sui candidati con `wire:poll`. (commit `ee218fa`)
  - **Notifiche toast** per le azioni Livewire in place: i flash di sessione non si
    mostravano (il layout non si ri-renderizza). Trait `NotificaUtente` + listener nel
    layout; convertiti Candidati/Uscite·Gestore/Revisione/OrdinePdf/Scheda. **Guardia date:**
    periodo/comunicato devono essere ≥ 2000-01-01 (evita anni assurdi che facevano fallire la
    scansione in silenzio). (commit `8818438`)
  - **Immagini catture via URL relativo** `/storage` (disco `public`, override
    `FILESYSTEM_PUBLIC_URL`): prima usavano `APP_URL` e in dev non caricavano (host/porta
    diversi). (commit `915042a`)
  - **Nota di dominio:** la cattura è automatica **alla conferma** del candidato
    (candidato→confermato→cattura), non alla scansione — verificato in collaudo.
- **Revisione UX (REVISIONE-UX.md, handoff Cowork):** fatti **UX-01** (prossimo passo
  contestuale + nota col motivo reale via `BlocchiGenerazione`), **UX-02** (metriche sulla
  scheda), **UX-04** (stepper delle fasi, partial `partials/fasi-rassegna`) e **UX-03**
  (alleggerimento scheda: rimosso l'embed di `uscite.gestore`, ora c'è un elenco compatto in
  **sola lettura** + bottone "Aggiungi a mano"; la gestione pesante — aggiunta, cattura,
  ricattura, sostituzione file, scarto — vive nella schermata dedicata `rassegne.uscite`,
  non più sulla scheda). Conteggi centralizzati in `Rassegna::conteggiPerStato()`. Solo UI.
  Test `SchedaProssimoPassoTest` (5) + `FasiRassegnaTest` (5) + `UsciteGestoreTest` (scheda
  senza azioni di gestione).
- **UX-05** (consolidamento stili inline): estratte poche utility in `app.css`
  (`.stack`, `.toolbar`, `.center`, `.nowrap`, `.plain`, `.hint`, scala `.mt-*`/`.mb-*`,
  `.m-0`) e `.btn` ora ha `text-decoration:none`; rimossi ~44 `style=` inline su 12 viste
  (app views 115 → 71). Refactor cosmetico a equivalenza, nessun cambio di comportamento
  (95 test verdi). **Resta solo UX-06** (REVISIONE-UX.md, TECH-DEBT TD-006).
- **Fix PDF (collaudo):** testo e foto ora **sulla stessa pagina** per ogni uscita. Prima
  l'immagine full-page (senza limite d'altezza) traboccava alla pagina successiva ("una
  pagina di testo, poi una di foto"). Soluzione finale (commit `ed112be`): l'immagine resta a
  **tutta larghezza** (`width:100%`) e viene **tagliata in alto** all'altezza della pagina.
  dompdf non ritaglia con `overflow:hidden`, quindi il taglio è **lato server con GD**
  (`GeneratorePdf::ritagliaInAlto()`): gli screenshot più alti di `larghezza × ratio` sono
  ritagliati in alto prima dell'embedding. Rapporto configurabile `capture.pdf_crop_ratio`
  (env `CAPTURE_PDF_CROP_RATIO`, default **1.1**). Blocco `.uscita` unico (intestazione +
  titolo + screenshot insieme). Verificato: 3 uscite con screenshot alti (fino a 3685px) e
  titoli/URL lunghi → 3 pagine, una ciascuna. `GeneratorePdf.php`, `pdf/rassegna.blade.php`,
  `config/capture.php`. (Approccio intermedio `max-height:650px` del commit `e394c03`
  superato: rimpiccioliva l'intera immagine invece di tagliarla.)
- **UI eliminazione (collaudo):** le Policy consentivano già l'eliminazione (solo
  supervisore, soft delete) ma mancava il bottone nell'interfaccia. Aggiunti "Elimina" sulla
  scheda **cliente** e sulla scheda **rassegna** (visibili solo al supervisore via
  `puoEliminare`), con conferma; azione `elimina()` che ri-autorizza lato server
  (`Gate::authorize('delete')`), fa soft delete, registra l'audit (`elimina_cliente` /
  `elimina_rassegna`) e reindirizza. Test `EliminazioneTest` (6): supervisore elimina,
  operatore riceve 403, bottone assente per l'operatore. Uscite: resta lo "scarto" (§10).
- **Cestino (collaudo):** schermata `Cestino` (rotta `cestino.index`, solo supervisore, voce
  in topbar) che elenca clienti/rassegne/uscite in soft delete e permette il **ripristino**
  (Policy `restore` + audit `ripristina_*`). Test `CestinoTest` (6). **Cancellazione
  definitiva**: l'utente ha scelto di **abilitarla** (deroga a §6/§10). Implementata:
  `forceDelete → supervisore` (Cliente/Rassegna/Uscita), service `EliminazioneDefinitiva`
  (cascata cliente→rassegne→uscite + pulizia file, in transazione), bottone "Elimina
  definitivamente" nel cestino con conferma, audit `elimina_definitiva_*` (il log resta).
  Specifica aggiornata (regole §10, CLAUDE §6) + **TECH-DEBT TD-007** (rischio alto:
  irreversibile, no backup file).
- **Cestino — selezione multipla:** checkbox per riga + "seleziona tutti", azioni in blocco
  (ripristina / elimina definitivamente i selezionati) e **"Svuota cestino"**. La cascata che
  rimuove record già selezionati è gestita (skip dei mancanti). Test `CestinoTest` (13 totali).
- **UX Revisione (collaudo):** risolta la confusione "clicco Revisiona ma è vuoto". Non era
  un bug (verificato: il server mostra le uscite catturate), ma una **pagina stantia** vista
  durante la cattura in coda. La schermata Revisione ora si **auto-aggiorna solo quando è
  vuota** (`wire:poll` nello stato vuoto), così le uscite appena catturate compaiono da sole;
  **nessun poll mentre si revisiona** (i campi non si azzerano). Lo stato vuoto distingue
  "Cattura in corso (N)" da "Revisione completata". Test `RevisioneTest` (5).
- **Scheda: metriche cliccabili + un solo pulsante (collaudo):** su richiesta utente, i 4
  quadrati (Candidati/Da revisionare/Approvate/Scartate) sono ora **link** alle rispettive
  schermate (candidati / revisione / ordine-PDF / gestore uscite); la card "Prossimo passo"
  con i 3 bottoni contestuali è sostituita da **un solo pulsante "Ordina e genera il PDF"**
  (con la nota che spiega l'eventuale blocco). Supera la UI a bottoni contestuali di UX-01
  (la logica dei conteggi e lo stepper UX-04 restano). Test `SchedaProssimoPassoTest`
  aggiornati (metriche-link, pulsante unico, nota, conteggi).
- **Gestore uscite: filtro per stato (collaudo):** menu a tendina + `#[Url] filtroStato`.
- **Stepper a 5 voci come navigazione unica (collaudo):** su richiesta utente lo stepper in
  alto è ora la navigazione principale: **Candidati → Revisione → Approvate → Ordine/PDF →
  Scartate**, ognuna col suo conteggio; Approvate/Scartate portano all'elenco filtrato delle
  uscite (`?stato=approvato|scartato`); lo stepper compare anche nel gestore. **Rimossi** i
  quadrati-metriche e il pulsante "Genera PDF" sulla scheda (tutto gestito dalle voci in alto;
  la nota di blocco resta). Supera la UI metriche+bottoni di UX-01/02. Test
  `SchedaProssimoPassoTest` + `FasiRassegnaTest` aggiornati (voci, conteggi, link filtrati,
  evidenziazione nel gestore).
- **Revisione: sostituzione file in-place (collaudo):** il pulsante "Gestisci file" portava
  al gestore (altra schermata). Ora la Revisione ha l'**upload del file sostitutivo lì
  dentro** (accanto a "Ricattura"): si carica screenshot/ritaglio e si **resta sulla stessa
  uscita** per poi approvare, senza cambiare finestra. Rimosso il link "Gestisci file".
  **Fix anteprima:** la sostituzione manuale ora **azzera lo screenshot automatico** (vecchio/
  rovinato) e diventa IL materiale, così l'anteprima e il PDF usano il nuovo file (prima
  l'anteprima mostrava ancora il vecchio screenshot). Vale anche per la sostituzione dal
  gestore. Test in `RevisioneTest`.
- **Anteprime robuste nel gestore/revisione (collaudo):** la riga del gestore mostrava la
  miniatura solo per `screenshot_path` (non per il file caricato) e non gestiva i file
  mancanti (immagine rotta → "sembra non ci sia niente"). Ora mostra il **materiale corrente**
  (screenshot o file caricato) come miniatura, un **segnaposto** chiaro se il file manca, e una
  nota per i PDF. Stesso controllo nell'anteprima della Revisione. Test in `UsciteGestoreTest`.
- **Eliminazione uscite dal gestore (collaudo):** nel gestore uscite (es. sezione Scartate)
  il supervisore può ora eliminare le uscite **singolarmente** e **in blocco** (selezione
  multipla + "Elimina selezionate"). È un **soft delete**: le uscite vanno nel **cestino**
  (recuperabili; la cancellazione definitiva resta lì). Audit `elimina_uscita`. Solo
  supervisore (UscitaPolicy::delete). Test in `UsciteGestoreTest` (single, blocco, permessi).
- **Gestione utenti (collaudo, richiesta utente "dove gestisco gli utenti?"):** voce
  **Utenti** in topbar (solo supervisore) con elenco, creazione/modifica e attivazione.
  Componenti `Utenti\Elenco` e `Utenti\Modifica`, rotte `/utenti[/nuovo|/{u}/modifica]`,
  gating via **`UserPolicy`** lato server (viewAny/create/update/attivazione = solo
  supervisore; l'operatore prende 403). **Decisione di dominio:** gli utenti **non si
  eliminano**, si **disattivano** (colonna `users.attivo`), così il log di audit resta
  integro (le azioni passate restano attribuite a un utente esistente). Un account
  disattivato **non può più fare login** (controllo in `LoginController`, non solo UI); non
  si può disattivare sé stessi. In modifica la password si cambia solo se compilata. Audit
  `crea_utente`/`modifica_utente`/`attiva_utente`/`disattiva_utente`. `docs/modello-dati.md`
  e CLAUDE §7 aggiornati. Test `UtentiTest` (9). **Debito noto:** nessun reset password
  self-service (lo fa il supervisore) — da tracciare come lavoro futuro. (commit `d4a8e2e`)
- **Deploy in produzione (15 lug 2026):** l'app è online su **https://rs.fulviabenussi.com**
  (VPS Hetzner Debian 13, condivisa con altri progetti — non toccati). Stack server: nginx →
  PHP 8.4-FPM → Laravel → **PostgreSQL** (`rassegna_db`/`rassegna_user`, DB dedicato; deviazione
  dalla spec MySQL → TD-008). Coda su tabella `database` via **Supervisor** (`rassegna-worker`),
  scheduler `rassegne:scansiona` alle 08:00 via cron (`/etc/cron.d/rassegna-stampa`), cache/
  sessioni su file (niente Redis condiviso). Cattura Playwright/Chromium in
  `/var/www/rassegna-stampa/.ms-playwright` (`PLAYWRIGHT_BROWSERS_PATH` nel worker). HTTPS con
  Let's Encrypt (rinnovo automatico). Utente **supervisore** creato (gianmaria.valente@gmail.com).
  Verifiche reali: redirect http→https 301, `/login` 200, asset https (no mixed content), login
  completo → dashboard 200 con nav da supervisore, **cattura di prova OK** (Chromium → screenshot
  17 KB). Codice consegnato via `git clone` del repo pubblico (aggiornabile con `git pull` +
  `composer install`/`npm run build`/`php artisan migrate --force`/`optimize`). Debito aperto:
  **backup produzione** non ancora schedulati (TD-009).
- **Fix post-deploy (500 su "Scansiona ora"):** in produzione la scansione dava errore 500.
  Causa: `uscite.url`/`titolo` erano `varchar(255)`, ma gli URL di redirect Google News superano
  i 255 caratteri; su SQLite (dev/test) la lunghezza non è applicata, su **PostgreSQL** sì
  (SQLSTATE 22001). Migration → `text` (indice unique deduplica invariato), test di schema
  anti-regressione, docs aggiornati. Verificato sul server: scansione reale OK, 72 candidati
  inseriti, URL più lungo 877 caratteri. (commit `2d21187`)
- **Fix paginazione (collaudo):** i pulsanti di navigazione pagine erano **enormi**. Causa:
  l'app usa CSS custom (non Tailwind), ma Livewire rendeva la paginazione con la vista default
  Tailwind, le cui frecce sono SVG con classi `w-5 h-5` inesistenti → SVG senza dimensioni.
  Vista `pagination/rassegna` dedicata (Livewire-native con `wire:click`, testo al posto degli
  SVG) passata esplicitamente nelle 4 viste che paginano (archivio, clienti, rassegne, log) +
  stile `.pagination` in `app.css`. Test di regressione. Deployato e verificato in produzione.
  (commit `c0f72a5`)
- **Candidati: riga interamente cliccabile (collaudo):** la riga del candidato è ora un
  `<label>` che avvolge la checkbox — un clic ovunque sulla riga la seleziona/deseleziona,
  senza dover centrare il quadratino (`.row.pick` + `cursor:pointer` in `app.css`). Il link
  "Apri l'articolo" resta cliccabile (elemento interattivo annidato: non attiva la label).
  Soluzione senza JS, sincronizzata con `wire:model`. Deployato. (commit `c2bcc8d`)
- **Nessun lavoro in sospeso.** Working tree pulito a ogni commit.

## Come usare questo file

A inizio sessione leggilo per sapere a che punto siamo. Durante il lavoro tienilo
aggiornato: sposta le voci da *In corso* a *Completato di recente*, aggiungi i prossimi
passi, annota le decisioni. Non duplicare convenzioni (CLAUDE.md) né debito (TECH-DEBT.md).
A fine sessione assicurati che rifletta lo stato reale. Tienilo corto.

**Ruoli in breve:** Project pianifica · Claude Code scrive e revisiona · Cowork verifica
il processo e tiene aggiornato questo file. (Dettagli in CLAUDE.md.)

## Stato corrente

**M1 (Fondamenta) completata.** Il progetto Laravel 12 esiste, con schema completo,
autenticazione a due ruoli applicati con Policy lato server, e CRUD Clienti/Rassegne su UI
Livewire modellata sui mockup. `docs/` (modello dati, regole di business) restano la fonte
di verità. Prossimo collo di bottiglia: la **cattura** (M2), che introduce Playwright e i
job in coda.

## Completato di recente

- **M1 — Fondamenta.** Scaffold Laravel 12 + Livewire 4 + Pest 4 (repo git inizializzato,
  remote `lovefromgian/rassegna-stampa`). Schema completo (enum di dominio, migration per
  clienti/testate/rassegne/uscite/documenti_generati/log_azioni + `users.ruolo`, soft
  delete, deduplica `unique(rassegna_id,url)`, fulltext condizionale su MySQL). Modelli
  Eloquent con relazioni e cast enum; **LogAzione immutabile**. Autenticazione a sessione
  (niente auto-registrazione) e **Policy lato server** per i due ruoli. CRUD Clienti (solo
  supervisore in scrittura) e Rassegne (entrambi), impostazioni cliente (logo su disco
  Laravel, colore d'accento, destinatari). Precompilazione periodo dal comunicato (+14 gg).
  Service `Audit` per il log. **25 test Pest verdi (59 asserzioni).**
- **Verifica di M1 (punto 6):** l'operatore riceve 403 su `clienti/nuovo` e
  `clienti/{c}/modifica` e non può montare né salvare il componente di modifica cliente —
  vincolo applicato lato server (Policy), non solo in UI. Test:
  `tests/Feature/ClientePolicyTest.php`.
- Intervista di progetto conclusa: definiti scope v1, due ruoli, modello dati completo,
  regole di business, mockup delle schermate principali.

## In corso ora

- Niente in corso: M1–M5 chiuse e pushate. **Scope v1 completo.** Eventuale seguito:
  saldo del debito tecnico (TECH-DEBT.md) e hardening pre-produzione.

## Prossimi passi concreti

### M1 — Fondamenta ✅ (completata)
1. ✅ Setup Laravel 12 + Pest + Livewire, repo git, `.env.example`.
2. ✅ `docs/modello-dati.md` e `docs/regole-business.md` (firmate, fonte di verità).
3. ✅ Migration complete: `clienti`, `rassegne`, `uscite`, `testate`, `documenti_generati`,
   `log_azioni`, `users` (con ruolo). Soft delete dove previsto.
4. ✅ Autenticazione + i due ruoli (Policy lato server, non solo UI).
5. ✅ CRUD Clienti (solo supervisore) e Rassegne. Impostazioni cliente: logo, colore
   d'accento, destinatari.
6. ✅ **Verifica:** cliente/rassegna creabili dall'interfaccia; l'operatore riceve 403 sulle
   rotte di modifica anagrafica (Policy lato server). Coperto dai test.

Note tecniche M1:
- Dev/test su **SQLite** (niente MySQL locale); produzione su MySQL 8 come da spec. Il
  fulltext su `testo_estratto` è creato solo su MySQL (serve alla ricerca d'archivio, M5).
- Auth senza auto-registrazione: gli utenti li crea l'agenzia. Seed demo: `supervisore@` e
  `operatore@example.com` (password `password`).

### M2 — Cattura ✅ (completata)
7. ✅ Integrazione Playwright + job in coda (`CatturaUscita`) dietro interfaccia `PageCapturer`.
8. ✅ Screenshot full-page + PDF pagina + testo + metadati. Cookie banner (euristico, TD-002).
9. ✅ `stato_cattura` esplicito ed errore leggibile; ricattura; sostituzione manuale del file.
10. ✅ Aggiunta manuale di un'uscita (URL online) e caricamento di un ritaglio (media manuali).
11. ✅ **Verifica reale:** cattura de Il Goriziano (screenshot full-page + PDF + testo +
    metadati) e cattura end-to-end via job reale su example.com. (Usata la testata reale,
    non l'URL specifico dell'articolo Grado, non disponibile.)

Note M2:
- Motore dietro interfaccia (`FakeCapturer` nei test → niente rete). `npm run capture:install`
  installa Chromium sul VPS.
- Bugfix layout Livewire full-page (slot vs @yield) → `component_layout` in AppServiceProvider.

### M3 — Revisione e PDF ✅ (completata)
12. ✅ Revisione uscita (`Rassegne\Revisione`): anteprima, metadati, tipo media, rilevanza,
    note, approva/scarta, una alla volta.
13. ✅ Ordine PDF (`Rassegne\OrdinePdf`): proposto (rilevanza poi data) + riordino manuale
    persistito in `posizione_pdf` che prevale. (Frecce su/giù; drag&drop → TD-003.)
14. ✅ Generazione PDF impaginato versionato (dompdf, `GeneratorePdf` + job `GeneraPdf`,
    template `pdf/rassegna`): copertina logo/colore, indice, una pagina per uscita.
15. ✅ Blocchi §7 (`BlocchiGenerazione`) con motivo esplicito. Download (`DocumentoDownloadController`) con audit.
16. ✅ **Verifica di verità:** rassegna "Grado punta sulla cultura" ricostruita da URL reali,
    PDF v1 generato e ispezionato. (Confronto col PDF originale del cliente: non disponibile
    in questo ambiente; impianto grafico modellato sui mockup e su regole §8.)

### M4 — Scoperta automatica ✅ (completata)
17. ✅ Interfaccia `ArticleDiscoverySource` + impl `GoogleNewsRss` (+ `FakeDiscoverySource`).
18. ✅ Parole chiave richieste/escluse; punteggio 0-100; deduplica su URL (`ScansioneRassegna`).
19. ✅ Comando `rassegne:scansiona` schedulato giornaliero + scansione manuale (`scansionaOra`).
20. ✅ Schermata `Candidati`: selezione multipla, conferma/scarto in blocco, debole segnalata.
21. ✅ **Verifica reale:** scansione live Google News/RSS su "Grado" → articoli reali FVG,
    UdineToday "Grado punta sulla cultura" a punteggio 100; falso positivo come debole/medio.

### M5 — Contorno ✅ (completata)
22. ✅ Log di audit consultabile globale e per rassegna (`Audit\Registro`), immutabile.
23. ✅ Archivio con ricerca full-text sul testo estratto (`Archivio`), trasversale a clienti/anni.
24. ✅ Statistiche per cliente e per testata (`Statistiche`).
25. ✅ Chiusura raccolta/rassegna e riapertura (supervisore) con versionamento PDF (§9 verificato).

## Checkpoint concordati

**Nessun checkpoint di approvazione.** L'utente ha scelto autonomia piena:

- Commit e **push automatici** (a fine milestone), senza chiedere conferma.
- Claude Code si ferma **solo davanti a un problema reale**: specifica ambigua, decisione
  bloccante, errore che non sa risolvere.
- Gli hook (test automatici, guard sui comandi distruttivi) restano attivi sempre.

## Decisioni recenti da ricordare

- **Push automatici, zero checkpoint.** Scelta esplicita dell'utente: Claude Code lavora in
  autonomia fino alla fine, si ferma solo sui problemi.
- **Scoperta automatica al quarto posto, non al primo**, benché sia il cuore del prodotto:
  finché cattura e PDF non funzionano, trovare gli articoli non serve a nulla. Ai punti
  M1-M3 l'agenzia ha già uno strumento utile (incolla i link, il sistema fa il resto).
- **Fonte di scoperta Google News/RSS**, dietro interfaccia astratta. Scelta pragmatica: le
  API a pagamento non coprono bene le testate iperlocali FVG, i servizi professionali
  costano troppo per partire. Sostituibile in seguito.
- **Carta stampata fuori dall'automazione.** Google News non la vede: ritagli caricati a
  mano. Vale anche per radio e TV.
- **Rassegna = contenitore vivo con chiusura riapribile.** Le uscite arrivano nei giorni
  successivi al comunicato (nel caso Grado: dal 25 al 27 giugno). PDF generato una volta a
  fine periodo, ma versionabile se arriva un'uscita tardiva.
- **Rassegna anche senza comunicato** (rassegna di periodo). Per questo il periodo di
  monitoraggio è sempre inizio+fine, precompilato dal comunicato quando c'è.
- **Rilevanza assegnata in revisione, non in conferma:** in conferma si decide solo
  dentro/fuori, con selezione multipla.
- **Ordine del PDF manuale** (drag & drop) con proposta automatica: l'ordine è una scelta
  editoriale, non cronologica.
- **Impianto grafico unico del PDF**, personalizzabile solo con logo e colore. Niente
  sistema di template: semplificazione voluta.
- **Storage locale ma astratto** (disco Laravel): oggi cartella del progetto, domani S3
  cambiando configurazione.
- **Invio a mano:** il sistema produce il PDF scaricabile, nessun server di posta.

## Note

- **Vincolo di deploy:** il VPS deve poter eseguire Chromium, un queue worker e lo
  scheduler. Non compatibile con hosting condiviso.
- **Licenze editoriali:** riprodurre pagine di giornale in un documento consegnato a terzi
  tocca il diritto d'autore degli editori. Rischio noto e accettato per l'uso interno.
  Da riaffrontare se il prodotto viene commercializzato ad altre agenzie.
