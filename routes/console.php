<?php

use Illuminate\Support\Facades\Schedule;

/*
| Tugas terjadwal. Di hosting, cukup satu cron per menit:
|   * * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1
| (bisa dipakai di shared hosting, tidak butuh proses yang menyala terus).
*/

// Mutasi usia otomatis (SKF-05) setiap hari setelah tengah malam.
Schedule::command('warga:perbarui-kategori')->dailyAt('00:05')->withoutOverlapping();

// Penutupan otomatis kegiatan setelah 24 jam (revisi). Cadangan; juga dicek saat halaman dibuka.
Schedule::command('kegiatan:tutup-otomatis')->everyFiveMinutes()->withoutOverlapping();
