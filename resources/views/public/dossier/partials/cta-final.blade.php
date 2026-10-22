{{-- CTA FINAL: el último punto de conversión. Visible siempre al final de la ficha. --}}
<section class="cta-final">
    <div class="cta-eyebrow">¿Te encaja?</div>
    <h2>Gestionamos la compra de este coche por ti</h2>
    <p>Escríbenos por WhatsApp o llama. Te explicamos el proceso completo: búsqueda, verificación, compra, transporte y trámites.</p>
    <div class="cta-buttons">
        @if($brand->whatsappUrl())
        <a href="{{ $brand->whatsappUrl('Hola, me interesa el '.$car->brand.' '.$car->model.'. ¿Podemos hablar sobre el proceso de compra?') }}"
           target="_blank" rel="noopener" class="btn primary big">
            💬 Hablar por WhatsApp
        </a>
        @endif
        @if($brand->telHref())
        <a href="{{ $brand->telHref() }}" class="btn ghost big">
            📞 {{ $brand->get('phone') }}
        </a>
        @endif
    </div>
</section>
