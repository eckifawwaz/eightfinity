import React from 'react';
import { socialLinks } from '../utils/socialLinks';

function WhatsAppIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M20.5 11.8A8.4 8.4 0 0 1 8 19.1l-4.1 1 1.1-4a8.3 8.3 0 1 1 15.5-4.3Z" />
            <path d="M8.6 7.5c.2-.4.4-.4.7-.4h.5c.2 0 .4.1.5.4l.7 1.6c.1.2.1.4 0 .6l-.4.5c-.1.2-.2.3 0 .5.4.7.9 1.3 1.5 1.8.7.6 1.4.9 1.7 1 .2.1.4.1.5-.1l.7-.8c.2-.2.4-.3.6-.2l1.7.8c.2.1.4.2.4.4 0 .4-.2 1.2-.8 1.7-.4.4-1.1.6-1.9.4-1-.2-2.2-.7-3.6-1.9-1.5-1.3-2.6-2.7-3.1-3.8-.4-.8-.5-1.5-.2-2 .1-.3.2-.5.4-.7Z" />
        </svg>
    );
}

function InstagramIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="4" width="16" height="16" rx="4.2" />
            <circle cx="12" cy="12" r="3.6" />
            <circle cx="16.7" cy="7.3" r="1" />
        </svg>
    );
}

export default function SocialLinks({ className = '' }) {
    return (
        <div className={`social-links ${className}`}>
            <a href={socialLinks.whatsapp} rel="noreferrer" target="_blank">
                <WhatsAppIcon />
                <span>WhatsApp</span>
            </a>
            <a href={socialLinks.instagram} rel="noreferrer" target="_blank">
                <InstagramIcon />
                <span>Instagram</span>
            </a>
        </div>
    );
}
