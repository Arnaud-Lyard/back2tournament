#!/usr/bin/env bash
set -euo pipefail

# Symfony Docker's Caddy container serves https://localhost with a self-signed
# cert from its own local CA. Node's fetch (used by the auth proxy routes and
# the API client) rejects it by default, so this pulls the CA's root cert out
# of the caddy_data volume for use via NODE_EXTRA_CA_CERTS in dev.
VOLUME="${CADDY_DATA_VOLUME:-back2tournament_caddy_data}"
OUT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/.certs/caddy-local-ca.pem"

mkdir -p "$(dirname "$OUT")"
docker run --rm -v "$VOLUME":/data alpine cat /data/caddy/pki/authorities/local/root.crt > "$OUT"

echo "Wrote $OUT"
