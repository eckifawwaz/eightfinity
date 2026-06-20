import React from 'react';

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function compactCurrency(value) {
    const amount = Number(value ?? 0);
    if (amount >= 1000000) return `IDR ${(amount / 1000000).toFixed(amount % 1000000 === 0 ? 0 : 1)}M`;
    if (amount >= 1000) return `IDR ${Math.round(amount / 1000)}K`;
    return currency.format(amount);
}

export default function AdminDashboard() {
    const dashboard = window.__ADMIN_DASHBOARD__ ?? {};
    const metricsData = dashboard.metrics ?? {};
    const bookings = dashboard.recent_bookings ?? [];
    const packages = dashboard.package_stats ?? [];
    const metrics = [
        { label: "Today's Bookings", value: metricsData.bookings_today ?? 0, icon: '▣', color: 'terracotta' },
        { label: 'Active Sessions', value: metricsData.active_sessions ?? 0, icon: '♙', color: 'gold' },
        { label: 'Queue Length', value: metricsData.queue_length ?? 0, icon: '◷', color: 'green' },
        { label: 'Revenue Today', value: compactCurrency(metricsData.revenue_today ?? 0), icon: '$', color: 'pink' },
    ];

    return (
        <div className="admin-dashboard-page admin-dashboard-screen">
            <aside className="admin-sidebar">
                <div>
                    <div className="admin-brand">
                        <img src="/image/logo-icon.png" alt="EightFinity" />
                        <strong><span>Eight</span>Finity</strong>
                    </div>

                    <nav className="admin-nav">
                        <a href="/dashboard" className="active"><span>▦</span>Dashboard</a>
                        <a href="/admin/bookings"><span>▣</span>Manage Bookings</a>
                        <a href="/admin/queue"><span>♙</span>Manage Queue</a>
                        <a href="/admin/customers"><span>▤</span>Customer Data</a>
                        <a href="/admin/layout"><span>♢</span>2D Layout View</a>
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

            <main className="admin-content">
                <section className="admin-overview-card">
                    <h1>Dashboard Overview</h1>
                    <p>Real-time insights from customer bookings</p>
                </section>

                <section className="admin-metrics">
                    {metrics.map((metric) => (
                        <article className="admin-metric-card" key={metric.label}>
                            <span className={`admin-metric-icon ${metric.color}`}>{metric.icon}</span>
                            <strong>{metric.value}</strong>
                            <small>{metric.label}</small>
                        </article>
                    ))}
                </section>

                <section className="admin-main-grid">
                    <article className="admin-bookings-card">
                        <div className="admin-card-header">
                            <h2>Recent Bookings</h2>
                            <a href="/admin/bookings">View All</a>
                        </div>

                        <table className="admin-bookings-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Package</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                {bookings.map((booking) => (
                                    <tr key={booking.id}>
                                        <td className="booking-id">#{booking.booking_code}</td>
                                        <td>{booking.customer_name}</td>
                                        <td>{booking.package_name}</td>
                                        <td>{booking.booking_time}</td>
                                        <td>
                                            <span className={`status-pill ${booking.status}`}>{booking.status}</span>
                                        </td>
                                        <td className="booking-amount">{currency.format(booking.amount ?? 0)}</td>
                                    </tr>
                                ))}
                                {!bookings.length && (
                                    <tr>
                                        <td className="admin-empty-table" colSpan="6">No bookings yet.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </article>

                    <article className="admin-package-card">
                        <div className="package-title">
                            <span>◇</span>
                            <h2>Package Stats</h2>
                        </div>
                        <div className="package-list">
                            {packages.map((item) => (
                                <div className="package-item" key={item.name}>
                                    <div>
                                        <strong>{item.name}</strong>
                                        <span>{item.count}</span>
                                    </div>
                                    <div className="package-track">
                                        <span style={{ width: `${item.width}%` }} />
                                    </div>
                                    <small>{compactCurrency(item.revenue)} revenue</small>
                                </div>
                            ))}
                            {!packages.length && (
                                <p className="dashboard-empty-copy">Package stats will appear after customer bookings.</p>
                            )}
                        </div>
                    </article>
                </section>
            </main>
        </div>
    );
}
