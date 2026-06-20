import React from 'react';
import { csrfToken } from '../../utils/csrf';

export default function UserSignUp() {
    const formErrors = window.__FORM_ERRORS__ ?? [];

    return (
        <main className="user-auth-page user-signup-page">
            <section className="user-auth-brand">
                <img src="/image/logo-full.png" alt="EightFinity" />
                <h2>Capture Your Infinite Moments</h2>
            </section>
            <section className="user-register-card">
                <img src="/image/logo-icon.png" alt="" className="user-auth-icon" />
                <h1>Create Account</h1>
                <p>Join Eightfinity and start booking your photo sessions</p>
                <form method="POST" action="/register" className="user-register-form">
                    <input type="hidden" name="_token" value={csrfToken} />
                    {formErrors.length > 0 && (
                        <section className="auth-error-card">
                            <strong>Registration could not be completed</strong>
                            {formErrors.map((error) => <p key={error}>{error}</p>)}
                        </section>
                    )}
                    <div className="user-form-grid">
                        <label>First Name<input name="first_name" placeholder="Maya" required /></label>
                        <label>Last Name<input name="last_name" placeholder="Putri" required /></label>
                    </div>
                    <label>Email Address<input type="email" name="email" placeholder="you@example.com" required /></label>
                    <label>Phone Number<input name="phone" placeholder="+62 xxx xxxx xxxx" required /></label>
                    <label>Alternate Phone Number<input name="alternate_phone" placeholder="+62 xxx xxxx xxxx" /></label>
                    <label>Password<input type="password" name="password" placeholder="At least 8 characters" minLength="8" required /></label>
                    <label>Confirm Password<input type="password" name="password_confirmation" placeholder="Repeat your password" minLength="8" required /></label>
                    <button type="submit" className="primary-button">Create Account</button>
                </form>
                <p className="user-auth-switch">Already have an account? <a href="/login">Sign In</a></p>
            </section>
        </main>
    );
}
