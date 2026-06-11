#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$ROOT/lapeh-api"
APP_DIR="$ROOT/lapeh_app"
PIDFILE="$ROOT/.lapeh-start.pid"

FLUTTER_DEVICE="${FLUTTER_DEVICE:-chrome}"
API_ONLY=false
OPEN_ADMIN=true

usage() {
  cat <<EOF
Usage: ./start.sh [options]

Start the Lapeh API (admin portal) and Flutter app together.

Options:
  --device <id>   Flutter device id (default: chrome). Run \`flutter devices\` to list.
  --api-only      Start API services only (no Flutter app)
  --no-open       Do not open the admin login page in a browser
  -h, --help      Show this help

Environment:
  FLUTTER_DEVICE  Same as --device
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --device)
      FLUTTER_DEVICE="$2"
      shift 2
      ;;
    --api-only)
      API_ONLY=true
      shift
      ;;
    --no-open)
      OPEN_ADMIN=false
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

require_dir() {
  if [[ ! -d "$1" ]]; then
    echo "Missing directory: $1" >&2
    exit 1
  fi
}

require_dir "$API_DIR"
if [[ "$API_ONLY" == false ]]; then
  require_dir "$APP_DIR"
fi

stop_existing() {
  if [[ ! -f "$PIDFILE" ]]; then
    return
  fi

  echo "Stopping previous Lapeh session..."
  local pid
  while IFS= read -r pid; do
    [[ -z "$pid" ]] && continue
    if kill -0 "$pid" 2>/dev/null; then
      kill -TERM "$pid" 2>/dev/null || true
    fi
  done < "$PIDFILE"

  rm -f "$PIDFILE"
  sleep 1

  local port pids
  for port in 8000 8080; do
    pids=$(lsof -tiTCP:"$port" -sTCP:LISTEN 2>/dev/null || true)
    if [[ -n "$pids" ]]; then
      echo "Stopping process(es) on port $port..."
      kill -TERM $pids 2>/dev/null || true
    fi
  done

  if pgrep -f "flutter run" >/dev/null 2>&1; then
    echo "Stopping Flutter dev server..."
    pkill -TERM -f "flutter run" 2>/dev/null || true
    sleep 1
  fi
}

write_pidfile() {
  {
    echo "$$"
    [[ -n "${API_PID:-}" ]] && echo "$API_PID"
  } > "$PIDFILE"
}

ensure_redis() {
  if redis-cli ping >/dev/null 2>&1; then
    return
  fi

  echo "Starting Redis..."
  if command -v redis-server >/dev/null 2>&1; then
    redis-server --daemonize yes
  elif [[ -x "$HOME/Library/Application Support/Herd/bin/redis-server" ]]; then
    "$HOME/Library/Application Support/Herd/bin/redis-server" --daemonize yes --port 6379
  else
    echo "Redis is not running and redis-server was not found." >&2
    exit 1
  fi

  sleep 1
  if ! redis-cli ping >/dev/null 2>&1; then
    echo "Failed to start Redis." >&2
    exit 1
  fi
}

cleanup() {
  rm -f "$PIDFILE"
  if [[ -n "${API_PID:-}" ]] && kill -0 "$API_PID" 2>/dev/null; then
    kill "$API_PID" 2>/dev/null || true
    wait "$API_PID" 2>/dev/null || true
  fi
}

trap cleanup EXIT INT TERM

stop_existing

ensure_redis

echo "Clearing Laravel caches..."
(
  cd "$API_DIR"
  php artisan optimize:clear
)

echo "Starting API, queue worker, and Reverb..."
(
  cd "$API_DIR"
  npx concurrently -c "#93c5fd,#c4b5fd,#fb7185" \
    "php artisan serve" \
    "php artisan queue:listen --tries=1 --timeout=0" \
    "php artisan reverb:start" \
    --names server,queue,reverb --kill-others
) &
API_PID=$!
write_pidfile

echo "Waiting for API..."
for _ in {1..30}; do
  if curl -sf "http://127.0.0.1:8000/up" >/dev/null 2>&1; then
    break
  fi
  sleep 0.5
done

if ! curl -sf "http://127.0.0.1:8000/up" >/dev/null 2>&1; then
  echo "API did not become ready on http://127.0.0.1:8000" >&2
  exit 1
fi

echo ""
echo "Admin portal: http://localhost:8000/admin/login"
echo "API base:     http://localhost:8000/api"
echo "Reverb:       http://localhost:8080"
echo ""

if [[ "$OPEN_ADMIN" == true ]]; then
  if command -v open >/dev/null 2>&1; then
    open "http://localhost:8000/admin/login"
  elif command -v xdg-open >/dev/null 2>&1; then
    xdg-open "http://localhost:8000/admin/login"
  fi
fi

if [[ "$API_ONLY" == true ]]; then
  echo "API-only mode. Press Ctrl+C to stop."
  wait "$API_PID"
  exit 0
fi

echo "Preparing Flutter app (clean build)..."
cd "$APP_DIR"
flutter clean
flutter pub get

echo "Starting Flutter app on device: $FLUTTER_DEVICE"
flutter run -d "$FLUTTER_DEVICE"
