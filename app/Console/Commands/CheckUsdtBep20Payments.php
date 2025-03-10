<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bsc;
use App\Models\UsdtPayment;
use Illuminate\Support\Facades\Log;

class CheckUsdtBep20Payments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-usdt-bep20-payments';

    protected $description = 'Check for USDT payments on BSC network';

    public function handle()
    {
        $this->info('Checking for USDT payments...');
        
        try {
            $pendingPayments = UsdtPayment::where('status', 'pending')->get();
            foreach ($pendingPayments as $payment) {
                $this->info("Checking payment #" . $payment->id);
                
                // Check if payment address has received the USDT
                $bsc = new Bsc();
                $balance = $bsc->checkUsdtBalance($payment->address);
                
                // Convert to numeric value for comparison
                $balanceValue = floatval($balance);
                $requiredAmount = floatval($payment->amount);
                
                if ($balanceValue >= $requiredAmount) {
                    $this->info("Payment #" . $payment->id . " has been received! Amount: " . $balance);
                    $payment->status = 'success';
                    $payment->received_amount = $balance;
                    $payment->save();
                } else {
                    $this->info("Payment #" . $payment->id . " still pending. Current balance: " . $balance);
                    
                    // Check if payment has expired
                    $expiryTime = $payment->created_at->addMinutes(30); // 30 minutes expiry
                    if (now() > $expiryTime) {
                        $this->info("Payment #" . $payment->id . " has expired.");
                        $payment->status = 'expired';
                        $payment->save();
                    }
                }
            }
            
            $this->info('Finished checking payments.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error checking USDT payments: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
