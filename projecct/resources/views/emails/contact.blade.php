@extends('emails.layout')

@section('title', 'Yeni İletişim Mesajı')

@section('content')
    <h1 style="margin:0 0 6px; font-size:20px; font-weight:700; color:#101828;">Yeni İletişim Mesajı</h1>
    <p style="margin:0 0 20px; font-size:13px; line-height:1.6; color:#667085;">
        İletişim formu aracılığıyla yeni bir mesaj aldınız.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
        <tr>
            <td width="48%" valign="top" style="background:#f8fafc; border:1px solid #eaecf0; border-radius:12px; padding:14px 16px;">
                <div style="font-size:11px; font-weight:700; color:#98a2b3; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px;">Gönderen</div>
                <div style="font-size:14px; font-weight:600; color:#101828;">{{ $name }}</div>
            </td>
            <td width="4%"></td>
            <td width="48%" valign="top" style="background:#f8fafc; border:1px solid #eaecf0; border-radius:12px; padding:14px 16px;">
                <div style="font-size:11px; font-weight:700; color:#98a2b3; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px;">E-posta</div>
                <div style="font-size:14px; font-weight:600; color:#1d4ed8; word-break:break-all;">{{ $email }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-bottom:14px;">
        <div style="font-size:11px; font-weight:700; color:#98a2b3; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px;">Konu</div>
        <span style="display:inline-block; padding:5px 14px; border-radius:20px; background:#eaf1ff; color:#1d4ed8; font-size:13px; font-weight:700; border:1px solid #cfe0ff;">
            {{ $subject }}
        </span>
    </div>

    <div style="border-top:1px solid #eaecf0; margin:0 0 16px;"></div>

    <div style="margin-bottom:24px;">
        <div style="font-size:11px; font-weight:700; color:#98a2b3; text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px;">Mesaj</div>
        <div style="background:#f8fafc; border:1px solid #eaecf0; border-radius:12px; padding:18px; font-size:14px; color:#344054; line-height:1.75; white-space:pre-wrap;">{{ $userMessage }}</div>
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td align="center" style="border-radius:10px; background:linear-gradient(135deg,#1d4ed8,#1e40af);">
                <a href="mailto:{{ $email }}" style="display:inline-block; padding:12px 28px; font-family:Arial,Helvetica,sans-serif; font-size:14px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:10px;">
                    Yanıtla →
                </a>
            </td>
        </tr>
    </table>
@endsection
