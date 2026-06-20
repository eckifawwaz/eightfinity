import React, { useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { csrfToken } from '../../utils/csrf';

const packages = {
    wedding: {
        name: 'Wedding Package',
        description: 'Elegant photo booth coverage for your wedding celebration',
        image: '/image/user-dashboard/package-wedding.png',
        options: [
            { duration: '8 Hours', price: 'Rp 5,800,000' },
        ],
    },
    reservation: {
        name: 'Reservation Package',
        description: 'Flexible photo booth service for private and corporate events',
        image: '/image/user-dashboard/package-reservation.png',
        options: [
            { duration: '4 Hours', price: 'Rp 899,000' },
            { duration: '4+1 Hours', price: 'Rp 899,000' },
        ],
    },
    unlimited: {
        name: 'Unlimited Package',
        description: 'Unlimited fun and photos for school or community events',
        image: '/image/user-dashboard/package-unlimited.png',
        options: [
            { duration: '2 Hours', price: 'Rp 2,000,000' },
            { duration: '3 Hours', price: 'Rp 2,500,000' },
            { duration: '4 Hours', price: 'Rp 3,000,000' },
        ],
    },
};

const timeSlots = [
    '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
    '15:00', '15:30', '16:00', '16:30', '17:00', '17:30',
];

const bookingLocations = [
    'Hotel Mulia Senayan, Ballroom 1',
    'Sudirman Central Business District',
    'Jakarta International School',
    'Customer Venue',
];

const features = [
    'Free design frame',
    'Gif share media',
    'Photo share media',
    'Physical photostrip (Tier B only)',
    'Unlimited Print (Tier B only)',
];

const today = new Date().toISOString().slice(0, 10);

export default function UserBook() {
    const [searchParams] = useSearchParams();
    const initialSlug = packages[searchParams.get('package')] ? searchParams.get('package') : 'wedding';
    const initialOption = Number(searchParams.get('option')) || 0;
    const rescheduleId = searchParams.get('reschedule');
    const isReschedule = Boolean(rescheduleId);
    const [selectedSlug, setSelectedSlug] = useState(initialSlug);
    const [selectedOption, setSelectedOption] = useState(initialOption);
    const [date, setDate] = useState(searchParams.get('date') ?? '');
    const [people, setPeople] = useState(searchParams.get('people') ?? 1);
    const [time, setTime] = useState(searchParams.get('time') ?? '');
    const [customerAddress, setCustomerAddress] = useState(searchParams.get('address') ?? '');
    const [bookingLocation, setBookingLocation] = useState(searchParams.get('location') ?? '');

    const selectedPackage = packages[selectedSlug];
    const option = selectedPackage.options[selectedOption] ?? selectedPackage.options[0];
    const isComplete = Boolean(date && time && people && customerAddress.trim() && bookingLocation);

    const paymentUrl = useMemo(() => {
        const params = new URLSearchParams({
            package: selectedSlug,
            option: String(selectedOption),
            date,
            people: String(people),
            time,
            address: customerAddress,
            location: bookingLocation,
        });

        return `/payment?${params.toString()}`;
    }, [bookingLocation, customerAddress, date, people, selectedOption, selectedSlug, time]);

    function selectPackage(slug) {
        setSelectedSlug(slug);
        setSelectedOption(0);
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
                    <p>From quick snaps to premium sessions, we&apos;ve got you covered</p>
                </div>

                <div className="book-package-tabs">
                    {Object.entries(packages).map(([slug, item]) => (
                        <button
                            className={selectedSlug === slug ? 'selected' : ''}
                            onClick={() => selectPackage(slug)}
                            type="button"
                            key={slug}
                        >
                            {item.name}
                        </button>
                    ))}
                </div>

                <div className="book-duration-grid">
                    {selectedPackage.options.map((item, index) => (
                        <article
                            className={selectedOption === index ? 'selected' : ''}
                            key={item.duration}
                            onClick={() => setSelectedOption(index)}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter' || event.key === ' ') {
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
                                {features.map((feature) => <li key={feature}>✓ {feature}</li>)}
                            </ul>
                        </article>
                    ))}
                </div>
            </section>

            <section className="user-book-section">
                <div className="section-title">
                    <h2>Select Date &amp; Time</h2>
                    <p>Pick your preferred schedule</p>
                </div>

                <div className="book-form-grid">
                    <label className="book-input-card">
                        <span className="book-input-head"><i>▣</i><strong>Choose Date</strong></span>
                        <input
                            value={date}
                            min={today}
                            onChange={(event) => setDate(event.target.value)}
                            type="date"
                        />
                    </label>
                    <label className="book-input-card">
                        <span className="book-input-head"><i>♙</i><strong>Number of Pax</strong></span>
                        <input
                            value={people}
                            onChange={(event) => setPeople(event.target.value)}
                            type="number"
                            min="1"
                        />
                    </label>
                </div>

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
                    <span className="book-input-head"><i>⌖</i><strong>Customer Address</strong></span>
                    <textarea
                        value={customerAddress}
                        onChange={(event) => setCustomerAddress(event.target.value)}
                        placeholder="Enter full address (Street, City, Province, Postal Code)"
                    />
                </label>

                <label className="book-location-card">
                    <span className="book-input-head"><i>▣</i><strong>Booking Location</strong></span>
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

                {isReschedule ? (
                    <form className="reschedule-booking-form" method="POST" action={`/bookings/${rescheduleId}/reschedule`}>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="PATCH" />
                        <input type="hidden" name="package" value={selectedSlug} />
                        <input type="hidden" name="option" value={selectedOption} />
                        <input type="hidden" name="date" value={date} />
                        <input type="hidden" name="people" value={people} />
                        <input type="hidden" name="time" value={time} />
                        <input type="hidden" name="address" value={customerAddress} />
                        <input type="hidden" name="location" value={bookingLocation} />
                        <button className="continue-payment" disabled={!isComplete} type="submit">
                            Save Reschedule
                        </button>
                    </form>
                ) : (
                    <Link
                        aria-disabled={!isComplete}
                        className={`continue-payment ${!isComplete ? 'disabled' : ''}`}
                        onClick={(event) => {
                            if (!isComplete) event.preventDefault();
                        }}
                        to={paymentUrl}
                    >
                        Continue To Payment
                    </Link>
                )}
            </section>

            <footer className="user-book-footer">
                <div><img src="/image/logo-icon.png" alt="" /><strong>EightFinity</strong></div>
                <p>Capture Your Infinite Moments</p>
                <small>© 2026 Eightfinity. All rights reserved.</small>
            </footer>
        </main>
    );
}
