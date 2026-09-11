<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-RIDE Driver Payment</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f6f8fc; font-family: Inter, Arial, sans-serif; color: #190a62; }
        main { width: min(92vw, 420px); padding: 42px 30px; text-align: center; background: #fff; border-radius: 24px; box-shadow: 0 18px 55px rgba(25,10,98,.10); }
        img { width: 74px; height: 74px; object-fit: contain; }
        h1 { margin: 24px 0 10px; font-size: 25px; }
        p { margin: 0; line-height: 1.6; color: #68708a; }
        .note { margin-top: 22px; padding: 13px 15px; border-radius: 12px; background: #eefbfc; color: #187e89; font-size: 13px; }
    </style>
</head>
<body>
<main>
    <img src="{{ asset('storage/logo.png') }}" alt="E-RIDE" onerror="this.style.display='none'">
    <h1>Payment received</h1>
    <p>Your OPay checkout has completed. You can safely return to the E-RIDE Driver app.</p>
    <div class="note">Your balance and remittance status will update after server confirmation.</div>
</main>
</body>
</html>
