

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New Eightfinity Booking</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h1 style="font-size: 22px; margin-bottom: 8px;">New booking received</h1>
    <p style="margin-top: 0;">A customer has submitted a booking payment check.</p>

    <table cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 640px;">
        <tr>
            <td style="font-weight: bold; border: 1px solid #e5e7eb;">Booking Code</td>
            <td style="border: 1px solid #e5e7eb;">{{ $booking->booking_code }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; border: 1px solid #e5e7eb;">Customer</td>
            <td style="border: 1px solid #e5e7eb;">{{ $booking->user?->name }} &lt;{{ $booking->user?->email }}&gt;</td>
        </tr>
        <tr>
            <td style="font-weight: bold; border: 1px solid #e5e7eb;">Package</td>
            <td style="border: 1px solid #e5e7eb;">{{ $booking->package_name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; border: 1px solid #e5e7eb;">Schedule</td>
            <td style="border: 1px solid #e5e7eb;">{{ $booking->booking_date->format('d M Y') }} at {{ $booking->booking_time }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; border: 1px solid #e5e7eb;">People</td>
            <td style="border: 1px solid #e5e7eb;">{{ $booking->people }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; border: 1px solid #e5e7eb;">Address</td>
            <td style="border: 1px solid #e5e7eb;">{{ $booking->customer_address }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; border: 1px solid #e5e7eb;">Location</td>
            <td style="border: 1px solid #e5e7eb;">{{ $booking->booking_location }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; border: 1px solid #e5e7eb;">Total</td>
            <td style="border: 1px solid #e5e7eb;">Rp {{ number_format($booking->amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; border: 1px solid #e5e7eb;">Payment</td>
            <td style="border: 1px solid #e5e7eb;">{{ strtoupper($booking->payment_provider) }} - {{ $booking->status }}</td>
        </tr>
    </table>

    <p>
        Open the admin dashboard to review this booking:
        <a href="{{ $adminBookingsUrl }}">{{ $adminBookingsUrl }}</a>
    </p>
</body>
</html>
