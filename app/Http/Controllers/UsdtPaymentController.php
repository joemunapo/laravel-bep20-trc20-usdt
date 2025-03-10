<?php

namespace App\Http\Controllers;

use App\Http\Utils\TronUtil;
use App\Models\TronAddress;
use App\Models\UsdtPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class UsdtPaymentController extends Controller
{
    protected $tronUtil;

    public function __construct(TronUtil $tronUtil)
    {
        $this->tronUtil = $tronUtil;
    }

    public function showPayment()
    {
        return view('amount');
    }

    public function initiatePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:3',
        ]);

        // $user = Auth::user();
        $amount = $request->input('amount');

        // Find or create an available address
        $tronAddress = $this->getAvailableAddress();

        if (!$tronAddress) {
            return redirect()->back()->with('error', 'No available payment addresses. Please try again later.');
        }

        // Create payment record
        $payment = UsdtPayment::create([
            'user_id' => 1,
            'amount' => $amount,
            'address' => $tronAddress->address,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(20),
        ]);

        // Assign address to payment
        $tronAddress->payment_id = $payment->id;
        $tronAddress->status = 'assigned';
        $tronAddress->expires_at = now()->addMinutes(20);
        $tronAddress->save();

        // Redirect to payment page
        return redirect()->route('pay-usdt', ['payment_id' => $payment->id]);
    }

    public function pay(Request $request)
    {
        $paymentId = $request->query('payment_id');

        $payment = UsdtPayment::where('id', $paymentId)
            ->where('user_id', 1)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$payment) {
            return redirect()->route('usdt-payment')->with('error', 'Invalid payment or payment expired.');
        }

        // Generate QR code
        $qrCode = QrCode::size(200)
            ->format('svg')
            ->gradient(0, 150, 136, 22, 160, 133, 'diagonal')
            ->style('square')
            ->eye('square')
            ->generate($payment->address);

        $expiresIn = Carbon::now()->diffInSeconds($payment->expires_at);
        $expiresInMinutes = floor($expiresIn / 60);
        $expiresInSeconds = $expiresIn % 60;

        return view('pay', [
            'payment' => $payment,
            'qrCode' => $qrCode,
            'expiresInMinutes' => $expiresInMinutes,
            'expiresInSeconds' => $expiresInSeconds,
        ]);
    }

    public function checkStatus(Request $request)
    {
        $paymentId = $request->query('payment_id');

        $payment = UsdtPayment::where('id', $paymentId)
            ->where('user_id', 1)
            ->first();

        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found']);
        }

        // If payment is still pending, manual check
        if ($payment->status === 'pending') {
            $balance = $this->tronUtil->getUsdtBalance($payment->address);

            if ($balance >= $payment->amount) {
                $payment->status = 'success';
                $payment->save();

                return response()->json(['status' => 'success', 'message' => 'Payment received!']);
            }

            if (Carbon::now()->gt($payment->expires_at)) {
                $payment->status = 'expired';
                $payment->save();

                return response()->json(['status' => 'expired', 'message' => 'Payment expired']);
            }

            return response()->json(['status' => 'pending', 'message' => 'Waiting for payment']);
        }

        return response()->json(['status' => $payment->status]);
    }

    protected function getAvailableAddress()
    {
        // Try to find an available address
        $address = TronAddress::where('status', 'available')->first();

        // If no available address, create a new one
        if (!$address) {
            $lastIndex = TronAddress::max('index') ?? 0;
            $newIndex = $lastIndex + 1;

            $addressInfo = $this->tronUtil->generateAddressFromMnemonic($newIndex);

            $address = TronAddress::create([
                'address' => $addressInfo['address'],
                'private_key' => $addressInfo['privateKey'],
                'index' => $newIndex,
                'status' => 'available',
            ]);
        }

        return $address;
    }
}
