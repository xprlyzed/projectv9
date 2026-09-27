{{-- Ortak e-posta düzeni: tablo tabanlı, satır-içi (inline) CSS, mobil uyumlu.
     Marka rengi e-posta istemcileri CSS değişkeni desteklemediği için sabit yazılır. --}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>@yield('title', config('app.name'))</title>
</head>
<body style="margin:0; padding:0; background:#eef1f8; -webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef1f8; margin:0; padding:0;">
        <tr>
            <td align="center" style="padding:32px 14px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(16,24,40,.10);">

                    {{-- Header --}}
                    <tr>
                        <td align="center" style="background:linear-gradient(135deg,#1d4ed8,#1e40af); padding:28px 30px;">
                            <span style="font-family:Arial,Helvetica,sans-serif; font-size:24px; font-weight:800; color:#ffffff; letter-spacing:-.5px;">artirdim<span style="color:#22c8e0;">.com</span></span>
                            <div style="margin-top:4px; font-family:Arial,Helvetica,sans-serif; font-size:10px; letter-spacing:2.5px; color:#bcd2ff;">LIVE AUCTION PLATFORM</div>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 30px 24px; font-family:Arial,Helvetica,sans-serif; color:#344054;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 30px; background:#f8fafc; border-top:1px solid #eaecf0; font-family:Arial,Helvetica,sans-serif; text-align:center;">
                            <div style="font-size:12px; color:#667085; line-height:1.6;">
                                Yardıma mı ihtiyacın var? <a href="mailto:destek@artirdim.com" style="color:#1d4ed8; text-decoration:none;">destek@artirdim.com</a>
                            </div>
                            <div style="font-size:11px; color:#98a2b3; margin-top:8px; line-height:1.6;">
                                Bu e-postayı siz talep etmediyseniz güvenle yok sayabilirsiniz.<br>
                                © {{ date('Y') }} {{ config('app.name') }} · Tüm hakları saklıdır.
                            </div>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>
