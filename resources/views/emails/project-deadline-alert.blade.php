<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Deadline Reminder</title>
</head>
<body style="font-family: sans-serif; color: #1a1a1a; padding: 32px; background: #f9fafb;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin-top: 0; margin-bottom: 16px; font-size: 20px; color: #111827;">
            Deadline in 2 Days — {{ $project->name }}
        </h2>
        <p style="margin-bottom: 16px; color: #374151;">
            Dear {{ $translator->name }}, this is a reminder that your assignment on the following project is due in 2 days.
        </p>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
            <tr style="background: #f9fafb;">
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600;">Project</td>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb;">{{ $project->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600;">Reference</td>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb;">{{ $project->reference }}</td>
            </tr>
            <tr style="background: #f9fafb;">
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600;">Deadline</td>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; color: #d97706; font-weight: 600;">
                    {{ $project->deadline?->format('d M Y') }}
                </td>
            </tr>
        </table>
        <p style="color: #6b7280; font-size: 14px;">Please ensure your deliverables are submitted on time.</p>
    </div>
</body>
</html>
