import React from 'react';
import { Link } from 'react-router-dom';

export default function Logo({ compact = false }) {
    return (
        <Link to="/home" className="brand-link">
            <img src="/image/logo-icon.png" alt="EightFinity" />
            {!compact && <span>EightFinity</span>}
        </Link>
    );
}
