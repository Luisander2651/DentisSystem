<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS personal_access_tokens_tokenable_type_tokenable_id_index');

        DB::statement(
            'ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE text USING tokenable_id::text'
        );

        DB::statement(
            'CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens (tokenable_type, tokenable_id)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS personal_access_tokens_tokenable_type_tokenable_id_index');

        // Keep only numeric ids before converting back to bigint.
        DB::statement("DELETE FROM personal_access_tokens WHERE tokenable_id !~ '^[0-9]+$'");

        DB::statement(
            'ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE bigint USING tokenable_id::bigint'
        );

        DB::statement(
            'CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens (tokenable_type, tokenable_id)'
        );
    }
};
