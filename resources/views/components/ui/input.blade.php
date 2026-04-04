@props([
    'variant' => 'string',
    'name' => null,
    'id' => null,
    'value' => '',
    'label' => null,
    'placeholder' => '',
    'errorText' => null,
])

@php
    $inputId = $id ?? $name ?? ('ui-input-' . uniqid());
    $resolvedValue = $name ? old($name, $value) : $value;

    $base = 'ui-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200';
    $isPassword = $variant === 'password';
    $inputType = $isPassword ? 'password' : 'text';

    $messages = [
        'string' => 'Solo se permite texto.',
        'number' => 'Solo numeros no negativos.',
        'email' => 'Ingresa un correo valido.',
        'password' => 'La contraseña no es valida.',
    ];

    $resolvedErrorText = $errorText ?? ($messages[$variant] ?? $messages['string']);
@endphp

<div class="w-full">
    @if ($label)
        <label for="{{ $inputId }}" class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $inputType }}"
            value="{{ $resolvedValue }}"
            placeholder="{{ $placeholder }}"
            data-ui-input
            data-variant="{{ $variant }}"
            data-error-target="{{ $inputId }}-error"
            data-error-text="{{ $resolvedErrorText }}"
            {{ $attributes->merge(['class' => $base . ($isPassword ? ' pr-11' : '')]) }}
        >

        @if ($isPassword)
            <button
                type="button"
                class="absolute inset-y-0 right-0 inline-flex w-10 items-center justify-center text-slate-500 transition-colors hover:text-slate-700 focus:outline-none"
                data-toggle-password
                data-target-input="{{ $inputId }}"
                aria-label="Mostrar u ocultar contrasena"
                aria-pressed="false"
            >
                <svg data-eye-open xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                <svg data-eye-closed xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.17-5.94" />
                    <path d="M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a21.78 21.78 0 0 1-3.17 4.22" />
                    <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                    <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
            </button>
        @endif
    </div>

    <span
        id="{{ $inputId }}-error"
        class="mt-1 hidden text-xs font-medium text-red-600"
        aria-live="polite"
    >
        {{ $resolvedErrorText }}
    </span>
</div>

@once
    <script>
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
                if (valid) {
                    errorTarget.classList.add('hidden');
                    input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
                    return;
                }

                errorTarget.textContent = input.dataset.errorText || 'Valor invalido.';
                errorTarget.classList.remove('hidden');
                input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
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
