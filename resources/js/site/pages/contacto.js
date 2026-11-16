/*
 * Public site (spec 026): entry of the contact page. Brings its stylesheet, says whether the clinic
 * is open now on the clinic's clock, marks today and the current hour in the weekly hours, and
 * copies the phone and the address. The form never reaches the server (A35): its submit is
 * cancelled here and the WhatsApp message is composed by contact.js (T023).
 */
import '../../../css/site/pages/contacto.css';

const RULER_FROM = 8 * 60;
const RULER_TO = 20 * 60;

function updateStatus(doc, D) {
    const status = D.clinic();

    doc.querySelectorAll('[data-status]').forEach((holder) => {
        const text = holder.querySelector('[data-status-text]');
        const head = status.open ? 'Abierto ahora' : 'Cerrado ahora';

        holder.classList.toggle('open', status.open);
        holder.classList.toggle('closed', !status.open);

        if (text) {
            text.textContent = holder.getAttribute('data-status') === 'short' ? head : `${head} · ${status.sub}`;
        }
    });

    doc.querySelectorAll('.wk-row[data-day]').forEach((row) => {
        const today = Number(row.getAttribute('data-day')) === status.day;
        const tag = row.querySelector('.today-tag');
        const now = row.querySelector('.wk-now');

        row.classList.toggle('today', today);

        if (tag) {
            tag.hidden = !today;
        }

        if (now) {
            now.hidden = !(today && status.minutes >= RULER_FROM && status.minutes <= RULER_TO);
            now.style.left = `${Math.max(0, Math.min(100, ((status.minutes - RULER_FROM) / (RULER_TO - RULER_FROM)) * 100)).toFixed(2)}%`;
        }
    });
}

function initCopy(doc, win) {
    doc.querySelectorAll('[data-copy]').forEach((button) => {
        const label = button.querySelector('[data-copy-label]') || button;
        const original = label.textContent;
        let timer;

        button.addEventListener('click', () => {
            const done = () => {
                label.textContent = button.getAttribute('data-copied') || 'Copiado';
                button.classList.add('ok');
                win.clearTimeout(timer);
                timer = win.setTimeout(() => {
                    label.textContent = original;
                    button.classList.remove('ok');
                }, 2000);
            };

            try {
                win.navigator.clipboard.writeText(button.getAttribute('data-copy')).then(done, done);
            } catch {
                done();
            }
        });
    });
}

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    window.Dentissa.ready(() => {
        updateStatus(document, window.Dentissa);
        window.setInterval(() => updateStatus(document, window.Dentissa), 60000);
        initCopy(document, window);
        document.querySelectorAll('[data-wa-form]').forEach((form) => form.addEventListener('submit', (event) => event.preventDefault()));
    });
}
