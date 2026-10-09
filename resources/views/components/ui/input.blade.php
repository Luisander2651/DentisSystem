{{--
    Campo del sistema de diseño (docs/design/system.md → Campo de formulario): 44 px de alto, letra
    de 16 px en tinta, etiqueta visible, marca de obligatorio y error enlazado al campo.
    `hint` añade un texto de ayuda. El propósito del campo se declara con `autocomplete`.
--}}
@props([
    'variant' => 'string',
    'name' => null,
    'id' => null,
    'value' => '',
    'label' => null,
    'placeholder' => '',
    'errorText' => null,
    'hint' => null,
])

@php
    $inputId = $id ?? $name ?? ('ui-input-' . uniqid());
    $resolvedValue = $name ? old($name, $value) : $value;

    $base = 'ui-input block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink transition-colors';
    $isPassword = $variant === 'password';
    $inputType = $isPassword ? 'password' : 'text';
    $inputMode = ['email' => 'email', 'number' => 'decimal'][$variant] ?? null;
    $isRequired = $attributes->has('required');

    $messages = [
        'string' => 'Solo se permite texto.',
        'number' => 'Solo números no negativos.',
        'email' => 'Ingresa un correo válido.',
        'password' => 'La contraseña no es válida.',
    ];

    $resolvedErrorText = $errorText ?? ($messages[$variant] ?? $messages['string']);
@endphp

<div class="w-full">
    @if ($label)
        <label for="{{ $inputId }}" class="mb-1.5 block text-sm font-semibold text-ink">
            {{ $label }}
            @if ($isRequired)
                <span class="text-danger" title="Obligatorio">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $inputType }}"
            value="{{ $resolvedValue }}"
            placeholder="{{ $placeholder }}"
            @if ($inputMode) inputmode="{{ $inputMode }}" @endif
            @if ($hint) aria-describedby="{{ $inputId }}-hint" @endif
            data-ui-input
            data-variant="{{ $variant }}"
            data-error-target="{{ $inputId }}-error"
            data-error-text="{{ $resolvedErrorText }}"
            {{ $attributes->merge(['class' => $base . ($isPassword ? ' pr-12' : '')]) }}
        >

        @if ($isPassword)
            <button
                type="button"
                class="absolute inset-y-0 right-0 inline-flex size-control items-center justify-center rounded-control text-muted transition-colors hover:text-ink"
                data-toggle-password
                data-target-input="{{ $inputId }}"
                aria-label="Mostrar contraseña"
                aria-pressed="false"
            >
                <svg data-eye-open xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                <svg data-eye-closed xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.17-5.94" />
                    <path d="M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a21.78 21.78 0 0 1-3.17 4.22" />
                    <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                    <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
            </button>
        @endif
    </div>

    @if ($hint)
        <p id="{{ $inputId }}-hint" class="mt-1 text-min text-muted">{{ $hint }}</p>
    @endif

    <p id="{{ $inputId }}-error" class="mt-1 hidden text-sm font-semibold text-danger">
        {{ $resolvedErrorText }}
    </p>
</div>

@once
    <script nonce="{{ Vite::cspNonce() }}">
        (function () {
            function isValidByVariant(value, variant) {
                if (value === '') {
                    return true;
                }

                if (variant === 'number') {
                    return /^(0|[1-9]\d*)(\.\d+)?$/.test(value);
                }

                if (variant === 'email') {
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                }

                if (variant === 'password') {
                    return true;
                }

                return /^[\p{L}\s]+$/u.test(value);
            }

            function togglePassword(button) {
                var inputId = button.dataset.targetInput;
                var input = document.getElementById(inputId);
                if (!input) {
                    return;
                }

                var openIcon = button.querySelector('[data-eye-open]');
                var closedIcon = button.querySelector('[data-eye-closed]');
                var isHidden = input.type === 'password';

                input.type = isHidden ? 'text' : 'password';
                button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                button.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');

                if (openIcon && closedIcon) {
                    openIcon.classList.toggle('hidden', isHidden);
                    closedIcon.classList.toggle('hidden', !isHidden);
                }
            }

            function validateInput(input) {
                var variant = input.dataset.variant || 'string';
                var errorTarget = document.getElementById(input.dataset.errorTarget);
                if (!errorTarget) {
                    return;
                }

                var value = input.value.trim();
                var isRequired = input.hasAttribute('required');
                var formatValid = isValidByVariant(value, variant);
                var valid = isRequired ? value !== '' && formatValid : formatValid;
                var describedBy = (input.getAttribute('aria-describedby') || '').split(/\s+/).filter(function (id) {
                    return id !== '' && id !== errorTarget.id;
                });

                if (valid) {
                    errorTarget.classList.add('hidden');
                    input.classList.remove('border-2', 'border-danger');
                    input.classList.add('border-field');
                    input.removeAttribute('aria-invalid');

                    if (describedBy.length > 0) {
                        input.setAttribute('aria-describedby', describedBy.join(' '));
                    } else {
                        input.removeAttribute('aria-describedby');
                    }

                    return;
                }

                errorTarget.textContent = input.dataset.errorText || 'Valor inválido.';
                errorTarget.classList.remove('hidden');
                input.classList.remove('border-field');
                input.classList.add('border-2', 'border-danger');
                input.setAttribute('aria-invalid', 'true');
                input.setAttribute('aria-describedby', describedBy.concat(errorTarget.id).join(' '));
            }

            document.addEventListener('input', function (event) {
                var input = event.target.closest('[data-ui-input]');
                if (!input) {
                    return;
                }

                validateInput(input);
            });

            document.addEventListener('blur', function (event) {
                var input = event.target.closest('[data-ui-input]');
                if (!input) {
                    return;
                }

                validateInput(input);
            }, true);

            document.addEventListener('click', function (event) {
                var button = event.target.closest('[data-toggle-password]');
                if (!button) {
                    return;
                }

                togglePassword(button);
            });
        })();
    </script>
@endonce
