#!/usr/bin/env bash
set -euo pipefail
umask 077

deploy_root=${1:?deployment root required}
release_id=${2:?release id required}
commit=${3:?commit required}
artifact_hash=${4:?artifact hash required}
mode=${5:-activate}
domain=amalezpacktrade.com
[[ "$deploy_root" == "$HOME/applications/amalez-pack-trade" ]] || { echo 'Unexpected deployment root' >&2; exit 1; }
[[ "$release_id" =~ ^[a-f0-9]{40}-[a-zA-Z0-9-]+$ && "$commit" =~ ^[a-f0-9]{40}$ && "$artifact_hash" =~ ^[a-f0-9]{64}$ ]] || exit 1
[[ "$mode" == stage || "$mode" == activate ]] || exit 1
public_root="$HOME/domains/$domain/public_html"
release="$deploy_root/releases/$release_id"
shared="$deploy_root/shared"
archive="$deploy_root/incoming/$release_id.tar.gz"
mkdir -p "$deploy_root/releases" "$deploy_root/backups" "$shared/storage" "$deploy_root/incoming"
chmod 755 "$HOME/applications" "$deploy_root" "$deploy_root/releases"
printf '%s  %s\n' "$artifact_hash" "$archive" | sha256sum --check --status

if [[ ! -e "$release/.prepared" ]]; then
    [[ ! -e "$release" ]] || { echo 'Incomplete release directory exists; refusing overwrite' >&2; exit 1; }
    # A release may contain only relative paths and no actual environment files.
    archive_entries=$(tar -tzf "$archive")
    if grep -Eq '(^/|(^|/)\.\.(/|$)|(^|/)\.env([^/]*$)|(^|/)\.git(/|$))' <<< "$archive_entries"; then
        echo 'Unsafe archive entry detected' >&2; exit 1
    fi
    mkdir "$release"
    tar -xzf "$archive" -C "$release" --no-same-owner
    chmod -R u=rwX,go=rX "$release"
    mkdir -p "$shared/storage/app/private" "$shared/storage/app/public" "$shared/storage/framework/cache/data" "$shared/storage/framework/sessions" "$shared/storage/framework/views" "$shared/storage/logs" "$release/bootstrap/cache"
    if [[ ! -f "$shared/.env" ]]; then
        install -m 600 "$release/deploy/production.env.example" "$shared/.env"
    fi
    ln -s "$shared/.env" "$release/.env"
    ln -s "$shared/storage" "$release/storage"
    chmod 700 "$shared" "$shared/storage"
    chmod 600 "$shared/.env"
    cd "$release"
    if ! grep -Eq '^APP_KEY=.+$' "$shared/.env"; then
        php artisan key:generate --force --no-interaction >/dev/null
    fi
    composer check-platform-reqs --no-dev
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan view:cache --no-interaction
    php -r '
        require "vendor/autoload.php";
        $app=require "bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        if(config("app.env")!=="production" || config("app.debug") || config("admin.registration_enabled") || config("app.url")!=="https://amalezpacktrade.com" || config("session.driver")!=="file" || config("cache.default")!=="file" || config("queue.default")!=="sync" || !config("session.secure") || !config("app.key")) {fwrite(STDERR,"Production configuration validation failed\n");exit(1);}
        if(config("admin.enabled")) {
            if(config("database.default")!=="mysql" || !Illuminate\Support\Facades\Schema::hasColumn("users","is_admin") || !Illuminate\Support\Facades\Schema::hasTable("demo_activity_logs") || !Illuminate\Support\Facades\Schema::hasTable("admin_bootstrap_invitations")) {fwrite(STDERR,"Admin database readiness validation failed\n");exit(1);}
        }
        $response=$app->make(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::create("https://amalezpacktrade.com/"));
        if($response->getStatusCode()!==200 || !str_contains($response->getContent(),"Protect the product.") || !str_contains($response->getContent(),"Amlez Pack Trade")) {fwrite(STDERR,"Staged Laravel smoke test failed\n");exit(1);}
        echo "Staged Laravel application boots with production settings.\n";
    '
    printf '%s\n' "$commit" > "$release/.prepared"
fi
[[ "$(cat "$release/.prepared")" == "$commit" ]] || exit 1
if [[ "$mode" == stage ]]; then echo 'Release staged; public document root unchanged.'; exit 0; fi

previous=$(readlink "$deploy_root/current" || true)
original_directory=''
rollback() {
    local result=$?
    trap - ERR
    if [[ -n "$previous" ]]; then
        ln -s "$previous" "$deploy_root/rollback-$release_id"
        mv -Tf "$deploy_root/rollback-$release_id" "$deploy_root/current"
    fi
    if [[ -n "$original_directory" && -d "$original_directory" ]]; then
        if [[ -L "$public_root" ]]; then mv "$public_root" "$deploy_root/backups/failed-public-link-$release_id"; fi
        mv "$original_directory" "$public_root"
    fi
    echo 'Activation failed; previous public target restored where available.' >&2
    exit "$result"
}
trap rollback ERR
if [[ -d "$public_root" && ! -L "$public_root" ]]; then
    backup="$deploy_root/backups/public_html-$release_id.tar.gz"
    tar -czf "$backup" -C "$public_root" .
    tar -tzf "$backup" >/dev/null
    sha256sum "$backup" > "$backup.sha256"
    original_directory="$deploy_root/backups/public_html-original-$release_id"
fi
ln -s "$release" "$deploy_root/next-$release_id"
mv -Tf "$deploy_root/next-$release_id" "$deploy_root/current"
if [[ -n "$original_directory" ]]; then mv "$public_root" "$original_directory"; fi
if [[ ! -e "$public_root" ]]; then
    ln -s "$deploy_root/current/public" "$public_root"
else
    [[ -L "$public_root" && "$(readlink "$public_root")" == "$deploy_root/current/public" ]] || { echo 'Unexpected public root; refusing replacement' >&2; false; }
fi
page=$(curl --fail --silent --show-error --max-time 25 --resolve "$domain:443:46.202.161.251" "https://$domain/")
[[ "$page" == *'Protect the product.'* && "$page" == *'Amlez Pack Trade'* ]] || false
live_commit=$(curl --fail --silent --show-error --max-time 20 --resolve "$domain:443:46.202.161.251" "https://$domain/release.json" | php -r '$r=json_decode(stream_get_contents(STDIN),true);echo $r["commit"]??"";')
[[ "$live_commit" == "$commit" ]] || false
trap - ERR
echo "Activated verified Laravel release $commit."
