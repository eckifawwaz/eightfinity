import { socialLinks } from './socialLinks';

const packageDurations = {
    'Wedding Package': ['4 hours', '8 hours'],
    'Reservation Package': ['4 hours', '4+1 hours'],
    'Unlimited Package': ['2 hours', '3 hours', '4 hours'],
};

const statusPresentation = {
    completed: { label: 'PEMBAYARAN BERHASIL', bg: '#EAF5EE', fg: '#1E7A5C' },
    confirmed: { label: 'PEMBAYARAN BERHASIL', bg: '#EAF5EE', fg: '#1E7A5C' },
    pending: { label: 'MENUNGGU PEMBAYARAN', bg: '#FDF1E4', fg: '#B5701E' },
    cancelled: { label: 'DIBATALKAN', bg: '#FBEAE7', fg: '#B23B2E' },
    refunded: { label: 'REFUND DIPROSES', bg: '#FDF1E4', fg: '#B5701E' },
};

function escapeHtml(value) {
    return String(value ?? '-').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[char]));
}

function formatReceiptDate(dateValue) {
    if (!dateValue) return '-';

    const rawValue = typeof dateValue === 'string' ? dateValue : String(dateValue);
    const date = new Date(rawValue.includes('T') ? rawValue : `${rawValue}T00:00:00`);

    if (Number.isNaN(date.getTime())) return rawValue;

    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
}

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

export function buildReceiptHtml(booking, logoUrl) {
    const duration = (packageDurations[booking.package_name]?.[booking.package_option] ?? '-').replace('hours', 'Jam');
    const status = statusPresentation[booking.status] ?? statusPresentation.pending;
    const snippets = Array.from({ length: 12 }).map(() => '<div class="snip"></div>').join('');

    return `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Struk EightFinity #${escapeHtml(booking.booking_code)}</title>
<style>
  body { background: #F3F1EC; font-family: 'Helvetica Neue', Arial, sans-serif; display: flex; justify-content: center; padding: 40px 20px; margin: 0; }
  .receipt { width: 430px; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px rgba(30,20,10,0.15); position: relative; }
  .header { padding: 30px 28px 20px; text-align: center; border-bottom: 3px dashed #ECE6D8; }
  .logo-img { width: 190px; margin: 0 auto 16px; display: block; }
  .status-badge { display: inline-flex; align-items: center; gap: 7px; background: ${status.bg}; color: ${status.fg}; padding: 7px 16px; border-radius: 999px; font-size: 11.5px; font-weight: 700; letter-spacing: 0.03em; }
  .dot { width: 7px; height: 7px; border-radius: 999px; background: ${status.fg}; }
  .body-section { padding: 24px 28px 6px; }
  .section-label { font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #E0653A; margin: 0 0 12px; }
  .row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px dashed #ECE6D8; font-size: 13.5px; }
  .row:last-child { border-bottom: none; }
  .row .label { color: #8A8072; }
  .row .value { font-weight: 700; color: #2A241E; text-align: right; }
  .divider { border: none; border-top: 3px dashed #ECE6D8; margin: 20px 0; }
  .total-box { background: #FDF1E4; border: 1.5px dashed #F2A22E; border-radius: 14px; padding: 16px 18px; display: flex; justify-content: space-between; align-items: center; margin: 14px 0 22px; }
  .total-box .label { font-size: 13px; color: #B5701E; font-weight: 700; }
  .total-box .value { font-size: 21px; font-weight: 800; color: #C1481E; }
  .footer { background: #FBFAF6; padding: 22px 28px 30px; text-align: center; }
  .booking-id { text-align: center; font-size: 12px; color: #8A8072; letter-spacing: 0.03em; margin-bottom: 14px; }
  .booking-id b { color: #2C1E8C; }
  .footer-note { font-size: 12.5px; color: #6B6255; line-height: 1.6; margin: 0; }
  .footer-note b { color: #E0653A; }
  .footer-brand { font-size: 11px; color: #B0A695; margin-top: 14px; }
  .snip-row { display: flex; justify-content: space-between; padding: 0 6px; }
  .snip { width: 16px; height: 16px; background: #F3F1EC; border-radius: 999px; }
</style>
</head>
<body>

<div class="receipt">

  <div class="header">
    <img class="logo-img" src="${logoUrl}" alt="EightFinity">
    <div class="status-badge">
      <span class="dot"></span>
      ${status.label}
    </div>
  </div>

  <div class="body-section">
    <p class="section-label">Detail Booking</p>
    <div class="row"><span class="label">Paket</span><span class="value">${escapeHtml(booking.package_name)}</span></div>
    <div class="row"><span class="label">Tanggal &amp; Waktu</span><span class="value">${formatReceiptDate(booking.booking_date)}, ${escapeHtml(booking.booking_time)}</span></div>
    <div class="row"><span class="label">Durasi</span><span class="value">${escapeHtml(duration)}</span></div>
    <div class="row"><span class="label">Ukuran Ruangan</span><span class="value">${escapeHtml(booking.booth_size)}</span></div>
    <div class="row"><span class="label">Lokasi</span><span class="value">${escapeHtml(booking.booking_location)}</span></div>

    <hr class="divider">

    <p class="section-label">Kontak &amp; Alamat</p>
    <div class="row"><span class="label">Nama</span><span class="value">${escapeHtml(booking.customer_name)}</span></div>
    <div class="row"><span class="label">Alamat</span><span class="value">${escapeHtml(booking.customer_address)}</span></div>

    <div class="total-box">
      <span class="label">Total Pembayaran</span>
      <span class="value">${currency.format(booking.amount ?? 0)}</span>
    </div>
  </div>

  <div class="footer">
    <p class="booking-id">Booking ID <b>#${escapeHtml(booking.booking_code)}</b></p>
    <p class="footer-note">
      Simpan struk ini sebagai bukti pemesanan kamu.<br>
      Terima kasih sudah percaya sama <b>EightFinity</b>!
    </p>
    <div class="footer-brand">eightfinityphoto@gmail.com &middot; ${escapeHtml(socialLinks.whatsapp.replace('https://', ''))}</div>
  </div>

  <div class="snip-row">
    ${snippets}
  </div>
</div>

</body>
</html>`;
}
