<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bsc;
use App\Models\UsdtPayment;

class MoveBep20Funds extends Command
{
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
            
            $bsc = new Bsc();
            
            foreach ($successfulPayments as $payment) {
                $this->info("Processing payment #" . $payment->id . " for address " . $payment->address);
                
                $balance = $bsc->checkUsdtBalance($payment->address);
                
                if (floatval($balance) > 0) {
                    $this->info("Moving " . $balance . " USDT to main wallet");
                    
                    try {
                        // Use gas-sponsored transfer to move funds
                        $result = $bsc->transferUsdtWithSponsoredGas($payment->address_index);
                        
                        if ($result) {
                            $this->info("Successfully initiated transfer to main wallet");
                            // Mark as moved immediately
                            $payment->funds_moved = true;
                            $payment->save();
                        } else {
                            $this->error("Failed to move funds");
                        }
                    } catch (\Exception $e) {
                        $this->error("Error moving funds: " . $e->getMessage());
                        logger()->error("Error moving funds for payment #" . $payment->id . ": " . $e->getMessage());
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
            logger()->error('Error moving funds: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
