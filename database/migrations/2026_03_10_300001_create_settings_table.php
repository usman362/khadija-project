<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('group')->default('general');
            $table->string('type')->default('string'); // string, boolean, integer, encrypted
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('group');
        });

        // Seed default payment settings
        $defaults = [
            ['key' => 'payment.active_gateway', 'value' => 'stripe', 'group' => 'payment', 'type' => 'string', 'description' => 'Active payment gateway (stripe or paypal)'],
            ['key' => 'payment.mode', 'value' => 'test', 'group' => 'payment', 'type' => 'string', 'description' => 'Payment mode (test or live)'],
            ['key' => 'payment.currency', 'value' => 'USD', 'group' => 'payment', 'type' => 'string', 'description' => 'Default payment currency'],
            ['key' => 'payment.stripe_public_key', 'value' => null, 'group' => 'payment', 'type' => 'encrypted', 'description' => 'Stripe publishable key'],
            ['key' => 'payment.stripe_secret_key', 'value' => null, 'group' => 'payment', 'type' => 'encrypted', 'description' => 'Stripe secret key'],
            ['key' => 'payment.stripe_webhook_secret', 'value' => null, 'group' => 'payment', 'type' => 'encrypted', 'description' => 'Stripe webhook signing secret'],
            ['key' => 'payment.paypal_client_id', 'value' => null, 'group' => 'payment', 'type' => 'encrypted', 'description' => 'PayPal client ID'],
            ['key' => 'payment.paypal_secret', 'value' => null, 'group' => 'payment', 'type' => 'encrypted', 'description' => 'PayPal secret key'],
            ['key' => 'payment.paypal_webhook_id', 'value' => null, 'group' => 'payment', 'type' => 'encrypted', 'description' => 'PayPal webhook ID'],
        ];

        $now = now();
        foreach ($defaults as $setting) {
            \DB::table('settings')->insert(array_merge($setting, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
