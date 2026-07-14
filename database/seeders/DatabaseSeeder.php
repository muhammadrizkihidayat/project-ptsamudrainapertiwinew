<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $operatorRole = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        $abkRole = Role::firstOrCreate(['name' => 'abk', 'guard_name' => 'web']);

        // 2. Create Users
        // Admin
        $admin = User::firstOrCreate([
            'email' => 'admin@samudra.com'
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('admin123'),
            'role' => 'super_admin',
            'status_akun' => 'aktif',
        ]);
        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole($adminRole);
        }

        // Operator
        $operator = User::firstOrCreate([
            'email' => 'operator@samudra.com'
        ], [
            'name' => 'Operator Samudra',
            'password' => Hash::make('operator123'),
            'role' => 'operator',
            'status_akun' => 'aktif',
        ]);
        if (!$operator->hasRole('operator')) {
            $operator->assignRole($operatorRole);
        }

        // ABK 1 (Dummy ABK dengan Profil Lengkap)
        $abk = User::firstOrCreate([
            'email' => 'abk@samudra.com'
        ], [
            'name' => 'Budi Santoso',
            'password' => Hash::make('abk123'),
            'role' => 'abk',
            'status_akun' => 'aktif',
        ]);
        if (!$abk->hasRole('abk')) {
            $abk->assignRole($abkRole);
        }

        // Profil ABK 1
        \App\Models\ProfilAbk::firstOrCreate([
            'user_id' => $abk->id
        ], [
            'nama_lengkap' => 'Budi Santoso',
            'tempat_lahir' => 'Pemalang',
            'tanggal_lahir' => '1995-08-17',
            'alamat' => 'Jl. Merdeka No. 12, Pemalang, Jawa Tengah',
            'nomor_hp' => '081234567890',
            'pengalaman_kerja' => '2 Tahun di Kapal Purshine Taiwan',
            'posisi_dilamar' => 'Taiwan - Purshine',
        ]);

        // Proses Pendaftaran ABK 1 (Tahap 1 Selesai, Tahap 2 Aktif)
        \App\Models\ProsesPendaftaran::firstOrCreate([
            'user_id' => $abk->id,
            'tahap' => 1,
        ], [
            'status' => 'Disetujui',
            'catatan' => 'Profil dan berkas administrasi telah disetujui.',
            'tanggal_update' => now(),
        ]);
        
        \App\Models\ProsesPendaftaran::firstOrCreate([
            'user_id' => $abk->id,
            'tahap' => 2,
        ], [
            'status' => 'Dalam Proses',
            'catatan' => 'Silakan unggah berkas Medical Check-Up (MCU).',
            'tanggal_update' => now(),
        ]);
    }
}
