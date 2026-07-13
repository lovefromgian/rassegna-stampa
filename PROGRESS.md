# PROGRESS — Stato vivo del lavoro

> Dove siamo: cosa è fatto, in corso, prossimi passi, decisioni da ricordare.
> Convenzioni e setup → CLAUDE.md. Debito tecnico → TECH-DEBT.md. Qui solo lo **stato**.

**Ultimo aggiornamento:** scope v1 + revisione UX (UX-01/02) · 13 lug 2026 · 90 test verdi (229 asserzioni)

## ▶ RIPRENDI DA QUI

> Blocco di ripresa rapida: aggiornato dopo **ogni sotto-passo** (non solo a fine
> milestone), per riprendere senza perdite se una sessione si interrompe (es. soglia
> token). Se leggi questo all'avvio: fai `git log --oneline -5`, poi continua da qui.

- **Stato:** **SCOPE v1 COMPLETO (M1–M5).** Il gestionale gira end-to-end: clienti/rassegne
  → scoperta automatica → cattura → revisione → PDF impaginato versionato → contorno (log,
  archivio, statistiche, chiusura/riapertura). **85 test verdi (221 asserzioni).**
- **M5 (Contorno) completata:** log di audit consultabile globale e per rassegna
  (`Audit\Registro`, immutabile); archivio con ricerca full-text sul testo estratto
  (`Archivio`, MySQL fulltext / LIKE su SQLite); statistiche per cliente e testata
  (`Statistiche`); chiusura raccolta/rassegna e **riapertura solo supervisore** con
  versionamento PDF.
- **Verifica reale M5:** ciclo di vita completo — v1 generata → chiusa → riaperta → uscita
  tardiva aggiunta → v2 generata, **entrambe le versioni conservate** (§9).
- **Prossimo passo concreto:** nessuna milestone residua nello scope v1. Eventuale lavoro
  successivo: saldare il debito tecnico (TD-001 backup, TD-002 cookie banner, TD-003
  drag&drop, TD-004 URL Google News, TD-005 snippet) e l'hardening pre-produzione.
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
  contestuale con conteggi: primario = Conferma N / Revisiona N / Genera PDF secondo lo stato;
  nota col motivo reale via `BlocchiGenerazione`) e **UX-02** (metriche a colpo d'occhio sulla
  scheda — Candidati/Da revisionare/Approvate/Scartate, un solo `groupBy`), riallineati al
  mockup 05. Test `SchedaProssimoPassoTest` (5). **Restano UX-03/04/05/06** per una sessione
  successiva (tracciati in REVISIONE-UX.md e TECH-DEBT TD-006).
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
