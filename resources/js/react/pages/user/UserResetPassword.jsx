import React, { useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { csrfToken } from '../../utils/csrf';

export default function UserResetPassword() {
    const { token } = useParams();
    const [searchParams] = useSearchParams();
    const formErrors = window.__FORM_ERRORS__ ?? [];
    const resetToken = window.__RESET_TOKEN__ ?? token ?? '';
    const resetEmail = window.__RESET_EMAIL__ ?? searchParams.get('email') ?? '';
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

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
                    <label>
                        Password
                        <span className="password-field">
                            <input
                                minLength="8"
                                name="password"
                                pattern="(?=.*[A-Z])(?=.*\d).{8,}"
                                placeholder="At least 8 characters"
                                required
                                title="Use at least 8 characters, 1 uppercase letter, and 1 number"
                                type={showPassword ? 'text' : 'password'}
                            />
                            <button
                                aria-label={showPassword ? 'Hide password' : 'Show password'}
                                className="password-toggle"
                                onClick={() => setShowPassword(!showPassword)}
                                type="button"
                            >
                                ◎
                            </button>
                        </span>
                        <small>Use 8+ characters with at least 1 uppercase letter and 1 number</small>
                    </label>
                    <label>
                        Confirm Password
                        <span className="password-field">
                            <input
                                minLength="8"
                                name="password_confirmation"
                                pattern="(?=.*[A-Z])(?=.*\d).{8,}"
                                placeholder="Repeat your password"
                                required
                                title="Use at least 8 characters, 1 uppercase letter, and 1 number"
                                type={showPasswordConfirmation ? 'text' : 'password'}
                            />
                            <button
                                aria-label={showPasswordConfirmation ? 'Hide password confirmation' : 'Show password confirmation'}
                                className="password-toggle"
                                onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                                type="button"
                            >
                                ◎
                            </button>
                        </span>
                    </label>
                    <button type="submit" className="primary-button">Reset Password</button>
                </form>

                <p className="user-auth-switch">Back to <a href="/login">Sign In</a></p>
            </section>
        </main>
    );
}
