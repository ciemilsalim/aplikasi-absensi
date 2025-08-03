<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Collection;
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
            session()->flash('error', 'Gagal memuat daftar backup.');
        }

        return view('admin.backup.index', compact('backups'));
    }

    /**
     * Membuat file backup baru dengan logika kondisional untuk lokal dan server.
     */
    public function create()
    {
        set_time_limit(300);

        // Cek apakah lingkungan saat ini adalah 'local'
        if (env('APP_ENV') === 'local') {
            // --- LOGIKA UNTUK LINGKUNGAN LOKAL (menggunakan exec) ---
            return $this->createBackupLocal();
        } else {
            // --- LOGIKA UNTUK LINGKUNGAN SERVER (menggunakan Artisan::call) ---
            return $this->createBackupServer();
        }
    }

    /**
     * Metode backup untuk lingkungan lokal menggunakan exec().
     */
    private function createBackupLocal()
    {
        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbHost = env('DB_HOST', '127.0.0.1');
        
        // Path spesifik untuk Laragon di Windows
        $pathToMysqldump = 'D:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe';

        $backupFolder = storage_path('app/SIASEK');
        if (!file_exists($backupFolder)) {
            mkdir($backupFolder, 0755, true);
        }

        $timestamp = now()->format('Y-m-d-His');
        $sqlFile = "backup-{$timestamp}.sql";
        $sqlPath = $backupFolder . DIRECTORY_SEPARATOR . $sqlFile;

        $command = "\"{$pathToMysqldump}\" --user={$dbUser} --password={$dbPass} --host={$dbHost} {$dbName} > \"{$sqlPath}\"";
        
        \exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            return redirect()->route('admin.backup.index')
                ->with('error', 'Gagal membuat backup database di lokal. Pastikan path mysqldump sudah benar.');
        }

        $zip = new ZipArchive;
        $zipFilename = "backup-db-{$timestamp}.zip";
        $zipPath = $backupFolder . DIRECTORY_SEPARATOR . $zipFilename;

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($sqlPath, $sqlFile);
            $zip->close();
            if (file_exists($sqlPath)) unlink($sqlPath);
            return redirect()->route('admin.backup.index')->with('success', 'Backup (lokal) berhasil dibuat: ' . $zipFilename);
        } else {
            return redirect()->route('admin.backup.index')->with('error', 'Gagal membuat file ZIP di lokal.');
        }
    }

    /**
     * Metode backup untuk lingkungan server menggunakan Artisan::call().
     */
    private function createBackupServer()
    {
        try {
            $filesBefore = $this->getBackupFiles();

            Artisan::call('backup:run', ['--only-db' => true]);

            $filesAfter = $this->getBackupFiles();
            $newFile = $filesAfter->diff($filesBefore)->first();

            if ($newFile) {
                Log::info("Backup via web (server) berhasil. File baru: " . $newFile);
                return redirect()->route('admin.backup.index')->with('success', 'Backup database (server) berhasil dibuat!');
            } else {
                $output = Artisan::output();
                Log::error("Proses backup server selesai tetapi tidak ada file baru. Output Artisan: " . $output);
                throw new \Exception('Tidak ada file backup baru yang dibuat. Pastikan path `mysqldump` sudah benar di `config/database.php` pada server Anda.');
            }
        } catch (\Exception $e) {
            Log::error('Backup Server Gagal: ' . $e->getMessage());
            return redirect()->route('admin.backup.index')->with('error', 'Gagal membuat backup di server. Error: ' . $e->getMessage());
        }
    }

    /**
     * Helper function untuk mendapatkan koleksi nama file backup.
     */
    private function getBackupFiles(): Collection
    {
        $diskName = 'local';
        $backupDirectory = 'SIASEK';
        $disk = Storage::disk($diskName);
        return collect($disk->allFiles($backupDirectory));
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
