@extends('emails.layout')

@section('title', 'Şifreni Sıfırla')

@section('content')
    <h1 style="margin:0 0 8px; font-size:20px; font-weight:700; color:#101828;">Şifreni Sıfırla</h1>
    <p style="margin:0 0 20px; font-size:14px; line-height:1.7; color:#475467;">
        Merhaba <strong style="color:#101828;">{{ $user->name }}</strong>,<br><br>
        Hesabın için bir şifre sıfırlama talebi aldık. Yeni bir şifre belirlemek için
        aşağıdaki butona tıkla. Bu bağlantı kısa süre içinde geçerliliğini yitirir.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 20px;">
        <tr>
            <td align="center" style="border-radius:10px; background:linear-gradient(135deg,#1d4ed8,#1e40af);">
                <a href="{{ $actionUrl }}" style="display:inline-block; padding:13px 30px; font-family:Arial,Helvetica,sans-serif; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:10px;">
                    Şifreyi Sıfırla
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:12px; line-height:1.6; color:#667085;">
        Buton çalışmazsa aşağıdaki bağlantıyı tarayıcına kopyala:<br>
        <a href="{{ $actionUrl }}" style="color:#1d4ed8; word-break:break-all;">{{ $actionUrl }}</a>
    </p>
@endsection
