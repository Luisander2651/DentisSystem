@props([
    'headers' => [],
    'rows' => [],
    'emptyMessage' => 'No hay informacion disponible.',
    'tableId' => null,
    'actions' => false,
    'actionPrefix' => 'user',
])

@php
    $tableId = $tableId ?: 'ui-table-'.uniqid();

    $columns = collect($headers)->map(function ($label, $key) {
        if (is_int($key)) {
            return [
                'key' => (string) $label,
                'label' => (string) $label,
            ];
        }

        return [
            'key' => (string) $key,
            'label' => (string) $label,
        ];
    })->values();

    $hasActions = filter_var($actions, FILTER_VALIDATE_BOOL);
    $columnCount = max(1, $columns->count() + ($hasActions ? 1 : 0));
@endphp

<div
    id="{{ $tableId }}"
    data-ui-table
    data-ui-table-columns='@json($columns)'
    data-ui-table-empty-message="{{ e($emptyMessage) }}"
    data-ui-table-actions="{{ $hasActions ? '1' : '0' }}"
    data-ui-table-action-prefix="{{ e($actionPrefix) }}"
    {{ $attributes->merge(['class' => 'w-full overflow-x-auto rounded-lg border border-slate-200 bg-white']) }}
>
    <table class="min-w-full border-collapse text-left text-sm text-slate-700">
        <thead class="bg-[#E91E63] text-white">
            <tr>
                @foreach ($columns as $column)
                    <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">
                        {{ $column['label'] }}
                    </th>
                @endforeach

                @if ($hasActions)
                    <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Acciones</th>
                @endif
            </tr>
        </thead>

        <tbody class="bg-white" data-ui-table-body>
            @forelse ($rows as $row)
                @php
                    $normalizedRow = is_array($row)
                        ? $row
                        : (is_object($row) ? (method_exists($row, 'toArray') ? $row->toArray() : get_object_vars($row)) : []);

                    $rawRowId = data_get($normalizedRow, 'id');
                    $rowId = is_scalar($rawRowId) ? trim((string) $rawRowId) : '';
                    $hasRowId = $rowId !== '';

                    $fullName = trim((string) data_get($normalizedRow, 'full_name', ''));
                    $firstName = trim((string) data_get($normalizedRow, 'first_name', ''));
                    $lastName = trim((string) data_get($normalizedRow, 'last_name', ''));
                    $composedName = trim($firstName.' '.$lastName);
                    $email = trim((string) data_get($normalizedRow, 'email', ''));

                    $actionPrefix = trim((string) $actionPrefix);
                    $actionPrefix = $actionPrefix !== '' ? $actionPrefix : 'user';

                    $rowLabel = $fullName !== ''
                        ? $fullName
                        : ($composedName !== ''
                            ? $composedName
                            : ($email !== ''
                                ? $email
                                : ($hasRowId ? 'ID '.$rowId : 'ID')));

                    $deleteButtonClasses = 'inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 transition'
                        .($hasRowId ? ' hover:border-red-200 hover:bg-red-50 hover:text-red-600' : ' opacity-50 cursor-not-allowed');

                    $editFirstName = trim((string) data_get($normalizedRow, 'first_name_value', data_get($normalizedRow, 'first_name', '')));
                    $editLastName = trim((string) data_get($normalizedRow, 'last_name_value', data_get($normalizedRow, 'last_name', '')));
                    $editRoleId = trim((string) data_get($normalizedRow, 'role_value', data_get($normalizedRow, 'role_id', '')));
                    $editStatus = trim((string) data_get($normalizedRow, 'status_value', data_get($normalizedRow, 'status', '')));

                    if ($editRoleId === 'Administrador') {
                        $editRoleId = 'admin';
                    } elseif ($editRoleId === 'Asistente') {
                        $editRoleId = 'asistent';
                    }

                    if ($editStatus === 'Activo') {
                        $editStatus = 'active';
                    } elseif ($editStatus === 'Inactivo') {
                        $editStatus = 'inactive';
                    }
                @endphp

                <tr class="border-t border-slate-200">
                    @foreach ($columns as $column)
                        <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">
                            {{ data_get($normalizedRow, $column['key'], '-') }}
                        </td>
                    @endforeach

                    @if ($hasActions)
                        <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 transition hover:border-[#F5C2D6] hover:bg-[#FFF1F6] hover:text-[#B5114A]"
                                    data-{{ e($actionPrefix) }}-edit
                                    data-{{ e($actionPrefix) }}-id="{{ e($rowId) }}"
                                    data-{{ e($actionPrefix) }}-first-name="{{ e($editFirstName) }}"
                                    data-{{ e($actionPrefix) }}-last-name="{{ e($editLastName) }}"
                                    data-{{ e($actionPrefix) }}-role-id="{{ e($editRoleId) }}"
                                    data-{{ e($actionPrefix) }}-status="{{ e($editStatus) }}"
                                    aria-label="Editar registro"
                                    @disabled(! $hasRowId)
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    class="{{ $deleteButtonClasses }}"
                                    data-{{ e($actionPrefix) }}-delete
                                    data-{{ e($actionPrefix) }}-id="{{ e($rowId) }}"
                                    data-{{ e($actionPrefix) }}-label="{{ e($rowLabel) }}"
                                    aria-label="Eliminar registro"
                                    @disabled(! $hasRowId)
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6l-1 14H6L5 6" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr class="border-t border-slate-200">
                    <td colspan="{{ $columnCount }}" class="px-4 py-6 text-center text-sm text-slate-500">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@once
    <script>
        (function () {
            if (window.renderUiTable) {
                return;
            }

            function escapeHtml(value) {
                return String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function getValue(row, key) {
                if (!row || !key) {
                    return '-';
                }

                return key.split('.').reduce(function (carry, part) {
                    if (carry === null || carry === undefined) {
                        return null;
                    }

                    return carry[part];
                }, row) ?? '-';
            }

            function getRowId(row) {
                if (!row || row.id === null || row.id === undefined) {
                    return '';
                }

                return String(row.id).trim();
            }

            function resolveUserLabel(row, rowId) {
                var fullName = String(row && row.full_name !== undefined && row.full_name !== null ? row.full_name : '').trim();
                if (fullName !== '') {
                    return fullName;
                }

                var firstName = String(row && row.first_name !== undefined && row.first_name !== null ? row.first_name : '').trim();
                var lastName = String(row && row.last_name !== undefined && row.last_name !== null ? row.last_name : '').trim();
                var composedName = (firstName + ' ' + lastName).trim();
                if (composedName !== '') {
                    return composedName;
                }

                var email = String(row && row.email !== undefined && row.email !== null ? row.email : '').trim();
                if (email !== '') {
                    return email;
                }

                return rowId !== '' ? ('ID ' + rowId) : 'ID';
            }

            function renderActions(row, actionPrefix) {
                actionPrefix = String(actionPrefix || 'user').trim() || 'user';

                var rowId = getRowId(row);
                var userLabel = resolveUserLabel(row, rowId);
                var hasRowId = rowId !== '';
                var firstName = String(row && row.first_name_value !== undefined && row.first_name_value !== null ? row.first_name_value : (row && row.first_name !== undefined && row.first_name !== null ? row.first_name : '')).trim();
                var lastName = String(row && row.last_name_value !== undefined && row.last_name_value !== null ? row.last_name_value : (row && row.last_name !== undefined && row.last_name !== null ? row.last_name : '')).trim();
                var roleId = String(row && row.role_value !== undefined && row.role_value !== null ? row.role_value : (row && row.role_id !== undefined && row.role_id !== null ? row.role_id : '')).trim();
                var status = String(row && row.status_value !== undefined && row.status_value !== null ? row.status_value : (row && row.status !== undefined && row.status !== null ? row.status : '')).trim();

                if (roleId === 'Administrador') {
                    roleId = 'admin';
                } else if (roleId === 'Asistente') {
                    roleId = 'asistent';
                }

                if (status === 'Activo') {
                    status = 'active';
                } else if (status === 'Inactivo') {
                    status = 'inactive';
                }

                var editButtonClasses = 'inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 transition hover:border-[#F5C2D6] hover:bg-[#FFF1F6] hover:text-[#B5114A]'
                    + (hasRowId ? '' : ' opacity-50 cursor-not-allowed');
                var editButtonAttributes = 'data-' + escapeHtml(actionPrefix) + '-edit data-' + escapeHtml(actionPrefix) + '-id="' + escapeHtml(rowId) + '" data-' + escapeHtml(actionPrefix) + '-first-name="' + escapeHtml(firstName) + '" data-' + escapeHtml(actionPrefix) + '-last-name="' + escapeHtml(lastName) + '" data-' + escapeHtml(actionPrefix) + '-role-id="' + escapeHtml(roleId) + '" data-' + escapeHtml(actionPrefix) + '-status="' + escapeHtml(status) + '"';
                var deleteButtonClasses = 'inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 transition'
                    + (hasRowId ? ' hover:border-red-200 hover:bg-red-50 hover:text-red-600' : ' opacity-50 cursor-not-allowed');
                var deleteButtonAttributes = 'data-' + escapeHtml(actionPrefix) + '-delete data-' + escapeHtml(actionPrefix) + '-id="' + escapeHtml(rowId) + '" data-' + escapeHtml(actionPrefix) + '-label="' + escapeHtml(userLabel) + '"';

                if (!hasRowId) {
                    editButtonAttributes += ' disabled';
                    deleteButtonAttributes += ' disabled';
                }

                return [
                    '<div class="flex items-center gap-2">',
                    '    <button type="button" class="' + editButtonClasses + '" ' + editButtonAttributes + ' aria-label="Editar registro">',
                    '        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
                    '            <path d="M12 20h9" />',
                    '            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />',
                    '        </svg>',
                    '    </button>',
                    '    <button type="button" class="' + deleteButtonClasses + '" ' + deleteButtonAttributes + ' aria-label="Eliminar registro">',
                    '        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
                    '            <polyline points="3 6 5 6 21 6" />',
                    '            <path d="M19 6l-1 14H6L5 6" />',
                    '            <path d="M10 11v6" />',
                    '            <path d="M14 11v6" />',
                    '            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />',
                    '        </svg>',
                    '    </button>',
                    '</div>',
                ].join('');
            }

            window.renderUiTable = function (tableId, rows) {
                var table = document.getElementById(tableId);

                if (!table) {
                    return;
                }

                var tbody = table.querySelector('[data-ui-table-body]');

                if (!tbody) {
                    return;
                }

                var columns = [];

                try {
                    columns = JSON.parse(table.dataset.uiTableColumns || '[]');
                } catch (error) {
                    columns = [];
                }

                var hasActions = table.dataset.uiTableActions === '1';
                var emptyMessage = table.dataset.uiTableEmptyMessage || 'No hay informacion disponible.';
                var columnCount = columns.length + (hasActions ? 1 : 0);

                if (!Array.isArray(rows) || rows.length === 0) {
                    tbody.innerHTML = '<tr class="border-t border-slate-200"><td colspan="' + columnCount + '" class="px-4 py-6 text-center text-sm text-slate-500">' + escapeHtml(emptyMessage) + '</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map(function (row) {
                    var cells = columns.map(function (column) {
                        return '<td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">' + escapeHtml(getValue(row, column.key)) + '</td>';
                    }).join('');

                    if (hasActions) {
                        cells += '<td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">' + renderActions(row, table.dataset.uiTableActionPrefix || 'user') + '</td>';
                    }

                    return '<tr class="border-t border-slate-200">' + cells + '</tr>';
                }).join('');
            };
        })();
    </script>
@endonce
