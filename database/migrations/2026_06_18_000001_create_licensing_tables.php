<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('license_key', 32)->unique();
            $table->string('customer_email')->index();
            $table->string('customer_name')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('billing_period', 16);
            $table->unsignedSmallInteger('machine_slots')->default(1);
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->index();
            $table->string('status', 24)->default('active')->index();
            $table->string('wompi_reference')->nullable()->index();
            $table->string('transaction_uuid')->nullable()->unique();
            $table->string('gridpay_product_uuid')->nullable();
            $table->decimal('amount_cop', 15, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('machine_activations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->string('machine_fingerprint', 64);
            $table->string('machine_label')->nullable();
            $table->timestamp('activated_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->cascadeOnDelete();

            $table->unique(['subscription_id', 'machine_fingerprint']);
            $table->index(['machine_fingerprint', 'deactivated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_activations');
        Schema::dropIfExists('subscriptions');
    }
};
