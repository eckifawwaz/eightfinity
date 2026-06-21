import React from 'react';
import { csrfToken } from '../../utils/csrf';

export default function UserForgotPassword() {
    const formErrors = window.__FORM_ERRORS__ ?? [];
    const status = window.__AUTH_STATUS__;

    return (
        <main className="user-auth-page user-login-page">
            <section className="user-auth-brand">
                <img src="/image/logo-full.png" alt="EightFinity" />
                <h2>Capture Your Infinite Moments</h2>
            </section>
            <section className="user-register-card user-login-card">
                <img src="/image/logo-icon.png" alt="" className="user-auth-icon" />
                <h1>Reset Password</h1>
                <p>Enter your account email and we will send a password reset link.</p>

                {status && (
                    <section className="auth-success-card">
                        <strong>Reset link sent</strong>
                        <p>{status}</p>
                    </section>
                )}

                <form method="POST" action="/forgot-password" className="user-register-form">
                    <input type="hidden" name="_token" value={csrfToken} />
                    {formErrors.length > 0 && (
                        <section className="auth-error-card">
                            <strong>Reset link could not be sent</strong>
                            {formErrors.map((error) => <p key={error}>{error}</p>)}
                        </section>
                    )}
                    <label>Email Address<input type="email" name="email" placeholder="you@example.com" required /></label>
                    <button type="submit" className="primary-button">Send Reset Link</button>
                </form>

                <p className="user-auth-switch">Remember your password? <a href="/login">Sign In</a></p>
            </section>
        </main>
    );
}
