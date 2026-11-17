/*
 * Public site (spec 026): the WhatsApp composer of the contact page, from contact.js of the proposal
 * in nueva-landing/. A motive writes its message, which the visitor can edit; sending opens wa.me in
 * a new tab with the message encoded (CA6). The form never reaches the server (A35): its submit is
 * always cancelled. The message only travels in the URL, never into the page (TM3, CA16).
 */

/** The wa.me address of a number with a message, encoded. */
export function whatsappUrl(number, message) {
    return `https://wa.me/${number}?text=${encodeURIComponent(message)}`;
}

/** Wires a composer form: its motives ([data-motivo]), its textarea and its error ([data-wa-error]). */
export function initComposer(form, win = window) {
    const message = form.querySelector('textarea');
    const error = form.querySelector('[data-wa-error]');
    const chips = [...form.querySelectorAll('[data-motivo]')];
    const number = form.getAttribute('data-wa-number');

    const markInvalid = (invalid) => {
        if (invalid) {
            message.setAttribute('aria-invalid', 'true');
        } else {
            message.removeAttribute('aria-invalid');
        }

        if (error) {
            error.hidden = !invalid;
        }
    };

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            const text = chip.getAttribute('data-motivo');

            chips.forEach((other) => {
                other.classList.toggle('on', other === chip);
                other.setAttribute('aria-pressed', other === chip ? 'true' : 'false');
            });
            message.value = text;
            markInvalid(false);

            if (text.endsWith(': ')) {
                message.focus();
                message.setSelectionRange(text.length, text.length);
            }
        });
    });

    message.addEventListener('input', () => {
        if (message.value.trim() !== '') {
            markInvalid(false);
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const text = message.value.trim();

        if (text === '') {
            markInvalid(true);
            message.focus();

            return;
        }

        markInvalid(false);
        win.open(whatsappUrl(number, text), '_blank', 'noopener,noreferrer');
    });
}
