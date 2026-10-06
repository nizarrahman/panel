#!/bin/bash

# =============================================
#   Nizarctyl Panel Installer
#   Berbasis Pterodactyl Panel 1.15.1
#   Dengan fitur: Register, Billing, Midtrans
# =============================================

set -e

# ---- Warna ----
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# ---- Variabel ----
INSTALL_DIR="/var/www/nizarctyl"
GITHUB_REPO="https://github.com/nizarrahman/panel/archive/refs/heads/main.zip"

# =============================================
# FUNGSI HELPER
# =============================================

print_banner() {
    echo ""
    echo -e "${CYAN}${BOLD}"
    echo "  _   _ _                      _       _ "
    echo " | \ | (_)______ _ _ _____ __ | |_ _  | |"
    echo " |  \| | |_ / _\` | '_/ __| || |  _| || | |"
    echo " | |\  | |/ / (_| | | \__ \__ _\  ||_|\_, |"
    echo " |_| \_|_/___\__,_|_| |___/    |_|   |__/ "
    echo ""
    echo -e "${NC}${BOLD}         Panel Installer v1.0${NC}"
    echo -e "${CYAN}  Berbasis Pterodactyl 1.15.1 + Billing Midtrans${NC}"
    echo ""
}

print_success() { echo -e "${GREEN}[✓]${NC} $1"; }
print_error()   { echo -e "${RED}[✗]${NC} $1"; }
print_info()    { echo -e "${BLUE}[i]${NC} $1"; }
print_warn()    { echo -e "${YELLOW}[!]${NC} $1"; }
print_step()    { echo -e "\n${BOLD}${CYAN}>>> $1${NC}"; }

ask() {
    # ask "Pertanyaan" VARIABLE_NAME "default"
    local prompt="$1"
    local var="$2"
    local default="$3"

    if [ -n "$default" ]; then
        read -rp "$(echo -e "${YELLOW}?${NC} ${prompt} [${default}]: ")" input
        eval "$var=\"${input:-$default}\""
    else
        read -rp "$(echo -e "${YELLOW}?${NC} ${prompt}: ")" input
        eval "$var=\"$input\""
    fi
}

ask_password() {
    local prompt="$1"
    local var="$2"
    read -rsp "$(echo -e "${YELLOW}?${NC} ${prompt}: ")" input
    echo ""
    eval "$var=\"$input\""
}

ask_yn() {
    local prompt="$1"
    local var="$2"
    local default="${3:-y}"
    read -rp "$(echo -e "${YELLOW}?${NC} ${prompt} [Y/n]: ")" input
    input="${input:-$default}"
    if [[ "$input" =~ ^[Yy]$ ]]; then
        eval "$var=true"
    else
        eval "$var=false"
    fi
}

check_root() {
    if [ "$(id -u)" != "0" ]; then
        print_error "Script ini harus dijalankan sebagai root!"
        echo "  Gunakan: sudo bash install.sh"
        exit 1
    fi
}

check_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$ID
        OS_VER=$VERSION_ID
    else
        print_error "Tidak dapat mendeteksi OS."
        exit 1
    fi

    case "$OS" in
        ubuntu)
            if [[ "$OS_VER" != "20.04" && "$OS_VER" != "22.04" && "$OS_VER" != "24.04" ]]; then
                print_warn "Ubuntu $OS_VER belum diuji. Lanjutkan dengan risiko sendiri."
            fi
            ;;
        debian)
            if [[ "$OS_VER" != "11" && "$OS_VER" != "12" ]]; then
                print_warn "Debian $OS_VER belum diuji."
            fi
            ;;
        *)
            print_error "OS $OS tidak didukung. Gunakan Ubuntu 20.04/22.04 atau Debian 11/12."
            exit 1
            ;;
    esac

    print_success "OS terdeteksi: $OS $OS_VER"
}

# =============================================
# LANGKAH INSTALASI
# =============================================

step_collect_info() {
    print_step "Konfigurasi Instalasi"
    echo ""

    ask "Domain panel (contoh: panel.domain.com)" DOMAIN ""
    ask "Email admin (untuk SSL & akun pertama)" ADMIN_EMAIL ""
    ask "Username admin" ADMIN_USERNAME "admin"
    ask "Nama depan admin" ADMIN_FIRSTNAME "Admin"
    ask "Nama belakang admin" ADMIN_LASTNAME "User"
    ask_password "Password admin" ADMIN_PASSWORD

    echo ""
    print_info "Konfigurasi Database:"
    ask "Nama database" DB_NAME "nizarctyl"
    ask "Username database" DB_USER "nizarctyl"
    ask_password "Password database" DB_PASS

    echo ""
    print_info "Konfigurasi Mail (opsional, bisa diubah nanti):"
    ask "SMTP Host" MAIL_HOST "smtp.gmail.com"
    ask "SMTP Port" MAIL_PORT "587"
    ask "SMTP Username" MAIL_USER ""
    ask_password "SMTP Password" MAIL_PASS
    ask "Email pengirim" MAIL_FROM "${ADMIN_EMAIL}"

    echo ""
    print_info "Fitur Tambahan:"
    ask_yn "Aktifkan registrasi user?" ALLOW_REGISTER
    ask_yn "Setup Midtrans Billing?" SETUP_MIDTRANS

    if [ "$SETUP_MIDTRANS" = "true" ]; then
        ask "Midtrans Server Key" MIDTRANS_SERVER_KEY ""
        ask "Midtrans Client Key" MIDTRANS_CLIENT_KEY ""
        ask_yn "Mode Production Midtrans? (n = Sandbox)" MIDTRANS_PROD
    fi

    echo ""
    echo -e "${BOLD}Ringkasan instalasi:${NC}"
    echo -e "  Domain      : ${CYAN}$DOMAIN${NC}"
    echo -e "  Admin Email : ${CYAN}$ADMIN_EMAIL${NC}"
    echo -e "  Database    : ${CYAN}$DB_NAME${NC}"
    echo -e "  Registrasi  : ${CYAN}$ALLOW_REGISTER${NC}"
    echo -e "  Billing     : ${CYAN}$SETUP_MIDTRANS${NC}"
    echo ""
    ask_yn "Lanjutkan instalasi?" CONFIRM
    if [ "$CONFIRM" != "true" ]; then
        print_warn "Instalasi dibatalkan."
        exit 0
    fi
}

step_install_deps() {
    print_step "Menginstall Dependensi Sistem"

    apt update -y -qq
    apt install -y -qq \
        curl wget git unzip zip \
        software-properties-common \
        ca-certificates lsb-release \
        apt-transport-https gnupg2

    print_success "Dependensi dasar terinstall"
}

step_install_php() {
    print_step "Menginstall PHP 8.2"

    case "$OS" in
        ubuntu)
            add-apt-repository ppa:ondrej/php -y > /dev/null 2>&1
            ;;
        debian)
            curl -sSL https://packages.sury.org/php/apt.gpg -o /usr/share/keyrings/php-archive-keyring.gpg
            echo "deb [signed-by=/usr/share/keyrings/php-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
                > /etc/apt/sources.list.d/php.list
            ;;
    esac

    apt update -y -qq
    apt install -y -qq \
        php8.2 php8.2-fpm php8.2-cli \
        php8.2-mysql php8.2-mbstring php8.2-xml \
        php8.2-curl php8.2-zip php8.2-bcmath \
        php8.2-gd php8.2-intl php8.2-readline \
        php8.2-redis php8.2-posix

    print_success "PHP 8.2 terinstall: $(php -v | head -1)"
}

step_install_mysql() {
    print_step "Menginstall MySQL"

    apt install -y -qq mysql-server

    systemctl enable --now mysql

    # Buat database & user
    mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
EOF

    print_success "MySQL terinstall dan database '$DB_NAME' dibuat"
}

step_install_redis() {
    print_step "Menginstall Redis"

    apt install -y -qq redis-server
    systemctl enable --now redis-server

    print_success "Redis terinstall"
}

step_install_nginx() {
    print_step "Menginstall Nginx"

    apt install -y -qq nginx
    systemctl enable --now nginx

    print_success "Nginx terinstall"
}

step_install_composer() {
    print_step "Menginstall Composer"

    if ! command -v composer &> /dev/null; then
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null
    fi

    print_success "Composer terinstall: $(composer --version 2>/dev/null | head -1)"
}

step_download_panel() {
    print_step "Mendownload Panel"

    mkdir -p "$INSTALL_DIR"
    cd "$INSTALL_DIR"

    # Download dari GitHub releases
    print_info "Mendownload Nizarctyl Panel..."
    curl -Lo /tmp/panel.zip "$GITHUB_REPO" 2>/dev/null || {
        print_error "Gagal download dari $GITHUB_REPO"
        print_info "Pastikan GITHUB_REPO di script sudah diupdate dengan URL yang benar."
        exit 1
    }

    unzip -q /tmp/panel.zip -d /tmp/panel_extracted/
    # Pindahkan isi folder ke INSTALL_DIR
    mv /tmp/panel_extracted/*/* "$INSTALL_DIR/" 2>/dev/null || \
    mv /tmp/panel_extracted/panel-main/* "$INSTALL_DIR/" 2>/dev/null || \
    cp -r /tmp/panel_extracted/*/. "$INSTALL_DIR/"

    rm -rf /tmp/panel.zip /tmp/panel_extracted

    print_success "Panel berhasil didownload ke $INSTALL_DIR"
}

step_configure_env() {
    print_step "Mengkonfigurasi Environment"

    cd "$INSTALL_DIR"
    cp .env.example .env

    # Generate app key
    php artisan key:generate --force --quiet

    APP_KEY=$(grep APP_KEY .env | cut -d '=' -f2)

    ALLOW_REG_VAL="false"
    [ "$ALLOW_REGISTER" = "true" ] && ALLOW_REG_VAL="true"

    cat > .env <<EOF
APP_ENV=production
APP_DEBUG=false
APP_KEY=${APP_KEY}
APP_THEME=pterodactyl
APP_TIMEZONE=UTC
APP_URL=https://${DOMAIN}
APP_LOCALE=en
APP_ENVIRONMENT_ONLY=true
ALLOW_REGISTRATION=${ALLOW_REG_VAL}

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=file
QUEUE_CONNECTION=redis
SESSION_DRIVER=file

HASHIDS_SALT=$(openssl rand -hex 8)
HASHIDS_LENGTH=8

MAIL_MAILER=smtp
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT}
MAIL_USERNAME=${MAIL_USER}
MAIL_PASSWORD=${MAIL_PASS}
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=${MAIL_FROM}
MAIL_FROM_NAME="Nizarctyl Panel"
EOF

    print_success ".env dikonfigurasi"
}

step_install_panel() {
    print_step "Menginstall Dependensi Panel (Composer)"

    cd "$INSTALL_DIR"

    composer install \
        --no-dev \
        --optimize-autoloader \
        --ignore-platform-req=ext-posix \
        --no-interaction \
        --quiet

    print_success "Composer install selesai"
}

step_migrate_db() {
    print_step "Migrasi Database"

    cd "$INSTALL_DIR"

    php artisan migrate --force --quiet

    print_success "Database berhasil dimigrasi"

    # Fix external_id jika perlu
    php artisan tinker --execute="
        try {
            \DB::statement('ALTER TABLE users MODIFY external_id VARCHAR(191) NULL DEFAULT NULL');
        } catch(\Exception \$e) {}
    " > /dev/null 2>&1

    # Cache config
    php artisan config:cache --quiet
    php artisan route:cache --quiet
    php artisan view:cache --quiet
    php artisan event:cache --quiet

    print_success "Cache dibuild"
}

step_create_admin() {
    print_step "Membuat Akun Admin"

    cd "$INSTALL_DIR"

    php artisan tinker --execute="
        \Pterodactyl\Services\Users\UserCreationService::class;
        \$service = app(\Pterodactyl\Services\Users\UserCreationService::class);
        \$service->handle([
            'email'      => '${ADMIN_EMAIL}',
            'username'   => '${ADMIN_USERNAME}',
            'name_first' => '${ADMIN_FIRSTNAME}',
            'name_last'  => '${ADMIN_LASTNAME}',
            'password'   => '${ADMIN_PASSWORD}',
            'root_admin' => true,
        ]);
        echo 'Admin created';
    " > /dev/null 2>&1 && print_success "Akun admin dibuat" || \
        print_warn "Akun admin gagal dibuat otomatis. Jalankan: php artisan p:user:make"
}

step_configure_nginx() {
    print_step "Mengkonfigurasi Nginx"

    cat > /etc/nginx/sites-available/nizarctyl <<EOF
server {
    listen 80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ${DOMAIN};

    root ${INSTALL_DIR}/public;
    index index.php;

    access_log /var/log/nginx/nizarctyl.access.log;
    error_log  /var/log/nginx/nizarctyl.error.log error;

    client_max_body_size 100m;
    client_body_timeout 120s;

    ssl_certificate     /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    ssl_session_cache   shared:SSL:10m;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header Referrer-Policy same-origin;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)\$;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize = 100M \n post_max_size = 100M";
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param HTTP_PROXY "";
        fastcgi_intercept_errors off;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

    ln -sf /etc/nginx/sites-available/nizarctyl /etc/nginx/sites-enabled/nizarctyl
    rm -f /etc/nginx/sites-enabled/default

    print_success "Konfigurasi Nginx dibuat"
}

step_install_ssl() {
    print_step "Menginstall SSL (Let's Encrypt)"

    apt install -y -qq certbot python3-certbot-nginx

    # Stop nginx sementara untuk standalone certbot
    systemctl stop nginx

    certbot certonly \
        --standalone \
        --non-interactive \
        --agree-tos \
        --email "$ADMIN_EMAIL" \
        -d "$DOMAIN" || {
            print_warn "SSL gagal diinstall. Nginx akan berjalan tanpa SSL dulu."
            # Fallback ke HTTP only
            cat > /etc/nginx/sites-available/nizarctyl <<EOF2
server {
    listen 80;
    server_name ${DOMAIN};

    root ${INSTALL_DIR}/public;
    index index.php;

    access_log /var/log/nginx/nizarctyl.access.log;
    error_log  /var/log/nginx/nizarctyl.error.log error;

    client_max_body_size 100m;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)\$;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param HTTP_PROXY "";
    }

    location ~ /\.ht { deny all; }
}
EOF2
        }

    systemctl start nginx
    print_success "SSL dikonfigurasi"
}

step_setup_supervisor() {
    print_step "Mengkonfigurasi Queue Worker (Supervisor)"

    apt install -y -qq supervisor

    cat > /etc/supervisor/conf.d/nizarctyl-worker.conf <<EOF
[program:nizarctyl-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${INSTALL_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/nizarctyl-worker.log
stopwaitsecs=3600
EOF

    supervisorctl reread > /dev/null 2>&1
    supervisorctl update > /dev/null 2>&1
    supervisorctl start nizarctyl-worker:* > /dev/null 2>&1 || true

    print_success "Queue worker dikonfigurasi"
}

step_setup_cron() {
    print_step "Mengkonfigurasi Cron Job"

    CRON_JOB="* * * * * php ${INSTALL_DIR}/artisan schedule:run >> /dev/null 2>&1"
    (crontab -l 2>/dev/null | grep -v "nizarctyl"; echo "$CRON_JOB") | crontab -

    print_success "Cron job ditambahkan"
}

step_set_permissions() {
    print_step "Mengatur Permission"

    chown -R www-data:www-data "$INSTALL_DIR"
    chmod -R 755 "$INSTALL_DIR/storage"
    chmod -R 755 "$INSTALL_DIR/bootstrap/cache"

    print_success "Permission diatur"
}

step_setup_billing() {
    print_step "Mengkonfigurasi Billing Midtrans"

    cd "$INSTALL_DIR"

    MIDTRANS_PROD_VAL="0"
    [ "$MIDTRANS_PROD" = "true" ] && MIDTRANS_PROD_VAL="1"

    php artisan tinker --execute="
        \DB::table('billing_settings')->updateOrInsert(['key' => 'billing_enabled'],     ['value' => '1', 'created_at' => now(), 'updated_at' => now()]);
        \DB::table('billing_settings')->updateOrInsert(['key' => 'midtrans_server_key'], ['value' => '${MIDTRANS_SERVER_KEY}', 'created_at' => now(), 'updated_at' => now()]);
        \DB::table('billing_settings')->updateOrInsert(['key' => 'midtrans_client_key'], ['value' => '${MIDTRANS_CLIENT_KEY}', 'created_at' => now(), 'updated_at' => now()]);
        \DB::table('billing_settings')->updateOrInsert(['key' => 'midtrans_production'], ['value' => '${MIDTRANS_PROD_VAL}', 'created_at' => now(), 'updated_at' => now()]);
        \DB::table('billing_settings')->updateOrInsert(['key' => 'shop_title'],          ['value' => 'Shop', 'created_at' => now(), 'updated_at' => now()]);
        echo 'Billing configured';
    " > /dev/null 2>&1

    print_success "Billing Midtrans dikonfigurasi"
    print_info "Daftarkan webhook URL ini di Midtrans Dashboard:"
    echo -e "     ${CYAN}https://${DOMAIN}/billing/webhook/midtrans${NC}"
}

print_summary() {
    echo ""
    echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}${BOLD}║       INSTALASI BERHASIL! 🎉                ║${NC}"
    echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${BOLD}Akses Panel:${NC}"
    echo -e "  URL     : ${CYAN}https://${DOMAIN}${NC}"
    echo -e "  Email   : ${CYAN}${ADMIN_EMAIL}${NC}"
    echo -e "  Username: ${CYAN}${ADMIN_USERNAME}${NC}"
    echo ""
    echo -e "${BOLD}Langkah Selanjutnya:${NC}"
    echo -e "  1. Buka ${CYAN}https://${DOMAIN}/admin${NC} dan login"
    echo -e "  2. Tambahkan ${BOLD}Location${NC} di Admin → Locations"
    echo -e "  3. Tambahkan ${BOLD}Node${NC} di Admin → Nodes"
    echo -e "  4. Tambahkan ${BOLD}Allocation${NC} di node (IP:port)"
    if [ "$SETUP_MIDTRANS" = "true" ]; then
        echo -e "  5. Daftarkan webhook di Midtrans Dashboard:"
        echo -e "     ${CYAN}https://${DOMAIN}/billing/webhook/midtrans${NC}"
        echo -e "  6. Buat plan di ${CYAN}https://${DOMAIN}/admin/billing/plans${NC}"
    fi
    echo ""
    echo -e "${BOLD}Perintah Berguna:${NC}"
    echo -e "  Lihat log  : ${CYAN}tail -f ${INSTALL_DIR}/storage/logs/laravel-\$(date +%Y-%m-%d).log${NC}"
    echo -e "  Queue log  : ${CYAN}tail -f /var/log/supervisor/nizarctyl-worker.log${NC}"
    echo -e "  Restart all: ${CYAN}supervisorctl restart nizarctyl-worker:* && systemctl reload nginx${NC}"
    echo ""
}

# =============================================
# MAIN
# =============================================

main() {
    print_banner
    check_root
    check_os
    step_collect_info

    echo ""
    print_info "Memulai instalasi..."
    echo ""

    step_install_deps
    step_install_php
    step_install_mysql
    step_install_redis
    step_install_nginx
    step_install_composer
    step_download_panel
    step_configure_env
    step_install_panel
    step_migrate_db
    step_set_permissions
    step_install_ssl
    step_configure_nginx
    systemctl reload nginx
    step_setup_supervisor
    step_setup_cron
    step_create_admin

    if [ "$SETUP_MIDTRANS" = "true" ]; then
        step_setup_billing
    fi

    print_summary
}

main
