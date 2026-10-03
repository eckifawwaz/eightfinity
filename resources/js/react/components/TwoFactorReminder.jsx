import React, { useState } from 'react';
import { csrfToken } from '../utils/csrf';

export default function TwoFactorReminder() {
    const auth = window.__AUTH__ ?? { authenticated: false };
    const path = window.location.pathname;
    const isAdminPage = path === '/dashboard' || path.startsWith('/admin');
    const [open, setOpen] = useState(
        Boolean(!isAdminPage && auth.authenticated && !auth.two_factor_enabled && auth.show_two_factor_reminder),
    );

    if (!open) return null;

    return (
        <section className="two-factor-reminder-overlay" role="dialog" aria-modal="true" aria-labelledby="two-factor-reminder-title">
            <div className="two-factor-reminder-modal">
                <button
                    type="button"
                    className="two-factor-reminder-close"
                    aria-label="Close security reminder"
                    onClick={() => setOpen(false)}
                >
                    ×
                </button>

                <div className="two-factor-reminder-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3 5.5 5.6v5.7c0 4.2 2.7 7.4 6.5 9.7 3.8-2.3 6.5-5.5 6.5-9.7V5.6L12 3Z" />
                        <path d="M9.5 11.4 11.2 13l3.5-3.7" />
                    </svg>
                </div>

                <span className="two-factor-reminder-eyebrow">Account security</span>
                <h2 id="two-factor-reminder-title">Enable Two-Factor Authentication</h2>
                <p>
                    Add an extra layer of protection. Every time you sign in, EightFinity will send a 6-digit OTP code to your email.
                </p>

                <div className="two-factor-reminder-benefits">
                    <div>
                        <strong>Email OTP</strong>
                        <span>The code is valid for a limited time.</span>
                    </div>
                    <div>
                        <strong>Blocks unauthorized sign-ins</strong>
                        <span>Your password alone is not enough to sign in while 2FA is enabled.</span>
                    </div>
                </div>

                <div className="two-factor-reminder-actions">
                    <form method="POST" action="/profile/security">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="PATCH" />
                        <button type="submit" name="two_factor_enabled" value="1" className="two-factor-reminder-primary">
                            Enable 2FA
                        </button>
                    </form>
                    <button type="button" className="two-factor-reminder-secondary" onClick={() => setOpen(false)}>
                        Maybe later
                    </button>
                </div>

                <small>You can enable or disable this anytime from your Profile page.</small>
            </div>
        </section>
    );
}
