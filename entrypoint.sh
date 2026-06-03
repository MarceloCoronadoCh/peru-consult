#!/bin/sh

set -e

echo "Descargando padrón de retenciones SUNAT..."
php scripts/download_retention.php || echo "Advertencia: Falló la descarga del padrón, continuando..."

exec /usr/bin/supervisord -c /etc/supervisord.conf