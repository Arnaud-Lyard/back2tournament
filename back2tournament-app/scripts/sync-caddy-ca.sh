#!/usr/bin/env bash
set -euo pipefail

VOLUME="${CADDY_DATA_VOLUME:-back2tournament_caddy_data}"
OUT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/.certs/caddy-local-ca.pem"

mkdir -p "$(dirname "$OUT")"
docker run --rm -v "$VOLUME":/data alpine cat /data/caddy/pki/authorities/local/root.crt > "$OUT"

echo "Wrote $OUT"
