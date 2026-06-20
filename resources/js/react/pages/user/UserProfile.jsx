import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { csrfToken } from '../../utils/csrf';

const packageDurations = {
    'Wedding Package': ['8 hours'],
    'Reservation Package': ['4 hours', '4+1 hours'],
    'Unlimited Package': ['2 hours', '3 hours', '4 hours'],
};

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

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

function statusLabel(status) {
    if (status === 'pending') return 'Pending';
    if (status === 'cancelled') return 'Cancelled';
    if (status === 'completed') return 'Completed';
    return 'Confirmed';
}

export default function UserProfile() {
    const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
    const [showEditProfile, setShowEditProfile] = useState(false);
    const profile = window.__USER_PROFILE__ ?? {};
    const user = profile.user ?? {};
    const bookings = profile.bookings ?? [];
    const summary = profile.summary ?? {};
    const totalSessions = summary.total_sessions ?? bookings.length;
    const totalPhotos = Math.max((summary.completed_sessions ?? 0) * 8, totalSessions * 2);

    return (
        <main className="user-book-page user-profile-page">
            <header className="user-book-nav">
                <Link to="/home" className="user-book-brand">
                    <img src="/image/logo-icon.png" alt="EightFinity" />
                    <strong>Eight<span>Finity</span></strong>
                </Link>
                <Link to="/profile" className="user-book-profile">Profile</Link>
            </header>

            <section className="profile-hero-card">
                <div className="profile-avatar">♙</div>
                <div>
                    <h1>{user.name ?? 'Customer'}</h1>
                    <p>{user.email ?? '-'}</p>
                </div>
                <div className="profile-stats">
                    <article><strong>{totalSessions}</strong><span>Total Sessions</span></article>
                    <article><strong>{totalPhotos}</strong><span>Total Photos</span></article>
                </div>
            </section>

            <section className="profile-section">
                <div className="payment-details-title">
                    <h2>Booking History</h2>
                    <p>Track and manage your photobooth rental schedules</p>
                </div>

                <div className="profile-booking-grid">
                    {bookings.length ? bookings.map((booking) => {
                        const duration = packageDurations[booking.package_name]?.[booking.package_option] ?? '-';
                        const rebookParams = new URLSearchParams({
                            package: booking.package_slug ?? '',
                            option: String(booking.package_option ?? 0),
                        });
                        const rescheduleParams = new URLSearchParams({
                            reschedule: String(booking.id),
                            package: booking.package_slug ?? '',
                            option: String(booking.package_option ?? 0),
                            date: typeof booking.booking_date === 'string' ? booking.booking_date.slice(0, 10) : '',
                            people: String(booking.people ?? 1),
                            time: booking.booking_time ?? '',
                            address: booking.customer_address ?? '',
                            location: booking.booking_location ?? '',
                        });
                        const canCancel = !['completed', 'cancelled'].includes(booking.status);

                        return (
                            <article className="profile-booking-card" key={booking.booking_code}>
                                <header>
                                    <div>
                                        <h3>{booking.package_name}</h3>
                                        <small>#{booking.booking_code}</small>
                                    </div>
                                    <span className={`profile-status ${booking.status}`}>{statusLabel(booking.status)}</span>
                                </header>
                                <p>▣ {formatDate(booking.booking_date)} • {booking.booking_time}</p>
                                <p>⌖ {booking.booking_location ?? '-'} • {booking.people} pax</p>
                                <p>◇ {duration} • {currency.format(booking.amount ?? 0)}</p>
                                <p>□ {(booking.payment_method ?? '-').toUpperCase()} • {(booking.payment_provider ?? '-').toUpperCase()}</p>
                                <div>
                                    <Link to={`/payment/success/${booking.id}`}>View Receipt</Link>
                                    {canCancel ? (
                                        <>
                                            <Link className="profile-booking-secondary" to={`/book?${rescheduleParams.toString()}`}>
                                                Reschedule
                                            </Link>
                                            <form method="POST" action={`/bookings/${booking.id}/cancel`}>
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <input type="hidden" name="_method" value="PATCH" />
                                                <button type="submit">Cancel</button>
                                            </form>
                                        </>
                                    ) : (
                                        <Link className="profile-booking-secondary" to={`/book?${rebookParams.toString()}`}>
                                            Rebook
                                        </Link>
                                    )}
                                </div>
                            </article>
                        );
                    }) : (
                        <article className="profile-empty-card">
                            <strong>No booking history yet</strong>
                            <p>Your completed payment and booking schedules will appear here.</p>
                            <Link to="/book">Book a Session</Link>
                        </article>
                    )}
                </div>
            </section>

            <section className="profile-info-card">
                <header>
                    <h2>Personal Information</h2>
                    <button type="button" onClick={() => setShowEditProfile(true)}>Edit Profile</button>
                </header>
                <dl>
                    <div><dt>Full Name</dt><dd>{user.name ?? '-'}</dd></div>
                    <div><dt>Email Address</dt><dd>{user.email ?? '-'}</dd></div>
                    <div><dt>Phone Number</dt><dd>{user.phone ?? '-'}</dd></div>
                    <div><dt>Alternate Phone</dt><dd>{user.alternate_phone ?? '-'}</dd></div>
                    <div><dt>Address</dt><dd>{user.address ?? 'No booking address yet'}</dd></div>
                </dl>
            </section>

            <div className="profile-signout">
                <button type="button" onClick={() => setShowLogoutConfirm(true)}>⇥ Sign Out</button>
            </div>

            {showLogoutConfirm && (
                <section className="user-logout-overlay" role="dialog" aria-modal="true">
                    <div className="logout-modal user-logout-modal">
                        <img src="/image/logo-icon.png" alt="EightFinity" />
                        <h1>Are you sure you want to sign out?</h1>
                        <p>You will be signed out of your account on this device.</p>

                        <div className="logout-actions">
                            <form method="POST" action="/logout">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" className="logout-confirm">Logout</button>
                            </form>
                            <button
                                type="button"
                                className="logout-cancel"
                                onClick={() => setShowLogoutConfirm(false)}
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </section>
            )}

            {showEditProfile && (
                <section className="user-logout-overlay" role="dialog" aria-modal="true">
                    <div className="profile-edit-modal">
                        <header>
                            <h2>Edit Profile</h2>
                            <button type="button" onClick={() => setShowEditProfile(false)} aria-label="Close edit profile">
                                ×
                            </button>
                        </header>

                        <form method="POST" action="/profile">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="_method" value="PUT" />

                            <label>
                                Full Name
                                <input name="name" defaultValue={user.name ?? ''} required />
                            </label>
                            <label>
                                Email Address
                                <input name="email" defaultValue={user.email ?? ''} required type="email" />
                            </label>
                            <label>
                                Phone Number
                                <input name="phone" defaultValue={user.phone ?? ''} />
                            </label>
                            <label>
                                Alternate Phone
                                <input name="alternate_phone" defaultValue={user.alternate_phone ?? ''} />
                            </label>

                            <div>
                                <button type="submit">Save Profile</button>
                                <button type="button" onClick={() => setShowEditProfile(false)}>Cancel</button>
                            </div>
                        </form>
                    </div>
                </section>
            )}

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
