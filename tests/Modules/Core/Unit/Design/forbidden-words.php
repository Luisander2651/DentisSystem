<?php

declare(strict_types=1);

/*
 * Spec 016, CA16 and CA30: what the interface may not show. A closed list, built on 2026-10-08
 * by extracting every visible text of resources/views and resources/js (941 distinct words)
 * and keeping the ones that are misspelt or that only a developer understands. UiCopyTest
 * fails when a migrated file shows any of them; a new word is added here first.
 */
return [
    /*
     * Spanish words written without their accent or their ñ => how they are written.
     * Matched as whole words, whatever their capitalisation.
     */
    'spelling' => [
        'accion' => 'acción',
        'aqui' => 'aquí',
        'busqueda' => 'búsqueda',
        'catalogo' => 'catálogo',
        'certificacion' => 'certificación',
        'clinica' => 'clínica',
        'clinico' => 'clínico',
        'clinicos' => 'clínicos',
        'codigo' => 'código',
        'conexion' => 'conexión',
        'contrasena' => 'contraseña',
        'descripcion' => 'descripción',
        'direccion' => 'dirección',
        'duracion' => 'duración',
        'eliminacion' => 'eliminación',
        'eliminara' => 'eliminará',
        'encontro' => 'encontró',
        'galeria' => 'galería',
        'gestion' => 'gestión',
        'informacion' => 'información',
        'intentalo' => 'inténtalo',
        'leon' => 'León',
        'linea' => 'línea',
        'medica' => 'médica',
        'medicas' => 'médicas',
        'medicos' => 'médicos',
        'menu' => 'menú',
        'operacion' => 'operación',
        'perez' => 'Pérez',
        'podras' => 'podrás',
        'previsualizacion' => 'previsualización',
        'promocion' => 'promoción',
        'sesion' => 'sesión',
        'telefono' => 'teléfono',
        'ultima' => 'última',
        'valido' => 'válido',
        'esta segura' => 'está segura',
        'esta seguro' => 'está seguro',
    ],

    /*
     * Terms of the trade, which a member of the clinic's staff has no reason to know.
     * Matched as whole words; the ones in capitals, only in capitals.
     */
    'technical' => [
        'API',
        'ID',
        'JSON',
        'HTTP',
        'backend',
        'frontend',
        'endpoint',
        'rutas admin',
        'base de datos',
        'login',
        'logout',
        'token',
    ],
];
