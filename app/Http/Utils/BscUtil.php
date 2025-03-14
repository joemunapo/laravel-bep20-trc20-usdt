<?php

namespace App\Http\Utils;

use App\Models\BepAddress;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use Elliptic\EC;
use Exception;
use Illuminate\Support\Facades\Http;
use Web3p\EthereumTx\Transaction;
use kornrunner\Keccak;

class BscUtil
{
    
    // USDT BEP20 contract address on BSC
    protected $usdtContractAddress = '0x55d398326f99059ff775485246999027b3197955';

    // HD wallet derivation path for BSC
    protected $derivationPath = "m/44'/60'/0'/0/";

    /**
     * Get the BSC RPC node URL
     */
    protected function getNodeUrl()
    {
        return env('BEP_NODE_URI', 'https://bsc-dataseed1.defibit.io/');
    }

    /**
     * Get the mnemonic phrase from environment
     */
    protected function getMnemonic()
    {
        $mnemonic = env('MNEMONIC_PHRASE', '');

        if (empty($mnemonic)) {
            throw new Exception('Mnemonic phrase is not set in environment');
        }

        return $mnemonic;
    }

    /**
     * Generate address for a specific index in HD wallet
     */
    public function generateAddress($index)
    {
        $mnemonic = $this->getMnemonic();

        // Convert mnemonic to seed
        $bip39 = new Bip39SeedGenerator();
        $seed = $bip39->getSeed($mnemonic);

        // Generate master key from seed
        $hdFactory = new HierarchicalKeyFactory();
        $master = $hdFactory->fromEntropy($seed);

        // Derive child key at specified index
        $child = $master->derivePath($this->derivationPath . $index);

        // Get private key from child
        $privateKey = $child->getPrivateKey();
        $privateKeyHex = $privateKey->getHex();

        // Generate Ethereum address from private key
        $ec = new EC('secp256k1');
        $keyPair = $ec->keyFromPrivate($privateKeyHex);
        $publicKey = $keyPair->getPublic(false, 'hex');

        // Remove the '04' prefix and get keccak hash of remaining
        $publicKeyWithout04 = substr($publicKey, 2);
        $keccak = Keccak::hash(hex2bin($publicKeyWithout04), 256);

        // Take the last 40 characters (20 bytes) of the keccak hash
        $ethAddress = '0x' . substr($keccak, -40);

        // Convert to checksum address
        $checksumAddress = Util::toChecksumAddress($ethAddress);

        return [
            'address' => $checksumAddress,
            'index' => $index,
            'private_key' => $privateKeyHex
        ];
    }

    /**
     * Get private key for an address index
     */
    public function getAddressPrivateKey($index)
    {
        $addressData = $this->generateAddress($index);
        return $addressData['private_key'];
    }

    /**
     * Get next available address
     */
    public function getNextAvailableAddress(): BepAddress
    {
        $index = BepAddress::getNextAvailableIndex();
        $addressData = $this->generateAddress($index);

        // Save the new address in database

        return BepAddress::query()->create([
            'address' => $addressData['address'],
            'address_index' => $index,
            'is_used' => true,
        ]);
    }

    /**
     * Make JSON-RPC request to BSC node
     */
    protected function rpcRequest($method, $params = [])
    {
        $nodeUrl = $this->getNodeUrl();
        $auth = [];

        if (env('BEP_USER') && env('BEP_PASSWORD')) {
            $auth = [
                'auth' => [
                    env('BEP_USER'),
                    env('BEP_PASSWORD')
                ]
            ];
        }

        $response = Http::withOptions($auth)->post($nodeUrl, [
            'jsonrpc' => '2.0',
            'id' => time(),
            'method' => $method,
            'params' => $params
        ]);

        $result = $response->json();

        if (isset($result['error'])) {
            logger()->error('BSC RPC Error: ' . json_encode($result['error']));
            throw new Exception('BSC RPC Error: ' . ($result['error']['message'] ?? 'Unknown error'));
        }

        return $result['result'] ?? null;
    }

    /**
     * Check USDT balance of an address
     */
    public function checkUsdtBalance($address)
    {
        try {
            // Function signature for balanceOf(address)
            $methodId = Util::getMethodId('balanceOf(address)');

            // Prepare call data
            $data = $methodId . Util::padAddress($address);

            $result = $this->rpcRequest('eth_call', [
                [
                    'to' => $this->usdtContractAddress,
                    'data' => $data
                ],
                'latest'
            ]);

            // Convert result to decimal
            $balance = Formatter::hexToDec($result);

            // Convert from token value to USDT amount (6 decimals)
            return Formatter::fromUsdtTokenValue($balance);
        } catch (Exception $e) {
            logger()->error("Error checking USDT balance: " . $e->getMessage());
            return '0';
        }
    }

    /**
     * Transfer USDT from an address to the main wallet
     */
    public function transferUsdt($privateKey, $amount)
    {
        try {
            // Generate main wallet address (index 0)
            $mainWallet = $this->generateAddress(0)['address'];

            // Get the sender's address
            $ec = new EC('secp256k1');
            $keyPair = $ec->keyFromPrivate($privateKey);
            $publicKey = $keyPair->getPublic(false, 'hex');
            $publicKeyWithout04 = substr($publicKey, 2);
            $keccak = Keccak::hash(hex2bin($publicKeyWithout04), 256);
            $senderAddress = '0x' . substr($keccak, -40);
            $senderAddress = Util::toChecksumAddress($senderAddress);

            // Check BNB balance for gas
            $bnbBalance = $this->rpcRequest('eth_getBalance', [$senderAddress, 'latest']);
            $bnbBalance = Formatter::fromWei(Formatter::hexToDec($bnbBalance));

            if (floatval($bnbBalance) < 0.0004) {
                throw new Exception("Not enough BNB for gas. Current: {$bnbBalance} BNB in address {$senderAddress}");
            }

            // Convert USDT amount to token value
            $amountInTokens = Formatter::toUsdtTokenValue($amount);

            // Function signature for transfer(address,uint256)
            $methodId = Util::getMethodId('transfer(address,uint256)');

            // Prepare transaction data
            $data = $methodId .
                Util::padAddress($mainWallet) .
                Util::padAmount($amountInTokens);

            // Get current gas price
            $gasPrice = $this->rpcRequest('eth_gasPrice');

            // Get nonce for sender address
            $nonce = $this->rpcRequest('eth_getTransactionCount', [$senderAddress, 'latest']);

            // Transaction parameters
            $txParams = [
                'nonce' => $nonce,
                'gasPrice' => $gasPrice,
                'gasLimit' => '0x30000',  // 196608 gas
                'to' => $this->usdtContractAddress,
                'value' => '0x0',
                'data' => $data,
                'chainId' => env('BEP_NETWORK') === 'mainnet' ? 56 : 97 // 56 for mainnet, 97 for testnet
            ];

            // Create and sign transaction
            $tx = new Transaction($txParams);
            $signedTx = '0x' . $tx->sign($privateKey);

            // Send transaction
            $txHash = $this->rpcRequest('eth_sendRawTransaction', [$signedTx]);

            logger()->info("USDT transfer initiated: {$amount} USDT from {$senderAddress} to {$mainWallet}. TX Hash: {$txHash}");

            return $txHash;
        } catch (Exception $e) {
            logger()->error("Error transferring USDT: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transfer the entire USDT balance from a payment address to the main wallet
     * Main wallet sponsors gas by sending exact BNB amount needed
     *
     * @param string $fromAddressIndex Index of the address where the USDT is located
     * @return string|bool Transaction hash or false on failure
     */
    public function transferUsdtWithSponsoredGas($fromAddressIndex)
    {
        try {
            // Generate main wallet address and private key (index 0)
            $mainWalletData = $this->generateAddress(0);
            $mainWallet = $mainWalletData['address'];
            $sponsorPrivateKey = $mainWalletData['private_key']; // Main wallet is the gas sponsor

            // Get the sender's address from index
            $senderData = $this->generateAddress($fromAddressIndex);
            $senderAddress = $senderData['address'];
            $senderPrivateKey = $senderData['private_key'];

            // Check the current USDT balance of the sender
            $currentBalance = $this->checkUsdtBalance($senderAddress);
            $currentBalanceFloat = floatval($currentBalance);

            if ($currentBalanceFloat <= 0) {
                logger()->warning("No USDT balance to transfer from {$senderAddress}.");
                return false;
            }

            logger()->info("Preparing to transfer {$currentBalance} USDT from {$senderAddress} to {$mainWallet}");

            // Convert USDT amount to token value (considering 6 decimals for USDT)
            $amountInTokens = Formatter::toUsdtTokenValue($currentBalance);

            // Function signature for transfer(address,uint256)
            $transferMethodId = Util::getMethodId('transfer(address,uint256)');
            $transferData = $transferMethodId .
                Util::padAddress($mainWallet) .
                Util::padAmount($amountInTokens);

            // Create a raw, unsigned transaction for the token transfer
            $rawTx = [
                'from' => $senderAddress,
                'to' => $this->usdtContractAddress,
                'data' => $transferData,
                'value' => '0x0'
            ];

            // Get the gas estimate for this transaction
            $gasEstimate = $this->rpcRequest('eth_estimateGas', [$rawTx]);

            // Add 20% buffer to gas estimate
            $gasLimit = Formatter::hexToDec($gasEstimate);
            $gasLimit = bcmul($gasLimit, '12', 0);
            $gasLimit = bcdiv($gasLimit, '10', 0);
            $gasLimitHex = Formatter::decToHex($gasLimit);

            // Get the current gas price
            $gasPrice = $this->rpcRequest('eth_gasPrice');

            // Get nonce for the sender
            $nonce = $this->rpcRequest('eth_getTransactionCount', [$senderAddress, 'latest']);

            // Create the transaction parameters
            $txParams = [
                'nonce' => $nonce,
                'gasPrice' => $gasPrice,
                'gasLimit' => $gasLimitHex,
                'to' => $this->usdtContractAddress,
                'value' => '0x0',
                'data' => $transferData,
                'chainId' => env('BEP_NETWORK') === 'mainnet' ? 56 : 97
            ];

            // Create and sign the transaction with the sender's private key
            $tx = new Transaction($txParams);
            $signedTx = '0x' . $tx->sign($senderPrivateKey);

            // Get the main wallet's nonce
            $mainNonce = $this->rpcRequest('eth_getTransactionCount', [$mainWallet, 'latest']);

            // Calculate the gas cost in wei
            $gasCostWei = bcmul(Formatter::hexToDec($gasPrice), $gasLimit);

            // Create a transaction from the main wallet to the sender with exact gas cost
            $fundingTxParams = [
                'nonce' => $mainNonce,
                'gasPrice' => $gasPrice,
                'gasLimit' => '0x5208', // 21000 gas for standard transfer
                'to' => $senderAddress,
                'value' => Formatter::decToHex($gasCostWei),
                'data' => '0x',
                'chainId' => env('BEP_NETWORK') === 'mainnet' ? 56 : 97
            ];

            $fundingTx = new Transaction($fundingTxParams);
            $signedFundingTx = '0x' . $fundingTx->sign($sponsorPrivateKey);

            // Send BNB to cover gas
            $fundingTxHash = $this->rpcRequest('eth_sendRawTransaction', [$signedFundingTx]);
            logger()->info("Sent exact gas amount from main wallet to payment wallet. Hash: {$fundingTxHash}");

            // Wait briefly for the funding transaction to be included
            sleep(3);

            // Now execute the token transfer from the payment wallet
            $txHash = $this->rpcRequest('eth_sendRawTransaction', [$signedTx]);
            logger()->info("USDT transfer initiated: {$currentBalance} USDT from {$senderAddress} to {$mainWallet}. TX Hash: {$txHash}");

            return $txHash;
        } catch (Exception $e) {
            logger()->error("Error in gas-sponsored USDT transfer: " . $e->getMessage());
            return false;
        }
    }
}
