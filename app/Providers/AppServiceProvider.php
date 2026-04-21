<?php

namespace App\Providers;

use App\Models\Barang;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use App\Observers\BarangObserver;
use App\Observers\KlaimObserver;
use App\Observers\LaporanBarangHilangObserver;
use App\Services\Super\Notifications\SuperTopbarNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // Tambahkan ini
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    private const TOPBAR_NOTIFICATIONS_LIMIT = 8;
    private ?bool $isDatabaseResponsive = null;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paksa skema HTTP jika di lingkungan local/produksi tanpa SSL
        if (config('app.env') === 'local' || config('app.env') === 'production') {
            URL::forceScheme('http');
        }

        // Hubungkan proses bisnis dengan notifikasi admin.
        LaporanBarangHilang::observe(LaporanBarangHilangObserver::class);
        Barang::observe(BarangObserver::class);
        Klaim::observe(KlaimObserver::class);

        // Data notifikasi global untuk semua halaman admin.
        View::composer('admin.partials.topbar', function ($view) {
            $viewData = $view->getData();
            if (($viewData['hideTopActions'] ?? false) === true) {
                $view->with('adminNotifications', collect())
                    ->with('adminUnreadNotificationsCount', 0);
                return;
            }

            if (!$this->databaseResponsive()) {
                $view->with('adminNotifications', collect())
                    ->with('adminUnreadNotificationsCount', 0);
                return;
            }

            $admin = Auth::guard('admin')->user();

            if (!$admin) {
                $view->with('adminNotifications', collect())
                    ->with('adminUnreadNotificationsCount', 0);
                return;
            }

            $notifications = $admin->notifications()
                ->select(['id', 'admin_id', 'title', 'message', 'action_url', 'read_at', 'created_at'])
                ->latest('created_at')
                ->limit(self::TOPBAR_NOTIFICATIONS_LIMIT)
                ->get();

            $unreadCount = $admin->notifications()
                ->whereNull('read_at')
                ->count();

            $view->with('adminNotifications', $notifications)
                ->with('adminUnreadNotificationsCount', $unreadCount);
        });

        // Data notifikasi global untuk semua halaman dashboard user.
        View::composer('user.partials.topbar', function ($view) {
            $viewData = $view->getData();
            if (($viewData['hideTopActions'] ?? false) === true) {
                $view->with('userNotifications', collect())
                    ->with('userUnreadNotificationsCount', 0);
                return;
            }

            if (!$this->databaseResponsive()) {
                $view->with('userNotifications', collect())
                    ->with('userUnreadNotificationsCount', 0);
                return;
            }

            $user = Auth::user();

            if (!$user) {
                $view->with('userNotifications', collect())
                    ->with('userUnreadNotificationsCount', 0);
                return;
            }

            $notifications = $user->notifications()
                ->select(['id', 'user_id', 'title', 'message', 'action_url', 'read_at', 'created_at'])
                ->latest('created_at')
                ->limit(self::TOPBAR_NOTIFICATIONS_LIMIT)
                ->get();

            $unreadCount = $user->notifications()
                ->whereNull('read_at')
                ->count();

            $view->with('userNotifications', $notifications)
                ->with('userUnreadNotificationsCount', $unreadCount);
        });

        // Data notifikasi operasional untuk topbar super admin.
        View::composer('super.partials.topbar', function ($view) {
            $viewData = $view->getData();
            if (($viewData['hideTopActions'] ?? false) === true) {
                $view->with('superNotifications', collect())
                    ->with('superUnreadNotificationsCount', 0);
                return;
            }

            if (!$this->databaseResponsive()) {
                $view->with('superNotifications', collect())
                    ->with('superUnreadNotificationsCount', 0);
                return;
            }

            if (!Auth::guard('super_admin')->check()) {
                $view->with('superNotifications', collect())
                    ->with('superUnreadNotificationsCount', 0);
                return;
            }

            $notificationData = app(SuperTopbarNotificationService::class)->build();

            $view->with('superNotifications', $notificationData['notifications'])
                ->with('superUnreadNotificationsCount', $notificationData['unreadCount']);
        });
    }

    private function databaseResponsive(): bool
    {
        if ($this->isDatabaseResponsive !== null) {
            return $this->isDatabaseResponsive;
        }

        $defaultConnection = (string) config('database.default', 'mysql');
        $connection = (array) config('database.connections.' . $defaultConnection, []);
        $driver = (string) ($connection['driver'] ?? '');

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->isDatabaseResponsive = true;
            return true;
        }

        $host = (string) ($connection['host'] ?? '');
        $port = (int) ($connection['port'] ?? 3306);

        if ($host === '' || $port <= 0) {
            $this->isDatabaseResponsive = false;
            return false;
        }

        if (!in_array($host, ['127.0.0.1', 'localhost'], true)) {
            $this->isDatabaseResponsive = true;
            return true;
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, 0.25);
        if (!is_resource($socket)) {
            $this->isDatabaseResponsive = false;
            return false;
        }

        stream_set_timeout($socket, 0, 250000);
        $probe = @fread($socket, 1);
        $meta = stream_get_meta_data($socket);
        fclose($socket);

        $isResponsive = !($probe === false || ($probe === '' && (($meta['timed_out'] ?? false) === true)));
        $this->isDatabaseResponsive = $isResponsive;

        return $isResponsive;
    }
}
