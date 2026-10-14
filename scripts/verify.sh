#!/usr/bin/env bash
# Install a real Craft CMS site in Docker, install this plugin into it, and
# read the tag out of the served HTML.
#
# 🔴 This is the check that matters, and reading the code is not a substitute.
# Every platform in this family has emitted a different byte-string from the
# same intent (Drupal: bare `defer`; TYPO3: `defer="defer"`; Docusaurus's own
# minifier rewrites `defer` to `defer="defer"` too). For Craft, the installed
# yii2 vendor code was read directly while building this plugin:
# yii\helpers\BaseHtml::renderTagAttributes() renders a bare `defer` for a PHP
# `true` and omits it for `false` — never `defer="1"` — but it also re-sorts a
# FIXED attribute list that includes `src` and excludes `defer`/`data-*`, so
# the shipped tag reads `src="..." defer data-domain="..."`, not
# `defer data-domain="..." src="..."`. That reordering is exercised here
# against the real framework, not re-derived from reading the source a second
# time.
#
# Unlike the Joomla and TYPO3 harnesses in this family, this one runs the
# whole stack in Docker rather than against a local database binary — Craft 5
# requires MySQL or PostgreSQL and has no SQLite driver, so there is no
# zero-dependency local option here the way SQLite gave TYPO3 one.
#
# Every container, volume and network this script creates is named
# pulse-verify-craft-*, and teardown removes ONLY those, by name — never a
# prune, and never anything this script did not itself create.
#
#   ./scripts/verify.sh            # Craft 5 (the current major)
#   ./scripts/verify.sh 5 --mutate # also proves the CP guard can fail
#
# Needs docker on PATH. Needs nothing else on the host — PHP, Composer and
# Craft all run inside the containers this script creates.
#
# Run end to end against a real install (14-10-2026). The command names and
# env vars below (php craft serve, clear-caches/compiled-templates,
# plugin/install, install/craft, the CRAFT_DB_* environment variables) were
# each checked against Craft's own current docs and, where it mattered, the
# installed vendor source, and confirmed against the real run.

set -uo pipefail

CRAFT_VERSION="${1:-5}"
MUTATE=0
for arg in "$@"; do
  [ "$arg" = "--mutate" ] && MUTATE=1
done

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PREFIX="pulse-verify-craft"
NET="${PREFIX}-net"
DB="${PREFIX}-db"
DB_VOLUME="${PREFIX}-db-data"
APP="${PREFIX}-app"
HTTP_PORT="${HTTP_PORT:-18200}"
DB_NAME="craftfixture"
# Generated per run: this is a throwaway fixture nobody logs into, but a
# literal password committed to a public repository is a thing somebody
# eventually reuses. Same reasoning as the Joomla and TYPO3 harnesses.
DB_ROOT_PASS="fixture-$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24)"
ADMIN_PASS="fixture-$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24)"
SECURITY_KEY="fixture-$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32)"

command -v docker >/dev/null || { echo "REFUSING: docker is not on PATH"; exit 1; }

# A real run found HTTP_PORT silently bound to something ELSE on the host
# before this script even started (a local tunnel process, in that case).
# Docker's `-p` publish still "succeeds" in
# that situation — it binds the IPv6 side while the pre-existing process kept
# the IPv4 side — so the container comes up fine and only curl against
# 127.0.0.1 silently hits the WRONG listener, for the entire 30-attempt
# `restart_server` retry loop, with no indication the port was ever the
# problem. Fail on this up front, loudly, rather than let it masquerade as
# "craft serve never came up" later.
if command -v lsof >/dev/null && lsof -nP -iTCP:"$HTTP_PORT" -sTCP:LISTEN >/dev/null 2>&1; then
  echo "REFUSING: something is already listening on 127.0.0.1:$HTTP_PORT — not a container this script started"
  lsof -nP -iTCP:"$HTTP_PORT" -sTCP:LISTEN || true
  echo "Pick a free port instead: HTTP_PORT=<other> $0 $*"
  exit 1
fi

# ── Teardown: ONLY our own named resources, never a prune ───────────────────
cleanup() {
  echo "==> tearing down ${PREFIX}-* resources"
  docker rm -f "$APP" "$DB" >/dev/null 2>&1 || true
  docker network rm "$NET" >/dev/null 2>&1 || true
  docker volume rm "$DB_VOLUME" >/dev/null 2>&1 || true
}
trap cleanup EXIT
cleanup # in case a previous run was interrupted — still only our own names

echo "==> creating network $NET"
docker network create "$NET" >/dev/null

echo "==> starting database $DB"
docker volume create "$DB_VOLUME" >/dev/null
docker run -d --name "$DB" --network "$NET" \
  -e MYSQL_ROOT_PASSWORD="$DB_ROOT_PASS" \
  -e MYSQL_DATABASE="$DB_NAME" \
  -v "$DB_VOLUME":/var/lib/mysql \
  mysql:8.0 >/dev/null

echo "==> waiting for the database"
for _ in $(seq 1 60); do
  docker exec "$DB" mysqladmin ping -uroot -p"$DB_ROOT_PASS" --silent >/dev/null 2>&1 && break
  sleep 1
done

echo "==> starting the app container (for Craft $CRAFT_VERSION)"
docker run -d --name "$APP" --network "$NET" -p "$HTTP_PORT":8080 \
  -w /app php:8.3-cli sleep infinity >/dev/null

echo "==> installing PHP extensions Craft requires and Composer"
# Package/extension list assembled from craftcms/cms's own composer.json
# (ext-curl, ext-dom, ext-intl, ext-openssl, ext-zip) plus pdo_mysql and gd for
# the DB driver and image handling, and bcmath, which craftcms/cms 5.10+ added
# as a hard requirement after this list was first assembled — confirmed
# against a real `composer create-project`, which refuses to resolve without
# it ("Problem 1 - ... require ext-bcmath * -> it is missing from your
# system") on the current 5.x line (5.10.8 resolved live here). curl and git
# are already present in php:8.3-cli; openssl and dom ship built in.
docker exec "$APP" bash -lc '
  set -e
  apt-get update -qq
  apt-get install -y -qq --no-install-recommends \
    unzip git libicu-dev libzip-dev libpng-dev libonig-dev default-mysql-client >/dev/null
  docker-php-ext-install -j"$(nproc)" intl zip gd pdo_mysql bcmath >/dev/null
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
'

# OPcache is ON by default in php:8.3-cli (the "cli-server" SAPI that `php
# craft serve` launches is governed by opcache.enable, NOT opcache.enable_cli
# — the two are different settings for different SAPI names) and its
# compiled-bytecode cache lives in SHARED MEMORY, independent of any one
# `php -S` process. restart_server()'s `pkill` + relaunch kills the OLD
# process, but the NEW one attaches to the SAME opcache SHM segment, so a
# file edited and re-served within one `opcache.revalidate_freq` window
# (2s default) can still serve pre-edit bytecode even though the process is
# new and `clear-caches/compiled-templates` (Twig's cache, not PHP's) ran.
# This is exactly the failure class the header comment above already named
# for TYPO3, but assumed a process restart alone was immune to here — a real
# --mutate run proved it is not: the CP-guard mutation never got caught
# (confirmed: the mutated /plugin/src/Plugin.php WAS the live symlinked
# source, so it wasn't a vendor-copy-vs-symlink issue; turning opcache off
# and re-running the identical mutation made it get caught immediately).
# Disabling opcache for the whole container, not just around --mutate, keeps
# every run (mutate or not) reading Plugin.php fresh off disk, which is what
# this harness exists to verify in the first place.
docker exec "$APP" bash -lc "echo 'opcache.enable=0' > /usr/local/etc/php/conf.d/zz-verify-disable-opcache.ini"

echo "==> creating a Craft $CRAFT_VERSION project"
docker exec "$APP" composer create-project "craftcms/craft:^${CRAFT_VERSION}.0" . --no-interaction --quiet

echo "==> configuring the database connection"
# CRAFT_DB_DSN / CRAFT_DB_USER / CRAFT_DB_PASSWORD are read directly from the
# installed craft\config\DbConfig source (vendor/craftcms/cms/src/config/DbConfig.php)
# while building this plugin, not copied from a tutorial — Craft 5 ships no
# config/db.php by default and resolves the DSN from these env vars alone.
#
# CRAFT_DB_DRIVER is required TOO, and its absence is not survivable even
# though Craft also parses a driver out of the DSN elsewhere: a real run hit
# `COLLATION 'utf8mb4_0900_ai_ci' is not valid for CHARACTER SET 'utf8mb3'`
# on the very first install migration. Root cause, traced into the installed
# vendor source and confirmed with a standalone reproduction: when
# CRAFT_SettingsService::_createConfigObj() builds DbConfig from env-only
# overrides (no config/db.php on disk), it does `new DbConfig($envArray)` —
# Yii's plain config-array constructor, which assigns `dsn` as a bare
# property and never calls the `dsn()` FLUENT setter that parses `driver`,
# `server` etc. out of it. DbConfig::getCharset()'s utf8→utf8mb4 upgrade
# checks `$this->driver === Connection::DRIVER_MYSQL`, which is false
# (`driver` stays null), so it returns the literal 'utf8' — which MySQL 8
# then rejects paired with the default utf8mb4 collation. Setting
# CRAFT_DB_DRIVER explicitly populates `driver` directly from its own env
# override, independent of DSN parsing, and getCharset() resolves to
# 'utf8mb4' as intended (confirmed against the real vendor classes, not
# just read).
docker exec "$APP" bash -lc "cat > .env <<EOF
CRAFT_ENVIRONMENT=production
CRAFT_SECURITY_KEY=$SECURITY_KEY
CRAFT_DB_DRIVER=mysql
CRAFT_DB_DSN=mysql:host=$DB;port=3306;dbname=$DB_NAME
CRAFT_DB_USER=root
CRAFT_DB_PASSWORD=$DB_ROOT_PASS
CRAFT_DEV_MODE=false
CRAFT_ALLOW_ADMIN_CHANGES=true
CRAFT_DISALLOW_ROBOTS=true
EOF"

echo "==> running the install migration"
docker exec "$APP" php craft install/craft \
  --username=admin --password="$ADMIN_PASS" --email=admin@example.com \
  --site-name="Pulse Fixture" --site-url="http://127.0.0.1:${HTTP_PORT}/" \
  --language=en-US --interactive=0

echo "==> copying the plugin into the container (writable — --mutate edits it)"
# A bind mount would make --mutate's sed fail against a read-only host mount
# and, worse, could mutate the real working tree. Copy instead, so the only
# thing this script ever writes to on the host is nothing at all.
docker exec "$APP" mkdir -p /plugin
docker cp "$PLUGIN_DIR/." "$APP:/plugin"
docker exec "$APP" rm -rf /plugin/vendor /plugin/.git

echo "==> installing the plugin from the copied source"
docker exec "$APP" composer config repositories.pulse path /plugin
docker exec "$APP" composer require ciphera/craft-pulse-analytics:@dev --no-interaction --quiet
docker exec "$APP" php craft plugin/install pulse-analytics --interactive=0

echo "==> writing a minimal fixture layout that calls {{ head() }}"
# A deliberately minimal layout, not Craft's own starter-kit welcome page —
# this is the fixture page this harness builds, built specifically so the
# {{ head() }} call is guaranteed present and the harness isn't depending on
# whatever a particular starter kit happens to ship.
docker exec "$APP" mkdir -p templates
docker exec "$APP" bash -lc "cat > templates/_layout.twig <<'EOF'
<!DOCTYPE html>
<html>
<head>
<title>Pulse fixture</title>
{{ head() }}
</head>
<body>
{{ beginBody() }}
<p>fixture</p>
{{ endBody() }}
</body>
</html>
EOF"
docker exec "$APP" bash -lc "cat > templates/index.twig <<'EOF'
{% extends '_layout' %}
EOF"

restart_server() {
  # Restarted (not just re-requested) after every config and every mutation,
  # so there is no ambiguity about whether some caching layer is serving a
  # stale render — the same family of bug the TYPO3 harness hit with its
  # opcache, dealt with here by never trusting a running process to notice a
  # change instead of by reasoning about which cache applies to `craft serve`.
  docker exec "$APP" pkill -f "craft serve" >/dev/null 2>&1 || true
  docker exec "$APP" php craft clear-caches/compiled-templates >/dev/null 2>&1 || true
  docker exec -d "$APP" php craft serve "0.0.0.0:8080"
  for _ in $(seq 1 30); do
    curl -sf -o /dev/null "http://127.0.0.1:${HTTP_PORT}/" && return 0
    sleep 1
  done
  echo "REFUSING: craft serve never came up"
  echo "    (if this is surprising, re-check: lsof -nP -iTCP:${HTTP_PORT} -sTCP:LISTEN — the"
  echo "     preflight check above only catches a port already held BEFORE this script starts)"
  exit 1
}

restart_server

FAILURES=0
CORE='https://js\.ciphera\.net/script\.js'
COMP='https://js\.ciphera\.net/script\.interactions\.js'

set_config() {
  # $1 domain  $2 companion (true/false, bare PHP)  $3 apiOrigin
  # config/pulse-analytics.php is Craft's own per-environment settings
  # override file for this plugin's handle — writing it is the scriptable
  # equivalent of saving the control panel settings form, with no need to
  # drive a real browser session against the CP.
  docker exec "$APP" mkdir -p config
  docker exec "$APP" bash -lc "cat > config/pulse-analytics.php <<EOF
<?php
return [
    'domain' => '$1',
    'companion' => $2,
    'apiOrigin' => '$3',
];
EOF"
  restart_server
}

# $1 label  $2 must-match ERE  $3 must-NOT-match ERE (optional)
check() {
  local html tags
  html=$(curl -s "http://127.0.0.1:${HTTP_PORT}/")
  tags=$(printf '%s' "$html" | grep -oE '<script[^>]*ciphera[^>]*></script>' | tr '\n' ' ')
  if printf '%s' "$tags" | grep -qE "$2"; then
    echo "    ✅ $1"
  else
    echo "    ❌ $1"
    echo "       expected to match: $2"
    echo "       got: ${tags:-<nothing>}"
    FAILURES=$((FAILURES + 1))
  fi
  if [ -n "${3:-}" ] && printf '%s' "$tags" | grep -qE "$3"; then
    echo "    ❌ $1 — unexpectedly present: $3"
    FAILURES=$((FAILURES + 1))
  fi
}

# ── Negative control: no plugin installed at all ────────────────────────────
# Proves the fixture page itself carries nothing Pulse-shaped before trusting
# any later "yes it's there" result.
echo "==> negative control: plugin disabled"
docker exec "$APP" php craft plugin/disable pulse-analytics --interactive=0
restart_server
NOTAG=$(curl -s "http://127.0.0.1:${HTTP_PORT}/" | grep -c 'ciphera' || true)
if [ "$NOTAG" -eq 0 ]; then
  echo "    ✅ a disabled plugin carries no tag (negative control holds)"
else
  echo "    ❌ a disabled plugin still carries $NOTAG reference(s) — the negative control itself is broken"
  FAILURES=$((FAILURES + 1))
fi
docker exec "$APP" php craft plugin/enable pulse-analytics --interactive=0

echo "==> checking the served HTML"

set_config "example.com" "false" ""
check "domain only" \
  "<script src=\"$CORE\" defer data-domain=\"example\.com\"></script>" ""

set_config "chosen.dev" "true" "https://proxy.example.com/"
check "domain + api + companion, companion carries no domain" \
  "<script src=\"$CORE\" defer data-domain=\"chosen\.dev\" data-api=\"https://proxy\.example\.com\"></script> <script src=\"$COMP\" defer></script>" \
  ""

set_config "" "false" ""
check "no domain configured: auto-detect, not a broken attribute" \
  "^<script src=\"$CORE\" defer></script> \$" \
  "data-domain"

set_config 'bad" onload="x' "false" ""
check "an invalid domain is dropped, never injected" \
  "^<script src=\"$CORE\" defer></script> \$" \
  "onload"

# ── The control panel, unconditionally ──────────────────────────────────────
echo "==> the control panel carries no tag (unconditional — not a setting)"
set_config "example.com" "true" ""
CP_HTML=$(curl -s "http://127.0.0.1:${HTTP_PORT}/admin/login")
CP_HITS=$(printf '%s' "$CP_HTML" | grep -c 'ciphera' || true)
if [ "$CP_HITS" -eq 0 ]; then
  echo "    ✅ the control panel carries no tag"
else
  echo "    ❌ the control panel carries $CP_HITS reference(s) to ciphera"
  FAILURES=$((FAILURES + 1))
fi

# ── --mutate: prove the CP guard can actually fail ──────────────────────────
if [ "$MUTATE" -eq 1 ]; then
  echo "==> --mutate: disabling the CP guard and proving the check goes red"
  docker exec "$APP" sed -i \
    's/if (\$request->getIsConsoleRequest() || \$request->getIsCpRequest())/if (false)/' \
    /plugin/src/Plugin.php
  docker exec "$APP" composer dump-autoload --quiet
  restart_server
  MUTATED_HITS=$(curl -s "http://127.0.0.1:${HTTP_PORT}/admin/login" | grep -c 'ciphera' || true)
  if [ "$MUTATED_HITS" -gt 0 ]; then
    echo "    ✅ mutation caught: the CP now carries $MUTATED_HITS reference(s), as it should after breaking the guard"
  else
    echo "    ❌ MUTATION NOT CAUGHT: the CP guard check would pass even with the guard disabled — the check is not load-bearing"
    FAILURES=$((FAILURES + 1))
  fi
  # Restore from the untouched host copy and re-verify the guard holds again,
  # so --mutate proves the check can fail without leaving the fixture (or,
  # since this never touches the host tree, the real plugin) in the broken
  # state.
  docker cp "$PLUGIN_DIR/src/Plugin.php" "$APP:/plugin/src/Plugin.php"
  docker exec "$APP" composer dump-autoload --quiet
  restart_server
  RESTORED_HITS=$(curl -s "http://127.0.0.1:${HTTP_PORT}/admin/login" | grep -c 'ciphera' || true)
  if [ "$RESTORED_HITS" -eq 0 ]; then
    echo "    ✅ restored: the CP guard holds again"
  else
    echo "    ❌ restore failed: the CP still carries $RESTORED_HITS reference(s) after restoring Plugin.php"
    FAILURES=$((FAILURES + 1))
  fi
fi

echo
if [ "$FAILURES" -eq 0 ]; then
  echo "Craft $CRAFT_VERSION: ALL CHECKS PASSED"
else
  echo "Craft $CRAFT_VERSION: $FAILURES FAILURE(S)"
fi
exit "$FAILURES"
