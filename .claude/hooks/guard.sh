#!/usr/bin/env bash
# Hook PreToolUse: rete di sicurezza. Blocca comandi pericolosi PRIMA che girino.
# Tarato su stack Laravel (PHP + DB relazionale). exit 2 = blocca e segnala il motivo.
#
# Reso eseguibile con: chmod +x .claude/hooks/guard.sh

set -uo pipefail
input="$(cat)"

patterns=(
  "rm -rf /"
  "rm -rf ~"
  "rm -rf \\*"
  "git push --force"          # force-push resta a conferma umana: riscrive la storia
  "git push -f"
  "php artisan migrate:fresh" # ricrea il DB da zero: distruttivo
  "php artisan db:wipe"
  "migrate:rollback"          # conferma umana: tocca lo schema
  "DROP TABLE"
  "DROP DATABASE"
  "TRUNCATE"
  "DELETE FROM"               # se il progetto usa soft delete, niente DELETE fisico
  "> .env"
  "chmod -R 777"
)

for p in "${patterns[@]}"; do
  if echo "$input" | grep -qi "$p"; then
    echo "⛔ Comando bloccato dal guard: pattern sensibile ('$p')." >&2
    echo "   È un checkpoint a conferma umana (vedi CLAUDE.md §9). Conferma manuale richiesta." >&2
    exit 2
  fi
done

exit 0
