import React, { useState } from 'react';
import { consumeFormErrors } from '../../utils/pageState';
import { csrfToken } from '../../utils/csrf';

export default function AdminLogin() {
    const formErrors = React.useMemo(() => consumeFormErrors(), []);
    const [showPassword, setShowPassword] = useState(false);

    return (
        <main className="login-page">
            <section className="login-brand-panel">
                <img src="/image/logo-full.png" alt="EightFinity" className="login-brand-logo" />
                <h2>Capture Your Infinite Moments</h2>
            </section>
            <section className="login-card">
                <img src="/image/logo-icon-transparent.png" alt="EightFinity" className="login-icon" />
                <span className="login-eyebrow">Sign In</span>
                <h1>Welcome Back!</h1>
                <p>Sign in to your Eightfinity admin account to manage bookings.</p>
                <form method="POST" action="/admin/login" className="form-stack">
                    <input type="hidden" name="_token" value={csrfToken} />
                    {formErrors.length > 0 && (
                        <section className="auth-error-card">
                            <strong>Login could not be completed</strong>
                            {formErrors.map((error) => <p key={error}>{error}</p>)}
                        </section>
                    )}
                    <label>
                        Email Address
                        <input type="email" name="email" placeholder="you@example.com" required autoFocus />
                    </label>
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
                        <label className="check-row">
                            <input type="checkbox" name="remember" />
                            Remember me?
                        </label>
                    </div>
                    <button type="submit" className="primary-button">Login</button>
                </form>
            </section>
        </main>
    );
}
