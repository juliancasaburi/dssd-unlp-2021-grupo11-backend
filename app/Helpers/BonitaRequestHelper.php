<?php

namespace App\Helpers;

class BonitaRequestHelper
{
    /**
     * Obtener headers
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  bool $additionalHeaders
     * @return array
     */
    public static function getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken, $additionalHeaders = false)
    {
        $headers = ['Cookie' => 'JSESSIONID=' . $jsessionid];

        if ($additionalHeaders) {
            $headers['Cookie'] = $headers['Cookie'] . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken;
            $headers['X-Bonita-API-Token'] = $xBonitaAPIToken;
        }

        return $headers;
    }
}
