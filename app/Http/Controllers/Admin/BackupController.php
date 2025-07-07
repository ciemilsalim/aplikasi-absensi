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
            $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
            $backupDirectory = config('backup.backup.destination.path');
            $disk = Storage::disk($diskName);

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
     * Membuat file backup baru menggunakan Spatie Backup via Symfony Process.
     */
    // public function create()
    // {
    //     try {
    //         set_time_limit(3000);

    //         $command = [
    //             'php',
    //             base_path('artisan'),
    //             'backup:run',
    //             '--only-db',
    //         ];

    //         $process = new Process($command, base_path());
    //         $process->setTimeout(3000);
    //         $process->run();

    //         if ($process->isSuccessful()) {
    //             Log::info("Backup via web berhasil. Output: " . $process->getOutput());
    //             return redirect()->route('admin.backup.index')->with('success', 'Backup database berhasil dibuat!');
    //         } else {
    //             throw new ProcessFailedException($process);
    //         }
            
    //     } catch (ProcessFailedException $exception) {
    //         // PERBAIKAN: Menangkap dan menampilkan pesan error yang lebih spesifik
    //         $process = $exception->getProcess();
    //         $errorOutput = $process->getErrorOutput();
            
    //         Log::error('Backup Gagal: ' . $errorOutput);
    //         return redirect()->route('admin.backup.index')->with('error', 'Gagal membuat backup: ' . $errorOutput);
    //     } catch (\Exception $e) {
    //         Log::error('Backup Gagal (Exception Umum): ' . $e->getMessage());
    //         return redirect()->route('admin.backup.index')->with('error', 'Gagal membuat backup: ' . $e->getMessage());
    //     }


    //     try {
    //         Artisan::call('backup:run --only-db');

    //         return response()->json([
    //             'message' => 'Backup database berhasil!',
    //             'output' => Artisan::output(),
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Backup database gagal!',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Mengunduh file backup.
     */
    public function download($filename)
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $path = config('backup.backup.destination.path') . '/' . $filename;

        if (!Storage::disk($diskName)->exists($path)) {
            abort(404);
        }
        
        return Storage::disk($diskName)->download($path);
    }

    /**
     * Menghapus file backup.
     */
    public function delete($filename)
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $path = config('backup.backup.destination.path') . '/' . $filename;

        if (Storage::disk($diskName)->exists($path)) {
            Storage::disk($diskName)->delete($path);
            return redirect()->route('admin.backup.index')->with('success', 'Backup berhasil dihapus.');
        }

        return redirect()->route('admin.backup.index')->with('error', 'File backup tidak ditemukan.');
    }

   public function manualBackupDatabase()
    {
        set_time_limit(300); // Hindari timeout 60 detik

        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $host   = env('DB_HOST', '127.0.0.1');
        $pathToMysqldump = 'D:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe';

        $backupFolder = storage_path('app/SIASEK');
        if (!file_exists($backupFolder)) {
            mkdir($backupFolder, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $sqlFile = "backup-{$timestamp}.sql";
        $sqlPath = $backupFolder . DIRECTORY_SEPARATOR . $sqlFile;

        // === 1. BACKUP DATABASE ===
        $command = "\"{$pathToMysqldump}\" --user={$dbUser} --password={$dbPass} --host={$host} {$dbName} > \"{$sqlPath}\"";
        exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            return redirect()->route('admin.backup.index')
                ->with('error', 'Gagal membuat backup database (code: ' . $resultCode . ')');
        }

        // === 2. BUAT FILE ZIP ===
        $zip = new ZipArchive;
        $zipFilename = "full-backup-{$timestamp}.zip";
        $zipPath = $backupFolder . DIRECTORY_SEPARATOR . $zipFilename;

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            // Tambahkan file SQL ke dalam ZIP
            $zip->addFile($sqlPath, $sqlFile);

            // Tambahkan seluruh isi project (kecuali folder tertentu)
            $rootPath = base_path();
            $exclude = ['vendor', 'node_modules', 'storage', '.git'];

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $relativePath = str_replace($rootPath . DIRECTORY_SEPARATOR, '', $filePath);

                // Lewati folder yang dikecualikan
                foreach ($exclude as $ex) {
                    if (str_starts_with($relativePath, $ex . '/') || str_starts_with($relativePath, $ex . '\\')) {
                        continue 2;
                    }
                }

                $zip->addFile($filePath, $relativePath);
            }

            $zip->close();

            // Hapus file .sql setelah masuk ke dalam ZIP
            if (file_exists($sqlPath)) {
                unlink($sqlPath);
            }

            return redirect()->route('admin.backup.index')
                ->with('success', 'Backup lengkap berhasil: ' . $zipFilename);
        } else {
            return redirect()->route('admin.backup.index')
                ->with('error', 'Gagal membuat file ZIP.');
        }
    }

}
