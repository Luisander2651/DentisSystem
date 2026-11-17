/*
 * Public site (spec 027): entry of the page "Deja tu opinión". Brings its stylesheet, counts the
 * characters of the opinion as the visitor writes and, once the opinion is sent, moves the focus to
 * the thanks so a screen reader reads them (CA10). Without JavaScript the page works the same.
 */
import '../../../css/site/pages/opinion.css';

export function countLabel(length, max = 255) {
    return `${length} de ${max} caracteres`;
}

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    window.Dentissa.ready(() => {
        const text = document.querySelector('#op-text');
        const count = document.querySelector('[data-op-count]');

        if (text && count) {
            const update = () => {
                count.textContent = countLabel(text.value.length, Number(text.getAttribute('maxlength')) || 255);
            };

            text.addEventListener('input', update);
            update();
        }

        document.querySelector('[data-op-thanks]')?.focus();
    });
}
