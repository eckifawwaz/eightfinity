import React from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { csrfToken } from '../../utils/csrf';

export default function UserResetPassword() {
    const { token } = useParams();
    const [searchParams] = useSearchParams();
    const formErrors = window.__FORM_ERRORS__ ?? [];
    const resetToken = window.__RESET_TOKEN__ ?? token ?? '';
    const resetEmail = window.__RESET_EMAIL__ ?? searchParams.get('email') ?? '';

    return (
        <main className="user-auth-page user-login-page">
            <section className="user-auth-brand">
                <img src="/image/logo-full.png" alt="EightFinity" />
                <h2>Capture Your Infinite Moments</h2>
            </section>
            <section className="user-register-card user-login-card">
                <img src="/image/logo-icon.png" alt="" className="user-auth-icon" />
                <h1>Create New Password</h1>
                <p>Use a new password for your Eightfinity account.</p>

                <form method="POST" action="/reset-password" className="user-register-form">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input type="hidden" name="token" value={resetToken} />
                    {formErrors.length > 0 && (
                        <section className="auth-error-card">
                            <strong>Password could not be reset</strong>
                            {formErrors.map((error) => <p key={error}>{error}</p>)}
                        </section>
                    )}
                    <label>Email Address<input type="email" name="email" defaultValue={resetEmail} placeholder="you@example.com" required /></label>
                    <label>Password<input type="password" name="password" placeholder="At least 8 characters" minLength="8" required /></label>
                    <label>Confirm Password<input type="password" name="password_confirmation" placeholder="Repeat your password" minLength="8" required /></label>
                    <button type="submit" className="primary-button">Reset Password</button>
                </form>

                <p className="user-auth-switch">Back to <a href="/login">Sign In</a></p>
            </section>
        </main>
    );
}
