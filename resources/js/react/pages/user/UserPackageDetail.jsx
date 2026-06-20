import React, { useState } from 'react';
import { Link, Navigate, useParams } from 'react-router-dom';

const packageData = {
    wedding: {
        name: 'Wedding Package',
        breadcrumb: 'Wedding Package',
        image: '/image/user-dashboard/package-wedding.png',
        galleryTitle: 'Examples of Our Photo Results',
        gallerySubtitle: 'Capture every precious moment with our professional photo quality',
        options: [
            { duration: 'Duration 8 Hours', price: 'Rp 5,800,000', badge: '' },
        ],
    },
    reservation: {
        name: 'Reservation Package',
        breadcrumb: 'Event Package',
        image: '/image/user-dashboard/package-reservation.png',
        galleryTitle: 'Showcase of Our Event Photos',
        gallerySubtitle: 'Capture every unforgettable moment with our professional photo quality',
        options: [
            { duration: 'Duration 4 Hours', price: 'Rp 899,000', badge: '' },
            { duration: 'Duration 4+1 Hours', price: 'Rp 899,000', badge: 'Most Popular' },
        ],
    },
    unlimited: {
        name: 'Unlimited Package',
        breadcrumb: 'School Package',
        image: '/image/user-dashboard/package-unlimited.png',
        galleryTitle: 'Examples of Our Photo Results',
        gallerySubtitle: 'Capture every precious moment with our professional photo quality',
        options: [
            { duration: 'Duration 2 Hours', price: 'Rp 2,000,000', badge: '' },
            { duration: 'Duration 3 Hours', price: 'Rp 2,500,000', badge: '' },
            { duration: 'Duration 4 Hours', price: 'Rp 3,000,000', badge: 'Most Popular' },
        ],
    },
};

const features = [
    'Free design frame',
    'QR share media',
    'Photo share media',
    'Physical photo print 6R only',
    'Unlimited photo print',
    'Unlimited photo file daily',
];

export default function UserPackageDetail() {
    const { slug } = useParams();
    const detail = packageData[slug];
    const [selectedOption, setSelectedOption] = useState(0);

    if (!detail) {
        return <Navigate to="/home" replace />;
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
                <h1>{detail.name}</h1>
                <i className="package-deco deco-one" />
                <i className="package-deco deco-two" />
                <i className="package-deco deco-three" />
            </section>

            <section className="package-style-section">
                <h2>Choose Your Package Style</h2>
                <div className={`package-options package-options-${detail.options.length}`}>
                    {detail.options.map((option, index) => (
                        <article className={selectedOption === index ? 'selected' : ''} key={option.duration}>
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
                                {features.map((feature) => <li key={feature}>✓ {feature}</li>)}
                            </ul>
                            <Link
                                to={`/book?package=${slug}&option=${index}`}
                                onClick={() => setSelectedOption(index)}
                            >
                                Choose Package
                            </Link>
                        </article>
                    ))}
                </div>
            </section>

            <section className="package-gallery">
                <h2>{detail.galleryTitle}</h2>
                <p>{detail.gallerySubtitle}</p>
                <div className="package-gallery-featured">
                    <img src={detail.image} alt={`${detail.name} featured result`} />
                    <span>{detail.name} Event</span>
                </div>
                <div className="package-gallery-thumbs">
                    {[1, 2, 3, 4, 5].map((item) => (
                        <img key={item} src={detail.image} alt={`${detail.name} gallery ${item}`} />
                    ))}
                </div>
                <div className="gallery-dots"><b /><i /><i /></div>
            </section>

            <footer className="package-detail-footer">
                <div className="customer-brand">
                    <img src="/image/logo-icon.png" alt="" />
                    <strong>EightFinity</strong>
                </div>
                <p>Capture Your Infinite Moments</p>
                <div><span>◉ WhatsApp</span><span>▣ Instagram</span></div>
                <small>© 2026 Eightfinity. All rights reserved.</small>
            </footer>
        </main>
    );
}
