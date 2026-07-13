# REVISIONE-UX — Handoff per Claude Code

> Prodotto da Cowork (verifica di processo, CLAUDE.md §11): **diagnosi + indicazioni**, non il fix.
> Metodo: reso i 10 mockup in immagini e confrontati con CSS e viste Livewire reali.
> **Vincoli trasversali validi per tutti i ticket:**
> - I mockup in `mockups/` sono la fonte di verità della UI (CLAUDE.md §4). Riallineare a quelli, non reinventare.
> - Le regole di business e i permessi lato server **non si toccano**: questi sono interventi di sola UI/UX.
> - `php artisan test` deve restare verde (85 test). Aggiungere test dove indicato.
> - Niente stili assoluti/nuove dipendenze non necessarie: riusare le classi già in `resources/css/app.css`.

Diagnosi di fondo: **l'estetica non è il problema** — la tavolozza è coerente e il CSS applicativo è completo.
La confusione nasce dal **divario tra i mockup (ottimi) e l'implementazione reale**, più dall'assenza di
una "mappa" del flusso a fasi. Sotto, in ordine di impatto.

---

## UX-01 — [ALTA] "Prossimo passo" contestuale con conteggi
**Il difetto più grave.** Oggi la card indica sempre lo stesso passo, anche quando è bloccato.

- **File:** `resources/views/livewire/rassegne/scheda.blade.php` (card "Prossimo passo", righe ~58–66);
  logica in `app/Livewire/Rassegne/Scheda.php` (`render()`, righe ~78–87).
- **Ora:** tre bottoni statici senza numeri; il bottone primario nero è **sempre** "Ordina e genera il PDF"
  (riga 63), anche con candidati pendenti e PDF bloccato. L'affordance centrale indica l'ultimo passo quando
  non è eseguibile.
- **Atteso (mockup 05):** un solo passo *consigliato* evidenziato in primario, con il conteggio; gli altri
  restano secondari. Logica suggerita:
  - `candidati (stato = Candidato) > 0` → primario **"Conferma i N candidati proposti"** → `rassegne.candidati`
  - altrimenti `da revisionare (stato = Catturato, + Confermato in cattura) > 0` → primario
    **"Revisiona le N uscite in attesa"** → `rassegne.revisione`
  - altrimenti → primario **"Ordina e genera il PDF"** → `rassegne.pdf`
  - Se la rassegna è `Chiusa`/`Riaperta`, adattare il messaggio (es. "Riapri per aggiungere uscite tardive").
- **Riusa:** il service `App\Services\BlocchiGenerazione` calcola già i motivi di blocco del PDF: usarlo per
  popolare la `.note` sotto i bottoni con il **motivo reale** (es. "Restano 6 candidati da decidere"), non il
  testo fisso attuale (riga 65).
- **Accettazione:** con candidati pendenti il primario è "Conferma…"; a candidati=0 e catture da revisionare
  il primario è "Revisiona…"; solo a tutto pronto il primario è "Genera PDF". Coprire con un test Livewire su
  `Scheda` che asserisce l'azione primaria per i tre stati.

## UX-02 — [ALTA] Rimettere le metriche a colpo d'occhio nella scheda
- **File:** `resources/views/livewire/rassegne/scheda.blade.php` (in testa, prima di "Prossimo passo");
  conteggi in `app/Livewire/Rassegne/Scheda.php::render()`.
- **Ora:** la scheda reale **non ha** la riga di metriche; per capire lo stato bisogna scorrere e contare a mano.
- **Atteso (mockup 05):** quattro tessere — **Candidati da decidere / Da revisionare / Approvate / Scartate** —
  calcolate da `stato` dell'uscita (`App\Enums\StatoUscita`: Candidato / Catturato / Approvato / Scartato).
- **Riusa:** classi `.metrics` e `.metric` già presenti in `resources/css/app.css` (righe ~64–68). Nessun CSS nuovo.
- **Nota performance:** calcolare i conteggi con un solo `selectRaw`/`groupBy` su `stato`, non 4 query separate.
- **Accettazione:** i quattro numeri compaiono e coincidono con i dati; sono gli stessi che alimentano UX-01.

## UX-03 — [MEDIA] La scheda fa due lavori: cruscotto + banco di lavoro
- **File:** `resources/views/livewire/rassegne/scheda.blade.php` (riga ~69 incorpora
  `<livewire:uscite.gestore>`, cioè elenco + form + cattura + screenshot + sostituzione file).
- **Ora:** le stesse uscite si gestiscono in 4 punti (scheda, Candidati, Revisione, Ordine/PDF): la scheda
  diventa lunghissima e sfuma il "dove faccio cosa".
- **Atteso (mockup 05):** la scheda è un **riepilogo** (stato + metriche + prossimo passo + elenco **compatto in
  sola lettura** delle uscite). La gestione pesante (aggiunta, cattura, ricattura, sostituzione file) resta nelle
  schermate di fase (Candidati/Revisione).
- **Indicazione:** sostituire l'embed del gestore completo con una lista compatta read-only; se serve un accesso
  rapido "aggiungi a mano", tenere solo quel bottone che porta alla schermata dedicata.
- **Accettazione:** la scheda entra "sopra la piega" su una rassegna tipica; nessuna azione distruttiva vive più
  sulla scheda.

## UX-04 — [MEDIA] Manca la "mappa" del flusso dentro la rassegna
- **File:** nuovo partial (es. `resources/views/partials/fasi-rassegna.blade.php`) incluso in
  `scheda/candidati/revisione/ordine-pdf`; nav globale già ok in `resources/views/partials/topbar.blade.php`.
- **Ora:** le fasi Candidati → Revisione → Ordine/PDF si raggiungono solo dai tre bottoni e dalle briciole:
  nessun indicatore persistente di "sei qui / fasi / quali sono fatte".
- **Atteso:** uno **stepper/tab** a tre passi, con la fase corrente evidenziata e le fasi completate marcate,
  presente in cima a tutte le schermate di fase. Coerente con la logica di stato di UX-01.
- **Accettazione:** da qualunque schermata di fase si vede a colpo d'occhio a che punto è il lavoro e si salta
  a un'altra fase senza passare dalle briciole.

## UX-05 — [MEDIA] Consolidare gli stili inline in utility
- **File:** diffuso — es. `scheda.blade.php` (riga ~20), `candidati.blade.php` (righe ~22–60),
  tutto `uscite/gestore.blade.php`.
- **Ora:** molti `style="..."` (flex, gap, margini, allineamenti) decisi caso per caso: probabilmente **il**
  motivo per cui il prodotto reale "sembra" meno ordinato dei mockup pur avendo identica tavolozza.
- **Atteso:** estrarre i pattern ricorrenti in classi utility (già esistono `.spread`, `.actions`, `.muted`,
  `.right`, `.mt-0`; aggiungerne poche tipo `.stack`, `.gap-sm`, `.wrap`). Le decisioni di spaziatura vengono
  dal sistema di design, non dal singolo file.
- **Accettazione:** riduzione netta degli `style=` inline nelle viste toccate; nessuna regressione visiva.

## UX-06 — [BASSA] Riga uscita del gestore troppo densa
- **File:** `resources/views/livewire/uscite/gestore.blade.php` (blocco `.row`, righe ~72–125).
- **Ora:** ogni riga impila testata, titolo, sottotitolo, nota cattura, errore, screenshot, sostituzione file e
  una colonna di 3–4 bottoni piccoli: con molte uscite è un muro.
- **Atteso:** riga essenziale (testata · titolo · data · stato + miniatura), azioni secondarie (Sostituisci file,
  Scarta) dietro un menu "⋯"; azione primaria di contesto (Cattura/Ricattura) visibile. Ispirarsi alla calma
  dell'elenco del mockup 05.
- **Accettazione:** la riga sta su ≤2 righe visive nel caso tipico; le azioni restano tutte raggiungibili.

---

## Cosa NON toccare (funziona ed è fedele ai mockup)
- **Candidati** (`candidati.blade.php`): selezione multipla, pill di corrispondenza, segnalazione duplicati,
  aggiunta manuale — quasi identica al mockup 06. OK.
- **Nuova rassegna** (mockup 04) e **Revisione** a due colonne con "Approva e vai avanti" (mockup 07). OK.
- **Tavolozza, CSS e nav globale**: completi e coerenti. Non è un lavoro di restyling.

## Ordine consigliato
UX-01 + UX-02 insieme (restituiscono alla scheda il senso di guida: è il 70% del problema percepito) →
UX-04 (mappa del flusso) → UX-03 (alleggerire la scheda) → UX-05 → UX-06.

## Definizione di "fatto" (CLAUDE.md §5)
Codice + test verdi (esito mostrato), viste riallineate ai mockup, `PROGRESS.md` aggiornato, eventuali
scostamenti residui in `TECH-DEBT.md`.
