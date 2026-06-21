<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>EightFinity</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/react/App.jsx'])
</head>
<body>
    <div id="root"></div>
    @isset($booking)
        <script>
            window.__BOOKING__ = @json($booking);
        </script>
    @endisset
    @isset($profile)
        <script>
            window.__USER_PROFILE__ = @json($profile);
        </script>
    @endisset
    @isset($adminBookings)
        <script>
            window.__ADMIN_BOOKINGS__ = @json($adminBookings);
        </script>
    @endisset
    @isset($adminDashboard)
        <script>
            window.__ADMIN_DASHBOARD__ = @json($adminDashboard);
        </script>
    @endisset
    @isset($adminQueue)
        <script>
            window.__ADMIN_QUEUE__ = @json($adminQueue);
        </script>
    @endisset
    @isset($adminCustomers)
        <script>
            window.__ADMIN_CUSTOMERS__ = @json($adminCustomers);
        </script>
    @endisset
    @isset($adminLayout)
        <script>
            window.__ADMIN_LAYOUT__ = @json($adminLayout);
        </script>
    @endisset
    @isset($verificationEmail)
        <script>
            window.__VERIFY_EMAIL__ = @json($verificationEmail);
        </script>
    @endisset
    @isset($verificationStatus)
        <script>
            window.__FLASH_STATUS__ = @json($verificationStatus);
        </script>
    @endisset
    @isset($authStatus)
        <script>
            window.__AUTH_STATUS__ = @json($authStatus);
        </script>
    @endisset
    @isset($resetToken)
        <script>
            window.__RESET_TOKEN__ = @json($resetToken);
        </script>
    @endisset
    @isset($resetEmail)
        <script>
            window.__RESET_EMAIL__ = @json($resetEmail);
        </script>
    @endisset
    @if ($errors->any())
        <script>
            window.__FORM_ERRORS__ = @json($errors->all());
        </script>
    @endif
</body>
</html>
