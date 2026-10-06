<?php

declare(strict_types=1);

namespace Peru\Sunat\Parser;

use DOMDocument;
use DOMXPath;

class XpathLoader
{
    public static function getXpathFromHtml(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        $prevState = libxml_use_internal_errors(true);
        // El HTML de SUNAT viene en windows-1252 con una meta charset="utf-8"
        // equivocada. libxml >= 2.12 honra la meta y corrompe el árbol.
        if (!mb_check_encoding($html, 'UTF-8')) {
            $converted = @mb_convert_encoding($html, 'UTF-8', 'Windows-1252');
            if (null !== $converted) {
                $html = $converted;
            }
        }
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($prevState);

        return new DOMXPath($dom);
    }
}
