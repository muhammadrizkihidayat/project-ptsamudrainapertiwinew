<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Diklat;
use App\Models\ProsesPendaftaran;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class DiklatController extends Controller
{
    public function schedule(Request $request, $id)
    {
        $request->validate([
            'batch' => 'required|string|max:50',
            'lokasi' => 'required|string|max:100',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai',
        ]);

        $diklat = Diklat::findOrFail($id);
        $candidate = $diklat->user;

        $diklat->batch = $request->batch;
        $diklat->lokasi = $request->lokasi;
        $diklat->tanggal_mulai = $request->tanggal_mulai;
        $diklat->tanggal_selesai = $request->tanggal_selesai;
        $diklat->status = 'Terjadwal';
        $diklat->save();

        // Update Proses Pendaftaran
        ProsesPendaftaran::create([
            'user_id' => $candidate->id,
            'tahap' => 3,
            'status' => 'Terjadwal',
            'catatan' => 'Jadwal Diklat telah diatur pada ' . $request->batch . ' di ' . $request->lokasi . ' (' . $request->tanggal_mulai . ' s/d ' . $request->tanggal_selesai . ').',
            'tanggal_update' => now(),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'aktivitas' => 'Mengatur jadwal Diklat ' . $candidate->name . ' (' . $request->batch . ').',
        ]);

        Notification::create([
            'user_id' => $candidate->id,
            'judul' => 'Jadwal Diklat Diterbitkan',
            'pesan' => 'Jadwal Diklat Anda telah diterbitkan. Anda tergabung dalam ' . $request->batch . ' yang akan dilaksanakan di ' . $request->lokasi . ' mulai tanggal ' . $request->tanggal_mulai . ' sampai ' . $request->tanggal_selesai . '.',
        ]);

        alert()->success('Berhasil', 'Jadwal Diklat berhasil diterbitkan!');
        return redirect()->back();
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Sedang Mengikuti Diklat,Lulus Diklat,Tidak Lulus Diklat',
        ]);

        $diklat = Diklat::findOrFail($id);
        $candidate = $diklat->user;

        $diklat->status = $request->status;
        $diklat->save();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'aktivitas' => 'Memperbarui status Diklat ' . $candidate->name . ' menjadi: ' . $request->status . '.',
        ]);

        Notification::create([
            'user_id' => $candidate->id,
            'judul' => 'Perubahan Status Diklat: ' . $request->status,
            'pesan' => 'Status Diklat Anda telah diperbarui menjadi ' . $request->status . '.',
        ]);

        if ($request->status === 'Lulus Diklat') {
            // Auto advance candidate to Stage 4 - Pengurusan Dokumen Pelaut
            ProsesPendaftaran::create([
                'user_id' => $candidate->id,
                'tahap' => 4,
                'status' => 'Dalam Proses',
                'catatan' => 'Dinyatakan Lulus Diklat. Silakan lakukan pengurusan Paspor, BST, dan Buku Pelaut secara mandiri, kemudian unggah buktinya.',
                'tanggal_update' => now(),
            ]);

            // Create initial empty marine docs row template
            $types = ['paspor', 'bst', 'buku_pelaut'];
            foreach ($types as $type) {
                \App\Models\DokumenPelaut::firstOrCreate(
                    ['user_id' => $candidate->id, 'jenis_dokumen' => $type],
                    ['status_verifikasi' => 'Menunggu Pengurusan Dokumen']
                );
            }

            Notification::create([
                'user_id' => $candidate->id,
                'judul' => 'Mulai Tahap 4: Pengurusan Dokumen Pelaut',
                'pesan' => 'Selamat atas kelulusan Diklat Anda! Sekarang Anda memasuki Tahap 4 (Pengurusan Dokumen Pelaut). Silakan mengurus Paspor, BST, dan Buku Pelaut Anda secara mandiri lalu unggah buktinya ke sistem.',
            ]);
        } else {
            $statusProses = $request->status === 'Sedang Mengikuti Diklat' ? 'Dalam Proses' : 'Ditolak';
            ProsesPendaftaran::create([
                'user_id' => $candidate->id,
                'tahap' => 3,
                'status' => $statusProses,
                'catatan' => 'Status Diklat terbaru: ' . $request->status . '.',
                'tanggal_update' => now(),
            ]);
        }

        alert()->success('Berhasil', 'Status Diklat berhasil diperbarui!');
        return redirect()->back();
    }
}
