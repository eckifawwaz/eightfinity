import React from 'react';
import { csrfToken } from '../../utils/csrf';

export default function UserVerifyEmail() {
    const formErrors = window.__FORM_ERRORS__ ?? [];
    const status = window.__FLASH_STATUS__;
    const email = window.__VERIFY_EMAIL__ ?? 'your email';

    return (
        <main className="user-auth-page user-signup-page">
            <section className="user-auth-brand">
                <img src="/image/logo-full.png" alt="EightFinity" />
                <h2>Capture Your Infinite Moments</h2>
            </section>
            <section className="user-register-card verify-email-card">
                <img src="/image/logo-icon.png" alt="" className="user-auth-icon" />
                <h1>Verify Email</h1>
                <p>Enter the 6-digit code sent to {email}</p>

                {status === 'verification-code-sent' && (
                    <section className="auth-success-card">
                        <strong>Verification code sent</strong>
                        <p>Please check your inbox or spam folder.</p>
                    </section>
                )}

                <form method="POST" action="/verify-email" className="user-register-form">
                    <input type="hidden" name="_token" value={csrfToken} />
                    {formErrors.length > 0 && (
                        <section className="auth-error-card">
                            <strong>Verification failed</strong>
                            {formErrors.map((error) => <p key={error}>{error}</p>)}
                        </section>
                    )}
                    <label>
                        Verification Code
                        <input name="code" inputMode="numeric" maxLength="6" minLength="6" placeholder="123456" required />
                    </label>
                    <button type="submit" className="primary-button">Verify Account</button>
                </form>

                <form method="POST" action="/verify-email/resend" className="verify-resend-form">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <button type="submit">Resend Code</button>
                </form>
            </section>
        </main>
    );
}
