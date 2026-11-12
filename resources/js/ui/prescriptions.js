/**
 * The prescription cards of the detail of an appointment (spec 018), shared by the patient
 * portal (spec 025, design.md → c3): medication and dosage as the title, duration and frequency
 * on one line, the instructions below. Everything is written with textContent.
 */
export function renderPrescriptions(container, prescriptions, emptyText = 'No se registraron prescripciones para esta cita.') {
    container.innerHTML = '';

    if (!prescriptions.length) {
        const empty = document.createElement('p');
        empty.className = 'text-sm text-muted';
        empty.textContent = emptyText;
        container.appendChild(empty);
        return;
    }

    prescriptions.forEach(function (prescription) {
        const card = document.createElement('div');
        card.className = 'rounded-box border border-line bg-canvas p-3 text-sm';

        const title = document.createElement('p');
        title.className = 'font-bold text-ink wrap-anywhere';
        title.textContent = prescription.medication + ' — ' + prescription.dosage;
        card.appendChild(title);

        const meta = document.createElement('p');
        meta.className = 'mt-1 text-sm text-muted';
        meta.textContent = prescription.duration_days + ' días · ' + prescription.daily_frequency + ' veces al día';
        card.appendChild(meta);

        if (prescription.instructions) {
            const instructions = document.createElement('p');
            instructions.className = 'mt-1 text-sm text-ink wrap-anywhere';
            instructions.textContent = prescription.instructions;
            card.appendChild(instructions);
        }

        container.appendChild(card);
    });
}
