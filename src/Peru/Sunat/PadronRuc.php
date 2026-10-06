<?php
/**
 * Consulta offline del Padrón Reducido de RUC (SQLite).
 *
 * Los datos provienen del padrón oficial de SUNAT, descargado y preparado
 * por scripts/download_padron_ruc.php. Devuelve null si el RUC no existe
 * en el padrón o si la base no está disponible.
 */

declare(strict_types=1);

namespace Peru\Sunat;

final class PadronRuc
{
    /** Código de departamento por prefijo del ubigeo. */
    private const DEPARTAMENTOS = [
        '01' => 'AMAZONAS', '02' => 'ANCASH', '03' => 'APURIMAC', '04' => 'AREQUIPA',
        '05' => 'AYACUCHO', '06' => 'CAJAMARCA', '07' => 'CALLAO', '08' => 'CUSCO',
        '09' => 'HUANCAVELICA', '10' => 'HUANUCO', '11' => 'ICA', '12' => 'JUNIN',
        '13' => 'LA LIBERTAD', '14' => 'LAMBAYEQUE', '15' => 'LIMA', '16' => 'LORETO',
        '17' => 'MADRE DE DIOS', '18' => 'MOQUEGUA', '19' => 'PASCO', '20' => 'PIURA',
        '21' => 'PUNO', '22' => 'SAN MARTIN', '23' => 'TACNA', '24' => 'TUMBES',
        '25' => 'UCAYALI',
    ];

    /** @var string */
    private $dbPath;

    /** @var \SQLite3|null */
    private $db;

    public function __construct(string $dbPath)
    {
        $this->dbPath = $dbPath;
    }

    /**
     * Busca un RUC en el padrón. Devuelve un array o null si no existe.
     *
     * @return null|array{ruc:string,razon:string,estado:string,condicion:string,ubigeo:string,direccion:string}
     */
    public function get(string $ruc): ?array
    {
        if (!$this->open()) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT ruc, razon, estado, condicion, ubigeo, direccion
            FROM padron WHERE ruc = :ruc');
        $stmt->bindParam(':ruc', $ruc, SQLITE3_TEXT);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $res->finalize();
        $stmt->close();

        return $row === false ? null : $row;
    }

    /**
     * Marca la DB como lista (crea el archivo .ready con la fecha actual).
     */
    public static function markReady(string $dbPath): void
    {
        @touch($dbPath . '.ready');
    }

    /**
     * Indica si la DB está disponible y construida.
     */
    public function available(): bool
    {
        return is_file($this->dbPath) && is_file($this->dbPath . '.ready');
    }

    /**
     * Devuelve el departamento a partir del ubigeo, o null.
     */
    public static function departamento(?string $ubigeo): ?string
    {
        if ($ubigeo === null || strlen($ubigeo) < 2) {
            return null;
        }

        return self::DEPARTAMENTOS[substr($ubigeo, 0, 2)] ?? null;
    }

    private function open(): bool
    {
        if ($this->db instanceof \SQLite3) {
            return true;
        }

        if (!is_file($this->dbPath) || !class_exists('\SQLite3')) {
            return false;
        }

        try {
            $this->db = new \SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
            $this->db->exec('PRAGMA cache_size = -16000');

            return true;
        } catch (\Exception $e) {
            $this->db = null;

            return false;
        }
    }
}