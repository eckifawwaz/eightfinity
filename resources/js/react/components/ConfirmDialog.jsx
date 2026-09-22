import React from 'react';

export default function ConfirmDialog({
    open,
    title = 'Konfirmasi',
    message,
    confirmLabel = 'Ya, lanjutkan',
    cancelLabel = 'Batal',
    danger = false,
    busy = false,
    onConfirm,
    onCancel,
}) {
    if (!open) return null;

    return (
        <div className="styled-dialog-backdrop" role="presentation" onMouseDown={onCancel}>
            <section
                aria-labelledby="styled-dialog-title"
                aria-modal="true"
                className="styled-dialog-card"
                onMouseDown={(event) => event.stopPropagation()}
                role="dialog"
            >
                <span className={`styled-dialog-icon ${danger ? 'danger' : ''}`}>{danger ? '!' : '?'}</span>
                <h2 id="styled-dialog-title">{title}</h2>
                <p>{message}</p>
                <div className="styled-dialog-actions">
                    <button type="button" className="styled-dialog-cancel" onClick={onCancel} disabled={busy}>
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        className={`styled-dialog-confirm ${danger ? 'danger' : ''}`}
                        onClick={onConfirm}
                        disabled={busy}
                    >
                        {busy ? 'Memproses...' : confirmLabel}
                    </button>
                </div>
            </section>
        </div>
    );
}
