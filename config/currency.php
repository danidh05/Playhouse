<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available Currencies
    |--------------------------------------------------------------------------
    |
    | List of available currencies for the application
    |
    */
    'currencies' => [
        'usd' => [
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
        ],
        'lbp' => [
            'name' => 'Lebanese Pound',
            'code' => 'LBP',
            'symbol' => 'LBP',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency to use for displaying prices
    |
    */
    'default' => 'usd',
]; 