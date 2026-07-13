<?php

namespace App\Jobs;

use App\Models\SiteVisit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Resuelve país/ciudad de una visita a partir de su IP.
 * Corre en cola para no meterle latencia a la carga de la landing.
 */
class GeolocateSiteVisit implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 15;

    public function __construct(public int $visitId, public string $ip) {}

    public function handle(): void
    {
        if (! config('services.geoip.enabled')) {
            return;
        }

        $visit = SiteVisit::find($this->visitId);
        if (! $visit || $visit->country_code) {
            return;   // borrada, o ya geolocalizada
        }

        $geo = $this->resolver($this->ip);
        if (! $geo) {
            return;
        }

        $visit->forceFill($geo)->save();
    }

    /**
     * Consulta el proveedor de geolocalización. Cachea por IP 30 días:
     * la ubicación de una IP no cambia seguido y así no gastamos cuota.
     *
     * @return array{country_code:string, country:string, city:?string}|null
     */
    private function resolver(string $ip): ?array
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;   // localhost o red interna: no hay nada que geolocalizar
        }

        return Cache::remember('geoip:'.$ip, now()->addDays(30), function () use ($ip) {
            try {
                $res = Http::timeout(8)
                    ->acceptJson()
                    ->get(rtrim((string) config('services.geoip.url'), '/').'/'.$ip);

                if (! $res->successful() || $res->json('success') !== true) {
                    return null;
                }

                $cc = strtoupper((string) $res->json('country_code'));
                if (strlen($cc) !== 2) {
                    return null;
                }

                return [
                    'country_code' => $cc,
                    'country' => (string) $res->json('country'),
                    'city' => $res->json('city') ?: null,
                ];
            } catch (\Throwable $e) {
                report($e);

                return null;
            }
        });
    }
}
