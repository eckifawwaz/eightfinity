import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { csrfToken } from '../../utils/csrf';

function Icon({ children, size = 18 }) {
    return (
        <svg
            aria-hidden="true"
            className="admin-profile-icon"
            fill="none"
            height={size}
            viewBox="0 0 24 24"
            width={size}
        >
            {children}
        </svg>
    );
}

const navItems = [
    ['Dashboard', '/dashboard', 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z'],
    ['Manage Bookings', '/admin/bookings', 'M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z'],
    ['Manage Queue', '/admin/queue', 'M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 2.13a4 4 0 0 1 0 7.75'],
    ['Customer Data', '/admin/customers', 'M4 5c0-1.1 3.58-2 8-2s8 .9 8 2-3.58 2-8 2-8-.9-8-2Zm0 0v7c0 1.1 3.58 2 8 2s8-.9 8-2V5M4 12v7c0 1.1 3.58 2 8 2s8-.9 8-2v-7'],
    ['2D Layout View', '/admin/layout', 'M3 6.5 9 4l6 2.5L21 4v13.5L15 20l-6-2.5L3 20Zm6-2.5v13.5M15 6.5V20'],
    ['Revenue', '/admin/revenue', 'M4 20V10m6 10V4m6 16v-7m6 7V13'],
];

const statusMessages = {
    'admin-profile-updated': 'Account settings saved.',
    'admin-password-updated': 'Password updated.',
    'admin-two-factor-enabled': 'Two-factor authentication enabled.',
    'admin-two-factor-disabled': 'Two-factor authentication disabled.',
    'admin-notifications-updated': 'Notification preferences saved.',
};

function formatDate(value, fallback) {
    if (!value) return fallback;

    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function AdminProfile() {
    const profile = window.__ADMIN_PROFILE__ ?? {};
    const admin = profile.user ?? {};
    const preferences = profile.notification_preferences ?? {};
    const formErrors = window.__FORM_ERRORS__ ?? [];
    const [showPasswordForm, setShowPasswordForm] = useState(false);
    const [desktopToasts, setDesktopToasts] = useState(preferences.desktop_toasts ?? true);
    const twoFactor = Boolean(admin.two_factor_enabled);
    const nextTwoFactorValue = twoFactor ? '0' : '1';

    return (
        <main className="admin-dashboard-page admin-profile-page">
            <aside className="admin-sidebar">
                <div>
                    <Link className="admin-brand" to="/admin/profile">
                        <img src="/image/logo-icon.png" alt="EightFinity" />
                        <strong><span>Eight</span>Finity</strong>
                    </Link>

                    <nav className="admin-nav">
                        {navItems.map(([label, href, path]) => (
                            <Link key={label} to={href}>
                                <Icon><path d={path} /></Icon>
                                {label}
                            </Link>
                        ))}
                    </nav>
                </div>

                <div className="admin-sidebar-bottom">
                    <Link className="admin-user-card admin-user-card-active" to="/admin/profile">
                        <span className="admin-avatar">{admin.name?.charAt(0) ?? 'A'}</span>
                        <span>
                            <strong>{admin.name ?? 'Admin User'}</strong>
                            <small>{admin.email ?? 'admin@eightfinity.com'}</small>
                        </span>
                    </Link>
                    <a className="admin-signout" href="/admin/logout">
                        <Icon><path d="M10 17l5-5-5-5M15 12H3M14 3h6a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-6" /></Icon>
                        Sign Out
                    </a>
                </div>
            </aside>

            <section className="admin-profile-content">
                <header className="admin-profile-heading">
                    <div>
                        <h1>{admin.name ?? 'Admin User'}</h1>
                        <p>✉ {admin.email ?? 'admin@eightfinity.com'}</p>
                        <small>Last login: {formatDate(admin.last_login_at, 'Not recorded yet')}</small>
                    </div>
                    <a href="/admin/profile/log" className="admin-orange-button">Download Log</a>
                </header>

                {profile.status && statusMessages[profile.status] && (
                    <section className="auth-status-card admin-profile-status">{statusMessages[profile.status]}</section>
                )}
                {formErrors.length > 0 && (
                    <section className="auth-error-card admin-profile-status">
                        <strong>Update could not be completed</strong>
                        {formErrors.map((error) => <p key={error}>{error}</p>)}
                    </section>
                )}

                <div className="admin-profile-grid">
                    <section className="admin-profile-card">
                        <h2>
                            <Icon><path d="M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" /></Icon>
                            Account Settings
                        </h2>
                        <form className="admin-settings-form" method="POST" action="/admin/profile">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="_method" value="PATCH" />
                            <label>
                                Phone Number
                                <span className="admin-input-icon">
                                    <Icon size={16}><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z" /></Icon>
                                    <input defaultValue={admin.phone ?? ''} name="phone" placeholder="+62 812 3456 7890" type="tel" />
                                </span>
                            </label>
                            <label>
                                Timezone
                                <select defaultValue={admin.timezone ?? 'Asia/Jakarta'} name="timezone">
                                    <option value="Asia/Jakarta">Western Indonesian Time (GMT+7)</option>
                                    <option value="America/New_York">Eastern Standard Time (GMT-5)</option>
                                    <option value="UTC">UTC</option>
                                </select>
                            </label>
                            <label>
                                Language
                                <select defaultValue={admin.language ?? 'en'} name="language">
                                    <option value="en">English (United States)</option>
                                    <option value="id">Bahasa Indonesia</option>
                                </select>
                            </label>
                            <button type="submit" className="admin-save-button">Save Changes</button>
                        </form>
                    </section>

                    <section className="admin-profile-card">
                        <h2>
                            <Icon><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Zm0-14v5m0 3h.01" /></Icon>
                            Security
                        </h2>
                        <div className="admin-security-list">
                            <div className="admin-security-row">
                                <span>
                                    <strong>Password Management</strong>
                                    <small>Last changed {formatDate(admin.password_changed_at, 'not recorded yet')}</small>
                                </span>
                                <button type="button" onClick={() => setShowPasswordForm((visible) => !visible)}>
                                    {showPasswordForm ? 'Close' : 'Update'}
                                </button>
                            </div>
                            {showPasswordForm && (
                                <form method="POST" action="/admin/profile/password" className="admin-password-form">
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input type="hidden" name="_method" value="PUT" />
                                    <label>
                                        Current Password
                                        <input name="current_password" type="password" required />
                                    </label>
                                    <label>
                                        New Password
                                        <input name="password" type="password" required />
                                    </label>
                                    <label>
                                        Confirm New Password
                                        <input name="password_confirmation" type="password" required />
                                    </label>
                                    <button type="submit" className="admin-save-button">Update Password</button>
                                </form>
                            )}
                            <form method="POST" action="/admin/profile/security" className="admin-security-row">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input type="hidden" name="_method" value="PATCH" />
                                <span><strong>Two-Factor Authentication</strong><small>Secure your account with email OTP</small></span>
                                <button
                                    aria-label="Toggle two-factor authentication"
                                    className={`admin-switch ${twoFactor ? 'active' : ''}`}
                                    name="two_factor_enabled"
                                    type="submit"
                                    value={nextTwoFactorValue}
                                >
                                    <span />
                                </button>
                            </form>
                            <div className="admin-connected-device">
                                <strong>Connected Devices</strong>
                                <p><span>▣ Chrome on MacOS</span><small>Active now</small></p>
                            </div>
                        </div>
                    </section>
                </div>

                <form method="POST" action="/admin/profile/notifications" className="admin-profile-card admin-notifications-card admin-notification-form">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input type="hidden" name="_method" value="PATCH" />
                    <h2>
                        <Icon><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" /></Icon>
                        Notification Preferences
                    </h2>
                    <div className="admin-notification-grid">
                        <fieldset>
                            <legend>Email Reports</legend>
                            <label><input defaultChecked={preferences.daily_summary ?? true} name="daily_summary" type="checkbox" value="1" /> Daily Summary</label>
                            <label><input defaultChecked={preferences.new_customer_alerts ?? false} name="new_customer_alerts" type="checkbox" value="1" /> New Customer Alerts</label>
                            <label><input defaultChecked={preferences.system_updates ?? true} name="system_updates" type="checkbox" value="1" /> System Updates</label>
                        </fieldset>
                        <fieldset>
                            <legend>Push Notifications</legend>
                            <label><input defaultChecked={preferences.booking_requests ?? true} name="booking_requests" type="checkbox" value="1" /> Booking Requests</label>
                            <label><input defaultChecked={preferences.low_stock_alerts ?? true} name="low_stock_alerts" type="checkbox" value="1" /> Low Stock Alerts</label>
                            <label><input defaultChecked={preferences.message_activity ?? false} name="message_activity" type="checkbox" value="1" /> Message Activity</label>
                        </fieldset>
                        <fieldset>
                            <legend>Visual &amp; Audio</legend>
                            <label className="admin-sound-label">
                                Alert Sound
                                <select defaultValue={preferences.alert_sound ?? 'corporate'} name="alert_sound">
                                    <option value="corporate">Corporate Minimal</option>
                                    <option value="soft">Soft Chime</option>
                                    <option value="none">No Sound</option>
                                </select>
                            </label>
                            <label className="admin-toast-row">
                                Desktop Toasts
                                <input
                                    checked={desktopToasts}
                                    name="desktop_toasts"
                                    onChange={(event) => setDesktopToasts(event.target.checked)}
                                    type="checkbox"
                                    value="1"
                                />
                            </label>
                        </fieldset>
                    </div>
                    <button type="submit" className="admin-save-button admin-notification-save">Save Preferences</button>
                </form>

                <section className="admin-account-danger">
                    <div>
                        <strong>Account Management</strong>
                        <p>Deactivate your admin access or transfer account ownership.</p>
                    </div>
                    <div>
                        <button type="button" className="admin-deactivate-button">Deactivate</button>
                        <button type="button" className="admin-delete-button">Delete Account</button>
                    </div>
                </section>
            </section>
        </main>
    );
}
