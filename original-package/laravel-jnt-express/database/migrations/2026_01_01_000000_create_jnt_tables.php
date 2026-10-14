<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jnt_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('jnt_shipments', function (Blueprint $t) {
            $t->id();
            $t->morphs('shippable');
            $t->string('awb')->nullable()->index();      // was order meta "jtawb"
            $t->string('jnt_order_id')->nullable();      // was "jtorder"
            $t->string('code')->nullable();              // was "jtcode"
            $t->string('status')->default('pending')->index();
            $t->boolean('insurance')->default(false);
            $t->decimal('cod', 12, 2)->default(0);
            $t->decimal('weight', 8, 3)->default(0);
            $t->json('request')->nullable();
            $t->json('response')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });

        Schema::create('jnt_shipment_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('jnt_shipment_id')->constrained('jnt_shipments')->cascadeOnDelete();
            $t->string('status')->nullable();
            $t->string('scan_code')->nullable();
            $t->text('note');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jnt_shipment_events');
        Schema::dropIfExists('jnt_shipments');
        Schema::dropIfExists('jnt_settings');
    }
};
