<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
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
            // PERBAIKAN: Menggunakan path storage yang lebih konsisten
            $backupDirectory = 'SIASEK'; 
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
            session()->flash('error', 'Gagal memuat daftar backup. Pastikan konfigurasi sudah benar.');
        }

        return view('admin.backup.index', compact('backups'));
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

   public function create()
    {
        set_time_limit(300); // Hindari timeout

        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $host   = env('DB_HOST', '127.0.0.1');
        
        // PERBAIKAN: Menghapus path hardcoded dan mengandalkan PATH environment
        // Ini lebih portabel dan tidak bergantung pada struktur folder lokal (seperti D:\laragon)
        $pathToMysqldump = 'mysqldump'; 

        $backupFolder = storage_path('app/SIASEK');
        if (!file_exists($backupFolder)) {
            mkdir($backupFolder, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $sqlFile = "backup-{$timestamp}.sql";
        $sqlPath = $backupFolder . DIRECTORY_SEPARATOR . $sqlFile;

        // === 1. BACKUP DATABASE ===
        $command = "\"{$pathToMysqldump}\" --user={$dbUser} --password={$dbPass} --host={$host} {$dbName} > \"{$sqlPath}\"";
        
        // PERBAIKAN: Menambahkan backslash `\` sebelum `exec`
        \exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            return redirect()->route('admin.backup.index')
                ->with('error', 'Gagal membuat backup database. Pastikan mysqldump ada di PATH environment server Anda.');
        }

        // === 2. BUAT FILE ZIP ===
        $zip = new ZipArchive;
        $zipFilename = "full-backup-{$timestamp}.zip";
        $zipPath = $backupFolder . DIRECTORY_SEPARATOR . $zipFilename;

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
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
}
