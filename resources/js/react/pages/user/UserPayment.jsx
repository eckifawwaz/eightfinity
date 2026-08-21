import React, { useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { csrfToken } from '../../utils/csrf';

const packages = {
    wedding: {
        name: 'Wedding Package',
        options: [
            { duration: '4 Hours', amount: 3000000 },
            { duration: '6 Hours', amount: 4500000 },
            { duration: '8 Hours', amount: 5800000 },
        ],
    },
    reservation: {
        name: 'Reservation Package',
        options: [
            { duration: '3 Hours', amount: 799000 },
            { duration: '4 Hours', amount: 899000 },
            { duration: '4+1 Hours', amount: 999000 },
        ],
    },
    unlimited: {
        name: 'Unlimited Package',
        options: [
            { duration: '2 Hours', amount: 2000000 },
            { duration: '3 Hours', amount: 2500000 },
            { duration: '4 Hours', amount: 3000000 },
        ],
    },
};

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function buildQrPattern(value) {
    const size = 25;
    let hash = 0;

    for (let index = 0; index < value.length; index += 1) {
        hash = ((hash << 5) - hash + value.charCodeAt(index)) | 0;
    }

    const finder = (row, col, startRow, startCol) => {
        const localRow = row - startRow;
        const localCol = col - startCol;
        if (localRow < 0 || localRow > 6 || localCol < 0 || localCol > 6) return false;
        return localRow === 0 || localRow === 6 || localCol === 0 || localCol === 6 || (
            localRow >= 2 && localRow <= 4 && localCol >= 2 && localCol <= 4
        );
    };

    return Array.from({ length: size }, (_, row) => (
        Array.from({ length: size }, (_, col) => {
            if (finder(row, col, 0, 0) || finder(row, col, 0, 18) || finder(row, col, 18, 0)) {
                return true;
            }

            const reservedFinderArea = (row < 8 && col < 8) || (row < 8 && col > 16) || (row > 16 && col < 8);
            if (reservedFinderArea) return false;

            const signal = (row * 31 + col * 17 + hash + ((row ^ col) * 13)) % 7;
            return signal === 0 || signal === 2 || signal === 5;
        })
    ));
}

export default function UserPayment() {
    const [params] = useSearchParams();
    const formErrors = window.__FORM_ERRORS__ ?? [];
    const packageSlug = packages[params.get('package')] ? params.get('package') : 'wedding';
    const optionIndex = Number(params.get('option')) || 0;
    const selectedPackage = packages[packageSlug];
    const selectedOption = selectedPackage.options[optionIndex] ?? selectedPackage.options[0];
    const usesMidtransPaymentLink = packageSlug === 'wedding' || packageSlug === 'reservation' || packageSlug === 'unlimited';
    const paymentPayload = [
        'EIGHTFINITY',
        selectedPackage.name,
        selectedOption.amount,
        params.get('date') ?? '',
        params.get('time') ?? '',
        params.get('booth_size') ?? '',
    ].join('|');
    const qrPattern = useMemo(() => buildQrPattern(paymentPayload), [paymentPayload]);
    const qrExpiry = '00 : 15 : 00';
    const [isSubmitting, setIsSubmitting] = useState(false);

    function downloadQr() {
        const svg = document.querySelector('.payment-qr-code svg');
        if (!svg) return;

        const blob = new Blob([svg.outerHTML], { type: 'image/svg+xml' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'eightfinity-qr-payment.svg';
        link.click();
        URL.revokeObjectURL(url);
    }

    return (
        <main className="user-book-page payment-page">
            <header className="user-book-nav">
                <Link to="/home" className="user-book-brand">
                    <img src="/image/logo-icon.png" alt="EightFinity" />
                    <strong>Eight<span>Finity</span></strong>
                </Link>
                <Link to="/profile" className="user-book-profile">Profile</Link>
            </header>

            <section className="user-book-hero user-payment-hero">
                <div>
                    <h1>Complete Your <span>Payment</span></h1>
                    <p>Secure and fast payment processing</p>
                </div>
                <i className="book-dot dot-a" /><i className="book-dot dot-b" />
                <i className="book-dot dot-c" /><i className="book-dot dot-d" /><i className="book-dot dot-e" />
            </section>

            <form action="/payment" method="POST" onSubmit={() => setIsSubmitting(true)}>
                <input type="hidden" name="_token" value={csrfToken} />
                <input type="hidden" name="package" value={packageSlug} />
                <input type="hidden" name="option" value={optionIndex} />
                <input type="hidden" name="date" value={params.get('date') ?? ''} />
                <input type="hidden" name="time" value={params.get('time') ?? ''} />
                <input type="hidden" name="booth_size" value={params.get('booth_size') ?? ''} />
                <input type="hidden" name="address" value={params.get('address') ?? ''} />
                <input type="hidden" name="location" value={params.get('location') ?? ''} />
                <input type="hidden" name="payment_method" value="qris" />
                <input type="hidden" name="payment_provider" value="qris" />

                <div className="user-payment-content">
                    <div className="section-title payment-section-title">
                        <h2>Payment</h2>
                        <p>{usesMidtransPaymentLink ? 'Continue to Midtrans to choose your payment method' : 'Scan the QR to do payment'}</p>
                    </div>

                    <aside className="booking-summary-card">
                        <h3>Booking Summary</h3>
                        <dl>
                            <div><dt>Package</dt><dd>{selectedPackage.name}</dd></div>
                            <div><dt>Duration</dt><dd>{selectedOption.duration}</dd></div>
                            <div><dt>Date</dt><dd>{params.get('date')}</dd></div>
                            <div><dt>Time</dt><dd>{params.get('time')}</dd></div>
                            <div><dt>Room Size</dt><dd>{params.get('booth_size')}</dd></div>
                            <div><dt>Address</dt><dd>{params.get('address') ?? '-'}</dd></div>
                            <div><dt>Location</dt><dd>{params.get('location') ?? '-'}</dd></div>
                        </dl>
                        <div className="booking-summary-total">
                            <strong>Total</strong>
                            <span>{currency.format(selectedOption.amount)}</span>
                        </div>
                    </aside>

                    <section className="user-payment-main">
                        {formErrors.length > 0 && (
                            <section className="payment-error-card">
                                <strong>Payment could not be submitted</strong>
                                {formErrors.map((error) => <p key={error}>{error}</p>)}
                            </section>
                        )}

                        <section className="payment-qr-card">
                            {usesMidtransPaymentLink ? (
                                <>
                                    <div className="payment-proof-head">
                                        <div>
                                            <strong>Payment</strong>
                                            <small>Pilih QRIS, e-wallet, virtual account, atau kartu di halaman pembayaran.</small>
                                        </div>
                                    </div>

                                    <div className="midtrans-payment-panel">
                                        <strong>Ready to pay</strong>
                                        <p>
                                            Setelah tombol ditekan, booking akan tersimpan lalu kamu masuk ke halaman
                                            pembayaran untuk menyelesaikan transaksi.
                                        </p>
                                        <dl>
                                            <div><dt>Amount</dt><dd>{currency.format(selectedOption.amount)}</dd></div>
                                            <div>
                                                <dt>Status</dt>
                                                <dd className="midtrans-status-live">
                                                    <i className="midtrans-status-dot" />
                                                    Menunggu pembayaran
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <button className="complete-payment-button" disabled={isSubmitting} type="submit">
                                        {isSubmitting ? (
                                            <>
                                                <i className="button-spinner" />
                                                Menghubungkan ke pembayaran...
                                            </>
                                        ) : 'Bayar Sekarang'}
                                    </button>
                                </>
                            ) : (
                                <>
                                    <div className="payment-proof-head">
                                        <span className="payment-proof-icon">▤</span>
                                        <strong>QR Code</strong>
                                    </div>

                                    <div className="payment-qr-body">
                                        <div className="payment-qr-code" aria-label="Eightfinity payment QR">
                                            <svg viewBox="0 0 25 25" role="img">
                                                <rect width="25" height="25" fill="#ffffff" />
                                                {qrPattern.map((row, rowIndex) => row.map((active, colIndex) => (
                                                    active ? (
                                                        <rect
                                                            fill="#0b0f16"
                                                            height="1"
                                                            key={`${rowIndex}-${colIndex}`}
                                                            width="1"
                                                            x={colIndex}
                                                            y={rowIndex}
                                                        />
                                                    ) : null
                                                )))}
                                            </svg>
                                        </div>

                                        <div className="payment-qr-instructions">
                                            <strong>Cara Membayar dengan Kode QR</strong>
                                            <ol>
                                                <li>Buka aplikasi bank atau e-wallet</li>
                                                <li>Scan atau upload Kode QR</li>
                                                <li>Periksa kembali total pembayaran</li>
                                                <li>Selesaikan pembayaran</li>
                                            </ol>
                                            <button className="payment-download-qr" onClick={downloadQr} type="button">
                                                Download Kode QR
                                            </button>
                                            <span>Kode berlaku hingga {qrExpiry}</span>
                                        </div>
                                    </div>

                                    <button className="complete-payment-button" type="submit">
                                        Cek pembayaran
                                    </button>
                                </>
                            )}
                        </section>
                    </section>
                </div>
            </form>

            <footer className="user-book-footer">
                <div><img src="/image/logo-icon-transparent.png" alt="" /><strong>EightFinity</strong></div>
                <p>Capture Your Infinite Moments</p>
                <small>© 2026 Eightfinity. All rights reserved.</small>
            </footer>
        </main>
    );
}
