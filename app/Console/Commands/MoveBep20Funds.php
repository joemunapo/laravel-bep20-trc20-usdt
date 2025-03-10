<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bsc;
use App\Models\UsdtPayment;
use Illuminate\Support\Facades\Log;

class MoveBep20Funds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:move-bep20-funds';

    protected $description = 'Move funds from payment addresses to main wallet';

    public function handle()
    {
        $this->info('Moving funds to main wallet...');
        
        try {
            $successfulPayments = UsdtPayment::where('status', 'success')
                                            ->where('funds_moved', false)
                                            ->get();
                                            
            if ($successfulPayments->isEmpty()) {
                $this->info('No new successful payments to process.');
                return Command::SUCCESS;
            }
            
            foreach ($successfulPayments as $payment) {
                $this->info("Processing payment #" . $payment->id . " for address " . $payment->address);
                
                $bsc = new Bsc();
                $balance = $bsc->checkUsdtBalance($payment->address);
                
                if (floatval($balance) > 0) {
                    $this->info("Moving " . $balance . " USDT to main wallet");
                    
                    try {
                        // Get the private key for this address
                        $privateKey = $bsc->getAddressPrivateKey($payment->address_index);
                        
                        // Transfer USDT to main wallet (index 0)
                        $result = $bsc->transferUsdt($privateKey, $balance);
                        
                        if ($result) {
                            $this->info("Successfully moved funds to main wallet");
                            $payment->funds_moved = true;
                            $payment->save();
                        } else {
                            $this->error("Failed to move funds");
                        }
                    } catch (\Exception $e) {
                        $this->error("Error moving funds: " . $e->getMessage());
                        Log::error("Error moving funds for payment #" . $payment->id . ": " . $e->getMessage());
                    }
                } else {
                    $this->info("No funds to move for payment #" . $payment->id);
                    $payment->funds_moved = true;
                    $payment->save();
                }
            }
            
            $this->info('Finished moving funds.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error moving funds: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
