# CLAUDE.md — Contesto progetto

> Leggere **prima** di qualsiasi modifica. Aggiornare nello stesso commit in cui cambia
> architettura o stato dei moduli. A inizio sessione leggere anche PROGRESS.md.

## 1. Cos'è questo progetto

Gestionale in **Laravel** per la produzione di **rassegne stampa**. Uso interno di
un'agenzia di comunicazione.

Il sistema, data una campagna di monitoraggio legata a un comunicato (o a un periodo),
**cerca automaticamente sul web gli articoli** che ne parlano, li **propone** all'operatore
che li conferma o li scarta, ne **cattura** screenshot / PDF / testo, e infine **genera un
PDF impaginato** della rassegna che l'agenzia scarica e consegna al cliente.

I clienti finali **non accedono** al sistema: ricevono solo il PDF.

**Stato attuale**: **scope v1 completo (M1–M5)**. Gestionale funzionante end-to-end:
clienti/rassegne, scoperta automatica (Google News/RSS), cattura (Playwright), revisione,
PDF impaginato versionato (dompdf), log/archivio/statistiche, chiusura e riapertura.
Debiti tecnici noti in TECH-DEBT.md. Stato vivo del lavoro in PROGRESS.md.

Se esiste una cartella `docs/` con le specifiche, **quella è la fonte di verità**: il
codice si conforma ai documenti, non viceversa. Se codice e `docs/` divergono, vince
`docs/`. Se la specifica è ambigua, **fermarsi e chiedere**, non indovinare.

## 2. Stack e comandi

- **Framework:** Laravel 12 · **PHP** 8.3+
- **Database:** MySQL 8 (o MariaDB)
- **Frontend:** Blade + Livewire
- **Test:** Pest
- **Cattura pagine:** Playwright (Chromium headless), invocato da un job in coda
- **Generazione PDF:** dompdf (`barryvdh/laravel-dompdf`), da template Blade, in coda
- **Coda:** queue worker Laravel (database o Redis)
- **Scheduler:** `schedule:run` per le scansioni giornaliere
- **Deploy:** VPS dedicato Hetzner (deve poter installare Chromium ed eseguire processi
  in background: no hosting condiviso)

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate            # applica le migration
php artisan test               # SEMPRE prima di considerare un task concluso
php artisan serve & npm run dev
php artisan queue:work         # worker: catture e generazione PDF
php artisan schedule:work      # scansioni giornaliere delle rassegne attive
```

## 3. Documentazione (fonte di verità)

| File | Cosa contiene | Stato |
|------|---------------|-------|
| `docs/modello-dati.md` | entità, campi, relazioni, stati | firmata |
| `docs/regole-business.md` | deduplica, blocchi alla generazione, riapertura, scansioni | firmata |
| `mockups/*.html` | mockup UI concordati (clienti, scheda cliente, impostazioni, rassegna, candidati, revisione, ordine PDF, generazione) | concordati in fase di progettazione |
| `TECH-DEBT.md` | registro autoritativo dei debiti tecnici | Vivo |
| `PROGRESS.md` | stato vivo del lavoro | Vivo |

Mantieni questa tabella con lo **stato** di ogni documento (bozza/firmata/stabile).

## 4. Convenzioni di codice

- Convenzioni Laravel: controller magri, logica in Service/Action, validazione in Form
  Request, relazioni Eloquent invece di query manuali.
- Ogni modifica allo schema passa da una **migration** + aggiornamento dei `docs/` del
  modello dati nello stesso commit. Mai modifiche manuali al DB.
- **Soft delete** su Cliente, Rassegna, Uscita: nulla si cancella fisicamente. Un'uscita
  scartata resta archiviata e recuperabile.
- **Storage astratto**: i file (screenshot, PDF pagina, ritagli caricati, rassegne
  generate) si scrivono SEMPRE tramite il filesystem di Laravel (`Storage::disk(...)`),
  mai con percorsi assoluti nel codice. Oggi il disco è locale (cartella dedicata del
  progetto); domani deve poter diventare S3 cambiando solo la configurazione.
- **La fonte di scoperta è dietro un'interfaccia** (es. `ArticleDiscoverySource`):
  l'implementazione attuale è Google News/RSS, ma deve essere sostituibile con un'API a
  pagamento o un servizio professionale senza toccare il resto del codice.
- **La cattura è sempre un job in coda**, mai sincrona nella richiesta HTTP. Ogni cattura
  ha uno stato esplicito e un errore leggibile in caso di fallimento.
- Stringhe utente dal sistema di localizzazione (interfaccia in italiano). Niente segreti
  nel codice (`.env`).
- Le viste Blade/Livewire si costruiscono guardando i **mockup concordati**, non a
  intuito.

## 5. Definizione di "fatto"

Un task è concluso solo quando:
1. Il codice funziona e i test passano (`php artisan test`), **esito mostrato, non riassunto**.
2. Eventuali nuove migration sono incluse e funzionanti.
3. PROGRESS.md è aggiornato.
4. Gli scostamenti noti non risolti sono tracciati in TECH-DEBT.md.

**"Funziona" ≠ "pronto per produzione".** Un modulo può passare i test ed essere
strutturalmente fragile (SQL injection, auth mancante, eventi senza consumer). La
distinzione va nominata apertamente nei verdetti, non ammorbidita.

## 6. Cosa NON fare

- **Non cancellare fisicamente** clienti, rassegne o uscite: si usa il soft delete.
- **Non modificare né cancellare il log di audit**, nemmeno da supervisore. È immutabile.
- **Non generare il PDF** se restano uscite in stato `candidato` o se un'uscita approvata
  non ha uno screenshot valido (vedi §8, regole di business).
- **Non sovrascrivere un PDF già generato**: si crea una nuova versione.
- Non scrivere percorsi di file assoluti: passare sempre dal disco Laravel.
- Non indovinare se la specifica è ambigua: chiedere.
- Non dare per "fatto" un vincolo applicato solo in UI (vedi §10). I permessi dei ruoli
  vanno applicati lato server (Policy), non nascondendo un bottone.

## 7. Dominio in breve

**Gerarchia:** Cliente → Rassegna → Uscite. Il PDF generato appartiene alla Rassegna ed è
versionato.

**Ruoli (due):**
- **Supervisore** — tutto: anagrafica clienti e impostazioni, eliminazioni, riapertura di
  una rassegna chiusa.
- **Operatore** — crea rassegne, conferma/scarta candidati, revisiona catture, genera il
  PDF. Non tocca l'anagrafica clienti, non elimina.

Nessuna segmentazione per cliente: ogni utente vede tutti i clienti dell'agenzia.

**Stati della Rassegna:** `in raccolta` → `in revisione` → `chiusa/inviata` → `riaperta`
(solo supervisore).

**Stati dell'Uscita:** `candidato` → `confermato` → `catturato` → `approvato` / `scartato`.

**Tipo di media:** online · carta stampata · radio · TV · agenzia di stampa · social/blog.
Solo `online` è cercabile automaticamente: gli altri richiedono inserimento manuale.

**Rilevanza:** principale · secondaria · citazione. La assegna l'operatore in revisione
(non in fase di conferma), e determina l'ordinamento proposto nel PDF.

**Audit:** ogni azione rilevante (conferma, scarto, approvazione, generazione PDF,
download, modifica anagrafica) registra chi e quando.

## 8. Regole di business (non negoziabili)

- **Deduplica:** una URL già presente nella stessa rassegna non viene riproposta. Duplicati
  con URL diverse (es. versione AMP) li unisce l'operatore in revisione.
- **Blocco alla generazione del PDF:** si genera solo se non restano uscite in stato
  `candidato` e se ogni uscita approvata ha uno screenshot valido. Le catture in errore si
  risolvono (ricattura o caricamento manuale) o si scartano: non si ignorano.
- **Riapertura:** solo il supervisore. Non cancella il PDF già generato: si aggiungono le
  uscite e si genera una **nuova versione**. Lo storico degli invii resta intero.
- **Scansione automatica:** una volta al giorno per ogni rassegna con periodo di
  monitoraggio attivo, più scansione manuale a richiesta.
- **Periodo di monitoraggio:** ogni rassegna ha inizio e fine. Se c'è un comunicato, il
  sistema precompila (inizio = data comunicato, fine = +14 giorni). Se non c'è (rassegna
  di periodo), lo imposta l'utente. Nessun caso speciale nel codice.
- **Parole chiave:** lista di termini richiesti + lista di termini da **escludere** (serve
  a tagliare i falsi positivi: "Grado" ne genera molti).
- **PDF:** impianto grafico unico (modellato sul PDF di riferimento del cliente Grado),
  personalizzabile solo con **logo** e **colore d'accento** del cliente. Nessun sistema di
  template.

## 9. Ritmo di lavoro e checkpoint

**Autonomia piena.** L'utente ha scelto di NON avere checkpoint di approvazione.

- Claude Code procede da solo: codice, file, commit, test, **push** (automatici a fine
  milestone, senza chiedere conferma).
- **Si ferma SOLO se c'è un problema reale**: specifica ambigua, decisione bloccante,
  errore che non sa risolvere. Non si ferma per far approvare avanzamenti normali.
- Gli hook (test automatici, guard sui comandi distruttivi) restano attivi sempre.
- **Git all'avvio**: a inizio sessione `git log --oneline -10` per allinearsi allo stato
  reale del repo, non al ricordo.

## 10. Disciplina di verifica e debito tecnico

- **"Mostra, non descrivere"**: l'output a video (un grep, il contenuto di un file, l'esito
  di un test) è il prodotto della verifica. Le affermazioni sul codice si ancorano a output
  letterali, non alla memoria.
- **Esito esplicito e letterale**: mai "fatto". Si dice cosa è stato verificato e con quale
  prova.
- **Vincolo UI-only → debito**: se un permesso o un blocco previsto dalla specifica è
  applicato solo nell'interfaccia e aggirabile via API, si apre una voce in TECH-DEBT.md.
  Vale in particolare per i permessi dei due ruoli e per i blocchi alla generazione del PDF.
- **TECH-DEBT.md è il registro autoritativo** degli scostamenti noti.

## 11. Ruoli degli strumenti

- **Chat / Project** — pianificazione: architettura, scope, specifiche, mockup.
- **Claude Code** — esecuzione + revisione tecnica nel contesto reale del repo.
- **Cowork** — verifica di **processo**: allineamento specifica↔codice, aggiornamento
  stato, prossimi passi. NON scrive codice; il suo prodotto è diagnosi + indicazioni per
  Claude Code, mai il fix.

## 12. File del progetto

- **CLAUDE.md** (questo) — convenzioni e setup. Stabile.
- **PROGRESS.md** — stato vivo. Si aggiorna a ogni sessione.
- **TECH-DEBT.md** — registro del debito tecnico.
- **docs/** — specifiche di dominio, fonte di verità.
- **mockups/** — mockup HTML concordati delle schermate.
