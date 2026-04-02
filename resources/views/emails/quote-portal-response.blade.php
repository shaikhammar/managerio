<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quote Response</title>
</head>
<body style="font-family: sans-serif; color: #1a1a1a; padding: 32px; background: #f9fafb;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin-top: 0; margin-bottom: 8px; font-size: 20px;">
            Quote {{ $quote->number }} —
            <span style="color: {{ $decision === 'approved' ? '#059669' : '#dc2626' }};">
                {{ $decision === 'approved' ? 'Approved' : 'Rejected' }}
            </span>
        </h2>

        <p style="margin-bottom: 4px;"><strong>Client:</strong> {{ $quote->contact?->name ?? 'Unknown' }}</p>
        <p style="margin-bottom: 16px;"><strong>Total:</strong> {{ number_format($quote->total, 2) }} {{ $quote->currency_code }}</p>

        @if($quote->portal_comment)
        <div style="background: #f3f4f6; border-left: 3px solid #d1d5db; padding: 12px 16px; border-radius: 4px; margin-bottom: 24px;">
            <p style="margin: 0 0 4px; font-size: 12px; text-transform: uppercase; color: #6b7280; letter-spacing: 0.05em;">Client Comment</p>
            <p style="margin: 0; color: #374151;">{{ $quote->portal_comment }}</p>
        </div>
        @endif

        <a href="{{ route('sales.quotes.show', $quote) }}"
           style="display: inline-block; background: #2563eb; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500;">
            View Quote in App
        </a>
    </div>
</body>
</html>
