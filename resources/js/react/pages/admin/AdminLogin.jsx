import React from 'react';
import { csrfToken } from '../../utils/csrf';

export default function AdminLogin() {
    return (
        <main className="login-page">
            <section className="login-brand-panel">
                <img src="/image/logo-full.png" alt="EightFinity" className="login-brand-logo" />
                <h2>Capture Your Infinite Moments</h2>
            </section>
            <section className="login-card">
                <img src="/image/logo-icon.png" alt="EightFinity" className="login-icon" />
                <span className="login-eyebrow">Sign In</span>
                <h1>Welcome Back!</h1>
                <p>Sign in to your Eightfinity admin account to manage bookings.</p>
                <form method="POST" action="/admin/login" className="form-stack">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <label>
                        Email Address
                        <input type="email" name="email" placeholder="you@example.com" required autoFocus />
                    </label>
                    <label>
                        Password
                        <span className="password-field">
                            <input type="password" name="password" placeholder="At least 8 characters" required />
                            <span aria-hidden="true">◎</span>
                        </span>
                        <small>use 8+ characters with letters and numbers</small>
                    </label>
                    <div className="form-row login-options">
                        <label className="check-row">
                            <input type="checkbox" name="remember" />
                            Remember me?
                        </label>
                        <a href="/admin/login">Forgot Password?</a>
                    </div>
                    <button type="submit" className="primary-button">Login</button>
                </form>
            </section>
        </main>
    );
}
