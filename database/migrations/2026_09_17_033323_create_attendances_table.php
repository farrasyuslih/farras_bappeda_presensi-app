<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->date('attendance_date');

            $table->enum('status', [
                'hadir', 'terlambat', 'sakit', 'izin'
            ]);

            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();

            $table->unique(['user_id', 'attendance_date']);
            $table->index('attendance_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
