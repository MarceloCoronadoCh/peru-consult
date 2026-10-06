#!/bin/sh

set -e

echo "Descargando padrón de retenciones SUNAT..."
php scripts/download_retention.php || echo "Advertencia: Falló la descarga del padrón, continuando..."

echo "Construyendo padrón RUC offline (SQLite)..."
php scripts/download_padron_ruc.php || echo "Advertencia: Falló la construcción del padrón RUC, continuando con consulta online..."

exec /usr/bin/supervisord -c /etc/supervisord.conf