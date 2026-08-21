import React, { useMemo, useState } from 'react';

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const statusLabels = {
    completed: { label: 'Berhasil', className: 'ok' },
    confirmed: { label: 'Berhasil', className: 'ok' },
    pending: { label: 'Pending', className: 'pending' },
    cancelled: { label: 'Dibatalkan', className: 'failed' },
    refunded: { label: 'Refund', className: 'pending' },
};

function formatChange(change) {
    const value = Number(change ?? 0);
    const sign = value > 0 ? '+' : '';
    return `${value >= 0 ? '↗' : '↘'} ${sign}${value}%`;
}

function formatDateTime(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';

    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    }).format(date).replace(',', ',');
}

function initials(name) {
    return (name ?? '?').trim().split(/\s+/).slice(0, 2).map((part) => part[0]?.toUpperCase()).join('');
}

function RevenueChart({ trend }) {
    const maxAmount = Math.max(1, ...trend.map((point) => point.amount));
    const currentIndex = trend.length - 1;

    return (
        <div className="revenue-chart">
            {trend.map((point, index) => {
                const isCurrent = index === currentIndex;
                const heightPct = Math.max(2, (point.amount / maxAmount) * 100);

                return (
                    <div className="revenue-bar-col" key={`${point.label}-${index}`}>
                        {isCurrent && <span className="revenue-bar-value">{currency.format(point.amount)}</span>}
                        <div className="revenue-bar-track">
                            <div
                                className={isCurrent ? 'revenue-bar current' : 'revenue-bar'}
                                data-tooltip={`${point.label}: ${currency.format(point.amount)}`}
                                style={{ height: `${heightPct}%` }}
                            />
                        </div>
                        <small>{point.label}</small>
                    </div>
                );
            })}
        </div>
    );
}

export default function AdminRevenue() {
    const revenue = window.__ADMIN_REVENUE__ ?? {};
    const [range, setRange] = useState('monthly');

    const trend = useMemo(
        () => (range === 'monthly' ? (revenue.monthly_trend ?? []) : (revenue.weekly_trend ?? [])),
        [range, revenue],
    );

    const metrics = [
        {
            label: 'PENDAPATAN MINGGU INI', icon: '🧾', color: 'orange',
            amount: revenue.week?.amount ?? 0, change: revenue.week?.change ?? 0,
        },
        {
            label: 'PENDAPATAN BULAN INI', icon: '📅', color: 'blue',
            amount: revenue.month?.amount ?? 0, change: revenue.month?.change ?? 0,
        },
        {
            label: 'PENDAPATAN TAHUN INI', icon: '🏛', color: 'green',
            amount: revenue.year?.amount ?? 0, change: revenue.year?.change ?? 0,
        },
    ];

    const transactions = revenue.transactions ?? [];

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
                        <a href="/admin/customers"><span>▤</span>Customer Data</a>
                        <a href="/admin/layout"><span>◇</span>2D Layout View</a>
                        <a href="/admin/revenue" className="active"><span>◆</span>Revenue</a>
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

            <main className="admin-content admin-revenue-content">
                <section className="admin-overview-card">
                    <h1>Revenue</h1>
                    <p>Real-time financial performance overview</p>
                </section>

                <section className="admin-metrics revenue-metrics">
                    {metrics.map((metric) => (
                        <article className="admin-metric-card" key={metric.label}>
                            <div className="admin-metric-card-head">
                                <span className={`admin-metric-icon ${metric.color}`}>{metric.icon}</span>
                                <span className={metric.change >= 0 ? 'revenue-trend up' : 'revenue-trend down'}>
                                    {formatChange(metric.change)}
                                </span>
                            </div>
                            <small>{metric.label}</small>
                            <strong>{currency.format(metric.amount)}</strong>
                        </article>
                    ))}
                </section>

                <section className="revenue-chart-card">
                    <header className="revenue-chart-header">
                        <div>
                            <h2>Tren Pendapatan Tahunan</h2>
                            <p>{range === 'monthly' ? 'Data visualized for the current fiscal year' : 'Data visualized for the last 12 weeks'}</p>
                        </div>
                        <div className="revenue-range-toggle">
                            <button type="button" className={range === 'weekly' ? 'active' : ''} onClick={() => setRange('weekly')}>Weekly</button>
                            <button type="button" className={range === 'monthly' ? 'active' : ''} onClick={() => setRange('monthly')}>Monthly</button>
                        </div>
                    </header>

                    {trend.length > 0 ? (
                        <RevenueChart trend={trend} />
                    ) : (
                        <p className="revenue-chart-empty">No revenue recorded yet.</p>
                    )}
                </section>

                <section className="revenue-transactions-card">
                    <header className="admin-card-header">
                        <h2>Transaksi Terbaru</h2>
                        <a href="/admin/bookings">Lihat Semua</a>
                    </header>

                    {transactions.length > 0 ? (
                        <table className="revenue-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Pelanggan</th>
                                    <th>ID Booking</th>
                                    <th>Status</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                {transactions.map((transaction) => {
                                    const status = statusLabels[transaction.status] ?? { label: transaction.status, className: 'pending' };

                                    return (
                                        <tr key={transaction.id}>
                                            <td>{formatDateTime(transaction.created_at)}</td>
                                            <td>
                                                <div className="revenue-customer-cell">
                                                    <span className="revenue-avatar">{initials(transaction.customer_name)}</span>
                                                    {transaction.customer_name}
                                                </div>
                                            </td>
                                            <td>#{transaction.booking_code}</td>
                                            <td><span className={`revenue-status ${status.className}`}>{status.label}</span></td>
                                            <td>{currency.format(transaction.amount)}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    ) : (
                        <p className="revenue-chart-empty">No transactions yet.</p>
                    )}
                </section>
            </main>
        </div>
    );
}
