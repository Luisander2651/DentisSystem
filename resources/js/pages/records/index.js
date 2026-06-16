(function () {
    if (window.__recordsIndexInit) {
        return;
    }

    window.__recordsIndexInit = true;

    var page = document.querySelector('[data-records-page]');
    var recordsGrid = document.getElementById('records-table-container');
    var errorBox = document.getElementById('records-error');
    var loadingBox = document.getElementById('records-loading');
    var emptyBox = document.getElementById('records-empty');
    
    var countTotal = document.querySelector('[data-records-total-count]');
    var countActive = document.querySelector('[data-records-active-count]');
    var countInactive = document.querySelector('[data-records-inactive-count]');

    var detailSection = document.getElementById('record-detail-section');
    var selectedPatientLabel = document.getElementById('record-selected-patient-label');
    var contactForm = document.getElementById('record-contact-form');
    var addressForm = document.getElementById('record-address-form');
    var medicalForm = document.getElementById('record-medical-form');
    var openContactFormButton = document.querySelector('[data-record-open-contact-form]');
    var openAddressFormButton = document.querySelector('[data-record-open-address-form]');
    var openMedicalFormButton = document.querySelector('[data-record-open-medical-form]');
    var cancelContactFormButton = document.querySelector('[data-record-cancel-contact-form]');
    var cancelAddressFormButton = document.querySelector('[data-record-cancel-address-form]');
    var cancelMedicalFormButton = document.querySelector('[data-record-cancel-medical-form]');
    var contactFormError = document.getElementById('record-contact-form-error');
    var addressFormError = document.getElementById('record-address-form-error');
    var medicalFormError = document.getElementById('record-medical-form-error');
    var deleteModal = document.getElementById('record-delete-modal');
    var deleteConfirmTarget = deleteModal ? deleteModal.querySelector('[data-confirm-delete-name]') : null;
    var deleteCancelButtons = deleteModal ? Array.from(deleteModal.querySelectorAll('[data-confirm-delete-cancel]')) : [];
    var deleteAcceptButton = deleteModal ? deleteModal.querySelector('[data-confirm-delete-submit]') : null;
    var hasContactInfo = false;
    var hasAddress = false;
    var hasMedicalData = false;
    var pendingDeleteEndpoint = null;
    var pendingDeleteLabel = null;
    var isDeleting = false;

    if (!page || !recordsGrid || !detailSection || !selectedPatientLabel) {
        return;
    }

    var selectedPatientId = String(page.dataset.selectedPatientId || '').trim();

    function showError(message) {
        if (!errorBox) {
            return;
        }

        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function hideError() {
        if (!errorBox) {
            return;
        }

        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function normalizeStatus(status) {
        if (status === 'active') return 'Activo';
        if (status === 'inactive') return 'Inactivo';
        return '-';
    }

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    function parseArrayField(value) {
        if (value === null || value === undefined) {
            return null;
        }

        var text = String(value).trim();
        if (text === '') {
            return null;
        }

        var items = text
            .split(/[\n,]/)
            .map(function (item) { return item.trim(); })
            .filter(function (item) { return item !== ''; });

        return items.length > 0 ? items : null;
    }

    function setFormError(errorBoxElement, message) {
        if (!errorBoxElement) {
            return;
        }

        if (!message) {
            errorBoxElement.textContent = '';
            errorBoxElement.classList.add('hidden');
            return;
        }

        errorBoxElement.textContent = message;
        errorBoxElement.classList.remove('hidden');
    }

    function openDeleteModal(label, endpointSuffix) {
        if (!deleteModal || !endpointSuffix) {
            return;
        }

        pendingDeleteLabel = label || 'este registro';
        pendingDeleteEndpoint = endpointSuffix;

        if (deleteConfirmTarget) {
            deleteConfirmTarget.textContent = pendingDeleteLabel;
        }

        deleteModal.classList.remove('hidden');
        deleteModal.classList.add('flex');
    }

    function closeDeleteModal() {
        pendingDeleteLabel = null;
        pendingDeleteEndpoint = null;

        if (!deleteModal) {
            return;
        }

        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
    }

    function closeAllForms() {
        [contactForm, addressForm, medicalForm].forEach(function (form) {
            if (!form) {
                return;
            }

            form.classList.add('hidden');
            form.reset();
        });

        setFormError(contactFormError, '');
        setFormError(addressFormError, '');
        setFormError(medicalFormError, '');
    }

    function setButtonLabel(button, hasData, addLabel, updateLabel) {
        if (!button) {
            return;
        }

        button.textContent = hasData ? updateLabel : addLabel;
    }

    function setSubmitLabel(form, hasData, createLabel, updateLabel) {
        if (!form) {
            return;
        }

        var submitButton = form.querySelector('button[type="submit"]');
        if (!submitButton) {
            return;
        }

        submitButton.textContent = hasData ? updateLabel : createLabel;
    }

    function syncEntityActions() {
        setButtonLabel(openContactFormButton, hasContactInfo, 'Agregar contacto', 'Actualizar contacto');
        setButtonLabel(openAddressFormButton, hasAddress, 'Agregar direccion', 'Actualizar direccion');
        setButtonLabel(openMedicalFormButton, hasMedicalData, 'Agregar datos medicos', 'Actualizar datos medicos');

        setSubmitLabel(contactForm, hasContactInfo, 'Guardar contacto', 'Actualizar contacto');
        setSubmitLabel(addressForm, hasAddress, 'Guardar direccion', 'Actualizar direccion');
        setSubmitLabel(medicalForm, hasMedicalData, 'Guardar datos medicos', 'Actualizar datos medicos');
    }

    function openForm(formElement) {
        if (!formElement) {
            return;
        }

        if (!selectedPatientId) {
            showError('Seleccione un paciente para gestionar informacion del expediente.');
            return;
        }

        closeAllForms();
        hideError();
        formElement.classList.remove('hidden');
    }

    async function upsertEntity(patientId, endpointSuffix, payload, useUpdate) {
        var xsrfToken = getCookie('XSRF-TOKEN');
        var response = await fetch('/api/v1/patients/' + encodeURIComponent(patientId) + '/' + endpointSuffix, {
            method: useUpdate ? 'PUT' : 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken || '',
            },
            body: JSON.stringify(payload),
        });

        var data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(data.error || data.message || 'No fue posible guardar la informacion.');
        }

        return data;
    }

    async function deleteEntity(patientId, endpointSuffix) {
        var xsrfToken = getCookie('XSRF-TOKEN');
        var response = await fetch('/api/v1/patients/' + encodeURIComponent(patientId) + '/' + endpointSuffix, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken || '',
            },
        });

        var data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(data.error || data.message || 'No fue posible eliminar la informacion.');
        }

        return data;
    }

    function toDisplay(value) {
        if (value === null || value === undefined) {
            return '-';
        }

        var text = String(value).trim();
        return text === '' ? '-' : text;
    }

    function arrayToDisplay(value) {
        if (!Array.isArray(value) || value.length === 0) {
            return '-';
        }

        return value.map(function (item) {
            return toDisplay(item);
        }).join(', ');
    }

    function renderPatientCards(payload) {
        var records = Array.isArray(payload && payload.data)
            ? payload.data
            : (Array.isArray(payload) ? payload : []);

        let activeCount = 0;
        let inactiveCount = 0;

        records.forEach(patient => {
            if (patient.status === 'active') activeCount++;
            else if (patient.status === 'inactive') inactiveCount++;
        });

        if (countTotal) countTotal.textContent = String(records.length);
        if (countActive) countActive.textContent = String(activeCount);
        if (countInactive) countInactive.textContent = String(inactiveCount);

        if (emptyBox) emptyBox.classList.toggle('hidden', records.length !== 0);

        if (records.length === 0) {
            recordsGrid.innerHTML = '';
            return;
        }

        recordsGrid.innerHTML = records.map(function (patient) {
            var id = patient && patient.id ? String(patient.id).trim() : '';
            var firstName = patient && patient.first_name ? String(patient.first_name).trim() : '';
            var lastName = patient && patient.last_name ? String(patient.last_name).trim() : '';
            var fullName = (firstName + ' ' + lastName).trim() || 'Paciente sin nombre';
            var email = patient && patient.email ? String(patient.email).trim() : 'Sin correo';
            var status = patient && patient.status ? patient.status : '';
            var statusLabel = normalizeStatus(status);
            var href = '/expedientes-clinicos/' + encodeURIComponent(id);

            var statusClasses = status === 'active'
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-red-50 text-red-700';

            var initial = (firstName.charAt(0) || lastName.charAt(0) || 'P').toUpperCase();

            return [
                '<article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">',
                    '<div class="flex items-start justify-between gap-4">',
                        '<div class="flex items-center gap-3">',
                            '<div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#FDF1F6] text-sm font-semibold text-[#B5114A]">', escapeHtml(initial), '</div>',
                            '<div>',
                                '<h3 class="text-base font-semibold text-slate-900 break-words">', escapeHtml(fullName), '</h3>',
                                '<p class="text-xs text-slate-500">Expediente Clinico</p>',
                            '</div>',
                        '</div>',
                        '<span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold ' + statusClasses + '">', escapeHtml(statusLabel), '</span>',
                    '</div>',
                    
                    '<div class="mt-4 grid gap-2">',
                        '<div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-600">',
                            '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
                            '<span class="break-all">', escapeHtml(email), '</span>',
                        '</div>',
                    '</div>',

                    '<div class="mt-4 flex flex-wrap gap-2">',
                        id !== ''
                            ? '<a href="' + escapeHtml(href) + '" class="rounded-full bg-[#B5114A] px-4 py-1.5 text-xs font-semibold text-white transition hover:bg-[#960d3d]">Consultar Expediente</a>'
                            : '',
                    '</div>',
                '</article>'
            ].join('');
        }).join('');
    }

    function renderContactInfo(contactInfo) {
        var tbody = document.getElementById('record-contact-info-body');
        if (!tbody) return;

        hasContactInfo = !!contactInfo;
        syncEntityActions();

        if (!contactInfo) {
            tbody.innerHTML = '<tr class="border-t border-slate-200"><td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">Sin informacion de contacto.</td></tr>';
            return;
        }

        tbody.innerHTML = [
            '<tr class="border-t border-slate-200">',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(toDisplay(contactInfo.phone_number)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(toDisplay(contactInfo.contact_email)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(toDisplay(contactInfo.emergency_contact)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">',
            '  <button type="button" data-record-delete-contact class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">Eliminar</button>',
            '</td>',
            '</tr>'
        ].join('');
    }

    function renderAddress(address) {
        var tbody = document.getElementById('record-address-body');
        if (!tbody) return;

        hasAddress = !!address;
        syncEntityActions();

        if (!address) {
            tbody.innerHTML = '<tr class="border-t border-slate-200"><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Sin informacion de direccion.</td></tr>';
            return;
        }

        tbody.innerHTML = [
            '<tr class="border-t border-slate-200">',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(toDisplay(address.street)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(toDisplay(address.city)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(toDisplay(address.state)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(toDisplay(address.postal_code)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">',
            '  <button type="button" data-record-delete-address class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">Eliminar</button>',
            '</td>',
            '</tr>'
        ].join('');
    }

    function renderMedicalData(medicalData) {
        var tbody = document.getElementById('record-medical-data-body');
        if (!tbody) return;

        hasMedicalData = !!medicalData;
        syncEntityActions();

        if (!medicalData) {
            tbody.innerHTML = '<tr class="border-t border-slate-200"><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Sin informacion medica.</td></tr>';
            return;
        }

        tbody.innerHTML = [
            '<tr class="border-t border-slate-200">',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(toDisplay(medicalData.blood_type)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(arrayToDisplay(medicalData.allergies)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(arrayToDisplay(medicalData.medications)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">' + escapeHtml(arrayToDisplay(medicalData.last_dentist_visit)) + '</td>',
            '<td class="px-4 py-3 align-top text-slate-700">',
            '  <button type="button" data-record-delete-medical class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">Eliminar</button>',
            '</td>',
            '</tr>'
        ].join('');
    }

    function renderRecord(recordPayload) {
        var data = recordPayload && recordPayload.data ? recordPayload.data : null;

        if (!data || !data.patient) {
            hasContactInfo = false;
            hasAddress = false;
            hasMedicalData = false;
            syncEntityActions();
            detailSection.classList.add('hidden');
            selectedPatientLabel.textContent = '-';
            return;
        }

        var patient = data.patient;
        var name = (toDisplay(patient.first_name) + ' ' + toDisplay(patient.last_name)).replace(/\s+/g, ' ').trim();
        selectedPatientLabel.textContent = name + ' (' + toDisplay(patient.email) + ')';

        renderContactInfo(data.contact_info || null);
        renderAddress(data.address || null);
        renderMedicalData(data.medical_data || null);

        detailSection.classList.remove('hidden');
    }

    async function loadPatients() {
        hideError();
        if (loadingBox) loadingBox.classList.remove('hidden');

        try {
            var response = await fetch('/api/v1/patients', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            var payload = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                if (recordsGrid) recordsGrid.innerHTML = '';
                showError(payload.error || payload.message || 'No se pudieron cargar los pacientes.');
                return;
            }

            renderPatientCards(payload);
        } catch (error) {
            if (recordsGrid) recordsGrid.innerHTML = '';
            showError('Error de conexion al cargar pacientes.');
        } finally {
            if (loadingBox) loadingBox.classList.add('hidden');
        }
    }

    async function loadRecord(patientId) {
        if (!patientId) {
            hasContactInfo = false;
            hasAddress = false;
            hasMedicalData = false;
            syncEntityActions();
            detailSection.classList.add('hidden');
            return;
        }

        hideError();

        try {
            var response = await fetch('/api/v1/patients/' + encodeURIComponent(patientId) + '/record', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            var payload = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                hasContactInfo = false;
                hasAddress = false;
                hasMedicalData = false;
                syncEntityActions();
                detailSection.classList.add('hidden');
                showError(payload.error || payload.message || 'No fue posible cargar el expediente.');
                return;
            }

            renderRecord(payload);
        } catch (error) {
            detailSection.classList.add('hidden');
            showError('Error de conexion al cargar el expediente.');
        }
    }

    if (openContactFormButton) {
        openContactFormButton.addEventListener('click', function () {
            openForm(contactForm);
        });
    }

    if (openAddressFormButton) {
        openAddressFormButton.addEventListener('click', function () {
            openForm(addressForm);
        });
    }

    if (openMedicalFormButton) {
        openMedicalFormButton.addEventListener('click', function () {
            openForm(medicalForm);
        });
    }

    if (cancelContactFormButton) {
        cancelContactFormButton.addEventListener('click', closeAllForms);
    }

    if (cancelAddressFormButton) {
        cancelAddressFormButton.addEventListener('click', closeAllForms);
    }

    if (cancelMedicalFormButton) {
        cancelMedicalFormButton.addEventListener('click', closeAllForms);
    }

    if (contactForm) {
        contactForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!selectedPatientId) {
                showError('Seleccione un paciente antes de guardar contacto.');
                return;
            }

            setFormError(contactFormError, '');

            var payload = {
                phone_number: contactForm.elements.phone_number ? contactForm.elements.phone_number.value.trim() : '',
                contact_email: contactForm.elements.contact_email ? contactForm.elements.contact_email.value.trim() : '',
                emergency_contact: contactForm.elements.emergency_contact ? contactForm.elements.emergency_contact.value.trim() : '',
            };

            try {
                await upsertEntity(selectedPatientId, 'contact-info', payload, hasContactInfo);
                await loadRecord(selectedPatientId);
                closeAllForms();
            } catch (error) {
                setFormError(contactFormError, error.message || 'No fue posible guardar contacto.');
            }
        });
    }

    if (addressForm) {
        addressForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!selectedPatientId) {
                showError('Seleccione un paciente antes de guardar direccion.');
                return;
            }

            setFormError(addressFormError, '');

            var payload = {
                street: addressForm.elements.street ? addressForm.elements.street.value.trim() : '',
                city: addressForm.elements.city ? addressForm.elements.city.value.trim() : '',
                state: addressForm.elements.state ? addressForm.elements.state.value.trim() : '',
                postal_code: addressForm.elements.postal_code ? addressForm.elements.postal_code.value.trim() : '',
            };

            try {
                await upsertEntity(selectedPatientId, 'address', payload, hasAddress);
                await loadRecord(selectedPatientId);
                closeAllForms();
            } catch (error) {
                setFormError(addressFormError, error.message || 'No fue posible guardar direccion.');
            }
        });
    }

    if (medicalForm) {
        medicalForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!selectedPatientId) {
                showError('Seleccione un paciente antes de guardar datos medicos.');
                return;
            }

            setFormError(medicalFormError, '');

            var payload = {
                blood_type: medicalForm.elements.blood_type ? medicalForm.elements.blood_type.value.trim() : '',
                allergies: parseArrayField(medicalForm.elements.allergies ? medicalForm.elements.allergies.value : ''),
                medications: parseArrayField(medicalForm.elements.medications ? medicalForm.elements.medications.value : ''),
                last_dentist_visit: parseArrayField(medicalForm.elements.last_dentist_visit ? medicalForm.elements.last_dentist_visit.value : ''),
            };

            try {
                await upsertEntity(selectedPatientId, 'medical-data', payload, hasMedicalData);
                await loadRecord(selectedPatientId);
                closeAllForms();
            } catch (error) {
                setFormError(medicalFormError, error.message || 'No fue posible guardar datos medicos.');
            }
        });
    }

    document.addEventListener('click', function (event) {
        var deleteContactButton = event.target.closest('[data-record-delete-contact]');
        var deleteAddressButton = event.target.closest('[data-record-delete-address]');
        var deleteMedicalButton = event.target.closest('[data-record-delete-medical]');

        if (!deleteContactButton && !deleteAddressButton && !deleteMedicalButton) {
            return;
        }

        if (!selectedPatientId) {
            showError('Seleccione un paciente antes de eliminar informacion.');
            return;
        }

        var endpointSuffix = null;
        var label = '';

        if (deleteContactButton) {
            endpointSuffix = 'contact-info';
            label = 'contacto';
        } else if (deleteAddressButton) {
            endpointSuffix = 'address';
            label = 'direccion';
        } else if (deleteMedicalButton) {
            endpointSuffix = 'medical-data';
            label = 'datos medicos';
        }

        if (!endpointSuffix) {
            return;
        }

        openDeleteModal(label, endpointSuffix);
    });

    if (deleteCancelButtons && deleteCancelButtons.length) {
        deleteCancelButtons.forEach(function (btn) {
            btn.addEventListener('click', closeDeleteModal);
        });
    }

    if (deleteModal) {
        deleteModal.addEventListener('click', function (event) {
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });
    }

    if (deleteAcceptButton) {
        deleteAcceptButton.addEventListener('click', async function () {
            if (!selectedPatientId || !pendingDeleteEndpoint || isDeleting) {
                return;
            }

            isDeleting = true;
            deleteAcceptButton.disabled = true;
            if (deleteCancelButtons && deleteCancelButtons.length) {
                deleteCancelButtons.forEach(function (btn) { btn.disabled = true; });
            }

            hideError();
            closeAllForms();

            try {
                await deleteEntity(selectedPatientId, pendingDeleteEndpoint);
                closeDeleteModal();
                await loadRecord(selectedPatientId);
            } catch (error) {
                closeDeleteModal();
                showError(error.message || 'No fue posible eliminar la informacion.');
            } finally {
                isDeleting = false;
                deleteAcceptButton.disabled = false;
                if (deleteCancelButtons && deleteCancelButtons.length) {
                    deleteCancelButtons.forEach(function (btn) { btn.disabled = false; });
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', async function () {
        syncEntityActions();

        if (!selectedPatientId) {
            [openContactFormButton, openAddressFormButton, openMedicalFormButton].forEach(function (button) {
                if (button) {
                    button.disabled = true;
                }
            });
        }

        await loadPatients();
        await loadRecord(selectedPatientId);
    });
})();
