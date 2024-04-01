<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('ac_service')->hasTable('anggotas')) {
            Schema::connection('ac_service')->create('anggotas', function (Blueprint $table) {
                $table->id();
                $table->string('custom_id')->unique();
                $table->string('type')->default('anggota');
                $table->string('member_code')->nullable()->unique()->comment('NO INDUK ANGGOTA');
                $table->date('registered_at')->nullable()->comment('TANGGAL');
                $table->string('name');
                $table->string('gender')->nullable()->comment('JENIS KELAMIN');
                $table->string('family_card_number')->nullable()->comment('NO KK');
                $table->string('national_id_number')->nullable()->comment('NIK');
                $table->date('birth_date')->nullable()->comment('TGL LAHIR');
                $table->string('family_role')->nullable()->comment('STATUS DLM KELUARGA');
                $table->string('membership_status')->nullable()->comment('STATUS KEANGGOTAAN');
                $table->string('phone_number')->nullable()->comment('NO TLP');
                $table->string('whatsapp_number')->nullable()->comment('NO WA');
                $table->string('email')->nullable();
                $table->string('location')->nullable()->comment('LOKASI');
                $table->string('street')->nullable()->comment('JALAN');
                $table->string('house_number')->nullable()->comment('NO RUMAH');
                $table->string('rt')->nullable();
                $table->string('rw')->nullable();
                $table->string('subdistrict')->nullable()->comment('KELURAHAN');
                $table->string('district')->nullable()->comment('KECAMATAN');
                $table->string('city')->nullable()->comment('KOTA');
                $table->string('province')->nullable()->comment('PROVINSI');
                $table->text('address')->nullable();
                $table->string('contact_name')->nullable();
                $table->json('phone_numbers')->nullable();
                $table->string('setup_status')->default('pending_ac');
                $table->timestamp('setup_completed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('anggotas');
    }
};
