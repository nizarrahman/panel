<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plans / produk yang dijual admin
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price');          // harga dalam Rupiah
            $table->unsignedInteger('memory');         // MB
            $table->unsignedInteger('cpu');            // %
            $table->unsignedInteger('disk');           // MB
            $table->unsignedInteger('databases')->default(0);
            $table->unsignedInteger('backups')->default(0);
            $table->unsignedInteger('allocations')->default(1);
            $table->unsignedInteger('duration_days');  // masa aktif
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Orders / transaksi
        Schema::create('billing_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();      // ID unik untuk Midtrans
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('plan_id');
            $table->foreign('plan_id')->references('id')->on('billing_plans')->onDelete('cascade');
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled'])->default('pending');
            $table->string('snap_token')->nullable();
            $table->string('payment_type')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();    // kapan order kadaluarsa
            $table->timestamps();
        });

        // Billing settings (Midtrans keys, dll)
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_orders');
        Schema::dropIfExists('billing_plans');
        Schema::dropIfExists('billing_settings');
    }
};
