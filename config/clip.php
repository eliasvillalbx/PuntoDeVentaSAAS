<?php
// config/clip.php

return [

    /*
    |--------------------------------------------------------------------------
    | Credenciales Clip (se leen del .env)
    |--------------------------------------------------------------------------
    */
    'api_key' => env('CLIP_API_KEY'),
    'secret'  => env('CLIP_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Endpoint Checkout v2
    |--------------------------------------------------------------------------
    |
    | Oficial: https://api.payclip.com/v2/checkout
    |
    */
    'checkout_url' => env('CLIP_CHECKOUT_URL', 'https://api.payclip.com/v2/checkout'),

    /*
    |--------------------------------------------------------------------------
    | Planes de suscripción (precios ejemplo)
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'mensual' => [
            'label'       => 'Plan mensual',
            'amount'      => 10.00,
            'currency'    => 'MXN',
            'description' => 'Suscripción mensual a la plataforma',
        ],
        'trimestral' => [
            'label'       => 'Plan trimestral',
            'amount'      => 11.00,
            'currency'    => 'MXN',
            'description' => 'Suscripción trimestral a la plataforma',
        ],
        'anual' => [
            'label'       => 'Plan anual',
            'amount'      => 13.00,
            'currency'    => 'MXN',
            'description' => 'Suscripción anual a la plataforma',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Valores por defecto para datos requeridos por Clip
    |--------------------------------------------------------------------------
    |
    | Los usamos si no encontramos datos en la BD (para que el checkout no truene).
    |
    */
    'defaults' => [

        // Dirección de facturación por defecto (puedes luego mapear desde Empresa)
        'billing_address' => [
            'street'          => 'Sin calle',
            'outdoor_number'  => 'SN',
            'interior_number' => 'SN',
            'locality'        => 'Sin colonia',
            'city'            => 'Cuernavaca',
            'state'           => 'Morelos',
            'zip_code'        => '62000',
            'country'         => 'MX',
            'reference'       => 'Sin referencia',
            'between_streets' => 'S/N y S/N',
            'floor'           => 'PB',
        ],

        // Teléfono placeholder si el usuario no tiene uno guardado
    ],

];
