# Modello dati — fonte di verità

> **Stato:** firmata (esito dell'intervista di progetto).
> Se il codice diverge da questo documento, **vince questo documento**.
> Ogni modifica allo schema passa da una migration + aggiornamento di questo file nello
> stesso commit.

## Gerarchia

```
Cliente
  └── Rassegna (una per comunicato, oppure di periodo)
        ├── Uscita (l'articolo raccolto)
        │     └── Testata (relazione)
        └── DocumentoGenerato (il PDF, versionato)

LogAzione — trasversale, immutabile
User — supervisore | operatore
```

---

## User

| Campo | Tipo | Note |
|---|---|---|
| `id` | id | |
| `name` | string | |
| `email` | string, unique | |
| `password` | string | hash |
| `ruolo` | enum | `supervisore` \| `operatore` |
| timestamps | | |

Nessuna segmentazione per cliente: ogni utente vede tutti i clienti dell'agenzia.

**Permessi (da applicare lato server con Policy, non solo in UI):**

| Azione | Supervisore | Operatore |
|---|---|---|
| Anagrafica e impostazioni cliente | ✅ crea, modifica, elimina | 👁 sola lettura |
| Rassegna | ✅ tutto | ✅ crea, modifica (no elimina) |
| Conferma / scarto candidati | ✅ | ✅ |
| Revisione e approvazione uscite | ✅ | ✅ |
| Generazione PDF e download | ✅ | ✅ |
| Eliminazione (cliente, rassegna, rassegna inviata) | ✅ | ❌ |
| Riapertura rassegna chiusa | ✅ | ❌ |
| Modifica del log di audit | ❌ (nessuno) | ❌ |

---

## Cliente

Soft delete.

| Campo | Tipo | Obbl. | Note |
|---|---|---|---|
| `id` | id | | |
| `nome` | string | ✅ | |
| `referente` | string | | nome persona di riferimento |
| `email_referente` | string | | |
| `telefono` | string | | |
| `destinatari_invio` | json | | lista di email a cui va consegnata la rassegna |
| `logo_path` | string | | file su disco Laravel; usato in copertina PDF |
| `colore_accento` | string | | hex; usato per bordi e intestazioni del PDF |
| `note` | text | | interne |
| `stato` | enum | ✅ | `attivo` \| `archiviato` (default `attivo`) |
| timestamps, softDeletes | | | |

Le impostazioni grafiche (logo, colore) sono **ereditate da ogni rassegna del cliente**:
non si riconfigurano ogni volta.

---

## Rassegna

Soft delete. È il contenitore vivo della campagna di monitoraggio.

| Campo | Tipo | Obbl. | Note |
|---|---|---|---|
| `id` | id | | |
| `cliente_id` | fk → clienti | ✅ | |
| `titolo` | string | ✅ | |
| `comunicato_titolo` | string | | opzionale: rassegna di periodo senza comunicato |
| `comunicato_sottotitolo` | string | | compare in copertina PDF |
| `comunicato_data` | date | | |
| `comunicato_testo` | text | | usato per suggerire le parole chiave |
| `comunicato_file_path` | string | | eventuale allegato originale |
| `parole_chiave` | json | ✅ | termini **richiesti** |
| `parole_escluse` | json | | termini che **escludono** un candidato (tagliano i falsi positivi) |
| `monitoraggio_inizio` | date | ✅ | |
| `monitoraggio_fine` | date | ✅ | |
| `stato` | enum | ✅ | vedi sotto |
| timestamps, softDeletes | | | |

**Periodo di monitoraggio.** Sempre inizio + fine, senza casi speciali nel codice:
- con comunicato → precompilato: inizio = `comunicato_data`, fine = +14 giorni (default
  configurabile);
- senza comunicato (rassegna di periodo) → lo imposta l'utente.

**Stati:**

```
in_raccolta ──> in_revisione ──> chiusa ──> riaperta ──┐
     ▲                                                  │
     └──────────────────────────────────────────────────┘
```

| Stato | Significato |
|---|---|
| `in_raccolta` | la scansione giornaliera è attiva, arrivano candidati |
| `in_revisione` | periodo scaduto o chiuso a mano; si decide sulle uscite |
| `chiusa` | PDF generato e consegnato |
| `riaperta` | solo supervisore; arrivata un'uscita tardiva → si genererà una nuova versione |

La scansione automatica gira solo sulle rassegne con periodo di monitoraggio attivo.

---

## Testata

Creata automaticamente dal sistema quando incontra una testata nuova durante una
scansione; correggibile a mano. Serve a evitare "Il Goriziano" e "Goriziano" come entità
diverse, e abilita le statistiche per testata.

| Campo | Tipo | Obbl. | Note |
|---|---|---|---|
| `id` | id | | |
| `nome` | string, unique | ✅ | |
| `sito` | string | | dominio |
| `tipo_prevalente` | enum | | stessi valori di `tipo_media` |
| `logo_path` | string | | |
| timestamps | | | |

---

## Uscita

L'entità centrale: ciò che il sistema raccoglie, l'operatore revisiona, il PDF impagina.
Soft delete (un'uscita scartata resta archiviata e recuperabile).

| Campo | Tipo | Obbl. | Note |
|---|---|---|---|
| `id` | id | | |
| `rassegna_id` | fk → rassegne | ✅ | |
| `testata_id` | fk → testate | ✅ | |
| `titolo` | string | ✅ | titolo dell'articolo |
| `data_pubblicazione` | date | ✅ | |
| `url` | string | | solo per `online`; **unique per rassegna** (deduplica) |
| `tipo_media` | enum | ✅ | `online` \| `carta` \| `radio` \| `tv` \| `agenzia` \| `social_blog` |
| `rilevanza` | enum | | `principale` \| `secondaria` \| `citazione` — assegnata in revisione |
| `stato` | enum | ✅ | ciclo di vita di business, vedi sotto |
| `stato_cattura` | enum | | stato tecnico della cattura web, vedi sotto (null = media manuale) |
| `punteggio_corrispondenza` | tinyint | | 0-100, calcolato in scoperta; guida l'ordinamento dei candidati |
| `screenshot_path` | string | | full-page: è l'immagine che finisce nel PDF |
| `pdf_pagina_path` | string | | versione multipagina leggibile |
| `testo_estratto` | longtext | | **indicizzato full-text**: abilita la ricerca d'archivio |
| `file_caricato_path` | string | | ritaglio cartaceo / file sostituito a mano |
| `pagina_giornale` | string | | es. "pag 55" (solo carta) |
| `errore_cattura` | text | | messaggio leggibile se la cattura fallisce |
| `cattura_completata_il` | datetime | | quando la cattura web è andata a buon fine |
| `posizione_pdf` | integer | | ordine manuale nel PDF |
| `note` | text | | interne, visibili solo al team |
| `data_rilevamento` | datetime | ✅ | quando il sistema l'ha trovata |
| timestamps, softDeletes | | | |

**Stati:**

```
candidato ──> confermato ──> catturato ──> approvato
    │              │              │
    └──────────────┴──────────────┴──────> scartato
```

| Stato | Chi lo produce |
|---|---|
| `candidato` | scoperta automatica: proposto, non ancora deciso |
| `confermato` | l'operatore ha detto "dentro" (schermata candidati) |
| `catturato` | il job Playwright ha prodotto screenshot + testo |
| `approvato` | l'operatore ha validato la cattura e assegnato la rilevanza |
| `scartato` | fuori dalla rassegna, ma archiviato e recuperabile |

**Stato della cattura** (`stato_cattura`, solo per le uscite `online` con URL). È il sotto-
processo tecnico che gira mentre l'uscita è `confermato`; al successo la porta a
`catturato`. Resta `null` per i media manuali (carta, radio, TV, agenzia).

```
in_attesa ──> in_corso ──> completata
                  │
                  └────────> errore   (errore_cattura contiene il messaggio leggibile)
```

| Stato cattura | Significato |
|---|---|
| `in_attesa` | job accodato, non ancora partito |
| `in_corso` | il job Playwright sta lavorando |
| `completata` | screenshot + PDF + testo prodotti; l'uscita passa a `catturato` |
| `errore` | cattura fallita; `errore_cattura` spiega perché. Da risolvere (ricattura / caricamento manuale) o scartare, non ignorare |

**Vincoli:**
- `url` unique nell'ambito della rassegna → deduplica automatica.
- Un'uscita `approvato` deve avere `screenshot_path` valido **oppure** `file_caricato_path`
  (per carta/radio/TV, dove non c'è una pagina da fotografare).
- Radio, TV, carta e agenzia **non sono cercabili automaticamente**: nascono da inserimento
  manuale.

---

## DocumentoGenerato

Il PDF della rassegna. Versionato: non si sovrascrive mai.

| Campo | Tipo | Obbl. | Note |
|---|---|---|---|
| `id` | id | | |
| `rassegna_id` | fk → rassegne | ✅ | |
| `versione` | integer | ✅ | 1, 2, 3… |
| `file_path` | string | ✅ | |
| `generato_da` | fk → users | ✅ | |
| `generato_il` | datetime | ✅ | |
| `scaricato_il` | datetime | | l'invio al cliente è manuale, fuori dal sistema |
| `uscite_incluse` | json | ✅ | snapshot degli id inclusi in questa versione |
| timestamps | | | |

Lo snapshot `uscite_incluse` serve a sapere **cosa è stato effettivamente consegnato** in
ciascuna versione, anche se le uscite cambiano dopo.

---

## LogAzione (audit)

**Immutabile.** Nessuno lo modifica né lo cancella, nemmeno il supervisore.

| Campo | Tipo | Note |
|---|---|---|
| `id` | id | |
| `user_id` | fk → users | chi |
| `azione` | string | `conferma_candidato`, `scarto_uscita`, `approva_uscita`, `genera_pdf`, `scarica_pdf`, `riapre_rassegna`, `modifica_cliente`, … |
| `entita_tipo` | string | classe del modello toccato |
| `entita_id` | integer | |
| `dettagli` | json | contesto (es. valori cambiati) |
| `created_at` | datetime | quando |

Consultabile per rassegna ("chi ha approvato queste uscite?") e in generale.

---

## Indici consigliati

- `uscite`: indice su (`rassegna_id`, `stato`), unique su (`rassegna_id`, `url`),
  **full-text su `testo_estratto`**.
- `rassegne`: indice su (`cliente_id`, `stato`), indice su `monitoraggio_fine` (per lo
  scheduler che seleziona le rassegne attive).
- `log_azioni`: indice su (`entita_tipo`, `entita_id`), indice su `user_id`.
