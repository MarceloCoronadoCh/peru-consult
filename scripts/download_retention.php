<?php

$url = 'https://ww1.sunat.gob.pe/descarga/AgentRet/AgenRet_TXT.zip';
$dataDir = __DIR__ . '/../data';
$zipPath = $dataDir . '/agentRet.zip';
$txtPath = $dataDir . '/agentRet.txt';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

echo "Iniciando descarga de padrón de SUNAT...\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Error al descargar el archivo. HTTP Status: $httpCode\n");
}

file_put_contents($zipPath, $data);
echo "Archivo ZIP descargado.\n";

$zip = new ZipArchive();
if ($zip->open($zipPath) === true) {
    $fileNameInZip = $zip->getNameIndex(0);
    $zip->extractTo($dataDir);
    $zip->close();

    rename($dataDir . '/' . $fileNameInZip, $txtPath);
    unlink($zipPath);
    echo "Padrón actualizado: $txtPath\n";

    // --- Inicia conversión a PHP Array ---
    echo "Convirtiendo padrón a PHP Array...\n";
    $content = file_get_contents($txtPath);
    if ($content === false) {
        die("Error al leer el archivo TXT para conversión.\n");
    }

    $data = explode('|', $content);
    $count = count($data);
    $agents = [];

    // Saltamos los primeros 4 que son el encabezado (RUC|Nombre|Fecha|Resolucion)
    for ($i = 4; $i + 3 < $count; $i += 4) {
        $ruc = preg_replace('/[^0-9]/', '', $data[$i]);
        if (strlen($ruc) !== 11) {
            continue;
        }

        $agents[$ruc] = [
            'razonSocial' => trim($data[$i + 1]),
            'fechaApartir' => trim($data[$i + 2]),
            'resolucion' => trim($data[$i + 3]),
        ];
    }

    $phpContent = "<?php\n\nreturn " . var_export($agents, true) . ";\n";
    file_put_contents($dataDir . '/agentRet.php', $phpContent);
    echo "PHP Array generado: " . $dataDir . "/agentRet.php\n";
} else {
    die("No se pudo abrir el archivo ZIP.\n");
}
