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
    public static function getBonitaEndpointURL(string $endpointName)
    {
        return config('services.bonita.api_url') . $endpointName;
    }

    /**
     * Obtener endpoint del servicio web de estampillado.
     *
     * @return string
     */
    public static function getServicioEstampilladoURL()
    {
        return config('services.estampillado.endpoint');
    }

    /**
     * Obtener endpoint del frontend.
     *
     * @return string
     */
    public static function getFrontendURL()
    {
        return config('services.frontend.endpoint');
    }
}
