<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $brand['name'] }}</title>
    <style>
        body { margin: 0; padding: 0; }
        .email-content p { margin: 0 0 16px; } .email-content p:last-child { margin-bottom: 0; }
        .email-content h1, .email-content h2, .email-content h3 { margin: 0 0 16px; color: #111827; line-height: 1.3; }
        .email-content h2 { font-size: 21px; } .email-content h3 { font-size: 17px; }
        .email-content ul, .email-content ol { margin: 0 0 16px; padding-left: 22px; }
        .email-content li { margin-bottom: 8px; }
        .email-content a { color: {{ $brand['color'] }}; }
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; }
            .email-content { padding: 28px 22px !important; }
            .email-outer { padding: 20px 12px !important; }
            .email-button { width: 100% !important; }
            .email-button a { display: block !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; color: #1f2937; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-text-size-adjust: 100%;">
    <div style="display: none; max-height: 0; overflow: hidden; opacity: 0; color: transparent;">
        A message from {{ $brand['name'] }}.
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f3f4f6">
        <tr>
            <td class="email-outer" align="center" style="padding: 40px 16px;">
                <table class="email-container" role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width: 600px; max-width: 600px;">
                    <tr>
                        <td align="center" style="padding: 0 0 20px;">
                            <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" height="36" style="display: block; margin: 0 auto; border: 0; height: 36px; width: auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="overflow: hidden; border: 1px solid #e5e7eb; border-radius: 12px; background-color: #ffffff;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td height="5" bgcolor="{{ $brand['color'] }}" style="height: 5px; background-color: {{ $brand['color'] }}; font-size: 0; line-height: 0;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td class="email-content" style="padding: 40px; color: #374151; font-size: 15px; line-height: 1.7;">
                                        {!! $body !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 22px 12px 0; color: #6b7280; font-size: 12px; line-height: 18px; text-align: center;">
                            <p style="margin: 0 0 8px;">You are receiving this email because you have an account with {{ $brand['name'] }}.</p>
                            @if ($brand['email'] || $brand['phone'] || $brand['address'])
                                <p style="margin: 0 0 8px;">
                                    @if ($brand['email']) <a href="mailto:{{ $brand['email'] }}" style="color: #6b7280; text-decoration: underline;">{{ $brand['email'] }}</a> @endif
                                    @if ($brand['phone']) <span>&nbsp;&middot;&nbsp;{{ $brand['phone'] }}</span> @endif
                                    @if ($brand['address']) <span>&nbsp;&middot;&nbsp;{{ $brand['address'] }}</span> @endif
                                </p>
                            @endif
                            <p style="margin: 0;">&copy; {{ now()->year }} {{ $brand['name'] }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
