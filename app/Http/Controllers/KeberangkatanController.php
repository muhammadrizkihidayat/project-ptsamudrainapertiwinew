<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Keberangkatan;
use App\Models\ProsesPendaftaran;
use App\Models\ActivityLog;
use App\Models\Notification;

class KeberangkatanController extends Controller
{
    public function save(Request $request, $id)
    {
        $request->validate([
            'maskapai' => 'required|string|max:100',
            'nomor_penerbangan' => 'required|string|max:50',
            'tanggal_berangkat' => 'required|date_format:Y-m-d\TH:i',
            'negara_tujuan' => 'required|string|max:100',
            'status' => 'required|string|in:Tiket Diproses,Jadwal Terbit,Berangkat,On Board',
        ]);

        $keberangkatan = Keberangkatan::findOrFail($id);
        $candidate = $keberangkatan->user;

        $keberangkatan->maskapai = $request->maskapai;
        $keberangkatan->nomor_penerbangan = $request->nomor_penerbangan;
        $keberangkatan->tanggal_berangkat = $request->tanggal_berangkat;
        $keberangkatan->negara_tujuan = $request->negara_tujuan;
        $keberangkatan->status = $request->status;
        $keberangkatan->save();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Memperbarui status keberangkatan ' . $candidate->name . ' menjadi: ' . $request->status . '.',
        ]);

        Notification::create([
            'user_id' => $candidate->id,
            'judul' => 'Informasi Keberangkatan: ' . $request->status,
            'pesan' => 'Rincian penerbangan diperbarui. Maskapai: ' . $request->maskapai . ', No Penerbangan: ' . $request->nomor_penerbangan . ', Keberangkatan: ' . $request->tanggal_berangkat . ', Tujuan: ' . $request->negara_tujuan . ', Status: ' . $request->status,
        ]);

        // Reflect in Proses Pendaftaran
        $statusProses = ($request->status === 'On Board') ? 'Selesai' : 'Dalam Proses';
        $catatanTahap = 'Keberangkatan diperbarui menjadi: ' . $request->status . '. ' . 
            ($request->status === 'On Board' ? 'Calon ABK telah naik ke kapal di negara tujuan. Proses rekrutmen selesai.' : 'Menunggu jadwal terbang.');

        ProsesPendaftaran::create([
            'user_id' => $candidate->id,
            'tahap' => 6,
            'status' => $statusProses,
            'catatan' => $catatanTahap,
            'tanggal_update' => now(),
        ]);

        alert()->success('Berhasil', 'Informasi keberangkatan berhasil disimpan!');
        return redirect()->back();
    }
}
