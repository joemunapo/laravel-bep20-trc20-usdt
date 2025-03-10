<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USDT Payment - Enter Amount</title>
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
            background-color: #f9a825;
            color: black;
            padding: 15px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
        
        .form-container {
            background-color: #e8f5e9;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        input:focus {
            outline: none;
            border-color: #f9a825;
            box-shadow: 0 0 5px rgba(249, 168, 37, 0.5);
        }
        
        .error {
            color: #e53935;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .submit-btn {
            background-color: #f9a825;
            color: black;
            border: none;
            padding: 12px 0;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .submit-btn:hover {
            background-color: #f57f17;
        }
        
        .alert {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #c62828;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            Enter the amount of USDT you want to send/pay
        </div>
        
        <div class="form-container">
            @if(session('error'))
                <div class="alert">
                    {{ session('error') }}
                </div>
            @endif
            
            <form method="POST" action="{{ url('/usdt-bep20') }}">
                @csrf
                
                <div class="form-group">
                    <label for="amount">Amount (MIN: 1 USDT)</label>
                    <input type="number" 
                           step="0.01" 
                           min="1" 
                           id="amount"
                           name="amount" 
                           value="{{ old('amount') }}" 
                           required>
                    
                    @error('amount')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="submit-btn">Submit</button>
            </form>
        </div>
    </div>
</body>
</html>