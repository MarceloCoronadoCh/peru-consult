<?php

namespace Peru\Sunat;

/**
 * Class Retention.
 */
class Retention
{
    /**
     * @var string
     */
    private $path;

    /**
     * Retention constructor.
     *
     * @param string $path Path to the retention agents text file.
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Get Retention Agent by RUC.
     *
     * @param string $ruc
     *
     * @return UserRetention|null
     */
    public function get(string $ruc): ?UserRetention
    {
        if (!file_exists($this->path)) {
            return null;
        }

        // Si el archivo es PHP, lo requerimos (aprovecha OPcache)
        if (pathinfo($this->path, PATHINFO_EXTENSION) === 'php') {
            $agents = require $this->path;
            
            if (isset($agents[$ruc])) {
                $data = $agents[$ruc];
                $user = new UserRetention();
                $user->ruc = $ruc;
                $user->razonSocial = $data['razonSocial'];
                $user->fechaApartir = $data['fechaApartir'];
                $user->resolucion = $data['resolucion'];

                return $user;
            }

            return null;
        }

        // Fallback para TXT (compatibilidad con versiones anteriores)
        $content = file_get_contents($this->path);
        if ($content === false) {
            return null;
        }

        $data = explode('|', $content);
        $count = count($data);

        for ($i = 4; $i + 3 < $count; $i += 4) {
            $currentRuc = preg_replace('/[^0-9]/', '', $data[$i]);
            
            if ($currentRuc === $ruc) {
                $user = new UserRetention();
                $user->ruc = $currentRuc;
                $user->razonSocial = trim($data[$i + 1]);
                $user->fechaApartir = trim($data[$i + 2]);
                $user->resolucion = trim($data[$i + 3]);

                return $user;
            }
        }

        return null;
    }
}
