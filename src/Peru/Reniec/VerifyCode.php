<?php

declare(strict_types=1);

namespace Peru\Reniec;

/**
 * Dígito Verificador oficial de RENIEC mediante algoritmo ponderado.
 */
final class VerifyCode
{
    /**
     * Calcula el dígito verificador de un DNI de 8 dígitos.
     *
     * @param string $dni
     *
     * @return string
     */
    public static function calculate(string $dni): string
    {
        $suma = 5;
        $hash = [3, 2, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 8; ++$i) {
            $suma += ((int) $dni[$i]) * $hash[$i];
        }
        $entero = (int) ($suma / 11);
        $digito = 11 - ($suma - $entero * 11);

        return (string) ($digito > 9 ? $digito - 10 : $digito);
    }
}