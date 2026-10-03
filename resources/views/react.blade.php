<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>EightFinity</title>
    <link rel="icon" type="image/png" href="{{ asset('image/logo-icon-transparent.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('image/logo-icon.png') }}">
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
    @isset($bookingAvailability)
        <script>
            window.__BOOKING_AVAILABILITY__ = @json($bookingAvailability);
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
    @isset($adminRevenue)
        <script>
            window.__ADMIN_REVENUE__ = @json($adminRevenue);
        </script>
    @endisset
    @isset($adminProfile)
        <script>
            window.__ADMIN_PROFILE__ = @json($adminProfile);
        </script>
    @endisset
    @isset($adminTwoFactor)
        <script>
            window.__ADMIN_TWO_FACTOR__ = @json($adminTwoFactor);
        </script>
    @endisset
    @isset($userTwoFactor)
        <script>
            window.__USER_TWO_FACTOR__ = @json($userTwoFactor);
        </script>
    @endisset
    @php
        $webUser = auth('web')->user();
        $authPayload = array_merge([
            'authenticated' => (bool) $webUser,
            'two_factor_enabled' => (bool) ($webUser?->two_factor_enabled ?? false),
            'show_two_factor_reminder' => (bool) session('show_two_factor_reminder', false),
        ], isset($auth) && is_array($auth) ? $auth : []);
    @endphp
    <script>
        window.__AUTH__ = @json($authPayload);
    </script>
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
