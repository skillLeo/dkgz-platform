#!/usr/bin/env bash
#
# DKGZ deployment for a host with SSH.
#
# Assets are built locally and committed, because the server has no npm — this
# script therefore never runs a build, it only installs PHP dependencies,
# migrates and warms the caches.
#
#   ./deploy.sh            deploy the current branch
#   ./deploy.sh --seed     deploy and run the production seeder (first install)
#
set -euo pipefail

SEED=false
[[ "${1:-}" == "--seed" ]] && SEED=true

cd "$(dirname "$0")"

step() { printf '\n\033[1m▸ %s\033[0m\n' "$1"; }

step 'Pulling the current branch'
git pull --ff-only

step 'Installing PHP dependencies'
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

step 'Putting the site into maintenance mode'
# `|| true` because the very first deploy has nothing to put down yet.
php artisan down --render="errors::503" --retry=60 || true

cleanup() {
    step 'Bringing the site back up'
    php artisan up || true
}
trap cleanup EXIT

step 'Running migrations'
php artisan migrate --force

if [[ "$SEED" == true ]]; then
    step 'Seeding the production dataset'
    php artisan db:seed --class=ProductionSeeder --force
fi

step 'Linking public storage'
# SafeStorage falls back to a controller-served route if this silently fails,
# which it does on some shared hosts where symlinks are disabled.
php artisan storage:link || echo '  storage:link unavailable — SafeStorage will serve media through /medien.'

step 'Rebuilding caches'
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

step 'Clearing the settings cache'
php artisan cache:clear

step 'Deployment finished'
php artisan about --only=environment
