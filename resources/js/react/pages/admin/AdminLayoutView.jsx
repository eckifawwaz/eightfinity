import React, { useEffect, useMemo, useState } from 'react';
import { csrfToken } from '../../utils/csrf';
import { flowSteps } from '../../utils/experienceFlow';

const layoutOptions = ['Wedding Setup', 'Corporate Event', 'Birthday Party', 'Graduation Setup', 'Custom Client Setup'];
const boothSizes = ['3 x 3 meter', '4 x 4 meter', '5 x 5 meter'];
const defaultPositions = {
    camera: { x: 55, y: 27 },
    props: { x: 70, y: 55 },
    printer: { x: 48, y: 55 },
    exit: { x: 83, y: 14 },
    enter: { x: 45, y: 82 },
};
const outsidePrinterPosition = { x: 11, y: 69 };

function normalizePositions(positions, printerPosition = 'inside') {
    return {
        camera: { ...defaultPositions.camera, ...(positions?.camera ?? {}) },
        props: { ...defaultPositions.props, ...(positions?.props ?? {}) },
        printer: {
            ...(printerPosition === 'outside' ? outsidePrinterPosition : defaultPositions.printer),
            ...(positions?.printer ?? {}),
        },
        exit: { ...defaultPositions.exit, ...(positions?.exit ?? {}) },
        enter: { ...defaultPositions.enter, ...(positions?.enter ?? {}) },
    };
}

function LayoutIcon({ name, className = 'layout-icon' }) {
    const paths = {
        booth: (
            <>
                <path d="M5 8.5 12 5l7 3.5v8L12 20l-7-3.5z" />
                <path d="M5 8.5 12 12l7-3.5" />
                <path d="M12 12v8" />
            </>
        ),
        camera: (
            <>
                <path d="M7.5 8.5h2L11 6.5h2l1.5 2h2a2 2 0 0 1 2 2v5.5a2 2 0 0 1-2 2h-9a2 2 0 0 1-2-2v-5.5a2 2 0 0 1 2-2z" />
                <circle cx="12" cy="13.2" r="3" />
            </>
        ),
        checkin: (
            <>
                <path d="M7.5 4.8h9a1.8 1.8 0 0 1 1.8 1.8v10.8a1.8 1.8 0 0 1-1.8 1.8h-9a1.8 1.8 0 0 1-1.8-1.8V6.6a1.8 1.8 0 0 1 1.8-1.8z" />
                <path d="M9 8h2v2H9zM13 8h2v2h-2zM9 12h2v2H9zM13 12h2v2h-2z" />
            </>
        ),
        enter: (
            <>
                <path d="M12 19V6" />
                <path d="m7.5 10.5 4.5-4.5 4.5 4.5" />
            </>
        ),
        exit: (
            <>
                <path d="M5 12h12" />
                <path d="m13 7 5 5-5 5" />
            </>
        ),
        finished: (
            <>
                <circle cx="9" cy="8" r="2.5" />
                <circle cx="15" cy="8" r="2.5" />
                <path d="M4.5 18c.6-3.1 2.2-4.7 4.5-4.7 1.3 0 2.3.4 3 1.2.7-.8 1.7-1.2 3-1.2 2.3 0 3.9 1.6 4.5 4.7" />
            </>
        ),
        printer: (
            <>
                <path d="M8 8V5.5h8V8" />
                <path d="M7 16H5.8a1.8 1.8 0 0 1-1.8-1.8v-3.4A1.8 1.8 0 0 1 5.8 9h12.4a1.8 1.8 0 0 1 1.8 1.8v3.4a1.8 1.8 0 0 1-1.8 1.8H17" />
                <path d="M7 13.5h10v5H7z" />
            </>
        ),
        props: (
            <>
                <path d="M7 5h10v14H7z" />
                <path d="M10 5v14M14 5v14M7 9h10M7 13h10" />
            </>
        ),
        user: (
            <>
                <circle cx="12" cy="9" r="3" />
                <path d="M6.5 18.5c.8-3.1 2.6-4.7 5.5-4.7s4.7 1.6 5.5 4.7" />
            </>
        ),
        wait: (
            <>
                <circle cx="12" cy="12" r="7.5" />
                <path d="M12 7.8V12l3 2" />
            </>
        ),
    };

    return (
        <svg aria-hidden="true" className={className} fill="none" viewBox="0 0 24 24">
            <g stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8">
                {paths[name]}
            </g>
        </svg>
    );
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
    const initialPrinterPosition = selectedBooking?.printer_position ?? 'inside';
    const [layoutName, setLayoutName] = useState(selectedBooking?.layout_name ?? 'Wedding Setup');
    const [boothSize, setBoothSize] = useState(selectedBooking?.booth_size ?? '3 x 3 meter');
    const [printerPosition, setPrinterPosition] = useState(initialPrinterPosition);
    const [positions, setPositions] = useState(
        normalizePositions(selectedBooking?.layout_positions, initialPrinterPosition),
    );

    useEffect(() => {
        if (!selectedBooking) return;

        const nextPrinterPosition = selectedBooking.printer_position ?? 'inside';

        setLayoutName(selectedBooking.layout_name ?? 'Wedding Setup');
        setBoothSize(selectedBooking.booth_size ?? '3 x 3 meter');
        setPrinterPosition(nextPrinterPosition);
        setPositions(normalizePositions(selectedBooking.layout_positions, nextPrinterPosition));
    }, [selectedBooking]);

    function moveLayoutItem(id, position) {
        setPositions((current) => ({
            ...current,
            [id]: position,
        }));
    }

    function choosePrinterPosition(nextPrinterPosition) {
        setPrinterPosition(nextPrinterPosition);
        setPositions((current) => ({
            ...current,
            printer: nextPrinterPosition === 'outside' ? outsidePrinterPosition : defaultPositions.printer,
        }));
    }

    function submitLayout(event) {
        if (!selectedBooking) {
            event.preventDefault();
        }
    }

    const roomClass = [
        'booth-room',
        `layout-${layoutName.toLowerCase().replaceAll(' ', '-')}`,
        `booth-${boothSize.charAt(0)}`,
        printerPosition === 'outside' ? 'printer-outside' : '',
    ].filter(Boolean).join(' ');

    return (
        <div className="admin-dashboard-page admin-layout-page">
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

            <main className="admin-content admin-layout-content">
                <section className="admin-overview-card">
                    <h1>Preview Layout Booth</h1>
                    <p>Simulate your photo booth setup and flow</p>
                </section>

                <section className="layout-main-grid">
                    <article className="layout-preview-card">
                        <div className="layout-card-title">
                            <h2>Booth Layout Preview</h2>
                        </div>

                        <div className="booth-canvas">
                            <div className="waiting-area">
                                <strong>Waiting Area</strong>
                                <div>
                                    <span><LayoutIcon name="user" /></span>
                                    <span><LayoutIcon name="user" /></span>
                                </div>
                            </div>

                            <div className={roomClass}>
                                <header>Backdrop</header>
                            </div>

                            <DraggableLayoutItem id="camera" onMove={moveLayoutItem} position={positions.camera} variant="camera-drag">
                                <LayoutIcon name="camera" className="drag-icon" />
                                <small>Camera</small>
                            </DraggableLayoutItem>
                            <DraggableLayoutItem id="props" onMove={moveLayoutItem} position={positions.props} variant="props-drag">
                                <LayoutIcon name="props" className="drag-icon" />
                                <small>Props</small>
                            </DraggableLayoutItem>
                            <DraggableLayoutItem id="printer" onMove={moveLayoutItem} position={positions.printer} variant="printer-drag">
                                <LayoutIcon name="printer" className="drag-icon" />
                                <small>Printer</small>
                            </DraggableLayoutItem>

                            <DraggableLayoutItem id="exit" onMove={moveLayoutItem} position={positions.exit} variant="marker-drag exit-drag">
                                <span className="marker-circle"><LayoutIcon name="exit" /></span>
                                <small>Exit</small>
                            </DraggableLayoutItem>
                            <DraggableLayoutItem id="enter" onMove={moveLayoutItem} position={positions.enter} variant="marker-drag enter-drag">
                                <span className="marker-circle"><LayoutIcon name="enter" /></span>
                                <small>Enter</small>
                            </DraggableLayoutItem>
                        </div>
                    </article>

                    <article className="booth-config-card">
                        <h2>Booth Configuration</h2>
                        <form method="POST" action={selectedBooking ? `/admin/layout/${selectedBooking.id}` : '/admin/layout'} onSubmit={submitLayout}>
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="_method" value="PATCH" />
                            <input type="hidden" name="layout_positions" value={JSON.stringify(positions)} />

                            {bookings.length > 1 && (
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
                            )}
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
                                    <button type="button" className={printerPosition === 'inside' ? 'active' : ''} onClick={() => choosePrinterPosition('inside')}>Inside</button>
                                    <button type="button" className={printerPosition === 'outside' ? 'active' : ''} onClick={() => choosePrinterPosition('outside')}>Outside</button>
                                </div>
                            </div>
                            <button type="submit" className="confirm-setup" disabled={!selectedBooking}>Confirm Setup</button>
                        </form>
                        {!selectedBooking && (
                            <p className="layout-config-note">Queue bookings are required before this setup can be saved.</p>
                        )}
                    </article>
                </section>

                <section className="experience-flow-card">
                    <h2>Experience Flow</h2>
                    <p>Your photo booth journey steps</p>
                    <div className="flow-steps">
                        {flowSteps.map(([number, image, title, subtitle]) => (
                            <article key={number} className="flow-step">
                                <span className={`flow-number step-${number}`}>{number}</span>
                                <img className="flow-step-image" src={image} alt="" />
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
