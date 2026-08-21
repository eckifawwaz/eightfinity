import React, { useState } from 'react';
import { Link, Navigate, useNavigate, useParams } from 'react-router-dom';
import SocialLinks from '../../components/SocialLinks';

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

const packageData = {
    wedding: {
        name: 'Wedding Package',
        breadcrumb: 'Wedding Package',
        image: '/image/user-dashboard/package-wedding-v2.png',
        description: [
            'Make your wedding day even more unforgettable with our exclusive Wedding Package.',
            'This package is specially designed to capture the most precious moments of your life, offering a premium photobooth experience that is elegant, modern, and memorable.',
        ],
        features: weddingFeatures,
        options: [
            { duration: 'Duration 4 Hours', price: 'Rp 3,000,000', badge: '' },
            { duration: 'Duration 6 Hours', price: 'Rp 4,500,000', badge: '' },
            { duration: 'Duration 8 Hours', price: 'Rp 5,800,000', badge: '' },
        ],
    },
    reservation: {
        name: 'Reservation Package',
        breadcrumb: 'Event Package',
        image: '/image/user-dashboard/package-reservation.png',
        description: [
            'Bring a professional photobooth experience to every special moment.',
            'Our Reservation Package is designed for corporate events, weddings, birthday parties, gatherings, and private celebrations. Guests can enjoy a smooth, fun, and unlimited photo experience, capturing every laugh, pose, and memory without worrying about photo limits.',
        ],
        features: reservationFeatures,
        options: [
            { duration: 'Duration 3 Hours', price: 'Rp 799,000', badge: '' },
            { duration: 'Duration 4 Hours', price: 'Rp 899,000', badge: '' },
            { duration: 'Duration 4+1 Hours', price: 'Rp 999,000', badge: 'Most Popular' },
        ],
    },
    unlimited: {
        name: 'Unlimited Package',
        breadcrumb: 'School Package',
        image: '/image/user-dashboard/package-unlimited.png',
        description: [
            'Enjoy a limitless photobooth experience with EightFinity’s Unlimited Package.',
            'There are no restrictions on the number of photos and no limits on fun. Every moment can be captured as many times as you want.',
        ],
        features: unlimitedFeatures,
        options: [
            { duration: 'Duration 2 Hours', price: 'Rp 2,000,000', badge: '' },
            { duration: 'Duration 3 Hours', price: 'Rp 2,500,000', badge: '' },
            { duration: 'Duration 4 Hours', price: 'Rp 3,000,000', badge: 'Most Popular' },
        ],
    },
};

export default function UserPackageDetail() {
    const { slug } = useParams();
    const navigate = useNavigate();
    const detail = packageData[slug];
    const [selectedOption, setSelectedOption] = useState(0);

    if (!detail) {
        return <Navigate to="/home" replace />;
    }

    function choosePackage(optionIndex) {
        setSelectedOption(optionIndex);
        navigate(`/book?package=${slug}&option=${optionIndex}`);
    }

    return (
        <main className="package-detail-page">
            <header className="package-detail-nav">
                <Link to="/home" className="customer-brand">
                    <img src="/image/logo-icon.png" alt="EightFinity" />
                    <strong>Eight<span>Finity</span></strong>
                </Link>
                <nav>
                    <Link to="/profile" className="package-start">Profile</Link>
                </nav>
            </header>

            <div className="package-breadcrumb">
                <Link to="/home">Home</Link><span>/</span><span>Packages</span><span>/</span><strong>{detail.breadcrumb}</strong>
            </div>

            <section className="package-detail-hero">
                <img src={detail.image} alt={detail.name} />
                <div className="package-detail-overlay" />
                <div className="package-hero-copy">
                    <h1>{detail.name}</h1>
                    {detail.description && detail.description.map((paragraph) => (
                        <p key={paragraph}>{paragraph}</p>
                    ))}
                </div>
                <i className="package-deco deco-one" />
                <i className="package-deco deco-two" />
                <i className="package-deco deco-three" />
            </section>

            <section className="package-style-section">
                <h2>Choose Your Package Style</h2>
                <div className={`package-options package-options-${detail.options.length}`}>
                    {detail.options.map((option, index) => (
                        <article
                            className={selectedOption === index ? 'selected' : ''}
                            key={option.duration}
                            onClick={() => setSelectedOption(index)}
                        >
                            {option.badge && <em>{option.badge}</em>}
                            <header>
                                <h3>{option.duration}</h3>
                                <small>Unlimited Photos</small>
                            </header>
                            <label>
                                Package Price
                                <button type="button" onClick={() => setSelectedOption(index)}>
                                    <strong>{option.price}</strong>
                                    <span>◉</span>
                                </button>
                            </label>
                            <ul>
                                {detail.features.map((feature) => <li key={feature}>✓ {feature}</li>)}
                            </ul>
                            <button
                                className="package-choose-button"
                                onClick={(event) => {
                                    event.stopPropagation();
                                    choosePackage(index);
                                }}
                                type="button"
                            >
                                Choose Package
                            </button>
                        </article>
                    ))}
                </div>
            </section>

            <footer className="package-detail-footer">
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
