<?php

namespace Peru\Jne;

use Peru\Reniec\Person;
use Peru\Reniec\VerifyCode;

class DniParser
{
    public function parse(string $dni, $data): ?Person
    {
        $person = new Person();
        $person->dni = $dni;
        $person->apellidoPaterno = $data->apePatSoli;
        $person->apellidoMaterno = $data->apeMatSoli;
        $person->nombres = $data->nombreSoli;
        $person->codVerifica = strval($this->getVerifyCode($dni));

        return $person;
    }

    private function getVerifyCode($dni)
    {
        return VerifyCode::calculate($dni);
    }
}
