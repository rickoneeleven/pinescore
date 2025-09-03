#!/usr/bin/env bash

set -euo pipefail

HOST="${1:-}"
if [[ -z "$HOST" ]]; then
  echo "usage: $(basename "$0") <host-or-ip> [max_ttl] [probes_per_ttl] [wait_secs]" >&2
  exit 1
fi

MAX_TTL="${2:-30}"
PROBES="${3:-3}"
WAIT="${4:-1}"

command -v ping >/dev/null || { echo "ping not found" >&2; exit 1; }
command -v traceroute >/dev/null || { echo "traceroute not found" >&2; exit 1; }

resolve_dest() {
  local h="$1"
  # Prefer IPv4
  getent ahostsv4 "$h" 2>/dev/null | awk 'NR==1{print $1; exit}' || true
}

DEST_IP=$(resolve_dest "$HOST")
DEST_DISP="$HOST${DEST_IP:+ ($DEST_IP)}"

echo "Comparing built-in traceroute vs TTL-stepped ping"
echo "Target: $DEST_DISP"
echo

echo "--- traceroute -I -q1 -w1 -m$MAX_TTL $HOST"
traceroute -I -q1 -w1 -m"$MAX_TTL" "$HOST" || true
echo

declare -A HOPS
REACHED=0

echo "--- ping TTL-step (-4 -c1 -W$WAIT, $PROBES probes per hop)"
for (( ttl=1; ttl<=MAX_TTL; ttl++ )); do
  hop_label="*"
  reached_here=0
  for (( attempt=1; attempt<=PROBES; attempt++ )); do
    # Capture output; suppress DNS resolution delays by forcing IPv4
    out=$(ping -4 -c1 -W "$WAIT" -t "$ttl" "$HOST" 2>&1 || true)

    if grep -q "Time to live exceeded" <<<"$out"; then
      # Example lines:
      # From 185.232.119.245 (185.232.119.245) icmp_seq=1 Time to live exceeded
      # From 185.232.119.245 icmp_seq=1 Time to live exceeded
      token=$(awk '/Time to live exceeded/ {for (i=1;i<=NF;i++) if ($i=="From") {print $(i+1); exit}}' <<<"$out")
      token=${token//(/}
      token=${token//)/}
      hop_label="$token"
      break
    fi

    if grep -q "bytes from" <<<"$out"; then
      # Destination reached at this TTL
      # Example: 64 bytes from 151.101.0.81: icmp_seq=1 ttl=55 time=9.87 ms
      dest=$(awk '/bytes from/ {print $4}' <<<"$out" | sed 's/://')
      hop_label="DEST $dest"
      reached_here=1
      break
    fi
  done

  HOPS[$ttl]="$hop_label"
  if (( reached_here == 1 )); then
    REACHED=1
    break
  fi
done

for (( ttl=1; ttl<=MAX_TTL; ttl++ )); do
  [[ -v HOPS[$ttl] ]] || break
  printf "%2d  %s\n" "$ttl" "${HOPS[$ttl]}"
  if [[ "${HOPS[$ttl]}" == DEST* ]]; then
    break
  fi
done

if (( REACHED == 0 )); then
  echo "Note: destination not confirmed within $MAX_TTL hops. Consider increasing max TTL or wait time."
fi

