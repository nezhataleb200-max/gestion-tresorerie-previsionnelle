<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('clients', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->string('nom', 150);
        $table->enum('type', ['societe', 'particulier'])->default('societe');
        $table->string('email', 150)->nullable()->unique();
        $table->string('telephone', 20)->nullable();
        $table->integer('delai_paiement')->default(30);
        $table->text('notes')->nullable();
        $table->boolean('actif')->default(true);
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};