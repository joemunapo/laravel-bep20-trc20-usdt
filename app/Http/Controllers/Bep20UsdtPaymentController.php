<?php

namespace App\Http\Controllers;

use App\Models\Bsc;
use App\Models\UsdtPayment;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class Bep20UsdtPaymentController extends Controller
{
    public function showPayment()
    {
        return view('usdt_payments.amount');
    }
    
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);
        
        $amount = $request->input('amount');
        
        // Get an unused address from BepAddress model or create a new one
        $bsc = new Bsc();
        $addressData = $bsc->getNextAvailableAddress();
        
        if (!$addressData) {
            logger()->error('Failed to generate new address for payment');
            return redirect()->back()->with('error', 'Unable to create payment address. Please try again later.');
        }
        
        // Create new payment record
        $payment = new UsdtPayment();
        $payment->user_id = 1;
        $payment->amount = $amount;
        $payment->address = $addressData['address'];
        $payment->address_index = $addressData['index'];
        $payment->status = 'pending';
        $payment->save();
        
        return redirect()->route('pay-bep20', ['id' => $payment->id]);
    }
    
    public function pay(Request $request)
    {
        $paymentId = $request->query('id');
        $payment = UsdtPayment::where('id', $paymentId)
                            ->where('user_id', 1)
                            ->where('status', 'pending')
                            ->first();
                            
        if (!$payment) {
            return redirect()->route('usdt-bep20-payment')->with('error', 'Invalid payment request');
        }
        
        $expiryTime = $payment->created_at->addMinutes(30);
        $remainingTime = now()->diff($expiryTime);
        
        $expiresInMinutes = $remainingTime->i;
        $expiresInSeconds = $remainingTime->s;
        
        if ($expiresInMinutes <= 0 && $expiresInSeconds <= 0) {
            $payment->status = 'expired';
            $payment->save();
            return redirect()->route('usdt-bep20-payment')->with('error', 'Payment has expired');
        }
        
        // Generate QR code
        $qrCode = QrCode::size(200)->generate($payment->address);
        
        return view('usdt_payments.pay', [
            'payment' => $payment,
            'qrCode' => $qrCode,
            'expiresInMinutes' => $expiresInMinutes,
            'expiresInSeconds' => $expiresInSeconds
        ]);
    }
    
    public function checkStatus(Request $request)
    {
        $paymentId = $request->query('payment_id');
        $payment = UsdtPayment::find($paymentId);
        
        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }
        
        if ($payment->user_id != 1) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return response()->json([
            'status' => $payment->status,
            'received_amount' => $payment->received_amount
        ]);
    }
}
