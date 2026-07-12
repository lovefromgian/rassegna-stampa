#!/usr/bin/env bash
# Hook PostToolUse: dopo una modifica a file, esegue la suite di test Laravel.
# Non blocca il lavoro per scelta; riporta l'esito così Claude Code può correggere.
#
# Reso eseguibile con: chmod +x .claude/hooks/run-tests.sh

set -uo pipefail
cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0

# Esegui solo se è un progetto Laravel inizializzato
if [ ! -f "artisan" ]; then
  echo "artisan non trovato: salto i test (progetto non ancora inizializzato)."
  exit 0
fi

echo "▶ php artisan test ..."
if php artisan test --compact 2>&1; then
  echo "✅ Test verdi."
  echo "  Nota: 'verde' non certifica il comportamento end-to-end del browser."
  echo "  Per i flussi UML/UI critici lo smoke resta da confermare (vedi PROGRESS)."
  exit 0
else
  echo "❌ Test falliti: correggi prima di proseguire."
  exit 2
fi
