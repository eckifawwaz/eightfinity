import React, { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { csrfToken } from '../../utils/csrf';
import { consumeFormErrors } from '../../utils/pageState';

const weddingFeatures = [
    'Unlimited photo session',
    'Unlimited print',
    'DSLR Camera',
    'Professional lighting',
    '6 Booth Operators',
    'Custom photo frame',
    'Basic props',
    'Softcopy via QR Code/AirDrop',
    'Setup & dismantle',
];

const reservationFeatures = [
    'DSLR Camera',
    'Professional lighting',
    'Custom photo frame',
    'Basic props',
    'Softcopy via QR Code/AirDrop',
    'Setup & dismantle',
];

const unlimitedFeatures = [
    'Unlimited photo session',
    'Unlimited print',
    'DSLR Camera',
    'Professional lighting',
    '2 Booth Operators',
    'Custom photo frame',
    'Basic props',
    'Softcopy via QR Code/AirDrop',
    'Setup & dismantle',
];

const packages = {
    wedding: {
        name: 'Wedding Package',
        description: 'Elegant photo booth coverage for your wedding celebration',
        image: '/image/user-dashboard/package-wedding-v2.png',
        features: weddingFeatures,
        options: [
            { duration: '4 Hours', price: 'Rp 3,000,000' },
            { duration: '6 Hours', price: 'Rp 4,500,000' },
            { duration: '8 Hours', price: 'Rp 5,800,000' },
        ],
    },
    reservation: {
        name: 'Reservation Package',
        description: 'Flexible photo booth service for private and corporate events',
        image: '/image/user-dashboard/package-reservation.png',
        features: reservationFeatures,
        options: [
            { duration: '3 Hours', price: 'Rp 799,000' },
            { duration: '4 Hours', price: 'Rp 899,000' },
            { duration: '4+1 Hours', price: 'Rp 999,000' },
        ],
    },
    unlimited: {
        name: 'Unlimited Package',
        description: 'Unlimited fun and photos for school or community events',
        image: '/image/user-dashboard/package-unlimited.png',
        features: unlimitedFeatures,
        options: [
            { duration: '2 Hours', price: 'Rp 2,000,000' },
            { duration: '3 Hours', price: 'Rp 2,500,000' },
            { duration: '4 Hours', price: 'Rp 3,000,000' },
        ],
    },
};

const timeSlots = [
    '10:00', '10:30', '11:00', '11:30',
    '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
    '15:00', '15:30', '16:00', '16:30', '17:00', '17:30',
    '18:00', '18:30', '19:00', '19:30', '20:00', '20:30',
    '21:00', '21:30',
];

const bookingLocations = [
    'Jakarta',
    'Bekasi',
];

const roomSizes = ['3 x 3 meter', '4 x 4 meter', '5 x 5 meter'];

function computeMinBookingDate() {
    const date = new Date();
    date.setDate(date.getDate() + 1);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

const minBookingDate = computeMinBookingDate();

export default function UserBook() {
    const [searchParams] = useSearchParams();
    const [formErrors] = useState(consumeFormErrors);
    const bookingAvailability = window.__BOOKING_AVAILABILITY__ ?? {};
    const [unavailableDates, setUnavailableDates] = useState(() => bookingAvailability.unavailable_dates ?? []);
    const [checkingAvailability, setCheckingAvailability] = useState(false);
    const [availabilityNotice, setAvailabilityNotice] = useState('');
    const initialSlug = packages[searchParams.get('package')] ? searchParams.get('package') : 'wedding';
    const initialOption = Number(searchParams.get('option')) || 0;
    const rescheduleId = searchParams.get('reschedule');
    const isReschedule = Boolean(rescheduleId);
    const [selectedSlug, setSelectedSlug] = useState(initialSlug);
    const [selectedOption, setSelectedOption] = useState(initialOption);
    const [date, setDate] = useState(searchParams.get('date') ?? '');
    const [boothSize, setBoothSize] = useState(searchParams.get('booth_size') ?? roomSizes[0]);
    const [time, setTime] = useState(searchParams.get('time') ?? '');
    const [customerAddress, setCustomerAddress] = useState(searchParams.get('address') ?? '');
    const [bookingLocation, setBookingLocation] = useState(searchParams.get('location') ?? '');

    const selectedPackage = packages[selectedSlug];
    const option = selectedPackage.options[selectedOption] ?? selectedPackage.options[0];
    const isDateUnavailable = date ? unavailableDates.includes(date) : false;
    const isComplete = Boolean(date && time && boothSize && customerAddress.trim() && bookingLocation && !isDateUnavailable);

    const paymentUrl = useMemo(() => {
        const params = new URLSearchParams({
            package: selectedSlug,
            option: String(selectedOption),
            date,
            booth_size: boothSize,
            time,
            address: customerAddress,
            location: bookingLocation,
        });

        return `/payment?${params.toString()}`;
    }, [bookingLocation, boothSize, customerAddress, date, selectedOption, selectedSlug, time]);

    function selectPackage(slug) {
        setSelectedSlug(slug);
        setSelectedOption(0);
    }

    useEffect(() => {
        let active = true;
        const query = new URLSearchParams();
        if (rescheduleId) query.set('reschedule', rescheduleId);

        fetch(`/book/availability?${query.toString()}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response) => response.ok ? response.json() : null)
            .then((data) => {
                if (active && data?.unavailable_dates) setUnavailableDates(data.unavailable_dates);
            })
            .catch(() => {});

        return () => { active = false; };
    }, [rescheduleId]);

    async function verifySelectedDate(selectedDate = date) {
        if (!selectedDate) return false;
        const query = new URLSearchParams({ date: selectedDate });
        if (rescheduleId) query.set('reschedule', rescheduleId);

        try {
            const response = await fetch(`/book/availability?${query.toString()}`, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) return !unavailableDates.includes(selectedDate);
            const data = await response.json();
            if (Array.isArray(data.unavailable_dates)) setUnavailableDates(data.unavailable_dates);
            return data.available !== false;
        } catch {
            return !unavailableDates.includes(selectedDate);
        }
    }

    async function continueToPayment() {
        if (!isComplete || checkingAvailability) return;
        setCheckingAvailability(true);
        setAvailabilityNotice('');
        const available = await verifySelectedDate();
        setCheckingAvailability(false);

        if (!available) {
            setAvailabilityNotice('Tanggal tersebut sudah penuh. Silakan pilih tanggal lain.');
            return;
        }

        // Full navigation intentionally resets one-time Laravel validation state.
        window.location.assign(paymentUrl);
    }

    return (
        <main className="user-book-page">
            <header className="user-book-nav">
                <Link to="/home" className="user-book-brand">
                    <img src="/image/logo-icon.png" alt="EightFinity" />
                    <strong>Eight<span>Finity</span></strong>
                </Link>
                <Link to="/profile" className="user-book-profile">Profile</Link>
            </header>

            <section className="user-book-hero">
                <div>
                    <h1>Book Your <span>Session</span></h1>
                    <p>{isReschedule ? 'Update your booking schedule and details' : 'Choose your perfect package and reserve your time slot'}</p>
                </div>
                <i className="book-dot dot-a" /><i className="book-dot dot-b" />
                <i className="book-dot dot-c" /><i className="book-dot dot-d" /><i className="book-dot dot-e" />
            </section>

            <section className="user-book-section">
                <div className="section-title">
                    <h2>Choose Your Experience</h2>
                    <p>
                        {isReschedule
                            ? 'Paket & durasi tidak bisa diubah saat reschedule — hanya jadwal dan lokasi yang bisa diperbarui.'
                            : "From quick snaps to premium sessions, we've got you covered"}
                    </p>
                </div>

                <div className={`book-package-tabs ${isReschedule ? 'locked' : ''}`}>
                    {Object.entries(packages).map(([slug, item]) => (
                        <button
                            className={selectedSlug === slug ? 'selected' : ''}
                            disabled={isReschedule}
                            onClick={() => selectPackage(slug)}
                            type="button"
                            key={slug}
                        >
                            {item.name}
                        </button>
                    ))}
                </div>

                <div className={`book-duration-grid ${isReschedule ? 'locked' : ''}`}>
                    {selectedPackage.options.map((item, index) => (
                        <article
                            aria-disabled={isReschedule}
                            className={selectedOption === index ? 'selected' : ''}
                            key={item.duration}
                            onClick={() => !isReschedule && setSelectedOption(index)}
                            onKeyDown={(event) => {
                                if (!isReschedule && (event.key === 'Enter' || event.key === ' ')) {
                                    event.preventDefault();
                                    setSelectedOption(index);
                                }
                            }}
                            role="button"
                            tabIndex={0}
                        >
                            {index === selectedPackage.options.length - 1 && selectedPackage.options.length > 1 && (
                                <em>Most Popular</em>
                            )}
                            <header>
                                <h3>Duration {item.duration}</h3>
                                <small>Unlimited Service</small>
                            </header>
                            <button
                                disabled={isReschedule}
                                type="button"
                                onClick={(event) => {
                                    event.stopPropagation();
                                    setSelectedOption(index);
                                }}
                            >
                                <span>Tier A/B/C Normal</span>
                                <strong>{item.price}</strong>
                                <i>{selectedOption === index ? '◉' : '○'}</i>
                            </button>
                            <ul>
                                {selectedPackage.features.map((feature) => <li key={feature}>✓ {feature}</li>)}
                            </ul>
                        </article>
                    ))}
                </div>
            </section>

            <section className="user-book-section">
                <div className="section-title">
                    <h2>Select Date &amp; Time</h2>
                    <p>Pick your preferred schedule &mdash; bookings need at least 1 day of preparation, so the earliest date is tomorrow</p>
                </div>

                <div className="book-form-grid">
                    <label className="book-input-card">
                        <span className="book-input-head"><i>▣</i><strong>Choose Date</strong></span>
                        <input
                            aria-invalid={isDateUnavailable}
                            value={date}
                            min={minBookingDate}
                            onChange={(event) => {
                                const nextDate = event.target.value;
                                setDate(nextDate);
                                setAvailabilityNotice('');
                                if (nextDate) verifySelectedDate(nextDate);
                            }}
                            type="date"
                        />
                    </label>
                    <label className="book-input-card">
                        <span className="book-input-head"><i>◇</i><strong>Room Size</strong></span>
                        <select value={boothSize} onChange={(event) => setBoothSize(event.target.value)}>
                            {roomSizes.map((size) => <option key={size} value={size}>{size}</option>)}
                        </select>
                    </label>
                </div>

                {(isDateUnavailable || availabilityNotice) && (
                    <section className="booking-date-warning">
                        <strong>Tanggal tidak tersedia</strong>
                        <p>{availabilityNotice || 'Tanggal tersebut sudah penuh. Silakan pilih tanggal lain untuk melanjutkan.'}</p>
                    </section>
                )}

                <div className="book-time-card">
                    <div className="book-input-head"><i>◷</i><strong>Choose Time Slot</strong></div>
                    <div className="time-slot-grid">
                        {timeSlots.map((slot) => (
                            <button
                                className={time === slot ? 'selected' : ''}
                                onClick={() => setTime(slot)}
                                type="button"
                                key={slot}
                            >
                                {slot}
                            </button>
                        ))}
                    </div>
                </div>

            </section>

            <section className="user-book-section">
                <div className="section-title">
                    <h2>Booking Location</h2>
                    <p>Pick your preferred venue details</p>
                </div>

                <label className="book-location-card">
                    <span className="book-input-head">
                        <i>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="7" />
                                <path d="M12 2v4M12 18v4M2 12h4M18 12h4" />
                            </svg>
                        </i>
                        <strong>Booking Address</strong>
                    </span>
                    <textarea
                        value={customerAddress}
                        onChange={(event) => setCustomerAddress(event.target.value)}
                        placeholder="Enter full address (Street, City, Province, Postal Code)"
                    />
                </label>

                <label className="book-location-card">
                    <span className="book-input-head">
                        <i>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12Z" />
                                <circle cx="12" cy="9" r="2.5" />
                            </svg>
                        </i>
                        <strong>Booking Location</strong>
                    </span>
                    <select value={bookingLocation} onChange={(event) => setBookingLocation(event.target.value)}>
                        <option value="">Select Location</option>
                        {bookingLocations.map((location) => (
                            <option value={location} key={location}>{location}</option>
                        ))}
                    </select>
                </label>

                <div className="booking-selection-summary">
                    <span><small>Selected Package</small><strong>{selectedPackage.name}</strong></span>
                    <span><small>Duration</small><strong>{option.duration}</strong></span>
                    <span><small>Total</small><strong>{option.price}</strong></span>
                </div>

                {formErrors.length > 0 && (
                    <section className="payment-error-card">
                        <strong>Booking schedule could not be saved</strong>
                        {formErrors.map((error) => <p key={error}>{error}</p>)}
                    </section>
                )}

                {isReschedule ? (
                    <form className="reschedule-booking-form" method="POST" action={`/bookings/${rescheduleId}/reschedule`}>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="PATCH" />
                        <input type="hidden" name="date" value={date} />
                        <input type="hidden" name="booth_size" value={boothSize} />
                        <input type="hidden" name="time" value={time} />
                        <input type="hidden" name="address" value={customerAddress} />
                        <input type="hidden" name="location" value={bookingLocation} />
                        <button className="continue-payment" disabled={!isComplete} type="submit">
                            Save Reschedule
                        </button>
                    </form>
                ) : (
                    <button
                        aria-disabled={!isComplete || checkingAvailability}
                        className={`continue-payment ${!isComplete || checkingAvailability ? 'disabled' : ''}`}
                        disabled={!isComplete || checkingAvailability}
                        onClick={continueToPayment}
                        type="button"
                    >
                        {checkingAvailability ? 'Checking Date...' : 'Continue To Payment'}
                    </button>
                )}
            </section>

            <footer className="user-book-footer">
                <div><img src="/image/logo-icon-transparent.png" alt="" /><strong>EightFinity</strong></div>
                <p>Capture Your Infinite Moments</p>
                <small>© 2026 Eightfinity. All rights reserved.</small>
            </footer>
        </main>
    );
}
