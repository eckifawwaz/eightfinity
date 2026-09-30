import React from 'react';
import { consumeFormErrors } from '../../utils/pageState';
import { csrfToken } from '../../utils/csrf';

export default function UserTwoFactor() {
    const twoFactor = window.__USER_TWO_FACTOR__ ?? {};
    const formErrors = React.useMemo(() => consumeFormErrors(), []);

    return (
        <main className="user-auth-page user-login-page">
            <section className="user-auth-brand">
                <img src="/image/logo-full.png" alt="EightFinity" />
                <h2>Capture Your Infinite Moments</h2>
            </section>
            <section className="user-register-card user-login-card">
                <img src="/image/logo-icon.png" alt="" className="user-auth-icon" />
                <span className="login-eyebrow">Two-Factor Authentication</span>
                <h1>Check Your Email</h1>
                <p>Enter the 6-digit code sent to {twoFactor.email ?? 'your email'}.</p>
                <form method="POST" action="/two-factor" className="user-register-form">
                    <input type="hidden" name="_token" value={csrfToken} />
                    {twoFactor.status === 'two-factor-code-sent' && (
                        <section className="auth-status-card">A new verification code has been sent.</section>
                    )}
                    {formErrors.length > 0 && (
                        <section className="auth-error-card">
                            <strong>Verification could not be completed</strong>
                            {formErrors.map((error) => <p key={error}>{error}</p>)}
                        </section>
                    )}
                    <label>
                        Verification Code
                        <input
                            autoComplete="one-time-code"
                            autoFocus
                            inputMode="numeric"
                            maxLength="6"
                            name="code"
                            pattern="[0-9]{6}"
                            placeholder="123456"
                            required
                            type="text"
                        />
                    </label>
                    <button type="submit" className="primary-button">Verify</button>
                </form>
                <form method="POST" action="/two-factor/resend" className="admin-two-factor-resend">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <button type="submit">Resend code</button>
                </form>
            </section>
        </main>
    );
}
