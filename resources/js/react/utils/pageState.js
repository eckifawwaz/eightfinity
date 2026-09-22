export function consumeFormErrors() {
    const errors = Array.isArray(window.__FORM_ERRORS__) ? [...window.__FORM_ERRORS__] : [];
    window.__FORM_ERRORS__ = [];
    return errors;
}
