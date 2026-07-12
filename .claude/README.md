# Configurazione per uno sviluppo più autonomo (Claude Code) — base Laravel

Questa cartella `.claude/` rende il ciclo **scrivi → verifica → testa** in gran parte
automatico, così intervieni ai checkpoint invece che a ogni passo. Stack di riferimento:
**Laravel** (PHP, Composer, Artisan, Pest/PHPUnit). Autonomia + guardrail, non senza rete.

## Cosa fa

| Pezzo | File | Cosa fa |
|---|---|---|
| **Reviewer** | `agents/code-reviewer.md` | Subagent di revisione tecnica, contesto separato da chi scrive. Verifica conformità a specifica, convenzioni, sicurezza, test. Applica "mostra non descrivere", "funziona ≠ pronto", "vincolo UI-only → debito". |
| **Test automatici** | `hooks/run-tests.sh` | Dopo ogni modifica, lancia `php artisan test`. Se rosso, Claude Code lo sa e corregge. |
| **Guardrail** | `hooks/guard.sh` | Blocca comandi distruttivi (rm -rf, migrate:fresh, db:wipe, DROP, DELETE, push) prima che girino. Il push è un checkpoint. |
| **Checkpoint di fine** | `settings.json` (Stop) | A fine turno un agente verifica coerenza con PROGRESS.md e test verdi. |
| **Notifiche** | `hooks/notify.sh` | Push ntfy o email quando Claude Code ti ridà la parola. |

> **Notifiche — cosa le attiva davvero.** Canale affidabile: evento **Stop** (fine
> turno, inclusa la pausa-domanda a un checkpoint). In più **Notification** con matcher
> `permission_prompt` per le richieste di permesso. Evitiamo `idle_prompt` (su molte
> configurazioni non scatta o genera falsi positivi).

## Attivazione (una volta sola)

```bash
chmod +x .claude/hooks/run-tests.sh .claude/hooks/guard.sh .claude/hooks/notify.sh
```

Configura le notifiche aprendo `notify.sh` e compilando `NTFY_TOPIC` (app ntfy, consigliato)
oppure `MAIL_TO` (richiede msmtp). Vuoti = nessuna notifica.

## Prima di affidartici

- `php artisan test` deve essere già verde, altrimenti l'hook segnala rosso a ogni modifica.
- Rivedi i pattern in `guard.sh` per i comandi rischiosi specifici del progetto.
- Se il progetto usa soft delete, tieni `DELETE FROM` tra i pattern bloccati.

## Principi di lavoro (in CLAUDE.md, applicati anche dal reviewer)

- **Mostra, non descrivere**: le affermazioni sul codice si ancorano a output letterale.
- **"Funziona" ≠ "pronto per produzione"**: i test verdi non chiudono il giudizio.
- **Vincolo UI-only → debito**: un controllo solo in UI e aggirabile va tracciato, non dato per fatto.
- **Autonomia tra checkpoint**: si lavora liberi, ci si ferma solo ai checkpoint (push, migration schema, aree sensibili, ambiguità).
- **Git all'avvio**: `git log --oneline -10` a inizio sessione per allinearsi al repo reale.

## Dove resta Cowork

Questa config chiude l'anello "scrittura + verifica tecnica" dentro Claude Code.
Cowork resta il verificatore di **processo** (allineamento specifica↔codice, stato,
prossimi passi) che attivi tu a fine blocco. Vedi i ruoli in CLAUDE.md.
