<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule; // <-- IMPORTANTE
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Suscripcion;

class Kernel extends ConsoleKernel
{
    // (opcional) zona horaria del scheduler
    protected function scheduleTimezone(): ?\DateTimeZone
    {
        return new \DateTimeZone('America/Mexico_City');
    }

    // Firma correcta (sin backslash si ya importaste Schedule)
    protected function schedule(Schedule $schedule): void
    {
        // Marcar suscripciones vencidas diariamente
        $schedule->call(function () {
            Suscripcion::where('estado', 'activa')
                ->where('fecha_vencimiento', '<', now())
                ->update(['estado' => 'vencida']);
        })->dailyAt('02:15'); // ajusta la hora si quieres
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
