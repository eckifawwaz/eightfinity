import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import SocialLinks from '../../components/SocialLinks';

const bookingGuideSteps = [
    {
        title: '1. Pilih Paket',
        description: 'Lihat detail Wedding, Reservation, atau Unlimited Package, lalu pilih yang paling cocok dengan acaramu.',
    },
    {
        title: '2. Tentukan Durasi & Jadwal',
        description: 'Pilih durasi sesi, tanggal, dan jam booking yang masih tersedia.',
    },
    {
        title: '3. Isi Lokasi Booking',
        description: 'Masukkan alamat lengkap venue dan pilih kota lokasi acara.',
    },
    {
        title: '4. Selesaikan Pembayaran',
        description: 'Bayar via QRIS/e-wallet, lalu tunggu konfirmasi dari admin sebelum hari-H.',
    },
];

const packages = [
    {
        name: 'WEDDING PACKAGE',
        slug: 'wedding',
        image: '/image/user-dashboard/package-wedding-v2.png',
    },
    {
        name: 'RESERVATION PACKAGE',
        slug: 'reservation',
        image: '/image/user-dashboard/package-reservation.png',
    },
    {
        name: 'UNLIMITED PACKAGE',
        slug: 'unlimited',
        image: '/image/user-dashboard/package-unlimited.png',
    },
];

export default function UserHome() {
    const [showGuide, setShowGuide] = useState(false);

    useEffect(() => {
        const seen = window.localStorage.getItem('eightfinity-booking-guide-seen');
        if (!seen) {
            setShowGuide(true);
        }
    }, []);

    function closeGuide() {
        window.localStorage.setItem('eightfinity-booking-guide-seen', '1');
        setShowGuide(false);
    }

    return (
        <main className="customer-dashboard">
            {showGuide && (
                <div className="user-logout-overlay" role="dialog" aria-modal="true">
                    <div className="booking-guide-modal">
                        <header>
                            <h2>Cara Booking di Eightfinity</h2>
                            <button type="button" onClick={closeGuide} aria-label="Tutup panduan">×</button>
                        </header>
                        <ol className="booking-guide-steps">
                            {bookingGuideSteps.map((step) => (
                                <li key={step.title}>
                                    <strong>{step.title}</strong>
                                    <p>{step.description}</p>
                                </li>
                            ))}
                        </ol>
                        <button type="button" className="booking-guide-cta" onClick={closeGuide}>
                            Mengerti, Mulai Booking
                        </button>
                    </div>
                </div>
            )}

            <header className="customer-nav">
                <Link to="/home" className="customer-brand">
                    <img src="/image/logo-icon.png" alt="EightFinity" />
                    <strong>Eight<span>Finity</span></strong>
                </Link>
                <nav>
                    <Link to="/profile" className="customer-start">Profile</Link>
                </nav>
            </header>

            <section className="customer-hero">
                <i className="hero-dot hero-dot-green" />
                <i className="hero-dot hero-dot-yellow" />
                <i className="hero-dot hero-dot-orange" />
                <i className="hero-dot hero-dot-light" />
                <i className="hero-dot hero-dot-blue" />
                <div>
                    <h1>Capture Your <span>Perfect</span><br />Moments</h1>
                    <p>Professional photo booth experience with instant digital delivery. Book your session in seconds!</p>
                    <div className="customer-hero-actions">
                        <Link to="/book">Get Started <b>→</b></Link>
                    </div>
                </div>
                <img src="/image/user-dashboard/hero-photobooth.png" alt="Friends enjoying an EightFinity photo booth" />
            </section>

            <section className="customer-about">
                <h2>About Us</h2>
                <small>Know more about us</small>
                <p>
                    Eightfinity is here to make your photo booth experience fun and hassle free. From booking to your turn to shoot,
                    everything is designed to feel smooth and easy. We want every moment you capture to feel more comfortable,
                    seamless, and enjoyable with no long waits and no schedule mix ups.
                </p>
                <p>Just focus on having fun and capturing the moment, let Eightfinity handle the rest.</p>
            </section>

            <section className="customer-quote">
                <div className="customer-photographer left">
                    <img src="/image/user-dashboard/hero-photobooth.png" alt="" />
                </div>
                <blockquote>
                    “Capture the moment and enjoy every second of it.<br />
                    Let the memories stay with you long after the camera stops”
                </blockquote>
                <div className="customer-photographer right">
                    <img src="/image/user-dashboard/hero-photobooth.png" alt="" />
                </div>
                <i className="quote-dot blue" />
                <i className="quote-dot orange" />
                <i className="quote-dot green" />
                <i className="quote-dot red" />
            </section>

            <section className="customer-experience">
                <h2>Choose Your Experience</h2>
                <p>From quick snaps to premium sessions, we&apos;ve got you covered</p>
                <div>
                    {packages.map((item) => (
                        <article key={item.name}>
                            <img src={item.image} alt={item.name} />
                            <section>
                                <h3>{item.name}</h3>
                                <Link to={`/packages/${item.slug}`}>View Details</Link>
                            </section>
                        </article>
                    ))}
                </div>
            </section>

            <footer className="customer-footer">
                <div className="customer-brand">
                    <img src="/image/logo-icon-transparent.png" alt="" />
                    <strong>EightFinity</strong>
                </div>
                <p>Capture Your Infinite Moments</p>
                <SocialLinks className="customer-socials" />
                <small>© 2026 Eightfinity. All rights reserved.</small>
            </footer>
        </main>
    );
}
