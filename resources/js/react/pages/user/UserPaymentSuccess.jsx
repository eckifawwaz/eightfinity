import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import SocialLinks from '../../components/SocialLinks';
import { flowSteps } from '../../utils/experienceFlow';
import { socialLinks } from '../../utils/socialLinks';

const packageDurations = {
    'Wedding Package': ['4 hours', '6 hours', '8 hours'],
    'Reservation Package': ['3 hours', '4 hours', '5 hours'],
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

export default function UserPaymentSuccess() {
    const { booking: bookingId } = useParams();
    const [booking, setBooking] = useState(() => (
        window.__BOOKING__ && String(window.__BOOKING__.id) === bookingId ? window.__BOOKING__ : null
    ));
    const [loadError, setLoadError] = useState('');

    useEffect(() => {
        let isMounted = true;

        fetch(`/payment/success/${bookingId}/data`, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (response.redirected || response.status === 401) {
                    window.location.href = '/login';
                    return null;
                }

                if (!response.ok) {
                    throw new Error('Booking request failed');
                }

                return response.json();
            })
            .then((data) => {
                if (isMounted && data) {
                    setBooking(data);
                }
            })
            .catch(() => {
                if (isMounted) {
                    setLoadError('Unable to load this booking.');
                }
            });

        return () => {
            isMounted = false;
        };
    }, [bookingId]);

    const isLoadingBooking = !booking && !loadError;
    const activeBooking = booking ?? {};
    const duration = activeBooking.duration_hours
        ? `${activeBooking.duration_hours} hours`
        : (packageDurations[activeBooking.package_name]?.[activeBooking.package_option] ?? '-');
    const bookingCode = activeBooking.booking_code ?? 'receipt';
    const adminWhatsappBase = socialLinks.whatsapp.split('?')[0];
    const rescheduleMessage = [
        'Halo Admin EightFinity, saya ingin mengajukan reschedule booking.',
        `Booking ID: #${bookingCode}`,
        `Jadwal saat ini: ${formatDate(activeBooking.booking_date)} ${activeBooking.booking_time ?? ''}`,
        'Mohon dibantu untuk perubahan jadwal. Terima kasih.',
    ].join('\n');
    const rescheduleWhatsappUrl = `${adminWhatsappBase}?text=${encodeURIComponent(rescheduleMessage)}`;

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
                    <h1>Payment <span>Success!</span></h1>
                    <p>Your payment has been processed successfully. Get ready for an amazing photo session!</p>
                    <div className="payment-confirm-actions">
                        <Link className="payment-confirm-primary" to="/profile">Check Your Booking</Link>
                        <a
                            className={`payment-confirm-secondary ${isLoadingBooking ? 'disabled' : ''}`}
                            href={isLoadingBooking ? undefined : `/bookings/${bookingId}/receipt.pdf`}
                            aria-disabled={isLoadingBooking}
                        >
                            ↓ Download Receipt PDF
                        </a>
                    </div>
                    {loadError && <p className="payment-confirm-error">{loadError}</p>}
                </div>
            </section>

            <section className="payment-details-section">
                <div className="payment-details-title">
                    <h2>Your Booking Details</h2>
                    <p>{isLoadingBooking ? 'Loading your booking details...' : 'Save this information for your session'}</p>
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
                                <dd>{activeBooking.package_name ?? '-'}</dd>
                            </div>
                            <div>
                                <dt>Date &amp; Time</dt>
                                <dd>{formatDate(activeBooking.booking_date)} at {activeBooking.booking_time ?? '-'}</dd>
                            </div>
                            <div>
                                <dt>Duration</dt>
                                <dd>{duration}</dd>
                            </div>
                            <div>
                                <dt>Booking ID</dt>
                                <dd className="payment-booking-code">#{activeBooking.booking_code ?? '-'}</dd>
                            </div>
                        </dl>
                        <small className="payment-reschedule-note">
                            Silahkan hubungi admin untuk reschedule maksimal H-3 sebelum hari-H.
                        </small>
                        {activeBooking.reschedule_available ? (
                            <a
                                className="payment-reschedule-contact-button"
                                href={rescheduleWhatsappUrl}
                                rel="noreferrer"
                                target="_blank"
                            >
                                Hubungi Admin untuk Reschedule
                            </a>
                        ) : (
                            <span className="payment-reschedule-closed">Reschedule sudah tidak tersedia karena telah memasuki H-3.</span>
                        )}
                    </article>

                    <article className="payment-detail-card">
                        <div className="payment-detail-head">
                            <span className="payment-detail-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="12" r="7" />
                                    <path d="M12 2v4M12 18v4M2 12h4M18 12h4" />
                                </svg>
                            </span>
                            <strong>Location &amp; Instructions</strong>
                        </div>
                        <dl>
                            <div>
                                <dt>Booking Location</dt>
                                <dd>{activeBooking.booking_location ?? '-'}</dd>
                            </div>
                            <div>
                                <dt>Booking Address</dt>
                                <dd>{activeBooking.customer_address ?? '-'}</dd>
                            </div>
                            <div>
                                <dt>Venue Setup Instructions</dt>
                                <dd>Our team will arrive 1 hour early ({activeBooking.team_arrival_time ?? '-'}) for equipment and backdrop installation.</dd>
                            </div>
                        </dl>
                        <div className="payment-note">
                            <strong>Important</strong>
                            <p>Please inform the building security/loading dock about the arrival of the EightFinity team.</p>
                        </div>
                    </article>
                </div>
            </section>

            <section className="experience-flow-card payment-experience-flow">
                <h2>Experience Flow</h2>
                <p>Your photo booth journey steps</p>
                <div className="flow-steps">
                    {flowSteps.map(([number, image, title, subtitle]) => (
                        <article key={number} className="flow-step">
                            <span className={`flow-number step-${number}`}>{number}</span>
                            <img className="flow-step-image" src={image} alt="" />
                            <strong>{title}</strong>
                            <small>{subtitle}</small>
                        </article>
                    ))}
                </div>
            </section>

            <footer className="user-book-footer payment-success-footer">
                <div><img src="/image/logo-icon-transparent.png" alt="" /><strong>EightFinity</strong></div>
                <p>Capture Your Infinite Moments</p>
                <SocialLinks className="payment-footer-socials" />
                <small>© 2026 Eightfinity. All rights reserved.</small>
            </footer>
        </main>
    );
}
