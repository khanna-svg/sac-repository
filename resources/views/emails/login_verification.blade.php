<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} - AIRIS Login Verification Code</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.6;">

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 32px 16px;">
        <tr>
            <td align="center">
                
                <!-- Main Container Card -->
                <table role="presentation" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    
                    <!-- AIRIS Signature Maroon Header -->
                    <tr>
                        <td style="background-color: #700000; padding: 24px 30px; text-align: left; border-bottom: 3px solid #FFD700;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="width: 44px; vertical-align: middle;">
                                        <div style="background-color: #ffffff; width: 38px; height: 38px; border-radius: 10px; border: 1px solid rgba(255,215,0,0.4); text-align: center; line-height: 38px;">
                                            <img src="{{ url('/images/airis-logo.png') }}" width="26" height="26" style="vertical-align: middle; display: inline-block;" alt="AIRIS Logo">
                                        </div>
                                    </td>
                                    <td style="padding-left: 12px; vertical-align: middle;">
                                        <h1 style="margin: 0; color: #FFD700; font-size: 18px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase;">
                                            AIRIS
                                        </h1>
                                        <p style="margin: 2px 0 0; color: #fef3c7; font-size: 11px; font-weight: 500; opacity: 0.9;">
                                            St. Anthony's College Institutional Research Repository
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Email Content Body -->
                    <tr>
                        <td style="padding: 32px 30px;">
                            <h2 style="margin: 0 0 12px; color: #0f172a; font-size: 18px; font-weight: 700;">
                                Account Verification Code
                            </h2>
                            <p style="margin: 0 0 16px; color: #475569; font-size: 14px;">
                                Hello <strong>{{ $name }}</strong>,
                            </p>
                            <p style="margin: 0 0 20px; color: #475569; font-size: 14px; line-height: 1.6;">
                                You recently initiated a sign-in request to the AIRIS repository with your official institutional email address (<span style="color: #700000; font-weight: 600;">{{ $email }}</span>). Use the 6-digit verification code below to complete your login:
                            </p>

                            <!-- 6-Digit Code Display Card -->
                            <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 16px; padding: 22px 20px; text-align: center; margin: 24px 0;">
                                <p style="margin: 0 0 6px; font-size: 11px; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 0.1em;">
                                    One-Time Verification Code
                                </p>
                                <div style="font-family: 'JetBrains Mono', 'Courier New', Courier, monospace; font-size: 36px; font-weight: 800; letter-spacing: 10px; color: #700000; padding: 6px 0;">
                                    {{ $code }}
                                </div>
                                <p style="margin: 6px 0 0; font-size: 12px; color: #78350f; font-weight: 500;">
                                    Expires in <strong>10 minutes</strong> (single use only)
                                </p>
                            </div>

                            <!-- Security Notes -->
                            <div style="background-color: #f8fafc; border-left: 3px solid #700000; padding: 14px 16px; border-radius: 0 10px 10px 0; margin-bottom: 24px;">
                                <p style="margin: 0; font-size: 12px; color: #475569; line-height: 1.6;">
                                    <strong>Security Notice:</strong> St. Anthony's College staff will never ask for your verification code. Do not share this code with anyone. If you did not make this request, you can safely disregard this email.
                                </p>
                            </div>

                            <p style="margin: 0; font-size: 13px; color: #64748b;">
                                Best regards,<br>
                                <strong style="color: #334155;">Research &amp; Systems Office (RESO)</strong><br>
                                St. Anthony's College
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f1f5f9; padding: 20px 30px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0 0 4px; font-size: 11px; color: #64748b; font-weight: 600;">
                                AIRIS - AI-Powered Institutional Research Repository
                            </p>
                            <p style="margin: 0; font-size: 10px; color: #94a3b8;">
                                St. Anthony's College • San Jose de Buenavista, Antique 5700, Philippines
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
