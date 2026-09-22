<?php

namespace App\Support;

use App\Models\Booking;

class SimpleReceiptPdf
{
    private const BLUE = [0.141, 0.349, 0.561];
    private const ORANGE = [0.965, 0.667, 0.231];
    private const INK = [0.149, 0.165, 0.192];
    private const MUTED = [0.482, 0.522, 0.576];
    private const BORDER = [0.878, 0.894, 0.914];
    private const PAPER = [0.969, 0.976, 0.988];
    private const WHITE = [1, 1, 1];
    private const GREEN = [0.102, 0.557, 0.357];
    private const GREEN_BG = [0.906, 0.973, 0.941];
    private const ORANGE_BG = [1, 0.969, 0.914];

    public static function make(Booking $booking): string
    {
        $booking->loadMissing('user:id,name,email');

        $duration = BookingLifecycle::durationHours($booking).' jam';
        $arrival = BookingLifecycle::teamArrivalTime($booking);
        $paid = in_array($booking->midtrans_status, ['settlement', 'capture'], true)
            || in_array($booking->status, ['confirmed', 'completed'], true);
        $status = $paid ? 'PEMBAYARAN BERHASIL' : strtoupper((string) $booking->status);
        $statusColor = $paid ? self::GREEN : self::ORANGE;
        $statusBg = $paid ? self::GREEN_BG : self::ORANGE_BG;

        $commands = [];
        $commands[] = self::fillRect(0, 0, 595, 842, self::PAPER);

        // Header / brand area.
        $commands[] = self::fillRect(0, 702, 595, 140, self::BLUE);
        $commands[] = self::text(44, 794, 'Eight', 24, true, self::WHITE);
        $commands[] = self::text(100, 794, 'Finity', 24, true, self::ORANGE);
        $commands[] = self::text(44, 767, 'PAYMENT RECEIPT', 10, true, [0.831, 0.906, 0.965]);
        $commands[] = self::text(44, 735, 'Terima kasih telah memilih EightFinity.', 10, false, [0.902, 0.941, 0.976]);

        // Booking identity card.
        $commands[] = self::fillRect(44, 642, 507, 92, self::WHITE);
        $commands[] = self::strokeRect(44, 642, 507, 92, self::BORDER);
        $commands[] = self::text(62, 705, 'BOOKING ID', 8, true, self::MUTED);
        $commands[] = self::text(62, 680, '#'.$booking->booking_code, 16, true, self::INK);
        $commands[] = self::fillRect(358, 676, 172, 31, $statusBg);
        $commands[] = self::text(370, 687, $status, 9, true, $statusColor);
        $commands[] = self::text(358, 657, 'Receipt dibuat otomatis oleh EightFinity', 7, false, self::MUTED);

        // Main cards.
        $leftX = 44;
        $rightX = 306;
        $cardY = 392;
        $cardW = 245;
        $cardH = 228;
        $commands[] = self::fillRect($leftX, $cardY, $cardW, $cardH, self::WHITE);
        $commands[] = self::strokeRect($leftX, $cardY, $cardW, $cardH, self::BORDER);
        $commands[] = self::fillRect($rightX, $cardY, $cardW, $cardH, self::WHITE);
        $commands[] = self::strokeRect($rightX, $cardY, $cardW, $cardH, self::BORDER);

        $commands[] = self::text(62, 590, 'DETAIL BOOKING', 10, true, self::BLUE);
        $y = 565;
        $y = self::labelValue($commands, 62, $y, 'Paket', $booking->package_name ?: '-', 28);
        $y = self::labelValue($commands, 62, $y, 'Tanggal', $booking->booking_date?->format('d M Y') ?: '-', 28);
        $y = self::labelValue($commands, 62, $y, 'Waktu', $booking->booking_time ?: '-', 28);
        $y = self::labelValue($commands, 62, $y, 'Durasi', $duration, 28);
        $y = self::labelValue($commands, 62, $y, 'Ukuran Booth', $booking->booth_size ?: '-', 28);

        $commands[] = self::text(324, 590, 'CUSTOMER & LOCATION', 10, true, self::BLUE);
        $y = 565;
        $y = self::labelValue($commands, 324, $y, 'Nama', $booking->user?->name ?: '-', 28);
        $y = self::labelValue($commands, 324, $y, 'Email', $booking->user?->email ?: '-', 28);
        $y = self::labelValue($commands, 324, $y, 'Lokasi', $booking->booking_location ?: '-', 28);
        $y = self::labelValue($commands, 324, $y, 'Alamat', $booking->customer_address ?: '-', 28, 32);

        // Payment summary.
        $commands[] = self::fillRect(44, 300, 507, 72, self::BLUE);
        $commands[] = self::text(62, 344, 'TOTAL PEMBAYARAN', 9, true, [0.831, 0.906, 0.965]);
        $commands[] = self::text(62, 318, 'Rp '.number_format((int) $booking->amount, 0, ',', '.'), 20, true, self::WHITE);
        $commands[] = self::text(370, 337, 'Metode', 8, true, [0.831, 0.906, 0.965]);
        $paymentMethod = strtoupper(trim(($booking->midtrans_payment_type ?: $booking->payment_provider ?: $booking->payment_method ?: '-')));
        $commands[] = self::text(370, 316, $paymentMethod, 11, true, self::WHITE);

        // Instructions card.
        $commands[] = self::fillRect(44, 154, 507, 124, self::WHITE);
        $commands[] = self::strokeRect(44, 154, 507, 124, self::BORDER);
        $commands[] = self::text(62, 250, 'INFORMASI PENTING', 10, true, self::BLUE);
        $commands[] = self::fillRect(62, 210, 6, 25, self::ORANGE);
        $commands[] = self::text(80, 226, 'Tim EightFinity tiba 1 jam lebih awal ('.$arrival.') untuk instalasi.', 9, false, self::INK);
        $commands[] = self::fillRect(62, 174, 6, 25, self::ORANGE);
        $commands[] = self::text(80, 190, 'Reschedule tersedia sebelum memasuki H-3. Hubungi admin EightFinity.', 9, false, self::INK);

        // Footer.
        $commands[] = self::line(44, 126, 551, 126, self::BORDER);
        $commands[] = self::text(44, 101, 'Simpan receipt ini sebagai bukti pemesanan.', 9, true, self::INK);
        $commands[] = self::text(44, 82, 'eightfinityphoto@gmail.com', 8, false, self::MUTED);
        $commands[] = self::text(433, 82, 'EightFinity 2026', 8, false, self::MUTED);

        return self::render(implode('', $commands));
    }

    private static function labelValue(array &$commands, float $x, float $y, string $label, string $value, int $maxChars = 28, int $lineHeight = 12): float
    {
        $commands[] = self::text($x, $y, strtoupper($label), 7, true, self::MUTED);
        $y -= 15;
        $lines = self::wrap($value, $maxChars);
        foreach ($lines as $line) {
            $commands[] = self::text($x, $y, $line, 9, true, self::INK);
            $y -= $lineHeight;
        }

        return $y - 9;
    }

    private static function wrap(string $text, int $maxChars): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if ($text === '') {
            return ['-'];
        }

        return explode("\n", wordwrap($text, $maxChars, "\n", true));
    }

    private static function text(float $x, float $y, string $text, float $size, bool $bold = false, array $color = self::INK): string
    {
        $font = $bold ? 'F2' : 'F1';
        $encoded = self::pdfText($text);
        [$r, $g, $b] = $color;

        return sprintf(
            "BT\n%.3F %.3F %.3F rg\n/%s %.2F Tf\n1 0 0 1 %.2F %.2F Tm\n(%s) Tj\nET\n",
            $r,
            $g,
            $b,
            $font,
            $size,
            $x,
            $y,
            $encoded,
        );
    }

    private static function fillRect(float $x, float $y, float $w, float $h, array $color): string
    {
        [$r, $g, $b] = $color;

        return sprintf("q\n%.3F %.3F %.3F rg\n%.2F %.2F %.2F %.2F re\nf\nQ\n", $r, $g, $b, $x, $y, $w, $h);
    }

    private static function strokeRect(float $x, float $y, float $w, float $h, array $color): string
    {
        [$r, $g, $b] = $color;

        return sprintf("q\n%.3F %.3F %.3F RG\n0.8 w\n%.2F %.2F %.2F %.2F re\nS\nQ\n", $r, $g, $b, $x, $y, $w, $h);
    }

    private static function line(float $x1, float $y1, float $x2, float $y2, array $color): string
    {
        [$r, $g, $b] = $color;

        return sprintf("q\n%.3F %.3F %.3F RG\n0.8 w\n%.2F %.2F m\n%.2F %.2F l\nS\nQ\n", $r, $g, $b, $x1, $y1, $x2, $y2);
    }

    private static function render(string $content): string
    {
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>';
        $objects[] = '<< /Length '.strlen($content).">>\nstream\n{$content}endstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $objectNumber = $index + 1;
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= "{$objectNumber} 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= 'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private static function pdfText(string $text): string
    {
        $encoded = function_exists('iconv')
            ? iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text)
            : $text;

        $encoded = $encoded === false ? $text : $encoded;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $encoded);
    }
}
