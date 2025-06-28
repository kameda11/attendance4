<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('target_date');
            $table->enum('request_type', ['create', 'update']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->time('clock_in_time')->nullable();
            $table->time('clock_out_time')->nullable();
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->time('break2_start_time')->nullable();
            $table->time('break2_end_time')->nullable();
            $table->string('status_note')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'target_date', 'status'], 'unique_pending_request');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_requests');
    }
}
