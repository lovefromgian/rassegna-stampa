# PROGRESS — Stato vivo del lavoro

> Dove siamo: cosa è fatto, in corso, prossimi passi, decisioni da ricordare.
> Convenzioni e setup → CLAUDE.md. Debito tecnico → TECH-DEBT.md. Qui solo lo **stato**.

**Ultimo aggiornamento:** M1 completata + push · 13 lug 2026 · 25 test verdi

## ▶ RIPRENDI DA QUI

> Blocco di ripresa rapida: aggiornato dopo **ogni sotto-passo** (non solo a fine
> milestone), per riprendere senza perdite se una sessione si interrompe (es. soglia
> token). Se leggi questo all'avvio: fai `git log --oneline -5`, poi continua da qui.

- **Stato:** M2 quasi completa. Fatti: M2-A (stato_cattura), M2-B (motore dietro
  interfaccia `PageCapturer` + `capture.cjs` + `FakeCapturer`), M2-C (job `CatturaUscita` +
  `GestioneCattura`), M2-D (UI `Uscite\Gestore` annidata nella scheda rassegna: aggiunta
  manuale online/media, elenco con pill stato, cattura/ricattura, sostituzione file, scarto).
  **41 test verdi.**
- **Bugfix importante (M2-D):** le pagine Livewire full-page non renderizzavano il corpo —
  Livewire 4 usava `layouts::app` (il layout @yield del controller) mentre inietta via slot.
  Fix: `config(['livewire.component_layout' => 'components.layouts.app'])` in
  AppServiceProvider. Aggiunti smoke test di rendering (RenderPagineTest) per bloccarlo.
- **Prossimo passo concreto:** **M2-E** — verifica reale: `npm i` (playwright) +
  `npm run capture:install` (chromium) e cattura di un URL reale via il PlaywrightCapturer,
  best-effort documentato (l'ambiente potrebbe non avere rete/chromium). Poi chiusura M2:
  aggiornare TECH-DEBT se serve, commit+push finale.
- **Decisione:** la cattura è automatica → NON scrive nel log di audit (§11 = azioni utente).
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

- Niente in corso: M1 chiusa. Prossimo avvio → M2 (cattura).

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

### M2 — Cattura
7. Integrazione Playwright + job in coda per la cattura di una URL.
8. Screenshot full-page + PDF pagina + estrazione testo + metadati. Gestione cookie banner.
9. Stati di cattura ed errori leggibili; ricattura; sostituzione manuale del file.
10. Aggiunta manuale di un'uscita (URL) e caricamento di un ritaglio cartaceo.
11. **Verifica:** incollo l'URL de Il Goriziano sull'articolo di Grado e ottengo screenshot
    pulito, testo estratto e metadati corretti.

### M3 — Revisione e PDF *(← prima milestone concreta)*
12. Schermata di revisione uscita: anteprima cattura, metadati, tipo di media, rilevanza,
    note, approva/scarta.
13. Vista ordine nel PDF: ordinamento proposto (rilevanza, poi data) + riordino manuale.
14. Generazione del PDF impaginato: copertina con logo e colore del cliente, indice delle
    uscite, una pagina per uscita con testata/data/link. Versionato.
15. Blocchi alla generazione (nessun candidato pendente, screenshot valido su ogni
    approvata). Download del PDF.
16. **Verifica di verità:** rigenero la rassegna "Grado punta sulla cultura" partendo dagli
    URL e confronto il risultato col PDF originale fornito dal cliente.

### M4 — Scoperta automatica
17. Interfaccia `ArticleDiscoverySource` + implementazione Google News/RSS.
18. Parole chiave richieste ed escluse; punteggio di corrispondenza; deduplica su URL.
19. Scansione giornaliera schedulata sulle rassegne con periodo attivo + scansione manuale.
20. Schermata candidati con selezione multipla, conferma/scarto in blocco.
21. **Verifica:** su un comunicato reale il sistema propone da solo le uscite, con falsi
    positivi segnalati come corrispondenza debole.

### M5 — Contorno
22. Log di audit consultabile (per rassegna e globale), immutabile.
23. Archivio con ricerca full-text sul testo estratto, trasversale a clienti e anni.
24. Statistiche per cliente e per testata.
25. Chiusura e riapertura rassegna (supervisore), versionamento PDF.

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
