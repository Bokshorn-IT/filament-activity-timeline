<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Logged but deliberately not fillable, so restoring it only works
            // if the action does not go through fill().
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('test_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('test_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('number');
            $table->string('status')->nullable();
            $table->unsignedBigInteger('test_customer_id')->nullable();
            $table->date('issue_date')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->decimal('total', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('test_invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('test_invoice_id');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_invoice_lines');
        Schema::dropIfExists('test_invoices');
        Schema::dropIfExists('test_users');
        Schema::dropIfExists('test_customers');
    }
};
