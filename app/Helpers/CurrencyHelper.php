<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Format currency amount with smart display logic.
     * Small LBP amounts (below threshold) are displayed in USD for better readability.
     * 
     * @param float $amount The amount to format
     * @param string $originalCurrency The original payment currency ('LBP' or 'USD')
     * @param string $storedCurrency The currency the amount is stored in
     * @return string Formatted currency string
     */
    public static function formatAmount($amount, $originalCurrency, $storedCurrency = null)
    {
        $lbpRate = config('play.lbp_exchange_rate', 90000);
        $threshold = config('play.usd_display_threshold', 5.00);
        
        // If the original payment was in USD, always display in USD format
        if ($originalCurrency === 'USD') {
            if ($storedCurrency === 'USD') {
                // Old format: stored in USD, display as-is
                return '$' . number_format($amount, 2);
            } else {
                // New format: stored as LBP equivalent, convert back to USD
                $usdAmount = $amount / $lbpRate;
                return '$' . number_format($usdAmount, 2);
            }
        }
        
        // If the original payment was in LBP, display in LBP format
        if ($originalCurrency === 'LBP') {
            return number_format($amount, 0) . ' L.L';
        }
        
        // Fallback to original logic for other currencies
        return self::formatAmountOriginal($amount, $originalCurrency, $storedCurrency);
    }
    
    /**
     * Original currency formatting logic (fallback).
     * 
     * @param float $amount
     * @param string $originalCurrency
     * @param string $storedCurrency
     * @return string
     */
    private static function formatAmountOriginal($amount, $originalCurrency, $storedCurrency = null)
    {
        $lbpRate = config('play.lbp_exchange_rate', 90000);
        
        if ($originalCurrency === 'LBP') {
            return number_format($amount, 0) . ' L.L';
        } else {
            // For USD payments or mixed currency scenarios
            if ($storedCurrency === 'USD') {
                // Old format: stored in USD, display as-is
                return '$' . number_format($amount, 2);
            } else {
                // New format: stored as LBP equivalent, convert back to USD
                $usdAmount = $amount / $lbpRate;
                return '$' . number_format($usdAmount, 2);
            }
        }
    }
    
    /**
     * Format product price with smart display logic.
     * 
     * @param object $product Product model instance
     * @param string $displayCurrency Preferred display currency
     * @return string Formatted price string
     */
    public static function formatProductPrice($product, $displayCurrency = null)
    {
        $lbpRate = config('play.lbp_exchange_rate', 90000);
        $threshold = config('play.usd_display_threshold', 5.00);
        
        // Determine which price to use
        $hasLbpPrice = isset($product->price_lbp) && $product->price_lbp > 0;
        $hasUsdPrice = isset($product->price) && $product->price > 0;
        
        if ($hasLbpPrice) {
            $lbpPrice = $product->price_lbp;
            $usdEquivalent = $lbpPrice / $lbpRate;
            
            // If USD equivalent is below threshold, show in USD
            if ($threshold > 0 && $usdEquivalent < $threshold) {
                return '$' . number_format($usdEquivalent, 2);
            }
            
            return number_format($lbpPrice, 0) . ' L.L';
        } elseif ($hasUsdPrice) {
            return '$' . number_format($product->price, 2) . ' <span class="text-xs text-gray-500">(USD)</span>';
        }
        
        return 'N/A';
    }
    
    /**
     * Get the USD equivalent of any amount.
     * 
     * @param float $amount
     * @param string $currency
     * @return float
     */
    public static function toUsd($amount, $currency)
    {
        if ($currency === 'LBP') {
            return $amount / config('play.lbp_exchange_rate', 90000);
        }
        
        return $amount;
    }
    
    /**
     * Check if an amount should be displayed in USD based on threshold.
     * 
     * @param float $amount
     * @param string $currency
     * @return bool
     */
    public static function shouldDisplayInUsd($amount, $currency)
    {
        $threshold = config('play.usd_display_threshold', 5.00);
        
        if ($threshold <= 0) {
            return false;
        }
        
        $usdAmount = self::toUsd($amount, $currency);
        return $usdAmount < $threshold;
    }
} 