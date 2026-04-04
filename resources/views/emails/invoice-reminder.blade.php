<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Reminder</title>
</head>
<body style="font-family: sans-serif; color: #1a1a1a; padding: 32px; background: #f9fafb;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin-top: 0; margin-bottom: 16px; font-size: 20px; color: #111827;">
            Payment Reminder — {{ $invoice->number }}
        </h2>
        <p>Dear {{ $invoice->contact?->name ?? 'Valued Client' }},</p>
        <p style="color: #374151;">This is a friendly reminder that the following invoice is overdue.</p>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
            <tr style="background: #f9fafb;">
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600;">Invoice</td>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb;">{{ $invoice->number }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600;">Due Date</td>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; color: #dc2626;">{{ $invoice->due_date?->format('d M Y') }}</td>
            </tr>
            <tr style="background: #f9fafb;">
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600;">Amount Due</td>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600;">{{ number_format($invoice->balance_due, 2) }} {{ $invoice->currency_code }}</td>
            </tr>
        </table>
        <p style="color: #6b7280; font-size: 14px;">Please arrange payment at your earliest convenience.</p>
    </div>
</body>
</html>
