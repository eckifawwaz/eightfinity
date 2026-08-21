import React, { useState } from 'react';
import { csrfToken } from '../../utils/csrf';

export default function UserSignIn() {
    const formErrors = window.__FORM_ERRORS__ ?? [];
    const [showPassword, setShowPassword] = useState(false);

    return (
        <main className="user-auth-page user-login-page">
            <section className="user-auth-brand">
                <img src="/image/logo-full.png" alt="EightFinity" />
                <h2>Capture Your Infinite Moments</h2>
            </section>
            <section className="user-register-card user-login-card">
                <img src="/image/logo-icon.png" alt="" className="user-auth-icon" />
                <h1>Welcome Back!</h1>
                <p>Sign in to your Eightfinity account to manage your bookings.</p>
                <form method="POST" action="/login" className="user-register-form">
                    <input type="hidden" name="_token" value={csrfToken} />
                    {formErrors.length > 0 && (
                        <section className="auth-error-card">
                            <strong>Login could not be completed</strong>
                            {formErrors.map((error) => <p key={error}>{error}</p>)}
                        </section>
                    )}
                    <label>Email Address<input type="email" name="email" placeholder="you@example.com" required /></label>
                    <label>
                        Password
                        <span className="password-field">
                            <input type={showPassword ? 'text' : 'password'} name="password" placeholder="At least 8 characters" required />
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
                    <div className="form-row login-options">
                        <label className="check-row"><input type="checkbox" name="remember" />Remember me?</label>
                        <a href="/forgot-password">Forgot Password?</a>
                    </div>
                    <button type="submit" className="primary-button">Login</button>
                </form>
                <p className="user-auth-switch">Don&apos;t have an account? <a href="/register">Sign Up</a></p>
            </section>
        </main>
    );
}
