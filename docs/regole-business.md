# Regole di business — fonte di verità

> **Stato:** firmata (esito dell'intervista di progetto).
> Se il codice diverge da questo documento, **vince questo documento**.
> Queste regole si applicano **lato server** (Service / Policy / Form Request). Un vincolo
> applicato solo nell'interfaccia e aggirabile via API **non è fatto**: si apre una voce in
> TECH-DEBT.md.

---

## 1. Flusso completo

```
[Cliente] ──> [Rassegna] ──> scansione giornaliera automatica
                                      │
                                      ▼
                            candidati proposti
                                      │
                     operatore conferma / scarta (selezione multipla)
                                      │
                                      ▼
                          job di cattura in coda (Playwright)
                                      │
                            screenshot + PDF + testo
                                      │
                     operatore revisiona: rilevanza, ordine, approva
                                      │
                                      ▼
                            generazione PDF impaginato
                                      │
                              download → invio a mano
```

---

## 2. Scoperta automatica

- **Fonte:** Google News / RSS, **dietro un'interfaccia astratta** (es.
  `ArticleDiscoverySource`). Deve essere sostituibile (API a pagamento, servizio
  professionale) senza toccare il resto del codice.
- **Frequenza:** una scansione al giorno per ogni rassegna con periodo di monitoraggio
  attivo (`monitoraggio_inizio` ≤ oggi ≤ `monitoraggio_fine`). Più frequente non serve: le
  testate non pubblicano ogni ora, e si evitano i limiti di Google.
- **Scansione manuale:** sempre disponibile a richiesta, anche fuori periodo.
- **Query:** parole chiave **richieste** + parole chiave **escluse**. Le esclusioni sono il
  meccanismo per tagliare i falsi positivi (un nome ambiguo come "Grado" ne genera molti:
  "gradi di temperatura", "grado di parentela").
- **Punteggio di corrispondenza:** ogni candidato riceve un punteggio 0-100 che guida
  l'ordinamento nella schermata di conferma. È un aiuto, non una decisione: la parola finale
  è dell'operatore.
- **Solo `tipo_media = online` è scopribile automaticamente.** Carta, radio, TV e agenzia
  nascono da inserimento manuale: Google News non li vede.

**Aspettativa realistica, da non nascondere all'utente:** qualunque fonte produce falsi
positivi e buchi. L'aggiunta manuale di un'uscita sfuggita è parte del flusso normale, non
un fallimento del sistema.

---

## 3. Deduplica

- Una `url` già presente **nella stessa rassegna** non viene riproposta come candidato
  (vincolo unique su `rassegna_id` + `url`).
- Lo stesso articolo può però arrivare con **URL diverse** (versione AMP, redirect, mirror).
  Il sistema segnala il sospetto duplicato (titolo molto simile, stessa testata, stessa
  data) ma **non decide da solo**: l'operatore unisce o scarta in revisione.
- Un'uscita scartata **non viene riproposta** nelle scansioni successive.

---

## 4. Cattura

- **Sempre un job in coda**, mai sincrona nella richiesta HTTP.
- Produce: screenshot full-page (l'immagine che finisce nel PDF), PDF multipagina della
  pagina (versione leggibile), testo estratto (indicizzato per la ricerca), metadati.
- **Cookie banner:** vanno chiusi prima della cattura (consent manager diffusi in Italia:
  Iubenda, OneTrust). Uno screenshot con mezzo articolo coperto dal banner **non è valido**.
- **Contenuti lazy:** scroll fino in fondo prima dello scatto, attesa `networkidle`, blocco
  dei domini pubblicitari (cattura più pulita e più veloce).
- **Paywall:** la cattura riflette ciò che vede un visitatore anonimo. Se l'articolo è
  troncato, l'operatore lo vede in revisione e decide (sostituzione manuale del file, o
  scarto).
- **Errori:** ogni fallimento salva un `errore_cattura` leggibile. Nessuna cattura fallita
  può essere ignorata silenziosamente: o si risolve (ricattura / caricamento manuale) o si
  scarta.

---

## 5. Revisione

- **In conferma si decide solo dentro/fuori.** La rilevanza NON si assegna lì: l'operatore
  non ha ancora letto l'articolo.
- **In revisione** l'operatore ha davanti la cattura e assegna:
  - `tipo_media` (correggibile rispetto a quanto dedotto);
  - `rilevanza`: `principale` (articolo dedicato) · `secondaria` (tratta il tema tra altri) ·
    `citazione` (menzione di passaggio);
  - note interne.
- **Approvazione** = la cattura è valida e i metadati sono corretti.

---

## 6. Ordine nel PDF

- **Ordinamento proposto dal sistema:** rilevanza (principale → secondaria → citazione),
  poi data.
- **L'operatore può riordinare a mano** (drag & drop). L'ordine finale è una scelta
  editoriale, non cronologica: nel PDF di riferimento del cliente Grado, l'uscita cartacea
  de Il Piccolo apre la rassegna pur non essendo la più recente.
- L'ordine manuale si salva in `posizione_pdf` e **prevale** sulla proposta.

---

## 7. Blocchi alla generazione del PDF

Il PDF si genera **solo se** entrambe le condizioni sono vere:

1. **Nessuna uscita in stato `candidato`** — cioè l'operatore ha deciso su tutte.
2. **Ogni uscita `approvato` ha un materiale valido**: `screenshot_path` (online) oppure
   `file_caricato_path` (carta, radio, TV, agenzia).

Le catture in errore vanno **risolte o scartate**, non ignorate. Il pulsante di generazione
resta bloccato e l'interfaccia dice **perché**, non si limita a essere inattivo.

---

## 8. Struttura del PDF generato

Impianto grafico **unico**, modellato sul PDF di riferimento del cliente. Personalizzabile
**solo** con logo e colore d'accento del cliente. **Nessun sistema di template**:
semplificazione voluta in fase di progettazione.

1. **Copertina** — logo del cliente, dicitura "Rassegna stampa", titolo e sottotitolo del
   comunicato, luogo e data. Bordo nel colore d'accento del cliente.
2. **Indice delle uscite** — elenco: testata, data, tipo (online / carta). Dà al cliente il
   colpo d'occhio sulla copertura ottenuta.
3. **Una pagina per uscita** — intestazione con **testata**, **data** e **link** (nel colore
   d'accento), poi lo screenshot / il ritaglio dell'articolo.

**Nota tecnica sullo screenshot full-page:** un articolo lungo produce un'immagine molto
alta e stretta; schiacciata in A4 diventa poco leggibile. È una scelta accettata (conta la
testimonianza dell'uscita), ma sono salvati **entrambi** i formati (immagine + PDF
multipagina): se in futuro si vuole privilegiare la leggibilità, il materiale c'è già.

---

## 9. Chiusura e riapertura

- La rassegna si chiude quando il periodo di monitoraggio scade (o l'operatore la chiude a
  mano) e il PDF viene generato. **Il PDF si genera una volta, a fine periodo.**
- **Le uscite tardive esistono** e vanno gestite: nel caso Grado, il comunicato è del 25
  giugno ma due testate hanno pubblicato il 27.
- **Riapertura: solo il supervisore.** Non cancella il PDF già generato: si aggiungono le
  uscite e si genera una **nuova versione** (v2, v3…). Ogni versione conserva lo snapshot
  delle uscite incluse, così si sa sempre **cosa è stato effettivamente consegnato e
  quando**.

---

## 10. Cancellazioni

- **Nulla si cancella fisicamente.** Soft delete su Cliente, Rassegna, Uscita.
- L'operatore può **scartare** un'uscita (resta archiviata, recuperabile).
- **Eliminare** un cliente, una rassegna, o una rassegna già inviata: **solo supervisore**.
- Il **log di audit non si tocca**: né modifica né cancellazione, da nessun ruolo.

---

## 11. Audit

Registrano chi e quando, almeno: conferma candidato, scarto uscita, approvazione uscita,
generazione PDF, download PDF, riapertura rassegna, modifica anagrafica/impostazioni
cliente, eliminazioni.

Consultabile **per rassegna** ("chi ha approvato queste uscite?") e in generale.

---

## 12. Storage

- I file (screenshot, PDF pagina, ritagli, rassegne generate) si scrivono **sempre tramite
  il filesystem di Laravel** (`Storage::disk(...)`), **mai** con percorsi assoluti nel
  codice.
- Oggi: disco locale, cartella dedicata del progetto.
- Domani: deve poter diventare S3 **cambiando solo la configurazione**, senza toccare il
  codice.

---

## 13. Vincoli noti (non sono lavoro da fare, sono cose da sapere)

- **Licenze editoriali.** Riprodurre integralmente pagine di giornale in un documento
  consegnato a terzi tocca il diritto d'autore degli editori. Rischio noto e **accettato**
  per l'uso interno dell'agenzia. Da riaffrontare se il gestionale viene commercializzato ad
  altre agenzie.
- **Carta stampata.** Non è raggiungibile via web: i PDF delle pagine di giornale arrivano
  da servizi professionali (Eco della Stampa, Data Stampa, Telpress) o da inserimento
  manuale. In questa versione: **manuale**.
- **Backup dei file.** I file nella cartella del progetto sul VPS **non sono coperti da
  backup** se non lo si prevede. L'archivio storico delle rassegne è il valore accumulato
  del sistema: da sistemare prima della produzione seria (tracciato in TECH-DEBT.md).
