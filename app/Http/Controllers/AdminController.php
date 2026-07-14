<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\ProfilAbk;
use App\Models\ProsesPendaftaran;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function users()
    {
        $users = User::orderBy('created_at', 'desc')->get();
        return view('admin.users_index', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users_create');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:super_admin,operator,abk',
            'status_akun' => 'required|string|in:aktif,nonaktif',
        ], [
            'name.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.unique' => 'Email sudah terdaftar',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status_akun' => $request->status_akun,
        ]);

        $user->assignRole($request->role);

        // If candidate, initialize profile and recruitment status
        if ($request->role === 'abk') {
            ProfilAbk::create([
                'user_id' => $user->id,
                'nama_lengkap' => $request->name,
            ]);

            ProsesPendaftaran::create([
                'user_id' => $user->id,
                'tahap' => 1,
                'status' => 'Dalam Proses',
                'catatan' => 'Akun dibuat oleh administrator.',
            ]);
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'aktivitas' => 'Membuat akun baru (' . strtoupper($request->role) . '): ' . $request->name . '.',
        ]);

        alert()->success('Berhasil', 'Pengguna baru berhasil ditambahkan!');
        return redirect()->route('admin.users');
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users_edit', compact('user'));
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|string|in:super_admin,operator,abk',
            'status_akun' => 'required|string|in:aktif,nonaktif',
            'password' => 'nullable|string|min:6',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->status_akun = $request->status_akun;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // Change role if needed
        if ($user->role !== $request->role) {
            $user->syncRoles([$request->role]);
            $user->role = $request->role;
        }

        $user->save();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'aktivitas' => 'Memperbarui informasi akun ' . $user->name . '.',
        ]);

        alert()->success('Berhasil', 'Informasi pengguna berhasil diperbarui!');
        return redirect()->route('admin.users');
    }

    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === Auth::id()) {
            alert()->error('Error', 'Anda tidak bisa menghapus akun Anda sendiri!');
            return redirect()->back();
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'aktivitas' => 'Menghapus akun ' . $user->name . ' (' . strtoupper($user->role) . ').',
        ]);

        $user->delete();

        alert()->success('Berhasil', 'Pengguna berhasil dihapus!');
        return redirect()->route('admin.users');
    }

    public function logs()
    {
        $logs = ActivityLog::with('user')->orderBy('created_at', 'desc')->paginate(30);
        return view('admin.logs', compact('logs'));
    }
}
