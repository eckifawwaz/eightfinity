import React from 'react';
import { csrfToken } from '../../utils/csrf';

export default function AdminLogout() {
    return (
        <main className="admin-logout-page">
            <section className="logout-modal">
                <img src="/image/logo-icon.png" alt="EightFinity" />
                <h1>Are you sure you want to sign out?</h1>
                <p>You will be signed out of your account on this device.</p>

                <div className="logout-actions">
                    <form method="POST" action="/admin/logout">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <button type="submit" className="logout-confirm">Logout</button>
                    </form>
                    <a href="/dashboard" className="logout-cancel">Cancel</a>
                </div>
            </section>
        </main>
    );
}
