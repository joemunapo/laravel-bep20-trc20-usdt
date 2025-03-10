<?php

namespace App\Http\Utils;

use kornrunner\Keccak;

class Util
{
    /**
     * Generate EIP-55 compliant Ethereum address
     * 
     * @param string $address Ethereum address without '0x' prefix
     * @return string
     */
    public static function toChecksumAddress($address)
    {
        $address = strtolower(str_replace('0x', '', $address));
        $hash = Keccak::hash(strtolower($address), 256);
        $checksumAddress = '0x';
        
        for ($i = 0; $i < strlen($address); $i++) {
            $charPos = $i >> 1;
            $hashByte = hexdec(substr($hash, $charPos, 1));
            
            if (ctype_alpha($address[$i])) {
                // If it's a letter (a-f), check the corresponding hash bit
                if (($hashByte & (8 >> ($i % 2))) != 0) {
                    $checksumAddress .= strtoupper($address[$i]);
                } else {
                    $checksumAddress .= $address[$i];
                }
            } else {
                // If it's a digit, just add it as-is
                $checksumAddress .= $address[$i];
            }
        }
        
        return $checksumAddress;
    }
    
    /**
     * Generate contract method ID (first 4 bytes of keccak256 hash of method signature)
     * 
     * @param string $methodSignature The method signature (e.g., "transfer(address,uint256)")
     * @return string
     */
    public static function getMethodId($methodSignature)
    {
        $hash = Keccak::hash($methodSignature, 256);
        return '0x' . substr($hash, 0, 8);
    }
    
    /**
     * Pad address to 32 bytes
     * 
     * @param string $address
     * @return string
     */
    public static function padAddress($address)
    {
        $address = str_replace('0x', '', $address);
        return str_pad($address, 64, '0', STR_PAD_LEFT);
    }
    
    /**
     * Pad amount to 32 bytes
     * 
     * @param string $amount
     * @return string
     */
    public static function padAmount($amount)
    {
        $amount = dechex($amount);
        return str_pad($amount, 64, '0', STR_PAD_LEFT);
    }
}