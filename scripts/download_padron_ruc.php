<?php
/**
 * Descarga el Padrón Reducido de RUC de SUNAT (zip ~393MB) y construye
 * data/padron_ruc.sqlite para consultas offline instantáneas.
 *
 * El padrón oficial se actualiza a diario y NO tiene bloqueo Cloudflare,
 * a diferencia de la consulta web de e-consultaruc.sunat.gob.pe.
 *
 * Uso: php scripts/download_padron_ruc.php
 */

declare(strict_types=1);

const ZIP_URL = 'http://www2.sunat.gob.pe/padron_reducido_ruc.zip';

$dataDir = __DIR__ . '/../data';
$zipPath = $dataDir . '/padron_reducido_ruc.zip';
$txtPath = $dataDir . '/padron_reducido_ruc.txt';
$dbPath = $dataDir . '/padron_ruc.sqlite';
$doneFile = $dbPath . '.ready';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

echo '[' . date('H:i:s') . "] Iniciando construcción del padrón RUC\n";

// 1) Descargar zip solo si falta el TXT descomprimido
if (!is_file($txtPath)) {
    echo '[' . date('H:i:s') . "] Descargando padrón...\n";
    $ch = curl_init(ZIP_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $data === false) {
        fwrite(STDERR, '[' . date('H:i:s') . "] ERROR descargando padrón. HTTP: {$httpCode}\n");
        exit(1);
    }
    file_put_contents($zipPath, $data);
    echo '[' . date('H:i:s') . "] ZIP descargado (" . round(strlen($data) / 1048576) . " MB)\n";

    // 2) Descomprimir
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        fwrite(STDERR, '[' . date('H:i:s') . "] ERROR: no se pudo abrir el ZIP\n");
        exit(1);
    }
    $inner = $zip->getNameIndex(0);
    $zip->extractTo($dataDir);
    $zip->close();
    rename($dataDir . '/' . $inner, $txtPath);
    unlink($zipPath);
    echo '[' . date('H:i:s') . "] TXT extraído\n";
} else {
    echo '[' . date('H:i:s') . "] TXT ya existe, se omite descarga\n";
}

// 3) Construir SQLite solo si el TXT es más nuevo que la DB
if (is_file($dbPath) && filemtime($txtPath) <= filemtime($dbPath)) {
    @touch($doneFile);
    echo '[' . date('H:i:s') . "] SQLite ya está actualizado\n";
    exit(0);
}

$tmpPath = $dbPath . '.tmp';
@unlink($tmpPath);

$db = new SQLite3($tmpPath);
$db->exec('PRAGMA journal_mode = OFF');
$db->exec('PRAGMA synchronous = OFF');
$db->exec('PRAGMA cache_size = -64000');
$db->exec('CREATE TABLE padron (
    ruc TEXT PRIMARY KEY,
    razon TEXT,
    estado TEXT,
    condicion TEXT,
    ubigeo TEXT,
    direccion TEXT
) WITHOUT ROWID');

$stmt = $db->prepare('INSERT INTO padron (ruc, razon, estado, condicion, ubigeo, direccion)
    VALUES (:ruc, :razon, :estado, :condicion, :ubigeo, :direccion)');

$fh = fopen($txtPath, 'r');
if ($fh === false) {
    fwrite(STDERR, '[' . date('H:i:s') . "] ERROR: no se pudo leer el TXT\n");
    exit(1);
}
fgetcsv($fh, 0, '|'); // header

$total = 0;
$db->exec('BEGIN');
while (($row = fgetcsv($fh, 0, '|')) !== false) {
    $ruc = preg_replace('/[^0-9]/', '', $row[0] ?? '');
    if (strlen($ruc) !== 11) {
        continue;
    }

    $razon = utf($row[1] ?? '');
    $estado = utf($row[2] ?? '');
    $condicion = utf($row[3] ?? '');
    $ubigeo = preg_match('/^\d{6}$/', $row[4] ?? '') ? $row[4] : '';

    // Dirección: TIPO VIA + NOMBRE VIA + CODIGO ZONA + TIPO/NOMBRE ZONA + NUMERO
    $parts = [];
    foreach ([$row[5], $row[6], $row[7], $row[8], $row[9]] as $p) {
        $p = trim(utf($p ?? ''));
        if ($p !== '' && $p !== '-' && $p !== '----') {
            $parts[] = $p;
        }
    }
    $direccion = implode(' ', $parts);

    $stmt->bindParam(':ruc', $ruc, SQLITE3_TEXT);
    $stmt->bindParam(':razon', $razon, SQLITE3_TEXT);
    $stmt->bindParam(':estado', $estado, SQLITE3_TEXT);
    $stmt->bindParam(':condicion', $condicion, SQLITE3_TEXT);
    $stmt->bindParam(':ubigeo', $ubigeo, SQLITE3_TEXT);
    $stmt->bindParam(':direccion', $direccion, SQLITE3_TEXT);
    $stmt->execute();

    if (++$total % 100000 === 0) {
        $db->exec('COMMIT');
        $db->exec('BEGIN');
        echo '[' . date('H:i:s') . "] {$total} filas...\n";
    }
}
$db->exec('COMMIT');
fclose($fh);
$stmt->close();
$db->close();

if (!rename($tmpPath, $dbPath)) {
    fwrite(STDERR, '[' . date('H:i:s') . "] ERROR renombrando la DB temporal\n");
    exit(1);
}
touch($doneFile);
@unlink($txtPath);

echo '[' . date('H:i:s') . "] LISTO: {$total} RUCs en {$dbPath}\n";

function utf(string $s): string
{
    if (!mb_check_encoding($s, 'UTF-8')) {
        $c = @iconv('windows-1252', 'UTF-8//TRANSLIT', $s);
        return $c === false ? $s : $c;
    }
    return $s;
}