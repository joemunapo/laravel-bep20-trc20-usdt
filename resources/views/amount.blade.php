<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USDT Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
        }
        .header {
            background-color: #2e7d32;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border-radius: 4px 4px 0 0;
        }
        .content {
            background-color: #e8f5e9;
            padding: 20px;
            border-radius: 0 0 4px 4px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            color: #2e7d32;
            font-size: 16px;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 12px 25px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            Enter the amount of USDT you want to send/pay
        </div>
        <div class="content">
            <form action="/usdt-payment" method="POST">
                @csrf
                <div class="form-group">
                    <label for="amount">Amount (MIN: 3 USDT)</label>
                    <input type="number" id="amount" name="amount" min="3" step="0.01" required>
                </div>
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>
</body>
</html>