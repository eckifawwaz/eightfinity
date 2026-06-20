import React, { useMemo, useState } from 'react';
import { csrfToken } from '../../utils/csrf';

const packageDurations = {
    'Wedding Package': ['8 hours'],
    'Reservation Package': ['4 hours', '4+1 hours'],
    'Unlimited Package': ['2 hours', '3 hours', '4 hours'],
};

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function formatDate(value) {
    if (!value) return '-';

    const rawValue = typeof value === 'string' ? value : String(value);
    const normalizedValue = rawValue.includes('T') ? rawValue : `${rawValue}T00:00:00`;
    const date = new Date(normalizedValue);

    if (Number.isNaN(date.getTime())) return rawValue;

    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
}

function paymentState(status) {
    if (status === 'cancelled') return 'cancelled';
    if (status === 'pending') return 'pending';
    return 'paid';
}

export default function AdminBookings() {
    const [query, setQuery] = useState('');
    const [activeTab, setActiveTab] = useState('all');
    const bookings = window.__ADMIN_BOOKINGS__ ?? [];

    const filteredBookings = useMemo(() => {
        const normalizedQuery = query.trim().toLowerCase();

        return bookings.filter((booking) => {
            const statusMatch = activeTab === 'all' || booking.status === activeTab;
            const searchable = [
                booking.booking_code,
                booking.customer_name,
                booking.customer_email,
                booking.package_name,
                booking.booking_location,
            ].join(' ').toLowerCase();

            return statusMatch && (!normalizedQuery || searchable.includes(normalizedQuery));
        });
    }, [activeTab, bookings, query]);

    return (
        <div className="admin-dashboard-page">
            <aside className="admin-sidebar">
                <div>
                    <div className="admin-brand">
                        <img src="/image/logo-icon.png" alt="EightFinity" />
                        <strong><span>Eight</span>Finity</strong>
                    </div>

                    <nav className="admin-nav">
                        <a href="/dashboard"><span>□</span>Dashboard</a>
                        <a href="/admin/bookings" className="active"><span>▣</span>Manage Bookings</a>
                        <a href="/admin/queue"><span>◌</span>Manage Queue</a>
                        <a href="/admin/customers"><span>▤</span>Customer Data</a>
                        <a href="/admin/layout"><span>◇</span>2D Layout View</a>
                    </nav>
                </div>

                <div className="admin-sidebar-bottom">
                    <a href="/admin/profile" className="admin-user-card">
                        <span className="admin-avatar">A</span>
                        <div>
                            <strong>Admin User</strong>
                            <small>admin@eightfinity.com</small>
                        </div>
                    </a>
                    <a href="/admin/logout" className="admin-signout">↪ Sign Out</a>
                </div>
            </aside>

            <main className="admin-content admin-bookings-content">
                <section className="admin-overview-card">
                    <h1>Manage Bookings</h1>
                    <p>View and manage all customer bookings</p>
                </section>

                <section className="booking-tools">
                    <label className="booking-search">
                        <span>⌕</span>
                        <input
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Search by customer name, booking ID, or email..."
                            type="search"
                            value={query}
                        />
                    </label>
                    <div className="booking-tabs">
                        {['all', 'confirmed', 'pending', 'completed', 'cancelled'].map((tab) => (
                            <button
                                className={activeTab === tab ? 'active' : ''}
                                key={tab}
                                onClick={() => setActiveTab(tab)}
                                type="button"
                            >
                                {tab}
                            </button>
                        ))}
                    </div>
                </section>

                <section className="manage-bookings-card">
                    <table className="manage-bookings-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Date & Time</th>
                                <th>People</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredBookings.map((booking) => {
                                const duration = packageDurations[booking.package_name]?.[booking.package_option] ?? '-';
                                const payment = paymentState(booking.status);

                                return (
                                    <tr key={booking.id}>
                                        <td className="booking-id">#{booking.booking_code}</td>
                                        <td>
                                            <strong>{booking.customer_name}</strong>
                                            <small>{booking.customer_email}</small>
                                        </td>
                                        <td>
                                            <span>{booking.package_name}</span>
                                            <small>{duration}</small>
                                        </td>
                                        <td>
                                            <span>▫ {formatDate(booking.booking_date)}</span>
                                            <small>◷ {booking.booking_time}</small>
                                        </td>
                                        <td>{booking.people}</td>
                                        <td><span className={`status-pill ${booking.status}`}>{booking.status}</span></td>
                                        <td>
                                            <strong>{currency.format(booking.amount ?? 0)}</strong>
                                            <small className={`payment-state ${payment}`}>
                                                {payment} • {(booking.payment_provider ?? '-').toUpperCase()}
                                            </small>
                                        </td>
                                        <td>
                                            <form className="booking-status-form" method="POST" action={`/admin/bookings/${booking.id}/status`}>
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <input type="hidden" name="_method" value="PATCH" />
                                                <label>
                                                    Update status
                                                </label>
                                                <select name="status" defaultValue={booking.status}>
                                                    <option value="pending">Pending</option>
                                                    <option value="confirmed">Confirmed</option>
                                                    <option value="completed">Completed</option>
                                                    <option value="cancelled">Cancelled</option>
                                                </select>
                                                <button type="submit">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                );
                            })}
                            {!filteredBookings.length && (
                                <tr>
                                    <td className="admin-empty-table" colSpan="8">
                                        No bookings found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </section>

                <section className="booking-pagination">
                    <span>
                        Showing <strong>{filteredBookings.length}</strong> of <strong>{bookings.length}</strong> bookings
                    </span>
                    <div>
                        <button type="button" className="active">Live Database</button>
                    </div>
                </section>
            </main>
        </div>
    );
}
