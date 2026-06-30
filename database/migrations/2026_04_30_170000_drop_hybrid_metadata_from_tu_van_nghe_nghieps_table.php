<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropIndexIfExists('tvnn_user_profile_hash_index');
        $this->dropIndexIfExists('tvnn_user_created_index');

        $columns = array_values(array_filter([
            Schema::hasColumn('tu_van_nghe_nghieps', 'input_hash') ? 'input_hash' : null,
            Schema::hasColumn('tu_van_nghe_nghieps', 'evidence_snapshot') ? 'evidence_snapshot' : null,
            Schema::hasColumn('tu_van_nghe_nghieps', 'structured_report') ? 'structured_report' : null,
            Schema::hasColumn('tu_van_nghe_nghieps', 'generation_provider') ? 'generation_provider' : null,
            Schema::hasColumn('tu_van_nghe_nghieps', 'billing_usage_id') ? 'billing_usage_id' : null,
            Schema::hasColumn('tu_van_nghe_nghieps', 'generated_at') ? 'generated_at' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('tu_van_nghe_nghieps', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        Schema::table('tu_van_nghe_nghieps', function (Blueprint $table) {
            if (!Schema::hasColumn('tu_van_nghe_nghieps', 'input_hash')) {
                $table->string('input_hash', 64)->nullable()->after('model_version');
            }
            if (!Schema::hasColumn('tu_van_nghe_nghieps', 'evidence_snapshot')) {
                $table->json('evidence_snapshot')->nullable()->after('input_hash');
            }
            if (!Schema::hasColumn('tu_van_nghe_nghieps', 'structured_report')) {
                $table->json('structured_report')->nullable()->after('evidence_snapshot');
            }
            if (!Schema::hasColumn('tu_van_nghe_nghieps', 'generation_provider')) {
                $table->string('generation_provider', 50)->nullable()->after('structured_report');
            }
            if (!Schema::hasColumn('tu_van_nghe_nghieps', 'billing_usage_id')) {
                $table->unsignedBigInteger('billing_usage_id')->nullable()->after('generation_provider');
            }
            if (!Schema::hasColumn('tu_van_nghe_nghieps', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('billing_usage_id');
            }
        });
    }

    private function dropIndexIfExists(string $indexName): void
    {
        try {
            Schema::table('tu_van_nghe_nghieps', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (Throwable) {
            // Index may not exist if the hybrid metadata migration never ran.
        }
    }
};
