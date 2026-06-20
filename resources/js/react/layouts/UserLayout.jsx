import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import Logo from '../components/Logo';
import { csrfToken } from '../utils/csrf';

export default function UserLayout({ children }) {
    const location = useLocation();
    const links = [
        { href: '/home', label: 'Home' },
        { href: '/book', label: 'Book' },
        { href: '/payment', label: 'Payment' },
    ];

    return (
        <div className="app-shell">
            <header className="top-nav">
                <Logo />
                <nav>
                    {links.map((link) => (
                        <Link
                            key={link.href}
                            to={link.href}
                            className={location.pathname === link.href ? 'active' : ''}
                        >
                            {link.label}
                        </Link>
                    ))}
                </nav>
                <form method="POST" action="/logout">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <button type="submit" className="ghost-button">Logout</button>
                </form>
            </header>
            <main>{children}</main>
        </div>
    );
}
