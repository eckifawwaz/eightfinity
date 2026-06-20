import React from 'react';
import { Link } from 'react-router-dom';

const packageDurations = {
    'Wedding Package': ['8 hours'],
    'Reservation Package': ['4 hours', '4+1 hours'],
    'Unlimited Package': ['2 hours', '3 hours', '4 hours'],
};

function formatDate(value) {
    if (!value) return '-';

    const rawValue = typeof value === 'string' ? value : String(value);
    const normalizedValue = rawValue.includes('T') ? rawValue : `${rawValue}T00:00:00`;
    const date = new Date(normalizedValue);

    if (Number.isNaN(date.getTime())) {
        return rawValue;
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
}

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

export default function UserPaymentSuccess() {
    const booking = window.__BOOKING__ ?? {};
    const duration = packageDurations[booking.package_name]?.[booking.package_option] ?? '-';
    const bookingCode = booking.booking_code ?? 'receipt';

    function downloadReceipt() {
        const receipt = [
            'EIGHTFINITY PAYMENT RECEIPT',
            '==========================',
            `Booking ID       : #${bookingCode}`,
            `Package          : ${booking.package_name ?? '-'}`,
            `Date & Time      : ${formatDate(booking.booking_date)} at ${booking.booking_time ?? '-'}`,
            `Duration         : ${duration}`,
            `Number of pax    : ${booking.people ?? '-'}`,
            `Booking Location : ${booking.booking_location ?? '-'}`,
            `Customer Address : ${booking.customer_address ?? '-'}`,
            `Total Payment    : ${currency.format(booking.amount ?? 0)}`,
            `Status           : ${(booking.status ?? 'pending').toUpperCase()}`,
            '',
            'Please save this receipt for your session.',
            'Capture Your Infinite Moments',
        ].join('\n');

        const blob = new Blob([receipt], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `eightfinity-receipt-${bookingCode}.txt`;
        link.click();
        URL.revokeObjectURL(url);
    }

    return (
        <main className="user-book-page payment-success-page">
            <header className="user-book-nav">
                <Link to="/home" className="user-book-brand">
                    <img src="/image/logo-icon.png" alt="EightFinity" />
                    <strong>Eight<span>Finity</span></strong>
                </Link>
                <Link to="/profile" className="user-book-profile">Profile</Link>
            </header>

            <section className="payment-confirm-hero">
                <div className="payment-confirm-panel">
                    <span className="payment-confirm-badge">✓</span>
                    <h1>Booking <span>Confirmed!</span></h1>
                    <p>Your payment has been processed successfully. Get ready for an amazing photo session!</p>
                    <div className="payment-confirm-actions">
                        <Link className="payment-confirm-primary" to="/home">Check Equipment Delivery Status</Link>
                        <button className="payment-confirm-secondary" onClick={downloadReceipt} type="button">
                            ↓ Download Receipt
                        </button>
                    </div>
                </div>
            </section>

            <section className="payment-details-section">
                <div className="payment-details-title">
                    <h2>Your Booking Details</h2>
                    <p>Save this information for your session</p>
                </div>

                <div className="payment-details-grid">
                    <article className="payment-detail-card">
                        <div className="payment-detail-head">
                            <span className="payment-detail-icon">▣</span>
                            <strong>Booking Details</strong>
                        </div>
                        <dl>
                            <div>
                                <dt>Package</dt>
                                <dd>{booking.package_name ?? '-'}</dd>
                            </div>
                            <div>
                                <dt>Date &amp; Time</dt>
                                <dd>{formatDate(booking.booking_date)} at {booking.booking_time ?? '-'}</dd>
                            </div>
                            <div>
                                <dt>Duration</dt>
                                <dd>{duration}</dd>
                            </div>
                            <div>
                                <dt>Booking ID</dt>
                                <dd className="payment-booking-code">#{booking.booking_code ?? '-'}</dd>
                            </div>
                        </dl>
                    </article>

                    <article className="payment-detail-card">
                        <div className="payment-detail-head">
                            <span className="payment-detail-icon">⌖</span>
                            <strong>Location &amp; Instructions</strong>
                        </div>
                        <dl>
                            <div>
                                <dt>Booking Location</dt>
                                <dd>{booking.booking_location ?? '-'}</dd>
                            </div>
                            <div>
                                <dt>Customer Address</dt>
                                <dd>{booking.customer_address ?? '-'}</dd>
                            </div>
                            <div>
                                <dt>Venue Setup Instructions</dt>
                                <dd>Our team will arrive 1 hour early (13:00) for equipment and backdrop installation.</dd>
                            </div>
                        </dl>
                        <div className="payment-note">
                            <strong>Important</strong>
                            <p>Please inform the building security/loading dock about the arrival of the EightFinity team.</p>
                        </div>
                    </article>
                </div>
            </section>

            <footer className="user-book-footer payment-success-footer">
                <div><img src="/image/logo-icon.png" alt="" /><strong>EightFinity</strong></div>
                <p>Capture Your Infinite Moments</p>
                <div className="payment-footer-socials">
                    <span>◉ Whatsapp</span>
                    <span>▣ Instagram</span>
                </div>
                <small>© 2026 Eightfinity. All rights reserved.</small>
            </footer>
        </main>
    );
}
