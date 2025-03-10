<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay USDT</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 400px;
            margin: 50px auto;
            padding: 0;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #2e7d32;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 16px;
        }

        .address-container {
            background-color: white;
            padding: 20px;
            text-align: center;
        }

        .address-label {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .address {
            background-color: #f5f5f5;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            word-break: break-all;
            font-family: monospace;
            font-size: 14px;
        }

        .qr-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }

        .timer {
            background-color: #e8f5e9;
            padding: 10px;
            text-align: center;
            color: #2e7d32;
            font-weight: bold;
        }

        .status {
            padding: 15px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f9f9f9;
        }

        .status-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #4caf50;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            send USDT TRC20 to the address bellow<br>
            Amount: {{ $payment->amount }} USDT
        </div>
        <div class="address-container">
            <div class="address-label">Address</div>
            <div class="address">{{ $payment->address }}</div>
            <div class="qr-container">
                {!! $qrCode !!}
            </div>
        </div>
        <div class="timer" id="timer">
            Expires in <span id="minutes">{{ $expiresInMinutes }}</span>min: <span
                id="seconds">{{ $expiresInSeconds }}</span>sec
        </div>
        <div class="status">
            <span class="status-icon"></span> Waiting For payment
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Timer functionality
            let minutes = {{ $expiresInMinutes }};
            let seconds = {{ $expiresInSeconds }};

            const timer = setInterval(function() {
                if (seconds === 0) {
                    if (minutes === 0) {
                        clearInterval(timer);
                        document.getElementById('timer').innerHTML = 'Payment Expired';
                        document.querySelector('.status').innerHTML =
                            '<span style="color: #f44336;">Payment expired</span>';
                        return;
                    }
                    minutes--;
                    seconds = 59;
                } else {
                    seconds--;
                }

                document.getElementById('minutes').textContent = minutes;
                document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            }, 1000);

            // Check payment status
            function checkPaymentStatus() {
                fetch('/check_status?payment_id={{ $payment->id }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            clearInterval(timer);
                            clearInterval(statusCheck);
                            document.querySelector('.status').innerHTML =
                                '<span style="color: #4caf50;">Payment received successfully!</span>';
                            // Redirect after 3 seconds
                            setTimeout(function() {
                                window.location.href = '/dashboard';
                            }, 3000);
                        } else if (data.status === 'expired') {
                            clearInterval(timer);
                            clearInterval(statusCheck);
                            document.querySelector('.status').innerHTML =
                                '<span style="color: #f44336;">Payment expired</span>';
                        }
                    })
                    .catch(error => {
                        console.error('Error checking payment status:', error);
                    });
            }

            // Check status every 10 seconds
            const statusCheck = setInterval(checkPaymentStatus, 10000);

            // Initial check
            checkPaymentStatus();
        });
    </script>
</body>

</html>
