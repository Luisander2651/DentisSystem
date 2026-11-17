/*
 * Public site (spec 027): entry of the page "Deja tu opinión". Brings its stylesheet, counts the
 * characters of the opinion as the visitor writes and, once the opinion is sent, moves the focus to
 * the thanks so a screen reader reads them (CA10), takes the focus to the first wrong field and
 * clears the mark and the message of a wrong field once its value is valid. Without JavaScript the page works the same.
 */
import '../../../css/site/pages/opinion.css';

export function countLabel(length, max = 255, min = 20) {
    return `${length} de ${max} caracteres (mínimo ${min})`;
}

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    window.Dentissa.ready(() => {
        const text = document.querySelector('#op-text');
        const count = document.querySelector('[data-op-count]');

        if (text && count) {
            const update = () => {
                count.textContent = countLabel(text.value.length, Number(text.getAttribute('maxlength')) || 255, Number(text.getAttribute('minlength')) || 20);
            };

            text.addEventListener('input', update);
            update();
        }

        /*
         * A field marked wrong stops being marked as soon as what it holds is valid: its message is
         * hidden and no longer describes it (CA3, R13). With errors from the server, the focus goes
         * to the first wrong field, unless a notice of the whole form comes first (R12).
         */
        const wrong = [...document.querySelectorAll('[data-op-form] [aria-invalid="true"]')];

        wrong.forEach((field) => {
            const clear = () => {
                if (!field.checkValidity()) {
                    return;
                }

                field.removeAttribute('aria-invalid');
                const kept = (field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean).filter((id) => {
                    const message = document.getElementById(id);

                    if (message && message.classList.contains('op-err')) {
                        message.hidden = true;

                        return false;
                    }

                    return true;
                });

                if (kept.length) {
                    field.setAttribute('aria-describedby', kept.join(' '));
                } else {
                    field.removeAttribute('aria-describedby');
                }
            };

            field.addEventListener('input', clear);
            field.addEventListener('change', clear);
        });

        /* A sign that the page is ready, which ui-audit waits for before typing (T007). */
        document.querySelector('[data-op-form]')?.setAttribute('data-op-ready', '');

        if (wrong.length && !document.querySelector('[data-op-form] .op-alert')) {
            wrong[0].focus();
        }

        document.querySelector('[data-op-thanks]')?.focus();
    });
}
