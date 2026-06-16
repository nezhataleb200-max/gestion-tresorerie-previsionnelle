<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tresorerie_id')
                  ->nullable()
                  ->constrained('tresorerie_mensuelle')
                  ->onDelete('cascade');
            $table->enum('type', ['deficit', 'retard_facture', 'tension'])->comment('Type d\'alerte');
            $table->enum('niveau', ['critique', 'warning', 'info'])->default('warning');
            $table->text('message');
            $table->date('mois_concerne')->nullable();
            $table->boolean('resolue')->default(false);
            $table->timestamps();

            $table->index('tresorerie_id');
            $table->index('resolue');
            $table->index('niveau');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes');
    }
};