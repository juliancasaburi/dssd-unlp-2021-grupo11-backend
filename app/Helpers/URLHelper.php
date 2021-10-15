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

    /**
     * Obtener endpoint del servicio web de estampillado.
     *
     * @return string
     */
    public function getServicioEstampilladoURL()
    {
        return config('services.estampillado.endpoint');
    }
}
