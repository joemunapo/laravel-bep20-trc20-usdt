<?php

namespace App\Http\Utils;

class Formatter
{
    /**
     * Format a value to wei (10^18)
     * 
     * @param float $value
     * @return string
     */
    public static function toWei($value)
    {
        return bcmul(strval($value), '1000000000000000000', 0);
    }
    
    /**
     * Format wei value to ether
     * 
     * @param string $value
     * @return string
     */
    public static function fromWei($value)
    {
        return bcdiv(strval($value), '1000000000000000000', 18);
    }
    
    /**
     * Format a hex value to decimal
     * 
     * @param string $hex
     * @return string
     */
    public static function hexToDec($hex)
    {
        $hex = (string) $hex;
        
        if (strpos($hex, '0x') === 0) {
            $hex = substr($hex, 2);
        }
        
        if (empty($hex)) {
            return '0';
        }
        
        $dec = '0';
        $len = strlen($hex);
        
        for ($i = 0; $i < $len; $i++) {
            $dec = bcadd(bcmul($dec, '16', 0), base_convert($hex[$i], 16, 10), 0);
        }
        
        return $dec;
    }
    
    /**
     * Format a decimal value to hex
     * 
     * @param string $dec
     * @return string
     */
    public static function decToHex($dec)
    {
        $dec = (string) $dec;
        
        if ($dec === '0') {
            return '0x0';
        }
        
        $hex = '';
        
        while (bccomp($dec, '0') > 0) {
            $dv = bcdiv($dec, '16', 0);
            $rem = bcmod($dec, '16');
            $dec = $dv;
            $hex = dechex($rem) . $hex;
        }
        
        return '0x' . $hex;
    }

    /**
     * Format a value to USDT token format (6 decimals)
     * 
     * @param float $value
     * @return string
     */
    public static function toUsdtTokenValue($value)
    {
        return bcmul(strval($value), '1000000', 0);
    }
    
    /**
     * Format token value to human-readable USDT amount
     * 
     * @param string $value
     * @return string
     */
    public static function fromUsdtTokenValue($value)
    {
        return bcdiv(strval($value), '1000000', 6);
    }
}