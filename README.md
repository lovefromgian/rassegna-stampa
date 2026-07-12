# Gestionale rassegna stampa — pacchetto di avvio

Contenuto prodotto in fase di progettazione (intervista di progetto). Da versionare nel
repo prima di far partire lo sviluppo.

## Cosa c'è qui

| File | Cosa è | Chi lo mantiene |
|---|---|---|
| `CLAUDE.md` | Contesto stabile: stack, comandi, convenzioni, dominio in breve, regole non negoziabili, ritmo di lavoro. **Claude Code lo legge a ogni sessione.** | si aggiorna quando cambia l'architettura |
| `PROGRESS.md` | Stato vivo: milestone, prossimi passi, decisioni prese e perché. | si aggiorna a ogni sessione |
| `TECH-DEBT.md` | Registro del debito tecnico. Nasce con una voce già aperta (TD-001, backup). | lo riempie Claude Code |
| `docs/modello-dati.md` | **Fonte di verità** del modello dati: entità, campi, stati, permessi, indici. | migration + doc nello stesso commit |
| `docs/regole-business.md` | **Fonte di verità** delle regole: scoperta, deduplica, cattura, blocchi al PDF, chiusura/riapertura. | |
| `mockups/` | Bozze statiche delle 10 schermate. Le viste si costruiscono guardando queste. | concordati, stabili |

**Se il codice diverge da `docs/`, vince `docs/`.** Se una specifica è ambigua: fermarsi e
chiedere, non indovinare.

## Da dove partire

1. Leggere `CLAUDE.md`, poi `PROGRESS.md`.
2. Aprire `mockups/index.html` in un browser per vedere le schermate concordate.
3. Partire da **M1 — Fondamenta** in `PROGRESS.md`.

## Il progetto in una riga

Data una campagna di monitoraggio legata a un comunicato, il sistema cerca sul web gli
articoli che ne parlano, li propone all'operatore che conferma, ne cattura screenshot e
testo, e genera un PDF impaginato da consegnare al cliente.

## Le tre cose da non dimenticare

- **La cattura è il rischio tecnico vero**, non la scoperta: banner cookie, paywall,
  contenuti lazy. Per questo M2 viene prima di M4.
- **I permessi vanno applicati lato server** (Policy). Nascondere un bottone non è un
  permesso: è un debito tecnico.
- **Il PDF non si sovrascrive mai**: si versiona. Serve a sapere cosa è stato davvero
  consegnato al cliente, e quando.
