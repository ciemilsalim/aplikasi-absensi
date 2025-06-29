<?php

return [
    'backup' => [
        /*
         * Nama aplikasi Anda. Nama ini akan digunakan dalam notifikasi dan
         * sebagai awalan untuk nama file backup.
         */
        'name' => config('app.name', 'laravel-backup'),

        'source' => [
            'files' => [
                /*
                 * Direktori yang ingin Anda backup.
                 */
                'include' => [
                    base_path(),
                ],

                /*
                 * File-file ini akan dikecualikan dari backup.
                 */
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],

            /*
             * Database yang ingin Anda backup.
             */
            'databases' => [
                'mysql',
            ],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_extension' => '',

        'destination' => [
            /*
             * Nama disk tempat backup akan disimpan. Secara default 'local'
             * yang mengarah ke storage/app/.
             */
            'disks' => [
                'local',
            ],
            
            /*
             * Path di dalam disk tempat file backup akan disimpan.
             * Jika filesystems.php Anda mengarah ke 'storage/app/private', maka
             * path ini akan menjadi 'storage/app/private/[nama_aplikasi]'.
             */
            'path' => config('app.name', 'laravel-backup'),
        ],

        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => env('BACKUP_PASSWORD'),
        'encryption' => 'default',
    ],

    /*
     * Konfigurasi notifikasi yang lengkap untuk mencegah error.
     */
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => ['mail'],
        ],
        
        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => 'admin@example.com', // Ganti dengan email admin Anda

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],

    /*
     * Konfigurasi untuk dump database.
     */
    'dump' => [
        'mysql' => [
            'use_single_transaction' => true,
            'timeout' => 60 * 5,
            'exclude_tables' => [],
            'add_extra_options' => [],
            // 'dump_binary_path' => 'D:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/',
            // Path ini sangat penting dan diambil dari file .env Anda.
            'dump_binary_path' => env('DB_DUMP_BINARY_PATH', ''),
        ],
    ],
];
