import { announceError } from './status';

// Spec 024 (D3): the PNG is drawn at this size, on the surface colour, so a phone reads it in a chat.
const QR_DOWNLOAD_SIZE = 1024;

/**
 * Spec 024 (D4), shared by the patient portal (spec 025): the downloaded file carries the date
 * and the hour of the appointment, never the name of the patient.
 */
export function qrFilename(date, time) {
    return 'cita-' + String(date || '') + '-' + String(time || '').slice(0, 5).replace(':', '') + '.png';
}

/**
 * Drives one QR block (components/appointments/qr-block): shows the image straight from the API,
 * puts the warning in its place when it fails, and downloads it as a PNG. Each show() or reset()
 * moves a counter on, so an image that arrives late never enables the button of another one.
 */
export function createAppointmentQr(section) {
    if (!section) {
        return null;
    }

    const image = section.querySelector('[data-view-appointment-qr-image]');
    const error = section.querySelector('[data-view-appointment-qr-error]');
    const download = section.querySelector('[data-view-appointment-qr-download]');
    let current = 0;

    function reset() {
        current++;
        section.classList.add('hidden');
        error.classList.add('hidden');
        image.classList.remove('invisible');
        image.onload = null;
        image.onerror = null;
        image.removeAttribute('src');
        image.alt = '';
        download.disabled = true;
        download.removeAttribute('data-view-appointment-qr-filename');
    }

    // Review of spec 024 (R3): the notice is seen in the place of the code and said once, by the
    // status region of the page.
    function showFailure() {
        image.classList.add('invisible');
        error.classList.remove('hidden');
        announceError(error.textContent.replace(/\s+/g, ' ').trim());
    }

    // Spec 024 (CA5, CA8, D2): the image comes straight from the API, so the content security
    // policy stays as it is and the SVG can run nothing.
    function show({ src, alt, filename }) {
        const token = ++current;

        try {
            section.classList.remove('hidden');
            download.setAttribute('data-view-appointment-qr-filename', filename);
            image.alt = alt;
            image.onload = function () {
                if (token === current) {
                    download.disabled = false;
                }
            };
            image.onerror = function () {
                if (token === current) {
                    showFailure();
                }
            };
            image.src = src;
        } catch (failure) {
            showFailure();
        }
    }

    // Spec 024 (CA6, D3): an SVG does not preview in a chat, so the download is a PNG drawn here
    // from the image already shown, on a white background (the SVG has none).
    function downloadPng() {
        const filename = download.getAttribute('data-view-appointment-qr-filename');

        if (!filename || !image.complete) return;

        try {
            const canvas = document.createElement('canvas');
            canvas.width = QR_DOWNLOAD_SIZE;
            canvas.height = QR_DOWNLOAD_SIZE;
            const context = canvas.getContext('2d');
            context.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--color-surface').trim();
            context.fillRect(0, 0, QR_DOWNLOAD_SIZE, QR_DOWNLOAD_SIZE);
            context.drawImage(image, 0, 0, QR_DOWNLOAD_SIZE, QR_DOWNLOAD_SIZE);

            canvas.toBlob(function (blob) {
                if (!blob) {
                    announceError('No pudimos descargar el código QR. Inténtalo de nuevo.');
                    return;
                }

                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(function () {
                    URL.revokeObjectURL(url);
                }, 0);
            }, 'image/png');
        } catch (failure) {
            announceError('No pudimos descargar el código QR. Inténtalo de nuevo.');
        }
    }

    download.addEventListener('click', downloadPng);

    return { show, reset };
}
