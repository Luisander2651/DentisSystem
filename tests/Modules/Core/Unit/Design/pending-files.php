<?php

declare(strict_types=1);

/*
 * Spec 016: views and scripts not yet migrated to the design tokens. OnlyDesignTokensTest and
 * UiCopyTest apply their rules to every file of resources/views and resources/js that is NOT
 * listed here. Migrating a file means taking it off this list; T073 leaves it empty.
 */
return [
    'resources/js/app.js',
    'resources/js/bootstrap.js',
    'resources/views/components/ui/table.blade.php',
    'resources/views/welcome.blade.php',
];
