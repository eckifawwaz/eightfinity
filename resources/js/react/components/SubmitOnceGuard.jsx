import { useEffect } from 'react';

export default function SubmitOnceGuard() {
    useEffect(() => {
        function handleSubmit(event) {
            if (event.defaultPrevented) return;
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.dataset.confirmForm === 'true') return;

            if (form.dataset.submitLocked === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitLocked = 'true';
            window.setTimeout(() => {
                if (!event.defaultPrevented) {
                    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                        button.disabled = true;
                        button.setAttribute('aria-disabled', 'true');
                    });
                } else {
                    delete form.dataset.submitLocked;
                }
            }, 0);
        }

        document.addEventListener('submit', handleSubmit, false);
        return () => document.removeEventListener('submit', handleSubmit, false);
    }, []);

    return null;
}
