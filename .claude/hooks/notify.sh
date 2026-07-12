#!/usr/bin/env bash
# Hook di notifica: avvisa quando Claude Code attende un riscontro (Notification)
# o ha terminato il lavoro (Stop).
#
# Due canali supportati — configura quello che preferisci qui sotto:
#   1) ntfy.sh (consigliato: zero setup, push sul telefono)
#      - installa l'app ntfy sul telefono e iscriviti a un topic a tua scelta
#      - metti il nome del topic in NTFY_TOPIC (scegline uno difficile da indovinare)
#   2) Email via msmtp (richiede msmtp configurato sulla macchina: ~/.msmtprc)
#      - metti il destinatario in MAIL_TO
#
# Reso eseguibile con: chmod +x .claude/hooks/notify.sh
# Uso negli hook: notify.sh "attesa"   (per Notification)
#                 notify.sh "fine"     (per Stop)

set -uo pipefail

# ---- CONFIGURAZIONE ---------------------------------------------------------
NTFY_TOPIC="gest-diebau"        # es. "gestionale-mario-x7k2" — vuoto = disattivato
MAIL_TO=""           # es. "tu@example.com"        — vuoto = disattivato
# -----------------------------------------------------------------------------

evento="${1:-attesa}"
progetto="$(basename "${CLAUDE_PROJECT_DIR:-$PWD}")"

if [ "$evento" = "fine" ]; then
  titolo="✅ Claude Code: tocca a te"
  corpo="Turno terminato nel progetto '$progetto'. Ha finito il lavoro o si è fermato a un checkpoint in attesa di una tua risposta."
else
  titolo="🔐 Claude Code chiede un permesso"
  corpo="Richiesta di permesso in sospeso nel progetto '$progetto'."
fi

# Canale 1: push via ntfy.sh
if [ -n "$NTFY_TOPIC" ]; then
  curl -s -m 10 \
    -H "Title: $titolo" \
    -H "Priority: high" \
    -d "$corpo" \
    "https://ntfy.sh/$NTFY_TOPIC" >/dev/null 2>&1 || true
fi

# Canale 2: email via msmtp
if [ -n "$MAIL_TO" ] && command -v msmtp >/dev/null 2>&1; then
  printf "Subject: %s\nTo: %s\n\n%s\n" "$titolo" "$MAIL_TO" "$corpo" \
    | msmtp "$MAIL_TO" >/dev/null 2>&1 || true
fi

# Non bloccare mai il lavoro per un problema di notifica
exit 0
