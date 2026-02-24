#!/bin/bash

#=================================================
# COMMON VARIABLES AND CUSTOM HELPERS
#=================================================

cgit_src_dir="${install_dir}/cgit"
gitolite_src_dir="${install_dir}/gitolite"
gitolite_home_dir="${data_dir}/gitolite"
repositories_dir="${gitolite_home_dir}/repositories"
admin_ssh_key_file="${data_dir}/admin_ssh_key.pub"
cgit_web_root="/var/www/html/cgit"
cgit_runtime_dir="${cgit_web_root}/cgi"
cgit_cache_dir="${cgit_web_root}/cache"
cgit_filters_dir="${cgit_web_root}/filters"
cgit_cgi_path="${cgit_runtime_dir}/cgit.cgi"
cgit_config_path="${cgit_web_root}/cgitrc"
cgit_build_config="${cgit_src_dir}/cgit.conf"
fcgiwrap_socket="/run/fcgiwrap.socket"
fcgiwrap_user="www-data"
git_http_backend="/usr/lib/git-core/git-http-backend"
gitolite_cmd="${gitolite_home_dir}/bin/gitolite"

_setup_source_tree() {
    local source_id="$1"
    local dest_dir="$2"
    local mode="${3:-install}"

    if [ "$mode" = "upgrade" ]; then
        ynh_setup_source --source_id="$source_id" --dest_dir="$dest_dir" --full_replace
    else
        ynh_setup_source --source_id="$source_id" --dest_dir="$dest_dir"
    fi
}

setup_sources() {
    local mode="${1:-install}"

    ynh_script_progression "Deploying cgit source..."
    _setup_source_tree "cgit" "$cgit_src_dir" "$mode"

    ynh_script_progression "Deploying gitolite source..."
    _setup_source_tree "gitolite" "$gitolite_src_dir" "$mode"

    chown -R "$app:$app" "$install_dir"
}

setup_data_layout() {
    ynh_script_progression "Preparing data directories..."
    mkdir -p "$gitolite_home_dir" "$repositories_dir"
    chmod 750 "$gitolite_home_dir" "$repositories_dir"
    chown -R "$app:$app" "$data_dir"
    ynh_app_setting_set --key=repositories_dir --value="$repositories_dir"
}

save_admin_ssh_key() {
    if [ -n "${admin_ssh_key:-}" ]; then
        printf "%s\n" "$admin_ssh_key" > "$admin_ssh_key_file"
        chmod 600 "$admin_ssh_key_file"
        chown "$app:$app" "$admin_ssh_key_file"
    fi
}

detect_git_http_backend() {
    local candidate
    for candidate in \
        "/usr/lib/git-core/git-http-backend" \
        "/usr/libexec/git-core/git-http-backend"
    do
        if [ -x "$candidate" ]; then
            git_http_backend="$candidate"
            ynh_app_setting_set --key=git_http_backend --value="$git_http_backend"
            return
        fi
    done

    ynh_die --message="Unable to find git-http-backend on this system."
}

inject_cgit_build_config() {
    ynh_script_progression "Injecting cgit build configuration..."
    cat > "$cgit_build_config" << EOF
CGIT_SCRIPT_PATH = $cgit_runtime_dir
CGIT_CONFIG = $cgit_config_path
CACHE_ROOT = $cgit_cache_dir
prefix = $cgit_web_root
libdir = \$(prefix)
filterdir = \$(libdir)/filters
EOF
    chown "$app:$app" "$cgit_build_config"
}

build_cgit_runtime() {
    ynh_script_progression "Building cgit runtime..."
    local built=0
    local candidate=""

    mkdir -p "$cgit_runtime_dir" "$cgit_cache_dir" "$cgit_filters_dir"
    inject_cgit_build_config

    if [ -f "$cgit_src_dir/Makefile" ]; then
        if make -C "$cgit_src_dir" NO_GETTEXT=1 NO_LUA=1 >/dev/null 2>&1; then
            for candidate in "$cgit_src_dir/cgit.cgi" "$cgit_src_dir/cgit"; do
                if [ -x "$candidate" ]; then
                    cp -f "$candidate" "$cgit_cgi_path"
                    built=1
                    break
                fi
            done
        else
            ynh_print_warn "Failed to build cgit from source, using packaged runtime."
        fi
    fi

    if [ "$built" -eq 0 ] && [ -x "/usr/lib/cgit/cgit.cgi" ]; then
        cp -f "/usr/lib/cgit/cgit.cgi" "$cgit_cgi_path"
        built=1
    fi

    [ "$built" -eq 1 ] || ynh_die --message="Unable to prepare cgit CGI runtime."
    mkdir -p "$cgit_src_dir/cgi"
    cp -f "$cgit_cgi_path" "$cgit_src_dir/cgi/cgit.cgi"
    chmod 755 "$cgit_src_dir/cgi/cgit.cgi"
    chmod 755 "$cgit_cgi_path"

    for candidate in "$cgit_src_dir/cgit.css" "/usr/share/cgit/cgit.css"; do
        if [ -f "$candidate" ]; then
            if [ "$candidate" != "$cgit_web_root/cgit.css" ]; then
                cp -f "$candidate" "$cgit_web_root/cgit.css"
            fi
            break
        fi
    done

    for candidate in "$cgit_src_dir/cgit.png" "/usr/share/cgit/cgit.png"; do
        if [ -f "$candidate" ]; then
            if [ "$candidate" != "$cgit_web_root/cgit.png" ]; then
                cp -f "$candidate" "$cgit_web_root/cgit.png"
            fi
            break
        fi
    done

    if [ -d "$cgit_src_dir/filters" ]; then
        cp -a "$cgit_src_dir/filters/." "$cgit_filters_dir/" 2>/dev/null || true
    fi

    cp -f "$cgit_build_config" "$cgit_web_root/cgit.conf"

    chmod 644 "$cgit_web_root/cgit.css" "$cgit_web_root/cgit.png" 2>/dev/null || true
    chmod 644 "$cgit_web_root/cgit.conf" 2>/dev/null || true
    chown -R "$app:$app" "$cgit_web_root"
    chmod 755 "$cgit_web_root" "$cgit_runtime_dir" "$cgit_filters_dir"
    chmod 775 "$cgit_cache_dir"
    ynh_app_setting_set --key=cgit_cgi_path --value="$cgit_cgi_path"
    ynh_app_setting_set --key=cgit_web_root --value="$cgit_web_root"
}

setup_gitolite() {
    ynh_script_progression "Installing gitolite runtime..."
    mkdir -p "$gitolite_home_dir/bin"

    if [ -x "$gitolite_src_dir/install" ]; then
        "$gitolite_src_dir/install" -to "$gitolite_home_dir/bin"
    fi

    if [ ! -x "$gitolite_cmd" ] && command -v gitolite >/dev/null 2>&1; then
        gitolite_cmd="$(command -v gitolite)"
    fi

    [ -x "$gitolite_cmd" ] || ynh_die --message="Unable to find gitolite executable."

    ynh_app_setting_set --key=gitolite_cmd --value="$gitolite_cmd"

    if [ ! -f "$gitolite_home_dir/.gitolite.rc" ]; then
        if [ ! -f "$admin_ssh_key_file" ]; then
            ynh_die --message="Admin SSH key is required to initialize gitolite."
        fi

        ynh_exec_as "$app" env HOME="$gitolite_home_dir" "$gitolite_cmd" setup -pk "$admin_ssh_key_file"
    fi

    chown -R "$app:$app" "$gitolite_home_dir"
}

grant_web_access() {
    ynh_script_progression "Granting web access to cgit and repositories..."
    local acl_users="www-data fcgiwrap ${fcgiwrap_user}"
    local acl_user=""

    if command -v setfacl >/dev/null 2>&1; then
        for acl_user in $acl_users; do
            [ -n "$acl_user" ] || continue
            if id -u "$acl_user" >/dev/null 2>&1; then
                setfacl -R -m "u:${acl_user}:rx" "$cgit_web_root" "$gitolite_home_dir" "$install_dir" "$cgit_src_dir" || true
                setfacl -m "u:${acl_user}:rwx" "$cgit_cache_dir" || true
                setfacl -d -m "u:${acl_user}:rwx" "$cgit_cache_dir" || true
                setfacl -m "u:${acl_user}:rx" "$repositories_dir" || true
                setfacl -d -m "u:${acl_user}:rx" "$repositories_dir" || true
            fi
        done
    else
        chmod o+rx "$install_dir" "$cgit_src_dir" "$cgit_src_dir/cgi" "$cgit_web_root" "$cgit_runtime_dir" "$cgit_filters_dir" "$gitolite_home_dir" "$repositories_dir" || true
        chgrp "$fcgiwrap_user" "$cgit_cache_dir" 2>/dev/null || chgrp www-data "$cgit_cache_dir" 2>/dev/null || true
        chmod 775 "$cgit_cache_dir" || true
    fi
}

prepare_cgit_entrypoint() {
    local candidate=""
    mkdir -p "$cgit_runtime_dir"
    [ -x "$cgit_cgi_path" ] && {
        ynh_app_setting_set --key=cgit_cgi_path --value="$cgit_cgi_path"
        return
    }

    for candidate in \
        "/usr/lib/cgit/cgit.cgi" \
        "/usr/lib/cgi-bin/cgit.cgi"
    do
        if [ -x "$candidate" ]; then
            cp -f "$candidate" "$cgit_cgi_path"
            chmod 755 "$cgit_cgi_path"
            chown "$app:$app" "$cgit_cgi_path"
            ynh_app_setting_set --key=cgit_cgi_path --value="$cgit_cgi_path"
            return
        fi
    done

    ynh_die --message="Unable to find cgit.cgi. Ensure cgit source/package provides a CGI binary."
}

write_cgit_config() {
    mkdir -p "$cgit_cache_dir"

    cat > "$cgit_config_path" << EOF
virtual-root=/
scan-path=$repositories_dir
enable-http-clone=1
cache-root=$cgit_cache_dir
cache-size=1000
css=/cgit.css
logo=/cgit.png
EOF

    chmod 644 "$cgit_config_path"
    chown "$app:$app" "$cgit_config_path"
    ynh_app_setting_set --key=cgit_config_path --value="$cgit_config_path"
}

ensure_fcgiwrap_running() {
    if systemctl list-unit-files --type=socket | grep -q "^fcgiwrap.socket"; then
        systemctl enable --now fcgiwrap.socket >/dev/null 2>&1 || ynh_die --message="Unable to start fcgiwrap.socket."
    elif systemctl list-unit-files --type=service | grep -q "^fcgiwrap.service"; then
        systemctl enable --now fcgiwrap.service >/dev/null 2>&1 || ynh_die --message="Unable to start fcgiwrap.service."
    else
        ynh_die --message="fcgiwrap unit not found on system."
    fi

    ynh_app_setting_set --key=fcgiwrap_socket --value="$fcgiwrap_socket"
}

detect_fcgiwrap_user() {
    local detected_user=""
    detected_user="$(systemctl show --property=User --value fcgiwrap.service 2>/dev/null || true)"

    if [ -n "$detected_user" ]; then
        fcgiwrap_user="$detected_user"
    elif id -u fcgiwrap >/dev/null 2>&1; then
        fcgiwrap_user="fcgiwrap"
    fi

    ynh_app_setting_set --key=fcgiwrap_user --value="$fcgiwrap_user"
}

select_working_cgit_cgi() {
    ynh_script_progression "Selecting a working cgit CGI executable..."
    local candidate=""
    local chosen=""

    for candidate in \
        "/usr/lib/cgit/cgit.cgi" \
        "/usr/lib/cgi-bin/cgit.cgi"
    do
        [ -x "$candidate" ] || continue
        if id -u "$fcgiwrap_user" >/dev/null 2>&1; then
            if ynh_exec_as "$fcgiwrap_user" test -x "$candidate" >/dev/null 2>&1; then
                chosen="$candidate"
                break
            fi
        else
            chosen="$candidate"
            break
        fi
    done

    [ -n "$chosen" ] || ynh_die --message="No system cgit.cgi is executable for fcgiwrap user '$fcgiwrap_user' (tried /usr/lib/cgit/cgit.cgi and /usr/lib/cgi-bin/cgit.cgi)."
    cgit_cgi_path="$chosen"
    ynh_app_setting_set --key=cgit_cgi_path --value="$cgit_cgi_path"
}

prepare_web_runtime() {
    ynh_script_progression "Preparing cgit runtime configuration..."
    detect_git_http_backend
    detect_fcgiwrap_user
    build_cgit_runtime
    setup_gitolite
    prepare_cgit_entrypoint
    select_working_cgit_cgi
    write_cgit_config
    grant_web_access
    ensure_fcgiwrap_running
}
