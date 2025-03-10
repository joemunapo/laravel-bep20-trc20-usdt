<?php

namespace App\Console\Commands;

use App\Http\Utils\TronUtil;
use App\Models\UsdtPayment;
use App\Models\TronAddress;
use Illuminate\Console\Command;

class MoveFunds extends Command
{
    protected $signature = 'app:move-funds';
    protected $description = 'Move USDT funds from payment addresses to the main wallet';

    protected $tronUtil;

    public function __construct(TronUtil $tronUtil)
    {
        parent::__construct();
        $this->tronUtil = $tronUtil;
    }

    public function handle()
    {
        $this->info('Moving funds to main wallet...');

        // Get central wallet address (index 0)
        $centralWalletInfo = $this->tronUtil->generateAddressFromMnemonic(0);
        $centralWalletAddress = $centralWalletInfo['address'];

        $this->info("Central wallet address: {$centralWalletAddress}");

        // Get all successful payments that haven't been moved yet
        $successfulPayments = UsdtPayment::where('status', 'success')
            ->whereHas('tronAddress', function ($query) {
                $query->where('status', 'assigned');
            })
            ->get();

        foreach ($successfulPayments as $payment) {
            $this->info("Processing payment {$payment->id} for address {$payment->address}");

            // Get address details including private key
            $tronAddress = TronAddress::where('address', $payment->address)->first();

            if (!$tronAddress) {
                $this->error("Address not found for payment {$payment->id}");
                continue;
            }

            // Check balance again to ensure funds are still there
            $balance = $this->tronUtil->getUsdtBalance($payment->address);

            if ($balance > 0) {
                $this->info("Found {$balance} USDT to move");

                try {
                    // Transfer funds to central wallet
                    $result = $this->tronUtil->transferUsdt(
                        $tronAddress->private_key,
                        $centralWalletAddress,
                        $balance
                    );

                    // Update address status
                    $tronAddress->status = 'available';
                    $tronAddress->payment_id = null;
                    $tronAddress->save();

                    $this->info("Funds moved successfully from {$payment->address} to {$centralWalletAddress}");
                } catch (\Exception $e) {
                    $this->error("Failed to move funds: " . $e->getMessage());
                }
            } else {
                $this->info("No funds found in {$payment->address}");

                // Update address status anyway
                $tronAddress->status = 'available';
                $tronAddress->payment_id = null;
                $tronAddress->save();
            }
        }

        $this->info('Fund movement completed');

        return Command::SUCCESS;
    }
}
