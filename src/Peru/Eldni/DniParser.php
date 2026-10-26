<?php

declare(strict_types=1);

namespace Peru\Eldni;

use DOMElement;
use DOMXPath;
use Peru\Reniec\Person;
use Peru\Reniec\VerifyCode;
use Peru\Sunat\Parser\XpathLoader;

class DniParser
{
    public function parse(string $dni, string $html): ?Person
    {
        $xp = XpathLoader::getXpathFromHtml($html);
        $rows = $xp->query('//table//tbody/tr');

        foreach ($rows as $row) {
            if (!$row instanceof DOMElement) {
                continue;
            }

            $cells = [];
            foreach ($row->getElementsByTagName('td') as $td) {
                $cells[] = trim($td->textContent);
            }

            if (count($cells) < 4 || $cells[0] === '') {
                continue;
            }

            return $this->buildPerson($dni, $cells);
        }

        return null;
    }

    /**
     * @param string   $dni
     * @param string[] $cells
     *
     * @return Person
     */
    private function buildPerson(string $dni, array $cells): Person
    {
        $person = new Person();
        $person->dni = $dni;
        $person->nombres = $cells[1];
        $person->apellidoPaterno = $cells[2];
        $person->apellidoMaterno = $cells[3];
        $person->codVerifica = VerifyCode::calculate($dni);

        return $person;
    }
}