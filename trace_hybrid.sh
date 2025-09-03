#!/usr/bin/env bash

set -euo pipefail

HOST="${1:-}"
MAX_TTL="${2:-30}"
TTL_PROBES="${3:-3}"
WAIT="${4:-1}"
ECHO_PROBES="${5:-3}"

if [[ -z "$HOST" ]]; then
  echo "usage: $(basename "$0") <host-or-ip> [max_ttl] [ttl_probes] [wait_secs] [echo_probes]" >&2
  exit 1
fi

need() { command -v "$1" >/dev/null || { echo "$1 not found" >&2; exit 1; }; }
need ping
need traceroute

resolve_ipv4() {
  getent ahostsv4 "$1" 2>/dev/null | awk 'NR==1{print $1; exit}' || true
}

DEST_IP=$(resolve_ipv4 "$HOST")
DEST_DISP="$HOST${DEST_IP:+ ($DEST_IP)}"
echo "Target: $DEST_DISP"

declare -A HOPS
declare -A RTT
declare -A METHOD

echo "Discovering hops with TTL-stepped ping..." >&2
REACHED=0
for (( ttl=1; ttl<=MAX_TTL; ttl++ )); do
  hop=""
  for (( attempt=1; attempt<=TTL_PROBES; attempt++ )); do
    out=$(ping -4 -c1 -W "$WAIT" -t "$ttl" "$HOST" 2>&1 || true)
    if grep -q "Time to live exceeded" <<<"$out"; then
      tok=$(awk '/Time to live exceeded/ {for (i=1;i<=NF;i++) if ($i=="From") {print $(i+1); exit}}' <<<"$out")
      tok=${tok//(/}
      tok=${tok//)/}
      hop="$tok"
      break
    fi
    if grep -q "bytes from" <<<"$out"; then
      HOPS[$ttl]="DEST ${DEST_IP:-$HOST}"
      REACHED=1
      break 2
    fi
  done
  HOPS[$ttl]="${hop:-*}"
done

echo "Measuring per-hop latency..." >&2
for (( ttl=1; ttl<=MAX_TTL; ttl++ )); do
  [[ -v HOPS[$ttl] ]] || break
  hop="${HOPS[$ttl]}"
  if [[ "$hop" == "*" ]]; then
    RTT[$ttl]="??"
    METHOD[$ttl]="none"
    continue
  fi
  if [[ "$hop" == DEST* ]]; then
    target="${hop#DEST }"
    # final host latency by echo
    ping_out=$(ping -4 -c "$ECHO_PROBES" -W "$WAIT" "$target" 2>/dev/null || true)
    avg=$(awk -F'/' '/rtt min\/avg\/max\/mdev/ {print $5}' <<<"$ping_out" || true)
    RTT[$ttl]="${avg:-??}"
    METHOD[$ttl]="echo"
    break
  fi

  # Prefer echo latency if the router replies
  ping_out=$(ping -4 -c "$ECHO_PROBES" -W "$WAIT" "$hop" 2>/dev/null || true)
  avg=$(awk -F'/' '/rtt min\/avg\/max\/mdev/ {print $5}' <<<"$ping_out" || true)
  if [[ -n "${avg:-}" ]]; then
    RTT[$ttl]="$avg"
    METHOD[$ttl]="echo"
    continue
  fi

  # Fallback 1: targeted TCP traceroute for this TTL only (443 then 80)
  got=""
  for port in 443 80; do
    tr_out=$(traceroute -T -p "$port" -q 3 -w 1 -f "$ttl" -m "$ttl" "$HOST" 2>/dev/null || true)
    # Extract times from last line
    line=$(tail -n1 <<<"$tr_out")
    if grep -q "\* \* \*" <<<"$line"; then
      continue
    fi
    # Collect ms fields and compute simple average of present values
    times=$(grep -oE "[0-9]+\.[0-9]+ ms" <<<"$line" | awk '{print $1}')
    if [[ -n "$times" ]]; then
      avgms=$(awk '{s+=$1; n++} END{if(n>0) printf("%.3f", s/n)}' <<<"$times")
      RTT[$ttl]="$avgms"
      METHOD[$ttl]="tcp:$port"
      got=1
      break
    fi
  done
  [[ -n "$got" ]] && continue

  # Fallback 2: ICMP traceroute at this TTL
  tr_out=$(traceroute -I -q 3 -w 2 -f "$ttl" -m "$ttl" "$HOST" 2>/dev/null || true)
  line=$(tail -n1 <<<"$tr_out")
  times=$(grep -oE "[0-9]+\.[0-9]+ ms" <<<"$line" | awk '{print $1}')
  if [[ -n "$times" ]]; then
    avgms=$(awk '{s+=$1; n++} END{if(n>0) printf("%.3f", s/n)}' <<<"$times")
    RTT[$ttl]="$avgms"
    METHOD[$ttl]="icmp"
    continue
  fi

  RTT[$ttl]="??"
  METHOD[$ttl]="none"
done

printf "%-4s %-20s %-10s %-6s\n" "TTL" "HOP" "RTT(ms)" "SRC"
for (( ttl=1; ttl<=MAX_TTL; ttl++ )); do
  [[ -v HOPS[$ttl] ]] || break
  hop="${HOPS[$ttl]}"
  rtt="${RTT[$ttl]:-??}"
  src="${METHOD[$ttl]:-none}"
  printf "%2d   %-20s %-10s %-6s\n" "$ttl" "$hop" "$rtt" "$src"
  if [[ "$hop" == DEST* ]]; then
    break
  fi
done

