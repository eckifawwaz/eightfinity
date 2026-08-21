<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #172033; line-height: 1.5;">
    <h1 style="color: #202660; font-size: 22px; margin-bottom: 8px;">{{ $title }}</h1>
    <p style="margin-top: 0;">{{ $body }}</p>

    @if ($actionUrl)
        <p>
            <a href="{{ $actionUrl }}" style="color: #24598f; font-weight: 700;">
                {{ $actionLabel ?: $actionUrl }}
            </a>
        </p>
    @endif
</body>
</html>
