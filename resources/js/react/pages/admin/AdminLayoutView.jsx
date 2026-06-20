import React, { useEffect, useMemo, useState } from 'react';
import { csrfToken } from '../../utils/csrf';

const flowSteps = [
    ['1', 'Check-In', 'Verify booking'],
    ['2', 'Wait', 'Queue area'],
    ['3', 'Enter Booth', 'Start session'],
    ['4', 'Take Photos', 'Capture moments'],
    ['5', 'Print Photos', 'Get prints'],
    ['6', 'Finished', 'Exit & enjoy'],
];

const layoutOptions = ['Wedding Setup', 'Corporate Event', 'Birthday Party', 'Graduation Setup', 'Custom Client Setup'];
const boothSizes = ['3 x 3 meter', '4 x 4 meter', '5 x 5 meter'];
const defaultPositions = {
    camera: { x: 58, y: 28 },
    props: { x: 60, y: 55 },
    printer: { x: 18, y: 66 },
};

function normalizePositions(positions) {
    return {
        camera: { ...defaultPositions.camera, ...(positions?.camera ?? {}) },
        props: { ...defaultPositions.props, ...(positions?.props ?? {}) },
        printer: { ...defaultPositions.printer, ...(positions?.printer ?? {}) },
    };
}

function DraggableLayoutItem({ children, id, onMove, position, variant = '' }) {
    function startDrag(event) {
        event.preventDefault();
        const canvas = event.currentTarget.closest('.booth-canvas');
        if (!canvas) return;

        const rect = canvas.getBoundingClientRect();
        const targetRect = event.currentTarget.getBoundingClientRect();
        const offsetX = event.clientX - targetRect.left;
        const offsetY = event.clientY - targetRect.top;
        event.currentTarget.setPointerCapture(event.pointerId);

        function moveItem(nextEvent) {
            const rawX = ((nextEvent.clientX - rect.left - offsetX) / rect.width) * 100;
            const rawY = ((nextEvent.clientY - rect.top - offsetY) / rect.height) * 100;
            onMove(id, {
                x: Math.max(0, Math.min(88, Math.round(rawX))),
                y: Math.max(0, Math.min(84, Math.round(rawY))),
            });
        }

        function stopDrag() {
            window.removeEventListener('pointermove', moveItem);
            window.removeEventListener('pointerup', stopDrag);
        }

        window.addEventListener('pointermove', moveItem);
        window.addEventListener('pointerup', stopDrag);
    }

    return (
        <button
            className={`layout-drag-item ${variant}`}
            onPointerDown={startDrag}
            style={{ left: `${position.x}%`, top: `${position.y}%` }}
            type="button"
        >
            {children}
        </button>
    );
}

export default function AdminLayoutView() {
    const layoutData = window.__ADMIN_LAYOUT__ ?? {};
    const bookings = layoutData.bookings ?? [];
    const initialBookingId = String(layoutData.selected_booking_id ?? bookings[0]?.id ?? '');
    const [selectedBookingId, setSelectedBookingId] = useState(initialBookingId);
    const selectedBooking = useMemo(
        () => bookings.find((booking) => String(booking.id) === selectedBookingId) ?? bookings[0],
        [bookings, selectedBookingId],
    );
    const [layoutName, setLayoutName] = useState(selectedBooking?.layout_name ?? 'Wedding Setup');
    const [boothSize, setBoothSize] = useState(selectedBooking?.booth_size ?? '3 x 3 meter');
    const [printerPosition, setPrinterPosition] = useState(selectedBooking?.printer_position ?? 'inside');
    const [entranceDirection, setEntranceDirection] = useState(selectedBooking?.entrance_direction ?? 'left');
    const [positions, setPositions] = useState(normalizePositions(selectedBooking?.layout_positions));

    useEffect(() => {
        if (!selectedBooking) return;

        setLayoutName(selectedBooking.layout_name ?? 'Wedding Setup');
        setBoothSize(selectedBooking.booth_size ?? '3 x 3 meter');
        setPrinterPosition(selectedBooking.printer_position ?? 'inside');
        setEntranceDirection(selectedBooking.entrance_direction ?? 'left');
        setPositions(normalizePositions(selectedBooking.layout_positions));
    }, [selectedBooking]);

    function moveLayoutItem(id, position) {
        setPositions((current) => ({
            ...current,
            [id]: position,
        }));
    }

    function resetPositions() {
        setPositions(normalizePositions());
    }

    const roomClass = [
        'booth-room',
        `layout-${layoutName.toLowerCase().replaceAll(' ', '-')}`,
        `booth-${boothSize.charAt(0)}`,
        printerPosition === 'outside' ? 'printer-outside' : '',
    ].filter(Boolean).join(' ');

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
                        <a href="/admin/layout" className="active"><span>◇</span>2D Layout View</a>
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

            <main className="admin-content admin-layout-content">
                <section className="admin-overview-card">
                    <h1>Preview Layout Booth</h1>
                    <p>Design booth setup per customer booking</p>
                </section>

                <section className="layout-main-grid">
                    <article className="layout-preview-card">
                        <div className="layout-card-title">
                            <div>
                                <h2>Booth Layout Preview</h2>
                                <p>{selectedBooking ? `${selectedBooking.customer_name} • #${selectedBooking.booking_code}` : 'No active booking selected'}</p>
                            </div>
                            {selectedBooking && <a href="/admin/queue">Back to Queue</a>}
                        </div>

                        <div className={`booth-canvas entrance-${entranceDirection}`}>
                            <div className="waiting-area">
                                <strong>Waiting Area</strong>
                                <div>
                                    <span>1</span>
                                    <span>2</span>
                                </div>
                            </div>

                            <div className={roomClass}>
                                <header>{layoutName}</header>
                            </div>

                            <DraggableLayoutItem id="camera" onMove={moveLayoutItem} position={positions.camera} variant="camera-drag">
                                ▣<small>Camera</small>
                            </DraggableLayoutItem>
                            <DraggableLayoutItem id="props" onMove={moveLayoutItem} position={positions.props} variant="props-drag">
                                ▦<small>Props</small>
                            </DraggableLayoutItem>
                            <DraggableLayoutItem id="printer" onMove={moveLayoutItem} position={positions.printer} variant="printer-drag">
                                ▤<small>Printer</small>
                            </DraggableLayoutItem>

                            <div className="exit-marker">➜<small>Exit</small></div>
                            <div className="enter-marker">{entranceDirection === 'left' ? '↑' : '↱'}<small>Enter</small></div>
                        </div>
                    </article>

                    <article className="booth-config-card">
                        <h2>Booth Configuration</h2>
                        {bookings.length ? (
                            <form method="POST" action={`/admin/layout/${selectedBooking?.id}`}>
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input type="hidden" name="_method" value="PATCH" />
                                <input type="hidden" name="layout_positions" value={JSON.stringify(positions)} />

                                <label>
                                    Customer Booking:
                                    <select value={selectedBookingId} onChange={(event) => setSelectedBookingId(event.target.value)}>
                                        {bookings.map((booking) => (
                                            <option key={booking.id} value={booking.id}>
                                                {booking.customer_name} - #{booking.booking_code}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <label>
                                    Select Layout:
                                    <select name="layout_name" value={layoutName} onChange={(event) => setLayoutName(event.target.value)}>
                                        {layoutOptions.map((option) => <option key={option}>{option}</option>)}
                                    </select>
                                </label>
                                <label>
                                    Booth Size:
                                    <select name="booth_size" value={boothSize} onChange={(event) => setBoothSize(event.target.value)}>
                                        {boothSizes.map((option) => <option key={option}>{option}</option>)}
                                    </select>
                                </label>
                                <div>
                                    <strong>Printer Position:</strong>
                                    <input type="hidden" name="printer_position" value={printerPosition} />
                                    <div className="config-toggle">
                                        <button type="button" className={printerPosition === 'inside' ? 'active' : ''} onClick={() => setPrinterPosition('inside')}>Inside</button>
                                        <button type="button" className={printerPosition === 'outside' ? 'active' : ''} onClick={() => setPrinterPosition('outside')}>Outside</button>
                                    </div>
                                </div>
                                <div>
                                    <strong>Entrance Direction:</strong>
                                    <input type="hidden" name="entrance_direction" value={entranceDirection} />
                                    <div className="config-toggle">
                                        <button type="button" className={entranceDirection === 'left' ? 'active' : ''} onClick={() => setEntranceDirection('left')}>Left</button>
                                        <button type="button" className={entranceDirection === 'right' ? 'active' : ''} onClick={() => setEntranceDirection('right')}>Right</button>
                                    </div>
                                </div>
                                <button type="button" className="reset-layout" onClick={resetPositions}>
                                    Reset Item Positions
                                </button>
                                <button type="submit" className="confirm-setup">Confirm Setup</button>
                            </form>
                        ) : (
                            <div className="layout-empty-state">
                                <strong>No queue bookings</strong>
                                <p>Pending or confirmed bookings will appear here.</p>
                                <a href="/admin/bookings">Open Manage Bookings</a>
                            </div>
                        )}
                    </article>
                </section>

                <section className="experience-flow-card">
                    <h2>Experience Flow</h2>
                    <p>Your photo booth journey steps</p>
                    <div className="flow-steps">
                        {flowSteps.map(([number, title, subtitle]) => (
                            <article key={number} className="flow-step">
                                <span className={`flow-number step-${number}`}>{number}</span>
                                <strong>{title}</strong>
                                <small>{subtitle}</small>
                            </article>
                        ))}
                    </div>
                </section>
            </main>
        </div>
    );
}
