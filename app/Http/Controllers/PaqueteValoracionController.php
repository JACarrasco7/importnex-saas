<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Support\ChromePath;
use App\Support\Esqueleto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Browsershot\Browsershot;

/**
 * Sirve los dos documentos de valoración como PDF (maquetados con Blade +
 * Browsershot a partir de los esqueletos .txt guardados por el importador).
 *
 *  - ficha-coche: documento del cliente. Puede colgar del expediente.
 *  - informe-interno: documento interno con costes y margen. SOLO equipo.
 */
class PaqueteValoracionController extends Controller
{
    private const DIR = 'cars/{id}/contenido';

    public function ficha(Car $car)
    {
        $contenido = $this->leer($car, 'ficha-publicitaria.txt');
        if ($contenido === null) {
            // Fallback: generar ficha mínima desde datos del coche.
            $contenido = $this->fichaDesdeCar($car);
        }

        $e = Esqueleto::desde($contenido);
        $qrUrl = $e->uno('QR') ?? route('public.car-request.index', ['slug' => 'jj-import-motors']);

        return $this->pdf('jj-import.ficha-coche', [
            'e' => $e,
            'car' => $car,
            'logo_base64' => $this->logo(),
            'fotos' => $this->fotos($car),
            'qr_svg' => QrCode::format('svg')
                ->size(220)->margin(1)->errorCorrection('H')
                ->backgroundColor(255, 255, 255)->color(6, 16, 31)
                ->generate($qrUrl),
            'telefono_1' => '675 70 14 39',
            'telefono_2' => '691 48 59 27',
            'email' => 'jjimportmotors@gmail.com',
        ], 'Ficha_'.$this->slug($car));
    }

    public function interno(Car $car)
    {
        // Requisito duro: solo el equipo interno (rol owner/operator autenticado).
        if (! auth()->check() || ! in_array(auth()->user()->role, ['owner', 'operator'], true)) {
            abort(403);
        }

        $contenido = $this->leer($car, 'informe-interno.txt');
        if ($contenido === null) {
            // Fallback: si el ZIP no trajo esqueleto, generamos uno mínimo
            // desde los datos del coche para que la ruta nunca quede vacía.
            $contenido = $this->esqueletoDesdeCar($car);
        }

        $e = Esqueleto::desde($contenido);

        return $this->pdf('jj-import.informe-interno', [
            'e' => $e,
            'car' => $car,
            'logo_base64' => $this->logo(),
            'telefono_1' => '675 70 14 39',
            'telefono_2' => '691 48 59 27',
            'email' => 'jjimportmotors@gmail.com',
        ], 'Informe_interno_'.$this->slug($car));
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /**
     * Genera un esqueleto ficha-publicitaria mínimo desde los datos del Car.
     * Usado cuando el ZIP no trajo contenido/*.txt.
     */
    private function fichaDesdeCar(Car $car): string
    {
        $lines = [];
        $lines[] = '# Ficha generada automáticamente (sin esqueleto del ZIP)';
        $lines[] = '[TITULO] '.trim($car->brand.' '.$car->model).' '.($car->year ?? '');

        // La vista hace filas('SPEC') → espera pares 'Etiqueta | Valor'.
        $spec = array_filter([
            'Año', (string) $car->year,
            'KM', number_format($car->mileage ?? 0, 0, ',', '.'),
            'Combustible', (string) $car->fuel,
            'Cambio', (string) $car->transmission,
            'Potencia', $car->cv ? $car->cv.' CV' : null,
            'Color', (string) $car->color,
        ]);
        $lines[] = '[SPEC] '.implode(' | ', $spec);

        if ($car->description) {
            $lines[] = '[DESCRIPCION] '.$car->description;
        }

        if ($car->purchase_price) {
            $lines[] = '[PRECIO] '.number_format((float) $car->purchase_price, 0, ',', '.');
        }

        return implode("\n", $lines);
    }

    private function esqueletoDesdeCar(Car $car): string
    {
        $lines = [];
        $lines[] = '# Informe interno generado automáticamente (sin esqueleto del ZIP)';
        $lines[] = '[TITULO] '.trim($car->brand.' '.$car->model).' '.($car->year ?? '');

        // La vista matchea verde/ambar/rojo (español), no los códigos del modelo.
        $semaforo = match (strtolower((string) $car->traffic_light)) {
            'green' => 'verde', 'amber' => 'ambar', 'red' => 'rojo',
            default => 'gris',
        };
        $lines[] = '[SEMAFORO] '.$semaforo;

        $spec = array_filter([
            'Marca', $car->brand,
            'Modelo', $car->model,
            'Año', (string) $car->year,
            'KM', number_format($car->mileage ?? 0, 0, ',', '.'),
            'Combustible', (string) $car->fuel,
            'Cambio', (string) $car->transmission,
            'Potencia', $car->cv ? $car->cv.' CV' : null,
            'CO2', $car->co2 ? $car->co2.' g/km' : null,
            'Color', (string) $car->color,
            'VIN', (string) $car->vin,
        ]);
        $lines[] = '[SPEC] '.implode(' | ', $spec);

        if ($car->description) {
            $lines[] = '[DESCRIPCION] '.$car->description;
        }

        // Costes
        $costes = array_filter([
            'Precio coche|'.($car->purchase_price ?? 0),
            'Transporte|'.($car->transport ?? 0),
            'ITV/matriculación|'.($car->itv_fee ?? 0),
            'Tasa DGT|'.($car->dgt_fees ?? 0),
            'Honorarios/gestoría|'.($car->professional_fees ?? 0),
            'Depósito|'.($car->deposit ?? 0),
        ], fn ($v) => $v !== '');
        foreach ($costes as $c) {
            $lines[] = '[COSTE] '.$c;
        }

        if ($car->verdict) {
            $lines[] = '[DICTAMEN] '.$car->verdict;
            if ($car->verdict_confidence) {
                $lines[] = '[CONFIANZA] '.$car->verdict_confidence;
            }
        }
        if ($car->verdict_reasoning) {
            $lines[] = '[RAZONAMIENTO] '.$car->verdict_reasoning;
        }
        if ($car->market_avg) {
            $lines[] = '[MERCADO] Media | '.number_format((float) $car->market_avg, 0, ',', '.');
        }

        return implode("\n", $lines);
    }

    private function leer(Car $car, string $archivo): ?string
    {
        $ruta = str_replace('{id}', (string) $car->id, self::DIR).'/'.$archivo;

        // Primero en disco local (privado); tolera si por compatibilidad está en public.
        foreach (['local', 'public'] as $disco) {
            $disk = Storage::disk($disco);
            if ($disk->exists($ruta)) {
                return $disk->get($ruta);
            }
        }

        return null;
    }

    private function logo(): ?string
    {
        $path = public_path('images/jj-import/logo-horizontal-blanco.png');

        return file_exists($path)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($path))
            : null;
    }

    /** @return array<int, string> URLs base64 de las fotos del coche. */
    private function fotos(Car $car): array
    {
        $car->load('photos');

        $result = [];
        foreach ($car->photos()->orderBy('sort_order')->get() as $foto) {
            $abs = str_starts_with($foto->url, '/storage/')
                ? public_path($foto->url)
                : storage_path('app/public/'.ltrim($foto->url, '/'));

            if (file_exists($abs)) {
                $mime = mime_content_type($abs) ?: 'image/jpeg';
                $result[] = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($abs));
            }
        }

        return $result;
    }

    private function slug(Car $car): string
    {
        return preg_replace('/[^A-Za-z0-9]+/', '-', trim($car->brand.' '.$car->model)) ?? 'coche';
    }

    private function pdf(string $vista, array $datos, string $nombreArchivo)
    {
        $html = View::make($vista, $datos)->render();

        $chrome = ChromePath::resolve();
        if (! $chrome) {
            return response($html, 200)->header('Content-Type', 'text/html');
        }

        $pdfPath = storage_path('app/private/tmp/'.uniqid('pdf_', true).'.pdf');
        if (! is_dir(dirname($pdfPath))) {
            mkdir(dirname($pdfPath), 0755, true);
        }

        try {
            Browsershot::html($html)
                ->setNodeBinary(ChromePath::nodeBinary())
                ->setChromePath($chrome)
                ->format('A4')
                ->landscape(false)
                ->margins(0, 0, 0, 0)
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->deviceScaleFactor(2)
                ->scale(1)
                ->savePdf($pdfPath);

            $download = response()->download($pdfPath, $nombreArchivo.'.pdf');
            register_shutdown_function(fn () => @unlink($pdfPath));

            return $download;
        } catch (\Throwable $e) {
            @unlink($pdfPath);
            Log::warning('PaqueteValoracion PDF failed', ['error' => $e->getMessage()]);

            return response($html, 200)->header('Content-Type', 'text/html');
        }
    }
}
