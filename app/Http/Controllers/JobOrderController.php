<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\JobOrder;
use App\Models\ProsesPendaftaran;
use App\Models\ActivityLog;
use App\Models\Notification;

class JobOrderController extends Controller
{
    public function assign(Request $request, $id)
    {
        $request->validate([
            'nama_kapal' => 'required|string|max:100',
            'negara_tujuan' => 'required|string|max:100',
            'posisi' => 'required|string|max:100',
            'status_job' => 'required|string|in:Waiting List,Job Tersedia,Dipilih untuk Job,Persiapan Keberangkatan',
        ]);

        $job = JobOrder::findOrFail($id);
        $candidate = $job->user;

        $job->nama_kapal = $request->nama_kapal;
        $job->negara_tujuan = $request->negara_tujuan;
        $job->posisi = $request->posisi;
        $job->status_job = $request->status_job;
        $job->save();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Memperbarui job order ' . $candidate->name . ': ' . $request->nama_kapal . ' (' . $request->status_job . ').',
        ]);

        Notification::create([
            'user_id' => $candidate->id,
            'judul' => 'Job Order Diperbarui: ' . $request->status_job,
            'pesan' => 'Informasi penempatan kapal Anda diperbarui. Kapal: ' . $request->nama_kapal . ', Negara Tujuan: ' . $request->negara_tujuan . ', Posisi: ' . $request->posisi . ', Status: ' . $request->status_job,
        ]);

        if ($request->status_job === 'Persiapan Keberangkatan') {
            // Auto advance candidate to Stage 6 - Keberangkatan
            $latest = ProsesPendaftaran::where('user_id', $candidate->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $currentStage = $latest ? $latest->tahap : 1;

            if ($currentStage < 6) {
                ProsesPendaftaran::create([
                    'user_id' => $candidate->id,
                    'tahap' => 6,
                    'status' => 'Dalam Proses',
                    'catatan' => 'Job kapal disetujui. Memasuki tahap persiapan keberangkatan dan tiket.',
                    'tanggal_update' => now(),
                ]);

                // Create initial flight details
                \App\Models\Keberangkatan::firstOrCreate(
                    ['user_id' => $candidate->id],
                    [
                        'status' => 'Tiket Diproses',
                        'negara_tujuan' => $request->negara_tujuan
                    ]
                );

                Notification::create([
                    'user_id' => $candidate->id,
                    'judul' => 'Mulai Tahap 6: Persiapan Keberangkatan',
                    'pesan' => 'Selamat! Job penempatan Anda telah disetujui. Sekarang Anda memasuki Tahap 6 (Keberangkatan). Operator sedang memproses tiket penerbangan Anda.',
                ]);
            }
        } else {
            ProsesPendaftaran::create([
                'user_id' => $candidate->id,
                'tahap' => 5,
                'status' => 'Dalam Proses',
                'catatan' => 'Status job order terbaru: ' . $request->status_job . '.',
                'tanggal_update' => now(),
            ]);
        }

        alert()->success('Berhasil', 'Job penempatan berhasil diperbarui!');
        return redirect()->back();
    }
}
