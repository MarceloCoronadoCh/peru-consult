<?php

declare(strict_types=1);

namespace Peru\Dni;

use Peru\Reniec\Person;
use Peru\Services\DniInterface;

/**
 * Estrategia Fallback: consulta el DNI al primer proveedor que devuelva datos.
 */
final class DniFallback implements DniInterface
{
    /**
     * @var DniInterface[]
     */
    private $providers;

    /**
     * DniFallback constructor.
     *
     * @param DniInterface[] $providers proveedores en orden de prioridad
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * Get Person Information by DNI.
     *
     * @param string $dni
     *
     * @return Person|null
     */
    public function get(string $dni): ?Person
    {
        foreach ($this->providers as $provider) {
            $person = $provider->get($dni);

            if ($person !== null) {
                return $person;
            }
        }

        return null;
    }
}