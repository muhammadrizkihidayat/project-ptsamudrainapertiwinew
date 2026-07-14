<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProfilAbk;
use App\Models\ProsesPendaftaran;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    public function index()
    {
        return view('auth.register');
    }

    public function handleregister(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'abk',
            'status_akun' => 'aktif',
        ]);

        // Assign Spatie Role - ensure role exists before assigning
        $abkRole = Role::firstOrCreate(['name' => 'abk', 'guard_name' => 'web']);
        $user->assignRole($abkRole);

        // Create associated profile
        ProfilAbk::create([
            'user_id' => $user->id,
            'nama_lengkap' => $request->name,
        ]);

        // Create associated recruitment status (Stage 1)
        ProsesPendaftaran::create([
            'user_id' => $user->id,
            'tahap' => 1,
            'status' => 'Dalam Proses',
            'catatan' => 'Pendaftaran berhasil. Silakan lengkapi profil dan upload dokumen wajib.',
        ]);

        // Log Activity
        ActivityLog::create([
            'user_id' => $user->id,
            'aktivitas' => 'Melakukan pendaftaran akun baru.',
        ]);

        // Login user
        Auth::login($user);

        return redirect('/dashboard');
    }
}
