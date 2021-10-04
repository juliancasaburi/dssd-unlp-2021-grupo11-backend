<?php

namespace App\Helpers;

class BonitaRequestHelper
{
    /**
     * Obtener headers
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @return array
     */
    public function getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken)
    {
        return [
            'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
            'X-Bonita-API-Token' => $xBonitaAPIToken,
        ];
    }
}
