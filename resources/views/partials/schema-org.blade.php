<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "AutoDealer",
  "name": "JJ Import Motors",
  "description": "Plataforma profesional de importación de vehículos con verificación AI. Ayudamos a dealers y particulares a importar coches de Alemania de forma segura.",
  "url": "{{ config('app.url') }}",
  "logo": "{{ config('app.url') }}/img/logo.png",
  "telephone": "+34 600 000 000",
  "email": "hola@jjimportmotors.com",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "",
    "addressLocality": "",
    "addressRegion": "",
    "postalCode": "",
    "addressCountry": "ES"
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday"
      ],
      "opens": "09:00",
      "closes": "18:00"
    }
  ],
  "priceRange": "€€€",
  "sameAs": [
    "https://www.linkedin.com/company/jj-import-motors",
    "https://twitter.com/jjimportmotors"
  ],
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "{{ config('app.url') }}/marketplace?q={search_term_string}"
    },
    "query-input": {
      "@type": "PropertyValueSpecification",
      "valueRequired": true,
      "valueName": "search_term_string"
    }
  }
}
</script>