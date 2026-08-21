import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { csrfToken } from '../../utils/csrf';
import SocialLinks from '../../components/SocialLinks';

const packageDurations = {
    'Wedding Package': ['4 hours', '8 hours'],
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
    const [profile, setProfile] = useState(() => window.__USER_PROFILE__ ?? null);
    const [profileError, setProfileError] = useState('');

    useEffect(() => {
        if (profile) {
            return undefined;
        }

        let isMounted = true;

        fetch('/profile/data', {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (response.redirected || response.status === 401 || response.status === 403) {
                    window.location.href = '/login';
                    return null;
                }

                if (!response.ok) {
                    throw new Error('Profile request failed');
                }

                return response.json();
            })
            .then((data) => {
                if (isMounted && data) {
                    setProfile(data);
                }
            })
            .catch(() => {
                if (isMounted) {
                    setProfileError('Unable to load profile data.');
                }
            });

        return () => {
            isMounted = false;
        };
    }, [profile]);

    const user = profile?.user ?? {};
    const bookings = profile?.bookings ?? [];
    const summary = profile?.summary ?? {};
    const isLoadingProfile = !profile && !profileError;
    const emptyValue = isLoadingProfile ? 'Loading...' : '-';
    const totalSessions = summary.total_sessions ?? bookings.length;
    const totalPhotos = summary.total_photos ?? 0;

    return (
        <main className="user-book-page user-profile-page">
            <header className="user-book-nav">
                <Link to="/home" className="user-book-brand">
                    <img src="/image/logo-icon.png" alt="EightFinity" />
                    <strong>Eight<span>Finity</span></strong>
                </Link>
                <Link to="/home" className="user-book-profile">Home</Link>
            </header>

            <section className="profile-hero-card">
                <div className="profile-avatar">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7" />
                    </svg>
                </div>
                <div>
                    <h1>{user.name ?? (isLoadingProfile ? 'Loading profile...' : 'Profile')}</h1>
                    <p>{user.email ?? (profileError || emptyValue)}</p>
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
                    {isLoadingProfile ? (
                        <article className="profile-empty-card">
                            <strong>Loading booking history...</strong>
                            <p>Fetching your account data.</p>
                        </article>
                    ) : profileError ? (
                        <article className="profile-empty-card">
                            <strong>Profile data unavailable</strong>
                            <p>{profileError}</p>
                            <a href="/profile">Reload Profile</a>
                        </article>
                    ) : bookings.length ? bookings.map((booking) => {
                        const duration = packageDurations[booking.package_name]?.[booking.package_option] ?? '-';
                        const rebookParams = new URLSearchParams({
                            package: booking.package_slug ?? '',
                            option: String(booking.package_option ?? 0),
                        });
                        const isCancelled = booking.status === 'cancelled';

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
                                <p>⌖ {booking.booking_location ?? '-'} • {booking.booth_size ?? '3 x 3 meter'}</p>
                                <p>◇ {duration} • {currency.format(booking.amount ?? 0)}</p>
                                <p>□ {(booking.payment_method ?? '-').toUpperCase()} • {(booking.payment_provider ?? '-').toUpperCase()}</p>
                                <div>
                                    <Link to={`/payment/success/${booking.id}`}>View Receipt</Link>
                                    {isCancelled ? (
                                        <form
                                            method="POST"
                                            action={`/bookings/${booking.id}`}
                                            onSubmit={(event) => {
                                                if (!window.confirm('Delete this cancelled booking?')) {
                                                    event.preventDefault();
                                                }
                                            }}
                                        >
                                            <input type="hidden" name="_token" value={csrfToken} />
                                            <input type="hidden" name="_method" value="DELETE" />
                                            <button type="submit">Delete</button>
                                        </form>
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
                    <button type="button" disabled={!profile} onClick={() => setShowEditProfile(true)}>Edit Profile</button>
                </header>
                <dl>
                    <div><dt>Full Name</dt><dd>{user.name ?? emptyValue}</dd></div>
                    <div><dt>Email Address</dt><dd>{user.email ?? emptyValue}</dd></div>
                    <div><dt>Phone Number</dt><dd>{user.phone ?? emptyValue}</dd></div>
                    <div><dt>Alternate Phone</dt><dd>{user.alternate_phone ?? emptyValue}</dd></div>
                    <div><dt>Address</dt><dd>{user.address ?? (isLoadingProfile ? emptyValue : 'No booking address yet')}</dd></div>
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
                            <label>
                                Address
                                <input name="address" defaultValue={user.address ?? ''} />
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
                <div><img src="/image/logo-icon-transparent.png" alt="" /><strong>EightFinity</strong></div>
                <p>Capture Your Infinite Moments</p>
                <SocialLinks className="payment-footer-socials" />
                <small>© 2026 Eightfinity. All rights reserved.</small>
            </footer>
        </main>
    );
}
