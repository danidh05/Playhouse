<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Play Sessions Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for play sessions,
    | including rates, complaint types, and other related settings.
    |
    */

    /**
     * Base hourly rate for play sessions.
     */
    'hourly_rate' => 10.00,

    /**
     * LBP to USD exchange rate
     * 
     * Used for currency conversion in the application.
     * For example, 90000 means 90,000 LBP = 1 USD
     */
    'lbp_exchange_rate' => 90000,

    /**
     * Available complaint types.
     * 
     * These types are used for form validation and in database records.
     * They should match the types referenced in the ComplaintRequest validation.
     */
    'complaint_types' => [
        'Facility',
        'Staff',
        'Safety',
        'Cleanliness',
        'Service',
        'Other',
    ],
    
    /**
     * Available payment methods.
     * 
     * These payment methods are used for form validation and in database records.
     */
    'payment_methods' => [
        'LBP',
        'USD',
    ],
]; 