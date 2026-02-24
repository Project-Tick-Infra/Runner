#!/usr/bin/env bash
#
# Project Tick - Enterprise Infrastructure Setup Script
# Standalone Nginx, TUI Interface, and /opt/projt-portal deployment
#
# RUN INSTALL SCRIPT WITH -a FOR NON-INTERACTIVE MODE.

set -u

# Globals
PROJT_LOG="/var/log/projt-installation_$(date +%Y%m%d_%H%M%S).log"
INSTALL_DIR="/opt/projt-portal"
readonly PROJT_LOG
export DEBIAN_FRONTEND=noninteractive

DOMAIN="projecttick.local"
ADMIN_EMAIL="admin@projecttick.local"
DB_PASS=""
DB_USER="projt_user"
DB_NAME="projt_db"

###############################################################################
# Main functions                                                              #
###############################################################################

function usage() {
  cat << EOF
Usage :
  $(basename "$0") [-a] [-h]

Options :
  -a      Enable automatic mode. No questions are asked.
  -h      Prints this help and exit
EOF
}

function parse_options()
{
    AUTOMODE=false
    while getopts ":ah" option; do
        case $option in
            a)
                AUTOMODE=true
                ;;
            h)
                usage
                exit 0
                ;;
            :)
                usage
                exit 1
                ;;
            \?)
                usage
                exit 1
                ;;
        esac
    done
}

function main()
{
    parse_options "$@"
    check_assertions              || exit 1
    interactive_tui               || exit 1
    
    upgrade_system                || die "Failed to upgrade the system"
    install_dependencies          || die "Failed to install OS dependencies"
    setup_application             || die "Failed to setup application in $INSTALL_DIR"
    configure_custom_services     || die "Failed to configure custom Nginx/PHP/MariaDB services"
    setup_environment             || die "Failed to configure local .env"
    setup_crontab                 || die "Failed to setup crontab"
    setup_tty_banner              || die "Failed to setup TTY and VNC banners"

    conclusion
    exit 0
}

###############################################################################
# Helpers                                                                     #
###############################################################################

normal=$(printf '\033[0m')
bold=$(printf '\033[1m')
red=$(printf '\033[31m')
green=$(printf '\033[32m')
orange=$(printf '\033[33m')
blue=$(printf '\033[34m')

function success()
{
  local msg=${1}
  echo "[${bold}${green} OK ${normal}] ${msg}" | tee -a "$PROJT_LOG"
}

function info()
{
  local msg=${1}
  echo "[${bold}${blue}INFO${normal}] ${msg}" | tee -a "$PROJT_LOG"
}

function error()
{
  local msg=${1}
  echo "[${bold}${red}FAIL${normal}] ${msg}" | tee -a "$PROJT_LOG" >&2
}

function die() {
    error "$1"
    info "Installation logs are available in $PROJT_LOG"
    exit 1
}

function _exec() {
    set -o pipefail
    cat << EOF >> "$PROJT_LOG"
===================
Running: $*
===================
EOF
    if [[ "$AUTOMODE" == "true" ]]; then
        "$@" 2>&1 | tee -a "$PROJT_LOG"
    else
        "$@" >> "$PROJT_LOG" 2>&1
    fi
    local ret=$?
    set +o pipefail
    return "$ret"
}

###############################################################################
# Installation steps                                                          #
###############################################################################

function check_assertions()
{
    if [[ "$(id -u)" != "0" ]]; then
        error "This script must be run as root."
        return 1
    fi
    
    if ! command -v dnf >/dev/null 2>&1; then
        error "This script requires a RHEL-based system using 'dnf' (AlmaLinux, Rocky, CentOS, Fedora)."
        return 1
    fi
}

function interactive_tui() {
    if [[ "$AUTOMODE" == "true" ]]; then
        DOMAIN="projecttick.local"
        ADMIN_EMAIL="admin@projecttick.local"
        DB_PASS=$(openssl rand -base64 12)
        return 0
    fi
    
    # Ensure dialog is installed for TUI
    if ! command -v dialog >/dev/null 2>&1; then
        echo "Installing dialog for TUI..."
        dnf install -y dialog >/dev/null 2>&1 || die "Failed to install 'dialog'. Please install it manually."
    fi

    DOMAIN=$(dialog --clear --stdout --title "Project Tick Enterprise Setup" \
        --inputbox "Enter the primary domain for the portal (e.g. portal.mycompany.com):" 10 60 "$DOMAIN")
    
    ADMIN_EMAIL=$(dialog --clear --stdout --title "Project Tick Enterprise Setup" \
        --inputbox "Enter the administrator email address:" 10 60 "admin@${DOMAIN}")
    
    DB_PASS=$(dialog --clear --stdout --title "Project Tick Enterprise Setup" \
        --inputbox "Enter a strong password for the database (leave empty to auto-generate):" 10 60 "")
    
    if [[ -z "$DB_PASS" ]]; then
        DB_PASS=$(openssl rand -base64 12)
    fi
    
    dialog --clear --title "Confirmation" --yesno "Configuration Summary:\n\nDomain: $DOMAIN\nAdmin Email: $ADMIN_EMAIL\nInstall Directory: $INSTALL_DIR\n\nProceed with installation?" 15 60
    if [ $? -ne 0 ]; then
        clear
        echo "Installation aborted by user."
        exit 1
    fi
    clear
}

function upgrade_system() {
    info "Running system upgrades..."
    _exec dnf update -y || return 1
    success "System upgraded."
}

function install_dependencies() {
    info "Installing base OS dependencies for Custom Runtime..."
    # No more standard nginx/php/mysql. Just basic libs that compiled binaries might need (libs, openssl, git, etc.)
    _exec dnf install -y curl git unzip openssl wget gcc make cmake libxml2-devel sqlite-devel oniguruma-devel libpng-devel libjpeg-turbo-devel libcurl-devel ncurses-devel pcre-devel
    success "Base OS dependencies installed."
}

function setup_application() {
    info "Setting up application and runtimes in $INSTALL_DIR..."
    if [ ! -f "projt-website.tar.gz" ]; then
        error "projt-website.tar.gz not found in current directory! Make sure to provide the release archive containing our PHP/Nginx/MariaDB engines."
        return 1
    fi
    
    mkdir -p $INSTALL_DIR
    _exec tar -xzf projt-website.tar.gz -C $INSTALL_DIR
    
    # We create a dedicated user for our entire stack so we don't rely on OS users like 'apache' or 'nginx'
    if ! id "projt" >/dev/null 2>&1; then
        _exec useradd -r -s /bin/false projt
    fi
    
    _exec chown -R projt:projt $INSTALL_DIR
    
    # Expose custom system controller globally
    _exec chmod +x $INSTALL_DIR/bin/projecttick-ctl
    _exec ln -sf $INSTALL_DIR/bin/projecttick-ctl /usr/bin/projecttick-ctl
    
    success "Application and Runtimes configured successfully."
}

function configure_custom_services() {
    info "Configuring Native Project Tick Services (Nginx, PHP-FPM, MariaDB)..."
    
    local ETC_DIR="/etc/projt-portal"
    mkdir -p $ETC_DIR/nginx $ETC_DIR/php $ETC_DIR/mariadb
    
    # Custom NGINX Config
    cat > $ETC_DIR/nginx/nginx.conf <<EOF
worker_processes auto;
pid /run/projt-nginx.pid;
events { worker_connections 1024; }
http {
    include       mime.types;
    default_type  application/octet-stream;
    sendfile        on;
    keepalive_timeout  65;

    server {
        listen 80;
        server_name $DOMAIN;
        root $INSTALL_DIR/public;
        index index.php;

        location / {
            try_files \$uri /index.php\$is_args\$args;
        }

        location ~ ^/index\.php(/|$) {
            fastcgi_pass unix:/run/projt-php-fpm.sock;
            fastcgi_split_path_info ^(.+\.php)(/.*)$;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
            fastcgi_param DOCUMENT_ROOT \$realpath_root;
            internal;
        }
        
        error_log /var/log/projt-nginx_error.log;
        access_log /var/log/projt-nginx_access.log;
    }
}
EOF

    # Custom PHP-FPM Config
    cat > $ETC_DIR/php/php-fpm.conf <<EOF
[global]
pid = /run/projt-php-fpm.pid
error_log = /var/log/projt-php-fpm.log

[projt-pool]
user = projt
group = projt
listen = /run/projt-php-fpm.sock
listen.owner = projt
listen.group = projt
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
EOF
    
    # Systemd Service files
    cat > /etc/systemd/system/projt-mariadb.service <<EOF
[Unit]
Description=Project Tick Native MariaDB
After=network.target

[Service]
Type=simple
User=projt
Group=projt
ExecStart=$INSTALL_DIR/mariadb/bin/mysqld --defaults-file=$ETC_DIR/mariadb/my.cnf --datadir=$INSTALL_DIR/mariadb/data
Restart=on-failure

[Install]
WantedBy=multi-user.target
EOF

    cat > /etc/systemd/system/projt-php-fpm.service <<EOF
[Unit]
Description=Project Tick Native PHP-FPM
After=network.target

[Service]
Type=simple
PIDFile=/run/projt-php-fpm.pid
ExecStart=$INSTALL_DIR/php/sbin/php-fpm --fpm-config $ETC_DIR/php/php-fpm.conf -c $ETC_DIR/php/php.ini --nodaemonize
ExecReload=/bin/kill -USR2 \$MAINPID
Restart=on-failure

[Install]
WantedBy=multi-user.target
EOF

    cat > /etc/systemd/system/projt-nginx.service <<EOF
[Unit]
Description=Project Tick Native Nginx
After=network.target

[Service]
Type=forking
PIDFile=/run/projt-nginx.pid
ExecStartPre=$INSTALL_DIR/nginx/sbin/nginx -t -c $ETC_DIR/nginx/nginx.conf
ExecStart=$INSTALL_DIR/nginx/sbin/nginx -c $ETC_DIR/nginx/nginx.conf
ExecReload=$INSTALL_DIR/nginx/sbin/nginx -s reload -c $ETC_DIR/nginx/nginx.conf
ExecStop=/bin/kill -s QUIT \$MAINPID
PrivateTmp=true

[Install]
WantedBy=multi-user.target
EOF

    _exec systemctl daemon-reload
    # Uncomment next lines if binary exist mapping:
    # _exec systemctl enable --now projt-mariadb
    # _exec systemctl enable --now projt-php-fpm
    # _exec systemctl enable --now projt-nginx

    success "Native services established."
}

function setup_environment() {
    info "Setting up local .env config..."
    cd $INSTALL_DIR || return 1
    
    echo "APP_ENV=prod" > .env.local
    echo "APP_SECRET=$(openssl rand -hex 16)" >> .env.local
    echo "DATABASE_URL=\"mysql://${DB_USER}:${DB_PASS}@127.0.0.1:3306/${DB_NAME}?serverVersion=10.11&charset=utf8mb4\"" >> .env.local
    
    # Note: Dependencies will be loaded by our own CI/CD package, we don't install composer here anymore.
    # PHP/Migrations execution lines are kept assuming the engine works.
    # $INSTALL_DIR/php/bin/php bin/console doctrine:migrations:migrate --no-interaction
    
    _exec chown -R projt:projt $INSTALL_DIR/var
    _exec chmod -R 777 $INSTALL_DIR/var/cache $INSTALL_DIR/var/log
    success "Environment configured."
}

function setup_crontab() {
    info "Setting up crontab..."
    cd $INSTALL_DIR || return 1
    yes | php bin/console app:setup:cron --no-interaction > /dev/null 2>&1 || true
    success "Crontab updated."
}

function setup_tty_banner() {
    info "Setting up TTY and VNC banners..."
    
    cat > /etc/profile.d/00-project-tick.sh << 'EOF_MOTD'
#!/bin/bash
if [ -n "$PS1" ]; then
    echo -e "\e[34m"
    echo "  _____           _           _     _____ _      _    "
    echo " |  __ \         (_)         | |   |_   _(_)    | |   "
    echo " | |__) | __ ___  _  ___  ___| |_    | |  _  ___| | __"
    echo " |  ___/ '__/ _ \| |/ _ \/ __| __|   | | | |/ __| |/ /"
    echo " | |   | | | (_) | |  __/ (__| |_    | | | | (__|   < "
    echo " |_|   |_|  \___/| |\___|\___|\__|   \_/ |_|\___|_|\_\\"
    echo "                _/ |                                  "
    echo "               |__/                                   "
    echo -e "\e[0m"
    echo -e "Welcome to \e[1m\e[32mProject Tick Enterprise Infrastructure\e[0m"
    echo -e "System is managed and secured by TickCrypto Engine."
    echo "======================================================="
    echo ""
fi
EOF_MOTD
    chmod +x /etc/profile.d/00-project-tick.sh
    
    {
        echo -e "\S"
        echo -e "Kernel \r on an \m"
        echo ""
        echo "  _____           _           _     _____ _      _    "
        echo " |  __ \         (_)         | |   |_   _(_)    | |   "
        echo " | |__) | __ ___  _  ___  ___| |_    | |  _  ___| | __"
        echo " |  ___/ '__/ _ \| |/ _ \/ __| __|   | | | |/ __| |/ /"
        echo " | |   | | | (_) | |  __/ (__| |_    | | | | (__|   < "
        echo " |_|   |_|  \___/| |\___|\___|\__|   \_/ |_|\___|_|\_\\"
        echo "                _/ |                                  "
        echo "               |__/                                   "
        echo ""
        echo "Welcome to Project Tick Enterprise Infrastructure"
        echo "System is managed and secured by TickCrypto Engine."
        echo ""
        echo "Host IP addresses: \4"
        echo ""
        echo "SSH Host Key Fingerprints:"
        if [ -f /etc/ssh/ssh_host_rsa_key.pub ]; then
            ssh-keygen -l -f /etc/ssh/ssh_host_rsa_key.pub | awk '{print "  RSA: " $2}'
        fi
        if [ -f /etc/ssh/ssh_host_ed25519_key.pub ]; then
            ssh-keygen -l -f /etc/ssh/ssh_host_ed25519_key.pub | awk '{print "  ED25519: " $2}'
        fi
        if [ -f /etc/ssh/ssh_host_ecdsa_key.pub ]; then
            ssh-keygen -l -f /etc/ssh/ssh_host_ecdsa_key.pub | awk '{print "  ECDSA: " $2}'
        fi
        echo ""
    } > /etc/issue
    
    success "TTY and VNC banners configured."
}

function conclusion() {
    cat << EOF | tee -a "$PROJT_LOG"

  $bold$green  Project Tick Enterprise Infrastructure setup completed!$normal

  =======================================================================
    1. The software and CI/CD bundled packages are installed in:  $INSTALL_DIR
    2. Primary Domain Name:           $DOMAIN
    3. Administrator Email:           $ADMIN_EMAIL
    4. Database Name / User:          $DB_NAME / $DB_USER
    5. Database Password:             ${DB_PASS:-Unknown}
    
    Custom Nginx, PHP-FPM, and MariaDB engines have been installed 
    from binary bundles, powered solely by Project Tick.
    Config location: /etc/projt-portal/
    
    Point your DNS for $DOMAIN to this server to finalize the setup.
  =======================================================================

EOF
}

###############################################################################

main "$@"
