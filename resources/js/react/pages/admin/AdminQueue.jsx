import React, { useMemo, useState } from 'react';
import { csrfToken } from '../../utils/csrf';

const packageDurations = {
    'Wedding Package': ['4 hours', '8 hours'],
    'Reservation Package': ['4 hours', '4+1 hours'],
    'Unlimited Package': ['2 hours', '3 hours', '4 hours'],
};

const equipmentLabels = {
    camera: 'Kamera Utama',
    printer: 'Printer',
    lighting: 'Lighting',
    backdrop: 'Backdrop',
};

const eventPhaseBadges = {
    in_progress: { label: '● SEDANG BERLANGSUNG', className: 'event-status-badge' },
    upcoming: { label: '◷ TERJADWAL', className: 'event-status-badge upcoming' },
    overdue: { label: '⚠ PERLU DISELESAIKAN', className: 'event-status-badge overdue' },
};

function PlayIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 5v14l12-7Z" fill="currentColor" stroke="none" />
        </svg>
    );
}

function QueueIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    );
}

function ClockIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3.5 3.5" />
        </svg>
    );
}

function TrendingUpIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 17 17 6M9 6h8v8" />
        </svg>
    );
}

const PAGE_SIZE = 10;

function ActionForm({ action, method = 'PATCH', fields = {}, className, disabled = false, children, confirmMessage }) {
    return (
        <form
            method="POST"
            action={action}
            onSubmit={(event) => {
                if (confirmMessage && !window.confirm(confirmMessage)) {
                    event.preventDefault();
                }
            }}
        >
            <input type="hidden" name="_token" value={csrfToken} />
            <input type="hidden" name="_method" value={method} />
            {Object.entries(fields).map(([name, value]) => (
                <input key={name} type="hidden" name={name} value={value} />
            ))}
            <button className={className} type="submit" disabled={disabled}>{children}</button>
        </form>
    );
}

function AddGuestModal({ bookingId, nextPosition, onClose }) {
    const [guestName, setGuestName] = useState('');
    const [checkedInAt, setCheckedInAt] = useState('');

    return (
        <div className="queue-modal-backdrop" onClick={onClose}>
            <form
                className="queue-modal-card"
                method="POST"
                action={`/admin/queue/${bookingId}/guests`}
                onClick={(event) => event.stopPropagation()}
            >
                <input type="hidden" name="_token" value={csrfToken} />
                <h2>Tambahkan Antrian</h2>

                <label className="queue-modal-field">
                    <span>Nama Tamu</span>
                    <input
                        name="guest_name"
                        type="text"
                        placeholder="Masukan nama....."
                        value={guestName}
                        onChange={(event) => setGuestName(event.target.value)}
                        required
                    />
                </label>

                <label className="queue-modal-field">
                    <span>Check-in</span>
                    <input
                        name="checked_in_at"
                        type="time"
                        placeholder="Masukan jam....."
                        value={checkedInAt}
                        onChange={(event) => setCheckedInAt(event.target.value)}
                        required
                    />
                </label>

                <p className="queue-modal-note">Akan otomatis masuk ke posisi antrian {nextPosition}</p>

                <div className="queue-modal-actions">
                    <button type="button" className="queue-modal-cancel" onClick={onClose}>Batal</button>
                    <button type="submit" className="queue-modal-submit">Tambahkan</button>
                </div>
            </form>
        </div>
    );
}

export default function AdminQueue() {
    const queueData = window.__ADMIN_QUEUE__ ?? {};
    const event = queueData.event ?? null;
    const queue = queueData.queue ?? [];
    const stats = queueData.stats ?? {};
    const currentGuest = event?.current_guest ?? null;
    const [page, setPage] = useState(1);
    const [showAddGuest, setShowAddGuest] = useState(false);

    const duration = event ? (packageDurations[event.package_name]?.[event.package_option] ?? '-') : '-';
    const equipment = event?.equipment_status ?? { camera: true, printer: true, lighting: true, backdrop: true };
    const badge = eventPhaseBadges[event?.event_phase] ?? eventPhaseBadges.in_progress;
    const progress = currentGuest?.session_progress_percent ?? 0;

    const pageCount = Math.max(1, Math.ceil(queue.length / PAGE_SIZE));
    const currentPage = Math.min(page, pageCount);
    const pagedQueue = useMemo(
        () => queue.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE),
        [queue, currentPage],
    );

    const metrics = [
        { label: 'In Session', value: stats.in_session ?? 0, icon: <PlayIcon />, color: 'green' },
        { label: 'In Queue', value: stats.in_queue ?? 0, icon: <QueueIcon />, color: 'gold' },
        { label: 'Avg Wait Time', value: stats.avg_wait_minutes != null ? `${stats.avg_wait_minutes}m` : '-', icon: <ClockIcon />, color: 'blue' },
        { label: 'Total Sessions', value: stats.total_sessions ?? 0, icon: <TrendingUpIcon />, color: 'terracotta' },
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
                        <a href="/admin/revenue"><span>◆</span>Revenue</a>
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
                    {metrics.map((metric) => (
                        <article className="admin-metric-card" key={metric.label}>
                            <span className={`admin-metric-icon ${metric.color}`}>{metric.icon}</span>
                            <strong>{metric.value}</strong>
                            <small>{metric.label}</small>
                        </article>
                    ))}
                </section>

                {event && (
                    <section className="queue-now-serving">
                        <div className="queue-now-serving-top">
                            <a className="queue-now-serving-link" href={`/admin/layout?booking=${event.id}`}>◇ Atur Layout</a>
                            {currentGuest ? (
                                <ActionForm
                                    action={`/admin/queue/guests/${currentGuest.id}/complete`}
                                    className="queue-now-serving-link"
                                    confirmMessage="Selesaikan sesi tamu ini?"
                                >
                                    ⊙ Selesaikan Section
                                </ActionForm>
                            ) : (
                                <ActionForm
                                    action={`/admin/queue/${event.id}`}
                                    fields={{ status: 'completed' }}
                                    className="queue-now-serving-link"
                                    confirmMessage="Selesaikan sesi booking ini?"
                                >
                                    ✓ Selesaikan Sesi
                                </ActionForm>
                            )}
                        </div>

                        <div className="queue-now-serving-body">
                            <div>
                                <small>{event.package_name?.toUpperCase()}</small>
                                <h2>{currentGuest ? currentGuest.guest_name : event.customer_name}</h2>
                                <span className={badge.className}>{badge.label}</span>
                            </div>
                            {currentGuest && (
                                <div className="queue-now-serving-circle">
                                    <strong>{String(currentGuest.serving_number).padStart(2, '0')}</strong>
                                    <small>Now Serving</small>
                                </div>
                            )}
                        </div>

                        <div className="queue-now-serving-meta">
                            <div>
                                <span>
                                    🕐 {currentGuest
                                        ? `${currentGuest.session_started_label} (${currentGuest.turn_minutes} min)`
                                        : `${event.booking_time} (${duration})`}
                                </span>
                                <span>📍 {event.booking_location ?? '-'}</span>
                                <span>◻ {event.booth_size ?? '3 x 3 meter'}</span>
                            </div>
                            <ActionForm
                                action={`/admin/queue/${event.id}/booth`}
                                fields={{ paused: event.booth_paused ? '0' : '1' }}
                                className="queue-now-serving-link"
                            >
                                {event.booth_paused ? '▷ Mulai Section' : 'Ⅱ Jeda Section'}
                            </ActionForm>
                        </div>

                        {currentGuest && (
                            <div className="queue-progress-block">
                                <div className="queue-progress-label">
                                    <span>Session Progress</span>
                                    <strong>{progress}%</strong>
                                </div>
                                <div className="queue-progress">
                                    <span style={{ width: `${progress}%` }} />
                                </div>
                            </div>
                        )}
                    </section>
                )}

                {!event && (
                    <section className="event-empty-card">
                        <strong>Belum ada event yang sedang berlangsung</strong>
                        <p>Konfirmasi booking dari halaman Manage Bookings agar muncul di sini.</p>
                        <a href="/admin/bookings">Buka Manage Bookings</a>
                    </section>
                )}

                {event && (
                    <section className="event-photo-row">
                        <span>📷 Total Foto Diambil</span>
                        <strong>{event.photos_taken}</strong>
                        <ActionForm
                            action={`/admin/queue/${event.id}/photos`}
                            className="event-photo-button"
                        >
                            + Foto
                        </ActionForm>
                    </section>
                )}

                {event && (
                    <section className="event-equipment-card">
                        <h3>Status Peralatan</h3>
                        <div className="event-equipment-list">
                            {Object.keys(equipmentLabels).map((key) => (
                                <ActionForm
                                    key={key}
                                    action={`/admin/queue/${event.id}/equipment`}
                                    fields={{ equipment: key, ok: equipment[key] ? '0' : '1' }}
                                    className={equipment[key] ? 'equipment-badge ok' : 'equipment-badge issue'}
                                >
                                    {equipment[key] ? '✓' : '!'} {equipmentLabels[key]}
                                </ActionForm>
                            ))}
                        </div>
                    </section>
                )}

                <section className="live-queue-card">
                    <header>
                        Live Queue
                        <button
                            type="button"
                            className="queue-add-button"
                            onClick={() => setShowAddGuest(true)}
                            disabled={!event}
                        >
                            + Tambah Antrian
                        </button>
                    </header>

                    <div className="queue-list">
                        {currentGuest && currentPage === 1 && (
                            <article className="queue-item active">
                                <div className="queue-item-top">
                                    <span className="queue-play">▷</span>
                                    <div className="queue-customer">
                                        <strong>{currentGuest.guest_name}</strong>
                                        <small>Tamu #{currentGuest.serving_number}</small>
                                    </div>
                                    <div>
                                        <small>Time</small>
                                        <strong>{currentGuest.session_started_label} ({currentGuest.turn_minutes} min)</strong>
                                    </div>
                                    <div>
                                        <small>Check-in</small>
                                        <strong>{currentGuest.checked_in_at}</strong>
                                    </div>
                                    <span className="queue-state in-session">In Session</span>
                                </div>

                                <div className="queue-progress-block">
                                    <div className="queue-progress-label">
                                        <span>Session Progress</span>
                                        <strong>{progress}%</strong>
                                    </div>
                                    <div className="queue-progress">
                                        <span style={{ width: `${progress}%` }} />
                                    </div>
                                </div>

                                <div className="queue-actions">
                                    <ActionForm
                                        action={`/admin/queue/guests/${currentGuest.id}/complete`}
                                        className="complete"
                                        confirmMessage="Selesaikan sesi tamu ini?"
                                    >
                                        ✓ Complete Session
                                    </ActionForm>
                                    <ActionForm
                                        action={`/admin/queue/${event.id}/booth`}
                                        fields={{ paused: event.booth_paused ? '0' : '1' }}
                                        className="secondary"
                                    >
                                        {event.booth_paused ? '▷ Resume Session' : 'Ⅱ Pause Session'}
                                    </ActionForm>
                                </div>
                            </article>
                        )}

                        {!currentGuest && !pagedQueue.length && (
                            <p className="dashboard-empty-copy">
                                {event ? 'Belum ada tamu yang mengantre. Klik "+ Tambah Antrian" untuk menambahkan.' : 'Belum ada event yang sedang berlangsung.'}
                            </p>
                        )}

                        {pagedQueue.map((item, index) => (
                            <article className="queue-item" key={item.id}>
                                <div className="queue-item-top">
                                    <span className="queue-number">{(currentPage - 1) * PAGE_SIZE + index + 1}</span>
                                    <div className="queue-customer">
                                        <strong>{item.guest_name}</strong>
                                    </div>
                                    <div />
                                    <div>
                                        <small>Check-in</small>
                                        <strong>{item.checked_in_at}</strong>
                                    </div>
                                    <span className="queue-state waiting">Waiting</span>
                                </div>

                                <div className="queue-actions">
                                    <ActionForm
                                        action={`/admin/queue/guests/${item.id}/start`}
                                        className="start"
                                        disabled={!!currentGuest}
                                    >
                                        ▷ Start Session
                                    </ActionForm>
                                    <ActionForm
                                        action={`/admin/queue/guests/${item.id}/move-next`}
                                        className="secondary"
                                        confirmMessage={`Hapus ${item.guest_name} dari antrian?`}
                                    >
                                        ⇥ Move to Next
                                    </ActionForm>
                                </div>
                            </article>
                        ))}
                    </div>

                    <section className="booking-pagination">
                        <span>
                            Showing <strong>{queue.length ? (currentPage - 1) * PAGE_SIZE + 1 : 0}</strong> to{' '}
                            <strong>{Math.min(currentPage * PAGE_SIZE, queue.length)}</strong> of{' '}
                            <strong>{queue.length}</strong> results
                        </span>
                        <div>
                            <button type="button" disabled={currentPage === 1} onClick={() => setPage(currentPage - 1)}>Previous</button>
                            {Array.from({ length: pageCount }, (_, i) => i + 1).map((num) => (
                                <button
                                    key={num}
                                    type="button"
                                    className={num === currentPage ? 'active' : ''}
                                    onClick={() => setPage(num)}
                                >
                                    {num}
                                </button>
                            ))}
                            <button type="button" disabled={currentPage === pageCount} onClick={() => setPage(currentPage + 1)}>Next</button>
                        </div>
                    </section>
                </section>
            </main>

            {showAddGuest && event && (
                <AddGuestModal
                    bookingId={event.id}
                    nextPosition={queue.length + (currentGuest ? 1 : 0) + 1}
                    onClose={() => setShowAddGuest(false)}
                />
            )}
        </div>
    );
}
