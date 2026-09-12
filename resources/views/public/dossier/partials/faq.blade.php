{{-- PREGUNTAS FRECUENTES (10-sep-2026) --}}
{{-- Las preguntas y respuestas vienen de $fichaFaq (controlador). Si no
     hay ficha del cliente v2, el controlador inyecta fallbacks honestos. --}}
<section class="faq reveal">
    <div class="section-title">Dudas</div>
    <h2 class="section-h">Preguntas frecuentes</h2>
    @foreach($fichaFaq as $i => $item)
        <details @if($i === 0) open @endif>
            <summary>{{ is_array($item) ? ($item['pregunta'] ?? '') : '' }}</summary>
            <p>{{ is_array($item) ? ($item['respuesta'] ?? '') : '' }}</p>
        </details>
    @endforeach
</section>
