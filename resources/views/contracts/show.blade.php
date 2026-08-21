<!doctype html>
<html lang="{{ $accepted ? 'es' : app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $ui['titulo'] }} · JJ Import Motors</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        estoril: { 50:'#eef4fb', 100:'#dbe8f4', 200:'#b5cfe8', 500:'#3a6ea5', 600:'#1f4e79', 700:'#173f63', 800:'#0e2c46' },
                        asphalt: { 50:'#f3f4f7', 100:'#e6e8ee', 200:'#c4c9d4', 400:'#7a8398', 600:'#4a5266', 700:'#2f3548', 800:'#1c2030', 900:'#0f121c' },
                        platinum: { 50:'#f7f8fa', 100:'#eef0f4' },
                    },
                },
            },
        };
    </script>
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-platinum-50 to-white text-asphalt-800">

<header class="border-b border-estoril-100/40 bg-white/80 backdrop-blur">
    <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
            <img src="/images/jj-import/logo-insignia.png" alt="JJ Import Motors" class="h-10 w-auto">
        </div>
        <span class="text-xs uppercase tracking-widest text-estoril-700">Contrato · JJ Import Motors</span>
    </div>
</header>

<main class="mx-auto max-w-4xl px-6 py-10">

    @if($accepted)
        <section class="rounded-3xl bg-white p-10 shadow-xl ring-1 ring-emerald-100">
            <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-asphalt-900">{{ $ui['gracias_titulo'] }}</h1>
                    <p class="mt-2 text-sm text-asphalt-600">
                        {{ $ui['gracias_texto'] }}
                    </p>
                    <p class="mt-3 text-xs text-asphalt-500">
                        Firmado el <strong>{{ $accepted_at }}</strong> · Hash SHA256
                        <code class="rounded bg-asphalt-50 px-1.5 py-0.5 font-mono text-[10px] text-asphalt-700">{{ substr($contractHash, 0, 24) }}…</code>
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('public.contract.pdf', $contract->public_token) }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-estoril-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                            </svg>
                            {{ $ui['descargar_pdf'] }}
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @else
        <section class="overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-estoril-100">
            <div class="bg-gradient-to-br from-estoril-600 to-asphalt-900 p-8 text-white">
                <h1 class="text-2xl font-bold">{{ $ui['titulo'] }}</h1>
                <p class="mt-2 text-sm text-estoril-100">{{ $ui['subtitulo'] }}</p>
                <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-2 text-xs sm:grid-cols-4">
                    <div>
                        <dt class="text-estoril-200 uppercase tracking-wider">Vehículo</dt>
                        <dd class="font-semibold">{{ $car->brand }} {{ $car->model }}</dd>
                    </div>
                    <div>
                        <dt class="text-estoril-200 uppercase tracking-wider">Año</dt>
                        <dd class="font-semibold">{{ $car->year ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-estoril-200 uppercase tracking-wider">Versión</dt>
                        <dd class="font-semibold">{{ $car->version ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-estoril-200 uppercase tracking-wider">Versión contrato</dt>
                        <dd class="font-mono text-[11px]">{{ $contract->contract_version }}</dd>
                    </div>
                </dl>
            </div>

            <article class="prose max-w-none p-8">
                {!! $contractTextHtml !!}
            </article>

            <form id="accept-form" method="POST" action="{{ route('public.contract.accept', $contract->public_token) }}" class="space-y-5 border-t border-asphalt-100 bg-asphalt-50 p-8">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-asphalt-500">Nombre y apellidos</label>
                        <input name="client_name" type="text" value="{{ old('client_name', $contract->client_name) }}" class="block w-full rounded-lg border-asphalt-200 text-sm focus:border-estoril-500 focus:ring-estoril-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-asphalt-500">DNI / NIE</label>
                        <input name="client_dni" type="text" value="{{ old('client_dni', $contract->client_dni) }}" class="block w-full rounded-lg border-asphalt-200 text-sm focus:border-estoril-500 focus:ring-estoril-500">
                    </div>
                </div>
                <label class="flex items-start gap-3">
                    <input id="accept" name="accept" type="checkbox" value="1" class="mt-1 h-5 w-5 rounded border-asphalt-300 text-estoril-600 focus:ring-estoril-500" required>
                    <span class="text-sm leading-relaxed text-asphalt-700">
                        {{ str_replace('{n}', (string) count(config('contracts.clausulas')), $ui['checkbox_label']) }}
                    </span>
                </label>

                <p class="text-[11px] text-asphalt-500">{{ $ui['legal_notice'] }}</p>

                <button id="submit-btn" type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-estoril-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:cursor-not-allowed disabled:opacity-50">
                    {{ $ui['boton_aceptar'] }}
                </button>

                <div id="error-msg" class="hidden rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
            </form>
        </section>

        <footer class="mt-8 text-center text-xs text-asphalt-400">
            {{ $prestador['razon_social'] }} · CIF {{ $prestador['cif'] }} · {{ $prestador['direccion'] }}
        </footer>
    @endif

</main>

@unless($accepted)
<script>
    (function () {
        const form = document.getElementById('accept-form');
        const btn = document.getElementById('submit-btn');
        const err = document.getElementById('error-msg');
        const ck = document.getElementById('accept');

        function setLoading(on) {
            btn.disabled = on;
            btn.textContent = on ? @json($ui['leyendo']) : @json($ui['boton_aceptar']);
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            err.classList.add('hidden');
            if (!ck.checked) {
                err.textContent = @json($ui['errores']['no_acepta']);
                err.classList.remove('hidden');
                return;
            }
            setLoading(true);
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
                    },
                    body: new FormData(form),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.ok) {
                    window.location.href = data.pdf_url;
                } else if (res.status === 409) {
                    err.textContent = @json($ui['errores']['ya_firmado']);
                    err.classList.remove('hidden');
                    setLoading(false);
                } else {
                    err.textContent = (data && data.message) || 'Error inesperado';
                    err.classList.remove('hidden');
                    setLoading(false);
                }
            } catch (e) {
                err.textContent = e.message || 'Error de red';
                err.classList.remove('hidden');
                setLoading(false);
            }
        });
    })();
</script>
@endunless

</body>
</html>
