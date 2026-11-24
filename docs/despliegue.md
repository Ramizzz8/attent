# Despliegue en el VPS (attent.andresrami.com)

Guía para publicar ATTENT en el VPS de Hetzner que ya sirve otras webs con Caddy. Requiere acceso SSH al servidor y poder crear un registro DNS.

## 1. DNS

Registro `A` (y `AAAA` si el VPS tiene IPv6) para `attent.andresrami.com` apuntando a la IP del VPS.

## 2. Paquetes del servidor (Debian/Ubuntu)

```bash
sudo apt install php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath postgresql unzip
# Composer y Node 24 si no están ya instalados
```

### Límites de subida de PHP

Los adjuntos admiten hasta 5 archivos de 5 MB. Los valores por defecto de PHP (2 MB / 8 MB) se quedan cortos. En `/etc/php/8.4/fpm/conf.d/99-attent.ini`:

```ini
upload_max_filesize = 6M
post_max_size = 30M
```

`sudo systemctl restart php8.4-fpm`

## 3. Base de datos

```bash
sudo -u postgres createuser attent --pwprompt
sudo -u postgres createdb attent --owner=attent
```

## 4. Código

```bash
sudo mkdir -p /var/www/attent && sudo chown $USER:www-data /var/www/attent
git clone <repositorio> /var/www/attent     # o subir con rsync desde C:\dev\attent
cd /var/www/attent
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env && php artisan key:generate
```

`.env` de producción (valores clave):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://attent.andresrami.com
APP_LOCALE=es

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=attent
DB_USERNAME=attent
DB_PASSWORD=<la del paso 3>

QUEUE_CONNECTION=database
MAIL_MAILER=smtp            # datos del proveedor SMTP (Brevo, Resend, el del dominio…)
MAIL_FROM_ADDRESS=no-responder@andresrami.com

BROADCAST_CONNECTION=reverb
REVERB_HOST=attent.andresrami.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
```

Tras cambiar las variables `VITE_*` (las de Reverb), hay que volver a ejecutar `npm run build`.

```bash
php artisan migrate --force
php artisan db:seed --force      # carga la demo (solo la primera vez)
php artisan storage:link
php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 5. Caddy

```caddy
attent.andresrami.com {
    root * /var/www/attent/public
    encode zstd gzip

    # WebSockets de Reverb
    @reverb path /app/* /apps/*
    reverse_proxy @reverb 127.0.0.1:8080

    php_fastcgi unix//run/php/php8.4-fpm.sock
    file_server
}
```

`sudo systemctl reload caddy`

## 6. Servicios (systemd)

`/etc/systemd/system/attent-cola.service`

```ini
[Unit]
Description=ATTENT - cola de trabajos
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/attent
ExecStart=/usr/bin/php artisan queue:work --sleep=1 --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

`/etc/systemd/system/attent-reverb.service`

```ini
[Unit]
Description=ATTENT - servidor Reverb
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/attent
ExecStart=/usr/bin/php artisan reverb:start --host=127.0.0.1 --port=8080
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now attent-cola attent-reverb
```

## 7. Tareas programadas

`sudo crontab -u www-data -e`

```cron
* * * * * cd /var/www/attent && php artisan schedule:run >> /dev/null 2>&1
```

(Cierra cada noche las incidencias resueltas hace más de 7 días.)

## 8. Comprobación

1. `https://attent.andresrami.com/talleres-ruiz`, `/fincas-levante` y `/soporte-tic` cargan.
2. Entrar en `/login` con `admin@talleres-ruiz.test` / `password`.
3. Abrir una incidencia desde el portal con un email real: llega el email de confirmación.
4. Con el panel abierto en otra pestaña, la incidencia confirmada aparece con un aviso en tiempo real.

## Actualizar

```bash
cd /var/www/attent && git pull
composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan migrate --force && php artisan optimize
sudo systemctl restart attent-cola attent-reverb
```
