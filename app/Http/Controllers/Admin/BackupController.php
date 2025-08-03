<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupController extends Controller
{
    /**
     * Menampilkan daftar file backup yang sudah ada.
     */
    public function index()
    {
        $backups = [];
        try {
            $backupDirectory = 'SIASEK'; // Nama folder di storage/app/
            $disk = Storage::disk('local');

            if (!$disk->exists($backupDirectory)) {
                $disk->makeDirectory($backupDirectory);
            }

            $files = $disk->allFiles($backupDirectory);
            
            $backups = collect($files)
                ->map(function ($file) use ($disk) {
                    if (!$disk->exists($file)) return null;
                    return [
                        'name' => basename($file),
                        'size' => round($disk->size($file) / 1024, 2),
                        'date' => date('Y-m-d H:i:s', $disk->lastModified($file)),
                    ];
                })
                ->filter()
                ->sortByDesc('date')
                ->values();

        } catch (\Exception $e) {
            Log::error('Gagal membaca daftar backup: ' . $e->getMessage());
            session()->flash('error', 'Gagal memuat daftar backup.');
        }

        return view('admin.backup.index', compact('backups'));
    }

    /**
     * Membuat file backup baru menggunakan exec() untuk lingkungan server.
     */
    public function create()
    {
        set_time_limit(300); // Hindari timeout

        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbHost = env('DB_HOST', '127.0.0.1');
        
        // PERBAIKAN: Menggunakan path generik untuk mysqldump
        $pathToMysqldump = 'mysqldump';

        $backupFolder = storage_path('app/SIASEK');
        if (!file_exists($backupFolder)) {
            mkdir($backupFolder, 0755, true);
        }

        $timestamp = now()->format('Y-m-d-His');
        $sqlFile = "backup-{$timestamp}.sql";
        $sqlPath = $backupFolder . DIRECTORY_SEPARATOR . $sqlFile;

        // === 1. BACKUP DATABASE ===
        // PERBAIKAN: Menghapus kutip ganda yang tidak perlu untuk kompatibilitas server
        $command = "{$pathToMysqldump} --user={$dbUser} --password={$dbPass} --host={$dbHost} {$dbName} > {$sqlPath}";
        
        \exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            return redirect()->route('admin.backup.index')
                ->with('error', 'Gagal membuat backup database. Fungsi `exec` mungkin dinonaktifkan di server Anda.');
        }

        // === 2. BUAT FILE ZIP ===
        $zip = new ZipArchive;
        $zipFilename = "backup-db-{$timestamp}.zip";
        $zipPath = $backupFolder . DIRECTORY_SEPARATOR . $zipFilename;

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            // Hanya tambahkan file SQL ke dalam ZIP
            $zip->addFile($sqlPath, $sqlFile);
            $zip->close();

            // Hapus file .sql setelah masuk ke dalam ZIP
            if (file_exists($sqlPath)) {
                unlink($sqlPath);
            }

            return redirect()->route('admin.backup.index')
                ->with('success', 'Backup database berhasil dibuat: ' . $zipFilename);
        } else {
            return redirect()->route('admin.backup.index')
                ->with('error', 'Gagal membuat file ZIP.');
        }
    }

    /**
     * Mengunduh file backup.
     */
    public function download($filename)
    {
        $path = 'SIASEK/' . $filename;
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File backup tidak ditemukan.');
        }
        return Storage::disk('local')->download($path);
    }

    /**
     * Menghapus file backup.
     */
    public function delete($filename)
    {
        $path = 'SIASEK/' . $filename;
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
            return redirect()->route('admin.backup.index')->with('success', 'Backup berhasil dihapus.');
        }
        return redirect()->route('admin.backup.index')->with('error', 'File backup tidak ditemukan.');
    }
}
