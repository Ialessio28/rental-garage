<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        // Custom Blade directive @rupiah($amount): shows 1230000 as "Rp 1.230.000".
        // Defined once here and reused in every view 
        Blade::directive('rupiah', fn (string $amount) =>
            "<?php echo 'Rp ' . number_format((float) ($amount), 0, ',', '.'); ?>");
    }
}
