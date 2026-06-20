import React, { useMemo, useState } from 'react';
import { csrfToken } from '../../utils/csrf';

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

export default function AdminCustomers() {
    const [query, setQuery] = useState('');
    const [selectedCustomer, setSelectedCustomer] = useState(null);
    const customerData = window.__ADMIN_CUSTOMERS__ ?? {};
    const customers = customerData.customers ?? [];
    const summary = customerData.summary ?? {};

    const filteredCustomers = useMemo(() => {
        const normalizedQuery = query.trim().toLowerCase();

        return customers.filter((customer) => {
            const searchable = [
                customer.customer_code,
                customer.name,
                customer.email,
                customer.phone,
                customer.alternate_phone,
                customer.favorite_package,
            ].join(' ').toLowerCase();

            return !normalizedQuery || searchable.includes(normalizedQuery);
        });
    }, [customers, query]);

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
                        <a href="/admin/bookings"><span>▣</span>Manage Bookings</a>
                        <a href="/admin/queue"><span>◌</span>Manage Queue</a>
                        <a href="/admin/customers" className="active"><span>▤</span>Customer Data</a>
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

            <main className="admin-content admin-customers-content">
                <section className="admin-overview-card">
                    <h1>Customer Data</h1>
                    <p>View and manage customer information</p>
                </section>

                <section className="customer-stats">
                    <article><strong>{summary.total_customers ?? 0}</strong><span>Total Customers</span></article>
                    <article><strong>{summary.active_customers ?? 0}</strong><span>Active Customers</span></article>
                    <article><strong>{summary.new_this_month ?? 0}</strong><span>New This Month</span></article>
                </section>

                <label className="booking-search customer-search">
                    <span>⌕</span>
                    <input
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Search by name, email, phone, or customer ID..."
                        type="search"
                        value={query}
                    />
                </label>

                <section className="manage-bookings-card">
                    <table className="manage-bookings-table customer-table">
                        <thead>
                            <tr>
                                <th>Customer ID</th>
                                <th>Name</th>
                                <th>Contact Info</th>
                                <th>Total Bookings</th>
                                <th>Total Spent</th>
                                <th>Last Visit</th>
                                <th>Favorite Package</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredCustomers.map((customer) => (
                                <tr key={customer.id}>
                                    <td className="booking-id">{customer.customer_code}</td>
                                    <td><strong>{customer.name}</strong></td>
                                    <td>
                                        <small>✉ {customer.email}</small>
                                        <small>⌕ {customer.phone ?? '-'}</small>
                                    </td>
                                    <td><strong>{customer.total_bookings}</strong></td>
                                    <td className="booking-id"><strong>{currency.format(customer.total_spent ?? 0)}</strong></td>
                                    <td>{formatDate(customer.last_visit)}</td>
                                    <td>{customer.favorite_package}</td>
                                    <td><span className={`customer-status ${customer.status.toLowerCase()}`}>{customer.status}</span></td>
                                    <td>
                                        <button className="view-button" onClick={() => setSelectedCustomer(customer)} type="button">
                                            ⊙ View
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {!filteredCustomers.length && (
                                <tr>
                                    <td className="admin-empty-table" colSpan="9">No customers found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </section>

                <section className="booking-pagination">
                    <span>
                        Showing <strong>{filteredCustomers.length}</strong> of <strong>{customers.length}</strong> customers
                    </span>
                    <div>
                        <button type="button" className="active">Live Database</button>
                    </div>
                </section>
            </main>

            {selectedCustomer && (
                <section className="user-logout-overlay" role="dialog" aria-modal="true">
                    <div className="customer-detail-modal">
                        <header>
                            <div>
                                <small>{selectedCustomer.customer_code}</small>
                                <h2>{selectedCustomer.name}</h2>
                                <p>{selectedCustomer.email}</p>
                            </div>
                            <button type="button" onClick={() => setSelectedCustomer(null)} aria-label="Close customer detail">
                                ×
                            </button>
                        </header>

                        <dl>
                            <div><dt>Phone</dt><dd>{selectedCustomer.phone ?? '-'}</dd></div>
                            <div><dt>Alternate Phone</dt><dd>{selectedCustomer.alternate_phone ?? '-'}</dd></div>
                            <div><dt>Total Bookings</dt><dd>{selectedCustomer.total_bookings}</dd></div>
                            <div><dt>Total Spent</dt><dd>{currency.format(selectedCustomer.total_spent ?? 0)}</dd></div>
                            <div><dt>Favorite Package</dt><dd>{selectedCustomer.favorite_package}</dd></div>
                            <div><dt>Status</dt><dd>{selectedCustomer.status}</dd></div>
                        </dl>

                        <section className="customer-edit-section">
                            <h3>Edit Customer</h3>
                            <form method="POST" action={`/admin/customers/${selectedCustomer.id}`}>
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input type="hidden" name="_method" value="PATCH" />
                                <label>
                                    Full Name
                                    <input name="name" defaultValue={selectedCustomer.name ?? ''} required />
                                </label>
                                <label>
                                    Email Address
                                    <input name="email" defaultValue={selectedCustomer.email ?? ''} required type="email" />
                                </label>
                                <label>
                                    Phone Number
                                    <input name="phone" defaultValue={selectedCustomer.phone ?? ''} />
                                </label>
                                <label>
                                    Alternate Phone
                                    <input name="alternate_phone" defaultValue={selectedCustomer.alternate_phone ?? ''} />
                                </label>
                                <button type="submit">Save Customer</button>
                            </form>
                        </section>

                        <section>
                            <h3>Booking History</h3>
                            <div className="customer-booking-list">
                                {selectedCustomer.bookings?.length ? selectedCustomer.bookings.map((booking) => (
                                    <article key={booking.id}>
                                        <strong>#{booking.booking_code}</strong>
                                        <span>{booking.package_name}</span>
                                        <small>{formatDate(booking.booking_date)} at {booking.booking_time}</small>
                                        <em>{currency.format(booking.amount ?? 0)} • {booking.status}</em>
                                    </article>
                                )) : (
                                    <p>No booking history yet.</p>
                                )}
                            </div>
                        </section>

                        <section className="customer-danger-section">
                            <div>
                                <h3>Delete Customer</h3>
                                <p>Deleting this customer will also remove their booking history.</p>
                            </div>
                            <form
                                method="POST"
                                action={`/admin/customers/${selectedCustomer.id}`}
                                onSubmit={(event) => {
                                    if (!window.confirm(`Delete ${selectedCustomer.name}? This cannot be undone.`)) {
                                        event.preventDefault();
                                    }
                                }}
                            >
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input type="hidden" name="_method" value="DELETE" />
                                <button type="submit">Delete Customer</button>
                            </form>
                        </section>
                    </div>
                </section>
            )}
        </div>
    );
}
