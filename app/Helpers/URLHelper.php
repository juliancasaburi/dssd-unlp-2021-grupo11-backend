<?php

namespace App\Helpers;

class URLHelper
{
    /**
     * Obtener endpoint de Bonita REST API.
     *
     * @param  string $endpointName
     * @return string
     */
    public function getBonitaEndpointURL(string $endpointName)
    {
        return config('services.bonita.api_url') . $endpointName;
    }
}
