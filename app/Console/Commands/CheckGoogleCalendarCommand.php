<?php

namespace App\Console\Commands;

use App\Exceptions\GoogleCalendarUnavailable;
use App\Models\GoogleAccount;
use App\Support\Google\GoogleCalendar;
use Illuminate\Console\Command;
use Throwable;

class CheckGoogleCalendarCommand extends Command
{
    protected $signature = 'google:calendar-check
                            {email : Alamat akun Google yang mau diuji}
                            {--keep : Biarkan event ujinya di kalender}';

    protected $description = 'Uji koneksi Google Calendar dengan membuat satu event sungguhan';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $account = GoogleAccount::query()->where('email', $email)->first();

        if ($account === null) {
            $this->components->error("Belum ada akun Google tersimpan untuk {$email}.");
            $this->components->info('Akun tersimpan setelah orangnya sekali masuk lewat tombol Google.');

            return self::FAILURE;
        }

        if (! $account->hasScope(GoogleCalendar::SCOPE)) {
            $this->components->error("Akun {$email} belum menyetujui izin kalender.");
            $this->components->info('Pastikan GOOGLE_CALENDAR_SCOPE=true, lalu minta yang bersangkutan keluar dan masuk lagi.');

            return self::FAILURE;
        }

        $start = now()->addHour()->startOfHour();

        try {
            $event = GoogleCalendar::for($account)->createEvent(GoogleCalendar::event(
                title: 'Uji koneksi Operations IM',
                start: $start,
                end: $start->copy()->addMinutes(30),
                description: 'Event ini dibuat oleh perintah google:calendar-check. Aman dihapus.',
            ));
        } catch (GoogleCalendarUnavailable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->components->error('Google menolak permintaannya: '.$e->getMessage());
            $this->components->info('Kalau pesannya menyebut API belum aktif, aktifkan Google Calendar API di Cloud Console.');

            return self::FAILURE;
        }

        $this->components->info("Berhasil. Event dibuat di kalender {$email}.");
        $this->components->twoColumnDetail('Waktu', $start->format('d M Y H:i'));
        $this->components->twoColumnDetail('Tautan', (string) ($event['htmlLink'] ?? '-'));

        if ($this->option('keep')) {
            return self::SUCCESS;
        }

        GoogleCalendar::for($account)->deleteEvent((string) $event['id']);

        $this->components->info('Event uji sudah dihapus lagi. Pakai --keep kalau ingin melihatnya sendiri.');

        return self::SUCCESS;
    }
}
