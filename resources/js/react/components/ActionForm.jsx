import React, { useRef, useState } from 'react';
import { csrfToken } from '../utils/csrf';
import ConfirmDialog from './ConfirmDialog';

export default function ActionForm({
    action,
    method = 'PATCH',
    fields = {},
    className,
    disabled = false,
    children,
    confirmMessage = '',
    confirmTitle = 'Konfirmasi',
    confirmLabel = 'Ya, lanjutkan',
    danger = false,
}) {
    const formRef = useRef(null);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    function submitNow() {
        if (!formRef.current || submitting) return;
        setSubmitting(true);
        setConfirmOpen(false);
        formRef.current.submit();
    }

    return (
        <>
            <form
                ref={formRef}
                method="POST"
                action={action}
                data-confirm-form={confirmMessage ? 'true' : undefined}
                onSubmit={(event) => {
                    if (submitting) {
                        event.preventDefault();
                        return;
                    }

                    if (confirmMessage) {
                        event.preventDefault();
                        setConfirmOpen(true);
                        return;
                    }

                    setSubmitting(true);
                }}
            >
                <input type="hidden" name="_token" value={csrfToken} />
                {method.toUpperCase() !== 'POST' && <input type="hidden" name="_method" value={method} />}
                {Object.entries(fields).map(([name, value]) => (
                    <input key={name} type="hidden" name={name} value={value} />
                ))}
                <button className={className} type="submit" disabled={disabled || submitting}>
                    {submitting ? 'Memproses...' : children}
                </button>
            </form>

            <ConfirmDialog
                open={confirmOpen}
                title={confirmTitle}
                message={confirmMessage}
                confirmLabel={confirmLabel}
                danger={danger}
                busy={submitting}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={submitNow}
            />
        </>
    );
}
