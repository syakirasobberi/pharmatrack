<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Your PharmaTrack Health Summary</title>
</head>
<body style="margin:0;background:#f4f4f5;font-family:Arial,sans-serif;color:#18181b;">
    <div style="max-width:600px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e4e4e7;">
            <h1 style="margin:0 0 16px;font-size:24px;color:#1d4ed8;">Your Health Summary</h1>
            <p style="margin:0 0 14px;line-height:1.6;">Hello {{ $patient->user->name }},</p>
            <p style="margin:0 0 14px;line-height:1.6;">
                {{ $pharmacist->name }} has sent your latest PharmaTrack health summary. The PDF is attached to this email.
            </p>
            <p style="margin:0;line-height:1.6;color:#52525b;">
                This document contains personal health information. Please keep it secure and avoid forwarding it to anyone you do not trust.
            </p>
        </div>
        <p style="margin:16px 0 0;text-align:center;font-size:12px;color:#71717a;">
            Sent from the PharmaTrack pharmacist portal.
        </p>
    </div>
</body>
</html>
