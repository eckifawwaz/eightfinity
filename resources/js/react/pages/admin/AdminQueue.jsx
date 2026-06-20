import React, { useMemo } from 'react';
import { csrfToken } from '../../utils/csrf';

const packageDurations = {
    'Wedding Package': ['8 hours'],
    'Reservation Package': ['4 hours', '4+1 hours'],
    'Unlimited Package': ['2 hours', '3 hours', '4 hours'],
};

function queueState(status) {
    if (status === 'confirmed') return 'In Session';
    return 'Waiting';
}

function waitTime(index) {
    return `${Math.max(index, 0) * 25}m`;
}

function QueueAction({ booking, nextStatus, children, className }) {
    return (
        <form method="POST" action={`/admin/queue/${booking.id}`}>
            <input type="hidden" name="_token" value={csrfToken} />
            <input type="hidden" name="_method" value="PATCH" />
            <input type="hidden" name="status" value={nextStatus} />
            <button className={className} type="submit">{children}</button>
        </form>
    );
}

export default function AdminQueue() {
    const queueData = window.__ADMIN_QUEUE__ ?? {};
    const queueItems = queueData.items ?? [];

    const orderedItems = useMemo(() => {
        const active = queueItems.filter((item) => item.status === 'confirmed');
        const waiting = queueItems.filter((item) => item.status !== 'confirmed');
        return [...active, ...waiting];
    }, [queueItems]);

    const inSession = queueItems.filter((item) => item.status === 'confirmed').length;
    const inQueue = queueItems.filter((item) => item.status !== 'confirmed').length;
    const totalSessions = (queueData.completed_sessions ?? 0) + queueItems.length;
    const queueStats = [
        { label: 'In Session', value: String(inSession), icon: '▷', color: 'green' },
        { label: 'In Queue', value: String(inQueue), icon: '◌', color: 'gold' },
        { label: 'Avg Wait Time', value: inQueue ? '25m' : '0m', icon: '◷', color: 'blue' },
        { label: 'Total Sessions', value: String(totalSessions), icon: '↗', color: 'orange' },
    ];

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
                        <a href="/admin/queue" className="active"><span>◌</span>Manage Queue</a>
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

            <main className="admin-content admin-queue-content">
                <section className="admin-overview-card">
                    <h1>Manage Queue</h1>
                    <p>Real-time queue management and session control</p>
                </section>

                <section className="admin-metrics queue-metrics">
                    {queueStats.map((metric) => (
                        <article className="admin-metric-card" key={metric.label}>
                            <span className={`admin-metric-icon ${metric.color}`}>{metric.icon}</span>
                            <strong>{metric.value}</strong>
                            <small>{metric.label}</small>
                        </article>
                    ))}
                </section>

                <section className="live-queue-card">
                    <header>Live Queue</header>
                    <div className="queue-list">
                        {orderedItems.map((item, index) => {
                            const active = item.status === 'confirmed';
                            const waitingPosition = orderedItems.filter((booking) => booking.status !== 'confirmed').findIndex((booking) => booking.id === item.id) + 1;
                            const duration = packageDurations[item.package_name]?.[item.package_option] ?? '-';

                            return (
                                <article className={active ? 'queue-item active' : 'queue-item'} key={item.id}>
                                    <div className="queue-item-top">
                                        <span className={active ? 'queue-play' : 'queue-number'}>
                                            {active ? '▷' : waitingPosition}
                                        </span>
                                        <div className="queue-customer">
                                            <strong>{item.customer_name}</strong>
                                            <small>#{item.booking_code}</small>
                                        </div>
                                        <div>
                                            <small>Package</small>
                                            <strong>{item.package_name}</strong>
                                        </div>
                                        <div>
                                            <small>Time</small>
                                            <strong>{item.booking_time} ({duration})</strong>
                                        </div>
                                        <div>
                                            <small>Location</small>
                                            <strong>{item.booking_location ?? '-'}</strong>
                                        </div>
                                        <span className={active ? 'queue-state in-session' : 'queue-state waiting'}>
                                            {queueState(item.status)}
                                        </span>
                                    </div>

                                    {active && (
                                        <div className="queue-progress-block">
                                            <div className="queue-progress-label">
                                                <span>Session Progress</span>
                                                <strong>60%</strong>
                                            </div>
                                            <div className="queue-progress">
                                                <span style={{ width: '60%' }} />
                                            </div>
                                        </div>
                                    )}

                                    {!active && (
                                        <div className="queue-wait-note">
                                            Estimated wait time: <strong>{waitTime(index)}</strong>
                                        </div>
                                    )}

                                    <div className="queue-layout-summary">
                                        <span>{item.layout_name ?? 'Wedding Setup'}</span>
                                        <span>{item.booth_size ?? '3 x 3 meter'}</span>
                                        <span>Printer {item.printer_position ?? 'inside'}</span>
                                        <span>Entrance {item.entrance_direction ?? 'left'}</span>
                                    </div>

                                    <div className="queue-actions">
                                        {active ? (
                                            <>
                                                <QueueAction booking={item} className="complete" nextStatus="completed">
                                                    ⊙ Complete Session
                                                </QueueAction>
                                                <a className="queue-layout-link" href={`/admin/layout?booking=${item.id}`}>◇ Layout</a>
                                            </>
                                        ) : (
                                            <>
                                                <QueueAction booking={item} className="start" nextStatus="confirmed">
                                                    ▷ Start Session
                                                </QueueAction>
                                                <a className="queue-layout-link" href={`/admin/layout?booking=${item.id}`}>◇ Layout</a>
                                            </>
                                        )}
                                    </div>
                                    {active && (
                                        <div className="queue-actions queue-actions-single">
                                            <QueueAction booking={item} className="secondary" nextStatus="pending">
                                                Ⅱ Pause Session
                                            </QueueAction>
                                        </div>
                                    )}
                                </article>
                            );
                        })}

                        {!orderedItems.length && (
                            <article className="profile-empty-card queue-empty-card">
                                <strong>No active queue yet</strong>
                                <p>Bookings marked pending or confirmed will appear here.</p>
                                <a href="/admin/bookings">Open Manage Bookings</a>
                            </article>
                        )}
                    </div>
                </section>

                <section className="booking-pagination">
                    <span>
                        Showing <strong>{orderedItems.length}</strong> live queue item{orderedItems.length === 1 ? '' : 's'}
                    </span>
                    <div>
                        <button type="button" className="active">Live Database</button>
                    </div>
                </section>
            </main>
        </div>
    );
}
