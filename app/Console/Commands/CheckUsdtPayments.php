<?php
namespace App\Console\Commands;

use App\Http\Utils\TronUtil;
use App\Models\UsdtPayment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckUsdtPayments extends Command
{
    protected $signature = 'app:check-usdt-payments';
    protected $description = 'Check for USDT payments on assigned addresses';

    protected $tronUtil;

    public function __construct(TronUtil $tronUtil)
    {
        parent::__construct();
        $this->tronUtil = $tronUtil;
    }

    public function handle()
    {
        $this->info('Checking for USDT payments...');
        
        // Get all pending payments that haven't expired
        $pendingPayments = UsdtPayment::where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->get();
            
        foreach ($pendingPayments as $payment) {
            $this->info("Checking payment {$payment->id} for address {$payment->address}");
            
            // Check if payment has been received
            $balance = $this->tronUtil->getUsdtBalance($payment->address);

            // logger("Pending", [$balance]);

            if ($balance >= $payment->amount) {
                $this->info("Payment received: {$balance} USDT");
                
                // Update payment status
                $payment->status = 'success';
                $payment->save();
                
                // You might want to notify the user here
                
                $this->info("Payment {$payment->id} marked as successful");
            } else {
                $this->info("No payment received yet for {$payment->id}");
            }
        }
        
        // Expire payments that have passed their expiration time
        UsdtPayment::where('status', 'pending')
            ->where('expires_at', '<=', Carbon::now())
            ->update(['status' => 'expired']);
            
        $this->info('Payment check completed');
        
        return Command::SUCCESS;
    }
}
