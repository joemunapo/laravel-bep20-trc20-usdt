<?php

// namespace App\Http\Utils;

// use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
// use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
// use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
// use Elliptic\EC;
// use kornrunner\Keccak;

// class TronUtil
// {
//     protected $mnemonic;
//     protected $tronGridUri;
//     protected $tronGridApiKey;

//     protected $usdtContractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'; // USDT TRC20 contract


//     public function __construct()
//     {
//         $this->mnemonic = env('MNEMONIC_PHRASE');
//         $this->tronGridUri = env('TRONGRID_URI', 'https://api.trongrid.io');
//         $this->tronGridApiKey = env('TRONGRID_API_KEY', '');
//     }

//     public function generateAddressFromMnemonic($index = 0)
//     {
//         // $bip39 = MnemonicFactory::bip39();
//         $seedGenerator = new Bip39SeedGenerator();
//         $seed = $seedGenerator->getSeed($this->mnemonic);

//         $hdFactory = new HierarchicalKeyFactory();
//         $master = $hdFactory->fromEntropy($seed);

//         // BIP44 derivation path for TRON: m/44'/195'/0'/0/index
//         $path = "44'/195'/0'/0/" . $index;
//         $key = $master->derivePath($path);

//         $publicKey = $key->getPublicKey();
//         $address = $this->publicKeyToAddress($publicKey);

//         return [
//             'address' => $address,
//             'privateKey' => $key->getPrivateKey()->getHex(),
//             'index' => $index
//         ];
//     }

//     protected function publicKeyToAddress(PublicKeyInterface $publicKey)
//     {
//         $pubkey = $publicKey->getHex();

//         // Remove the first two characters (04 prefix)
//         if (substr($pubkey, 0, 2) === '04') {
//             $pubkey = substr($pubkey, 2);
//         }

//         // Convert to EC point
//         $ec = new EC('secp256k1');
//         $pubPoint = $ec->keyFromPublic($pubkey, 'hex')->getPublic();

//         // Get X and Y coordinates
//         $x = $pubPoint->getX()->toString(16, 64);
//         $y = $pubPoint->getY()->toString(16, 64);

//         // Use X and Y to get the full public key
//         $fullPubKey = '04' . $x . $y;

//         // Get keccak hash
//         $hash = Keccak::hash(hex2bin(substr($fullPubKey, 2)), 256);

//         // Get the last 20 bytes
//         $address = substr($hash, 24);

//         // Add 41 (TRON address prefix) and convert to base58
//         $addressHex = '41' . $address;
//         $addressBin = hex2bin($addressHex);
//         $base58Address = $this->base58EncodeCheck($addressBin);

//         return $base58Address;
//     }

//     protected function base58EncodeCheck($input)
//     {
//         $hash = hash('sha256', $input, true);
//         $hash = hash('sha256', $hash, true);
//         $checksum = substr($hash, 0, 4);
//         $input .= $checksum;

//         return $this->base58Encode($input);
//     }

//     protected function base58Encode($input)
//     {
//         $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
//         $base = strlen($alphabet);

//         if (is_string($input)) {
//             $input = str_split($input);
//             $input = array_map('ord', $input);
//         }

//         $output = '';
//         $leading_zeros = 0;

//         while (!empty($input) && $input[0] === 0) {
//             $leading_zeros++;
//             array_shift($input);
//         }

//         $carry = 0;
//         $digits = [];

//         foreach ($input as $byte) {
//             $carry = $carry * 256 + $byte;
//             $digits_count = 0;

//             while ($carry !== 0 || $digits_count < count($digits)) {
//                 $carry += (isset($digits[$digits_count]) ? $digits[$digits_count] : 0) * 256;
//                 $digits[$digits_count] = $carry % $base;
//                 $carry = ($carry - $digits[$digits_count]) / $base;
//                 $digits_count++;
//             }
//         }

//         for ($i = 0; $i < $leading_zeros; $i++) {
//             $output .= $alphabet[0];
//         }

//         for ($i = count($digits) - 1; $i >= 0; $i--) {
//             $output .= $alphabet[$digits[$i]];
//         }

//         return $output;
//     }

//     public function getUsdtBalance($address)
//     {
//         // First, try to get the correct balance from contract directly
//         $url = "{$this->tronGridUri}/v1/accounts/{$address}/tokens";
//         $response = $this->makeApiRequest($url);

//         if (isset($response['data']) && !empty($response['data'])) {
//             foreach ($response['data'] as $token) {
//                 if (
//                     $token['tokenId'] === $this->usdtContractAddress ||
//                     (isset($token['tokenAbbr']) && $token['tokenAbbr'] === 'USDT')
//                 ) {
//                     return $token['balance'] / 1000000; // Convert from TRC20 decimals
//                 }
//             }
//         }

//         logger("Calculating using trx");

//         // Fallback to calculating from transaction history
//         $url = "{$this->tronGridUri}/v1/accounts/{$address}/transactions/trc20";
//         $response = $this->makeApiRequest($url);

//         if (!isset($response['data']) || empty($response['data'])) {
//             return 0;
//         }

//         $balance = 0;
//         foreach ($response['data'] as $tx) {
//             if ($tx['token_info']['address'] === $this->usdtContractAddress) {
//                 if ($tx['to'] === $address) {
//                     $balance += $tx['value'] / 1000000; // Convert from TRC20 decimals
//                 } elseif ($tx['from'] === $address) {
//                     $balance -= $tx['value'] / 1000000;
//                 }
//             }
//         }

//         return $balance;
//     }

//     /**
//      * Convert TRON address from base58 to hex format
//      * 
//      * @param string $address TRON address in base58 format
//      * @return string Address in hex format (with 0x prefix)
//      */
//     protected function addressToHex($address)
//     {
//         // Decode from base58
//         $decoded = $this->base58Decode($address);

//         // Remove checksum (last 4 bytes)
//         $withoutChecksum = substr($decoded, 0, -4);

//         // Convert to hex
//         $hex = bin2hex($withoutChecksum);

//         return '0x' . $hex;
//     }

//     /**
//      * Decode a base58 string
//      * 
//      * @param string $base58 Base58 encoded string
//      * @return string Binary data
//      */
//     protected function base58Decode($base58)
//     {
//         $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
//         $base = strlen($alphabet);

//         $indexes = array_flip(str_split($alphabet));
//         $chars = str_split($base58);

//         $decimal = 0;
//         foreach ($chars as $char) {
//             $decimal = $decimal * $base + $indexes[$char];
//         }

//         $output = '';
//         while ($decimal > 0) {
//             $byte = $decimal & 0xFF;
//             $output = chr($byte) . $output;
//             $decimal >>= 8;
//         }

//         // Add leading zeros
//         foreach ($chars as $char) {
//             if ($char != $alphabet[0]) {
//                 break;
//             }
//             $output = chr(0) . $output;
//         }

//         return $output;
//     }


//     /**
//      * Transfer USDT TRC20 tokens from one address to another
//      * 
//      * @param string $fromPrivateKey Private key of the sender
//      * @param string $toAddress Recipient TRON address
//      * @param float $amount Amount to transfer in USDT
//      * @return array Response from the blockchain
//      */
//     public function transferUsdt($fromPrivateKey, $toAddress, $amount)
//     {
//         // Convert amount to TRC20 decimals (USDT has 6 decimals)
//         $amountInSun = (int)($amount * 1000000);

//         // Get sender address from private key
//         $fromAddress = $this->getAddressFromPrivateKey($fromPrivateKey);

//         // Convert addresses to hex format
//         $fromAddressHex = $this->addressToHex($fromAddress);
//         $toAddressHex = $this->addressToHex($toAddress);

//         // Create contract call data for TRC20 transfer()
//         // Function signature: transfer(address _to, uint256 _value)
//         $methodSignature = 'transfer(address,uint256)';
//         $methodSignatureHash = substr(hash('sha256', $methodSignature), 0, 8);

//         // Parameter 1: address _to (padded to 32 bytes)
//         $paramTo = str_pad(substr($toAddressHex, 2), 64, '0', STR_PAD_LEFT);

//         // Parameter 2: uint256 _value (padded to 32 bytes)
//         $paramValue = str_pad(dechex($amountInSun), 64, '0', STR_PAD_LEFT);

//         // Combine method ID and parameters
//         $data = $methodSignatureHash . $paramTo . $paramValue;

//         // Create the contract call transaction
//         $contractCallData = [
//             'owner_address' => $fromAddressHex,
//             'contract_address' => $this->addressToHex($this->usdtContractAddress),
//             'function_selector' => 'transfer(address,uint256)',
//             'parameter' => $paramTo . $paramValue,
//             'fee_limit' => 50000000, // 50 TRX in SUN
//             'call_value' => 0,
//             'visible' => true
//         ];

//         // 1. Create the transaction
//         $url = "{$this->tronGridUri}/wallet/triggersmartcontract";
//         $createTxResponse = $this->makeApiRequest($url, 'POST', $contractCallData);

//         if (!isset($createTxResponse['transaction']) || !isset($createTxResponse['constant_result'])) {
//             return [
//                 'success' => false,
//                 'message' => 'Failed to create transaction',
//                 'response' => $createTxResponse
//             ];
//         }

//         // 2. Sign the transaction with private key
//         $transaction = $createTxResponse['transaction'];
//         $signedTx = $this->signTransaction($transaction, $fromPrivateKey);

//         // 3. Broadcast the signed transaction
//         $url = "{$this->tronGridUri}/wallet/broadcasttransaction";
//         $broadcastResponse = $this->makeApiRequest($url, 'POST', $signedTx);

//         return [
//             'success' => isset($broadcastResponse['result']) && $broadcastResponse['result'] === true,
//             'transaction_id' => isset($broadcastResponse['txid']) ? $broadcastResponse['txid'] : null,
//             'response' => $broadcastResponse
//         ];
//     }

//     /**
//      * Derive TRON address from private key
//      * 
//      * @param string $privateKey Hex private key
//      * @return string TRON address in base58 format
//      */
//     protected function getAddressFromPrivateKey($privateKey)
//     {
//         // Remove '0x' prefix if present
//         if (substr($privateKey, 0, 2) === '0x') {
//             $privateKey = substr($privateKey, 2);
//         }

//         // Create EC instance
//         $ec = new EC('secp256k1');

//         // Import private key and get public key point
//         $key = $ec->keyFromPrivate($privateKey, 'hex');
//         $pubPoint = $key->getPublic(false, 'hex');

//         // Remove '04' prefix if it exists (uncompressed public key format marker)
//         if (substr($pubPoint, 0, 2) === '04') {
//             $pubPoint = substr($pubPoint, 2);
//         }

//         // Get keccak hash of public key
//         $hash = Keccak::hash(hex2bin($pubPoint), 256);

//         // Get the last 20 bytes
//         $address = substr($hash, 24);

//         // Add 41 prefix (TRON address prefix) and convert to base58
//         $addressHex = '41' . $address;
//         $addressBin = hex2bin($addressHex);
//         $base58Address = $this->base58EncodeCheck($addressBin);

//         return $base58Address;
//     }


//     /**
//      * Sign a transaction with a private key
//      * 
//      * @param array $transaction Transaction data
//      * @param string $privateKey Private key in hex format
//      * @return array Signed transaction
//      */
//     protected function signTransaction($transaction, $privateKey)
//     {
//         // Remove '0x' prefix if present
//         if (substr($privateKey, 0, 2) === '0x') {
//             $privateKey = substr($privateKey, 2);
//         }

//         // Convert transaction to JSON
//         $txID = $transaction['txID'];
//         $rawDataHex = $transaction['raw_data_hex'];

//         // Create signature
//         $ec = new EC('secp256k1');
//         $key = $ec->keyFromPrivate($privateKey, 'hex');

//         // Hash the raw data for signing
//         $hash = hash('sha256', hex2bin($rawDataHex), true);

//         // Sign the hash
//         $signature = $key->sign(bin2hex($hash));

//         // Get R and S values from signature
//         $r = $signature->r->toString(16);
//         $s = $signature->s->toString(16);

//         // Ensure r and s are 64 characters (32 bytes) each
//         $r = str_pad($r, 64, '0', STR_PAD_LEFT);
//         $s = str_pad($s, 64, '0', STR_PAD_LEFT);

//         // Combine R and S for the signature
//         $signatureHex = $r . $s;

//         // Add signature to transaction
//         $transaction['signature'] = [$signatureHex];

//         return $transaction;
//     }

//     protected function makeApiRequest($url, $method = 'GET', $data = [])
//     {
//         $ch = curl_init();

//         curl_setopt($ch, CURLOPT_URL, $url);
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_HTTPHEADER, [
//             'TRON-PRO-API-KEY: ' . $this->tronGridApiKey,
//             'Content-Type: application/json'
//         ]);

//         if ($method === 'POST') {
//             curl_setopt($ch, CURLOPT_POST, true);
//             curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
//         }

//         $response = curl_exec($ch);
//         curl_close($ch);

//         return json_decode($response, true);
//     }
// }
