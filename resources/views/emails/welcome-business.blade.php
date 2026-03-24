<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ManagerIO</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; color: #1e293b; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #2563eb, #4f46e5); padding: 32px 40px; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.8); margin: 6px 0 0; font-size: 14px; }
        .body { padding: 32px 40px; }
        .body p { font-size: 15px; line-height: 1.7; color: #475569; margin: 0 0 16px; }
        .step { display: flex; gap: 12px; margin-bottom: 14px; align-items: flex-start; }
        .step-num { background: #eff6ff; color: #2563eb; border-radius: 50%; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; margin-top: 2px; }
        .step-text { font-size: 14px; color: #475569; line-height: 1.5; }
        .step-text strong { color: #1e293b; }
        .cta { display: block; text-align: center; background: linear-gradient(135deg, #2563eb, #4f46e5); color: #fff; text-decoration: none; font-weight: 600; font-size: 15px; padding: 14px 28px; border-radius: 8px; margin: 28px 0 8px; }
        .footer { padding: 20px 40px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Welcome to ManagerIO!</h1>
            <p>Your business <strong style="color:#fff">{{ $business->name }}</strong> is ready to go.</p>
        </div>
        <div class="body">
            <p>Hi {{ $owner->name }},</p>
            <p>
                Your ManagerIO workspace is set up and ready. Here are a few things to get you started:
            </p>

            <div class="step">
                <div class="step-num">1</div>
                <div class="step-text"><strong>Add your clients</strong> — Go to Sales → Customers to add the translation clients you invoice.</div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-text"><strong>Add your translators</strong> — Go to Purchases → Suppliers to add your freelance translators and editors.</div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-text"><strong>Create your first invoice</strong> — Go to Sales → Invoices → New Invoice to bill your first client.</div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-text"><strong>Review your chart of accounts</strong> — Under Accounting → Chart of Accounts, customise the default accounts for your business.</div>
            </div>
            <div class="step">
                <div class="step-num">5</div>
                <div class="step-text"><strong>Check the docs</strong> — Visit the Getting Started guide for a full walkthrough.</div>
            </div>

            <a href="{{ config('app.url') }}/dashboard" class="cta">Go to Dashboard →</a>

            <p style="font-size:13px; color:#94a3b8; text-align:center; margin-top:8px;">
                Questions? Reply to this email and we'll help you out.
            </p>
        </div>
        <div class="footer">
            © {{ date('Y') }} ManagerIO · Built for the translation industry
        </div>
    </div>
</body>
</html>
