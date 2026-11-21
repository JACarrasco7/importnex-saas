{{-- Datos de la empresa para buscadores (21-nov-2026). Antes decia
     "AutoDealer" (no somos concesionario: A31), "verificacion AI" y un
     telefono de relleno (+34 600 000 000) que Google podia mostrar. --}}
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "ProfessionalService",
  "name": "JJ Import Motors",
  "description": "Servicio de búsqueda, revisión e importación de coches desde Alemania y dentro de España, con honorarios fijos. No somos concesionario: el coche se compra al vendedor y se matricula a nombre del cliente.",
  "url": "{{ config('app.url') }}",
  "logo": "{{ config('app.url') }}/images/jj-import/logo-insignia.png",
  "image": "{{ config('app.url') }}/img/og-image.jpg",
  "telephone": "+34 675 70 14 39",
  "email": "jjimportmotors@gmail.com",
  "areaServed": "ES",
  "address": {
    "@@type": "PostalAddress",
    "addressLocality": "Huelva",
    "addressRegion": "Andalucía",
    "addressCountry": "ES"
  }
}
</script>
