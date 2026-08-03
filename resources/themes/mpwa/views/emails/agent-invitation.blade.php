<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('Agent Invitation') }}</title>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background:#edf2f7; margin:0; padding:0; color:#718096; }
.wrap { max-width:570px; margin:40px auto; background:#fff; border-radius:4px; box-shadow:0 2px 4px rgba(0,0,0,.06); overflow:hidden; }
.header { padding:24px 32px; text-align:center; border-bottom:1px solid #edf2f7; }
.header a { color:#3d4852; font-size:20px; font-weight:700; text-decoration:none; }
.body { padding:32px; }
h1 { color:#3d4852; font-size:18px; font-weight:700; margin:0 0 16px; }
p { font-size:15px; line-height:1.6; margin:0 0 16px; }
.btn { display:inline-block; background:#128c7e; color:#fff !important; text-decoration:none; border-radius:4px; padding:12px 28px; font-size:15px; font-weight:600; margin:20px 0; }
.meta { font-size:13px; color:#a0aec0; background:#f7fafc; border-radius:4px; padding:12px 16px; margin:20px 0 0; }
.meta strong { color:#4a5568; }
.footer { padding:20px 32px; text-align:center; font-size:12px; color:#b0adc5; border-top:1px solid #edf2f7; }
.url-copy { word-break:break-all; font-size:13px; color:#3869d4; }
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <a href="{{ config('app.url') }}">{{ config('app.name') }}</a>
    </div>
    <div class="body">
        <h1>{{ __('Hello, :name!', ['name' => $agent->name]) }}</h1>
        <p>{{ __('You have been invited to join :app as a support agent. Click the button below to set up your account and start handling conversations.', ['app' => config('app.name')]) }}</p>

        <div style="text-align:center">
            <a href="{{ $acceptUrl }}" class="btn">{{ __('Accept Invitation & Set Password') }}</a>
        </div>

        <div class="meta">
            <strong>{{ __('Role') }}:</strong> {{ ucfirst($agent->role) }}<br>
            @if($agent->team)
            <strong>{{ __('Team') }}:</strong> {{ $agent->team->name }}<br>
            @endif
            <strong>{{ __('Expires') }}:</strong> {{ $expiresAt->format('d M Y, H:i') }}
        </div>

        <p style="margin-top:20px;font-size:13px;color:#a0aec0">
            {{ __('If the button does not work, copy and paste this link into your browser:') }}<br>
            <a href="{{ $acceptUrl }}" class="url-copy">{{ $acceptUrl }}</a>
        </p>

        <p>{{ __('Regards,') }}<br><strong>{{ config('app.name') }}</strong></p>
    </div>
    <div class="footer">
        {{ __('© :year :app. All rights reserved.', ['year' => date('Y'), 'app' => config('app.name')]) }}
    </div>
</div>
</body>
</html>
