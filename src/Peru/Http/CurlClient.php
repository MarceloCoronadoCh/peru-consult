<?php

namespace Peru\Http;

class CurlClient implements ClientInterface
{
    private const USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.72 Safari/537.36';
    private $ch;

    public function __construct()
    {
        $this->ch = curl_init();
    }

    public function get(string $url, array $headers = [])
    {
        $this->setDefaultConfig($url, $headers);
        curl_setopt($this->ch, CURLOPT_POST, 0);

        return curl_exec($this->ch);
    }

    public function post(string $url, $data, array $headers = [])
    {
        if (is_array($data)) {
            // Headers del propio navegador de la traza de SUNAT
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        $headers['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $headers['Accept-Language'] = 'es-PE,es;q=0.9';
        $headers['Origin'] = 'https://e-consultaruc.sunat.gob.pe';
        $headers['Referer'] = 'https://e-consultaruc.sunat.gob.pe/cl-ti-itmrconsruc/jcrS00Alias';
        $raw = is_array($data) ? http_build_query($data) : $data;

        $this->setDefaultConfig($url, $headers);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $raw);

        return curl_exec($this->ch);
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    private function setDefaultConfig(string $url, array $headers): void
    {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, '');
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, '');
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_ENCODING, '');          // gzip/deflate como un browser
        curl_setopt($this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->buildHeaders($headers));
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    }

    private function buildHeaders(array $headers): array {
        $formatHeaders = [];
        foreach ($headers as $key => $value) {
            $formatHeaders[] = "$key: $value";
        }

        return $formatHeaders;
    }
}