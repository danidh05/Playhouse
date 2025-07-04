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
    'hourly_rate' => 3.00,

    /**
     * LBP to USD exchange rate
     * 
     * Used for currency conversion in the application.
     * For example, 90000 means 90,000 LBP = 1 USD
     */
    'lbp_exchange_rate' => 90000,

    /**
     * USD display threshold
     * 
     * When LBP amounts convert to USD values below this threshold,
     * display them in USD instead of LBP for better readability.
     * Set to 0 to disable this feature.
     */
    'usd_display_threshold' => 5.00,

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
        'USD',
        'LBP',
    ],
]; 