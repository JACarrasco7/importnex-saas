/* =============================================================================
   JJ Import Motors — librería de extractores
   Auditada y validada el 9 de agosto de 2026.

   Uso: pega el bloque de la fuente que toque en la consola de la pestaña abierta
   y llama a la función. Todas devuelven la misma forma de objeto (ver NORMALIZA).

   REGLAS QUE VALEN PARA TODAS:
   - textContent, nunca innerText: en pestañas de fondo innerText devuelve vacío.
   - No leas location.href ni construyas cadenas con "?a=1&b=2" dentro del código.
   - Comillas simples dentro de browser_batch.
   - Devuelve rebanadas: JSON.stringify de 25 fichas se trunca.
   ========================================================================== */

/* -----------------------------------------------------------------------------
   0 · NORMALIZA — forma común de salida
   -------------------------------------------------------------------------- */
window.__norm = function (o) {
  return {
    fuente:      o.fuente || null,   // 'cochesnet' | 'wallapop' | 'milanuncios' | 'autouncle' | 'autoscout'
    url:         o.url || null,      // enlace directo al anuncio (SIEMPRE)
    urlOrigen:   o.urlOrigen || null,// si es agregador, el anuncio en el portal real
    titulo:      o.titulo || null,
    version:     o.version || null,
    precio:      o.precio ?? null,   // contado, IVA incluido
    precioFin:   o.precioFin ?? null,// financiado, si lo hay
    tasacion:    o.tasacion ?? null, // valoración de la propia plataforma, en euros
    valoracion:  o.valoracion || null,
    anio:        o.anio ?? null,
    matriculacion: o.matriculacion || null,
    km:          o.km ?? null,
    cv:          o.cv ?? null,
    combustible: o.combustible || null,
    cambio:      o.cambio || null,
    carroceria:  o.carroceria || null,
    acabado:     o.acabado || null,
    etiqueta:    o.etiqueta || null, // distintivo DGT
    profesional: o.profesional ?? null,
    garantiaMeses: o.garantiaMeses ?? null,
    vendedor:    o.vendedor || null,
    valoracionVendedor: o.valoracionVendedor ?? null,
    provincia:   o.provincia || null,
    ciudad:      o.ciudad || null,
    pais:        o.pais || null,
    publicado:   o.publicado || null, // ISO
    dias:        o.dias ?? null,      // días en el mercado
    bajadaPrecio: o.bajadaPrecio ?? null,
    fotos:       o.fotos ?? null,
    descripcion: o.descripcion || null // TEXTO LIBRE — criba de siniestros y preparaciones
  };
};

/* -----------------------------------------------------------------------------
   1 · COCHES.NET  —  la mejor fuente española
   URL:  /<marca-slug>/<modelo-slug>/segunda-mano/?fr=<anio>&pf=<precioMin>&pg=<pagina>
   Los slugs salen de listFiltersOptions (ver __cnCatalogo). 165 marcas.

   OJO 1: RECUERDA LOS FILTROS entre navegaciones. Verifica initialSearch.
   OJO 2: el <h1> miente cuando usas parámetros. Fíate de totalResults.
   OJO 3: no hay filtro de potencia por URL. Se filtra por items[].hp en local.
   -------------------------------------------------------------------------- */
window.__cnProps = function () {
  const s = [...document.querySelectorAll('script')].map(x => x.textContent || '')
    .find(t => t.indexOf('window.__INITIAL_PROPS__') >= 0);
  if (!s) return null;
  const m = s.match(/window\.__INITIAL_PROPS__\s*=\s*JSON\.parse\(/); if (!m) return null;
  let i = m.index + m[0].length; const q = s[i]; let j = i + 1, esc = false;
  for (; j < s.length; j++) { const c = s[j];
    if (esc) { esc = false; continue; } if (c === '\\') { esc = true; continue; } if (c === q) break; }
  try { return JSON.parse(JSON.parse(s.slice(i, j + 1))); } catch (e) { return null; }
};

/* Tabla maestra marca -> modelos, con IDs y slugs. Sirve para CUALQUIER modelo. */
window.__cnCatalogo = function (P, filtroMarca) {
  const slug = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
                     .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  return P.listFiltersOptions.vehicles.options
    .filter(m => !filtroMarca || new RegExp(filtroMarca, 'i').test(m.label))
    .map(m => ({ marca: m.label, marcaId: m.id, marcaSlug: slug(m.label),
      modelos: m.models.map(x => ({ modelo: x.label, id: x.id, slug: slug(x.label) })) }));
};

/* 3 = "Precio justo" · 4 = "Buen precio" (calibrado 9-ago-2026 contra el texto de
   la tarjeta). La escala sube con la ganga; 5 y 2 sin confirmar. */
window.__CN_RANK = { 5: 'Super precio', 4: 'Buen precio', 3: 'Precio justo', 2: 'Precio alto' };

window.__cochesnet = function (P) {
  P = P || window.__cnProps();
  const R = P.initialResults;
  const hoy = Date.now();
  return {
    total: R.totalResults,
    paginas: R.totalPages,
    filtrosAplicados: P.initialSearch,   // VERIFICA ESTO SIEMPRE
    anuncios: R.items.map(a => window.__norm({
      fuente: 'cochesnet',
      url: a.url ? 'https://www.coches.net' + a.url : null,
      titulo: a.title, precio: a.price, precioFin: a.financedPrice,
      tasacion: a.priceAverageIndicator,
      valoracion: window.__CN_RANK[a.priceRankIndicator] || null,
      anio: a.year, km: a.km, cv: a.hp,
      combustible: a.fuelType, etiqueta: a.environmentalLabel,
      profesional: a.isProfessional, garantiaMeses: a.warrantyMonths,
      vendedor: a.seller && a.seller.name,
      valoracionVendedor: a.seller && a.seller.ratings && a.seller.ratings.average,
      provincia: a.location && a.location.mainProvince,
      ciudad: a.location && a.location.cityLiteral,
      pais: 'ES',
      publicado: a.publicationDate,
      dias: a.publicationDate ? Math.round((hoy - Date.parse(a.publicationDate)) / 864e5) : null,
      fotos: (a.photos || []).length || null
    }))
  };
};

/* -----------------------------------------------------------------------------
   2 · MILANUNCIOS  —  también __INITIAL_PROPS__ (misma casa que Coches.net)
   URL:  /coches-de-segunda-mano/?s=<marca>%20<modelo>%20<version>
   41 anuncios por página en el JSON, frente a ~7 con contenido en el DOM.
   Resuelve limpiamente la trampa de los dos precios: cashPrice vs financedPrice.
   -------------------------------------------------------------------------- */
window.__milanuncios = function () {
  const P = window.__cnProps();          // mismo parser
  if (!P || !P.adListPagination) return null;
  const pg = P.adListPagination.pagination;
  const hoy = Date.now();
  const tag = (a, t) => { const x = (a.tags || []).find(y => y.type === t); return x ? x.text : null; };
  const n = s => { const m = String(s || '').replace(/\./g, '').match(/\d+/); return m ? +m[0] : null; };
  return {
    total: pg.totalAds, paginas: pg.totalPages, siguiente: pg.nextToken,
    anuncios: P.adListPagination.adList.ads.map(a => window.__norm({
      fuente: 'milanuncios',
      url: a.url ? 'https://www.milanuncios.com' + a.url : null,
      titulo: a.title,
      precio: a.price && a.price.cashPrice && a.price.cashPrice.value,      // CONTADO
      precioFin: a.price && a.price.financedPrice && a.price.financedPrice.value,
      anio: n(tag(a, 'año')), km: n(tag(a, 'kilómetros')),
      combustible: tag(a, 'combustible'),
      profesional: a.sellerType === 'professional',
      garantiaMeses: a.warrantyPeriod ? n(a.warrantyPeriod) : null,
      provincia: a.province && a.province.name, ciudad: a.city && a.city.name,
      pais: 'ES', publicado: a.publishDate,
      dias: a.publishDate ? Math.round((hoy - Date.parse(a.publishDate)) / 864e5) : null,
      fotos: (a.images || []).length || null,
      descripcion: a.description
    }))
  };
};

/* -----------------------------------------------------------------------------
   3 · WALLAPOP  —  sin JSON: __NEXT_DATA__ no trae anuncios y la API no se ve.
   Selector estable: [class*="RetrievalItemCard"], quedándose con los nodos que
   contienen EXACTAMENTE UN enlace /item/ y un precio. Dedupe por URL.
   La tarjeta trae año, km, CV, combustible, nº de fotos y LA DESCRIPCIÓN.
   -------------------------------------------------------------------------- */
window.__wallapop = function () {
  const T = e => (e && e.textContent || '').replace(/\s+/g, ' ').trim();
  const por = {};
  [...document.querySelectorAll('[class*="RetrievalItemCard"]')].forEach(c => {
    const ls = new Set([...c.querySelectorAll('a[href*="/item/"]')].map(x => x.getAttribute('href')));
    if (ls.size !== 1) return;
    const a = c.querySelector('a[href*="/item/"]');
    const bruto = T(c);
    const t = bruto.replace(/^Image \d+ of \d+/, '');
    if (!/€/.test(t)) return;
    const g = re => (t.match(re) || [])[1];
    const o = window.__norm({
      fuente: 'wallapop', url: a.href,
      precio: +String(g(/([\d.]{4,9})\s*€/) || '').replace(/\./g, '') || null,
      anio: +(g(/(20[0-2]\d)\s*·/) || 0) || null,           // el año va ANTES del separador
      km: +String(g(/(\d[\d.]{2,8})\s*km/i) || '').replace(/\./g, '') || null,
      cv: +(g(/(\d{2,4})\s*cv/i) || 0) || null,
      combustible: g(/(Gasolina|Di[ée]sel|H[íi]brido|El[ée]ctrico)/i),
      pais: 'ES',
      fotos: +(bruto.match(/Image \d+ of (\d+)/) || [])[1] || null,
      descripcion: (t.split(/[\d.]{4,9}\s*€/).pop() || '').trim()
    });
    const prev = por[o.url];
    if (!prev || (o.descripcion || '').length > (prev.descripcion || '').length) por[o.url] = o;
  });
  // < 3.000 € en Wallapop suelen ser piezas, no coches
  return { anuncios: Object.values(por).filter(x => x.precio && x.precio >= 3000) };
};

/* -----------------------------------------------------------------------------
   4 · AUTOUNCLE  —  agregador alemán (mobile.de + Autoscout24 + Kleinanzeigen)
   URL:  /es/coches-segunda-mano/<Marca>/<Modelo>/f-<combustible>/g-<cambio>
         ?s[min_year]=&s[max_km]=&s[min_hp]=
   s[min_hp] SÍ filtra por potencia: es la forma limpia de aislar una versión.
   Marca y modelo son obligatorios; sin ellos redirige a PUCH.
   NUNCA leas document.body.textContent: trae 300.000 chars de payload RSC.
   -------------------------------------------------------------------------- */
window.__autouncle = function () {
  const T = e => (e && e.textContent || '').replace(/\s+/g, ' ').trim();
  return {
    total: +(document.title.match(/(\d+)\s+coches/) || [])[1] || null,
    anuncios: [...document.querySelectorAll('article')].map(a => {
      const t = T(a);
      const pr = [...t.matchAll(/([\d]{1,3}(?:\.\d{3})+)\s*€/g)].map(m => +m[1].replace(/\./g, ''));
      const lis = [...a.querySelectorAll('li')].map(T).filter(Boolean);
      const g = re => lis.find(x => re.test(x)) || null;
      const det = a.querySelector('a[href*="/d/"]');
      const src = [...a.querySelectorAll('a[href]')]
        .find(x => /enlace-externo|das_wiedersehen/.test(x.getAttribute('href')));
      return window.__norm({
        fuente: 'autouncle',
        url: det ? det.href : null,
        urlOrigen: src ? src.href : null,      // redirección al anuncio real
        version: T(a.querySelector('h3+p')),
        precio: pr[0] || null, tasacion: pr[1] || null,
        matriculacion: lis[0] || null,
        km: +String((g(/km/) || '').replace(' km', '')).replace(/\./g, '') || null,
        cv: +((g(/CV/) || '').match(/\d+/) || [])[0] || null,
        acabado: (g(/Acabado/) || '').replace('Acabado: ', '') || null,
        combustible: g(/Gasolina|Di[eé]sel|H[íi]brido|El[eé]ctrico/),
        cambio: g(/Manual|Autom/),
        carroceria: lis.find(x => /Coupe|Berlina|SUV|Familiar|Cabrio|Monovolumen/.test(x)),
        valoracion: (t.match(/(Superprecio|Buen precio|Precio justo|Un poco caro|Caro)/) || [])[1],
        dias: +((t.match(/D[ií]as[^0-9]{0,25}(\d+)/) || [])[1] || 0) || null,
        vendedor: (t.match(/(mobile\.de|Kleinanzeigen|Autoscout24|heycar)/i) || [])[1] || 'concesionario',
        pais: 'DE'
      });
    })
  };
};

/* Ficha de AutoUncle: /es/d/<id>. Entra por h2/h3, NUNCA por body. */
window.__autouncleFicha = function () {
  const T = e => (e && e.textContent || '').replace(/\s+/g, ' ').trim();
  const h = [...document.querySelectorAll('h2,h3')].find(e => /Datos t[eé]cnicos/i.test(T(e)));
  if (!h) return null;
  let p = h.parentElement, txt = '';
  for (let i = 0; i < 4 && p; i++) { if (T(p).length > 400) { txt = T(p); break; } p = p.parentElement; }
  return txt;   // ya viene traducido al español
};

/* -----------------------------------------------------------------------------
   5 · AUTOSCOUT24  —  SOLO PARA CONTAR. No es referencia de precio en España.
   .es  /lst/<marca>/<modelo>?atype=C&desde=<anio>&powerfrom=&powerto=&powertype=kw
   .de  ... &fregfrom=<anio>   (fregfrom, no desde)
   Ignora powertype=ps y filtra SIEMPRE en kW.  kW = CV / 1,36
   RELLENA CON COCHES EXTRANJEROS SIN AVISAR: cuenta location.countryCode.
   -------------------------------------------------------------------------- */
window.__autoscout = function () {
  const s = document.querySelector('script#__NEXT_DATA__'); if (!s) return null;
  const j = JSON.parse(s.textContent);
  const L = (j.props && j.props.pageProps && j.props.pageProps.listings) || [];
  const h1 = document.querySelector('h1');
  const paises = {};
  L.forEach(a => { const cc = (a.location || {}).countryCode || '?'; paises[cc] = (paises[cc] || 0) + 1; });
  return {
    h1: h1 ? T2(h1) : null, enPagina: L.length, paises,     // <-- LEE paises SIEMPRE
    anuncios: L.map(a => { const v = a.vehicle || {}, p = a.price || {}, lo = a.location || {};
      return window.__norm({
        fuente: 'autoscout',
        url: a.url ? 'https://www.autoscout24.es' + a.url : null,
        titulo: [v.make, v.model, v.modelVersionInput].filter(Boolean).join(' '),
        version: v.modelVersionInput, precio: p.priceRaw,
        km: +String(v.mileageInKm || '').replace(/[^\d]/g, '') || null,
        combustible: v.fuel, anio: +String(v.firstRegistration || '').slice(-4) || null,
        ciudad: lo.city, pais: lo.countryCode
      });
    })
  };
  function T2(e) { return (e.textContent || '').replace(/\s+/g, ' ').trim(); }
};

/* -----------------------------------------------------------------------------
   6 · KM77  —  PVP, CO2, tipo de IEDMT y etiqueta DGT. Fuente canónica.
   /coches/<marca>/<modelo>/<anio-gama>/<carroceria>/<acabado>/<version>/datos
   PVP completo = "precio sin impuestos" x (1 + IVA + tipo)
   Se lee bien con web_fetch: no hace falta navegador.
   -------------------------------------------------------------------------- */

/* =============================================================================
   RECETA PARA UN MODELO CUALQUIERA
   1. Coches.net: abre /<marca>/<modelo>/segunda-mano/, saca __cnCatalogo para
      confirmar los slugs, y __cochesnet paginando con &pg=. Filtra hp en local.
      VERIFICA initialSearch en cada página.
   2. Milanuncios: /coches-de-segunda-mano/?s=<marca> <modelo> <version>, __milanuncios.
   3. Wallapop: /app/search?keywords=... , __wallapop. Filtra por título y por CV.
   4. AutoScout24.es: solo para contar unidades ES. Mira paises.
   5. AutoUncle: /es/coches-segunda-mano/<Marca>/<Modelo>/... con s[min_hp].
   6. km77: ficha de la versión para PVP, CO2, tipo y etiqueta.
   ========================================================================== */

/* -----------------------------------------------------------------------------
   7 · AUTOSCOUT24.DE — FICHA DE ANUNCIO. La mejor fuente alemana de detalle.
   Se llega desde AutoUncle: /es/enlace-externo/<portal>/<idAU>/<idAnuncio>
   `__NEXT_DATA__.props.pageProps.listingDetails` trae:

     .description                    <-- TEXTO LIBRE ENTERO. La criba vive aqui
     .vehicle.equipment.*            <-- EQUIPAMIENTO ESTRUCTURADO por categorias:
                                         comfortAndConvenience, safetyAndSecurity,
                                         entertainmentAndMedia, extras
     .vehicleReport.carfax           <-- INFORME CARFAX si existe
     .prices.public.evaluationRanges <-- valoracion de precio de AutoScout
     .images[]                       <-- numero de fotos
     .seller.type                    <-- private / dealer

   El id del portal en la URL de AutoUncle ya dice el tipo:
   `autoscout24private` = particular, `autoscout24` = profesional.
   -------------------------------------------------------------------------- */
window.__as24ficha = function () {
  const s = document.querySelector('script#__NEXT_DATA__'); if (!s) return null;
  const L = (JSON.parse(s.textContent).props || {}).pageProps.listingDetails; if (!L) return null;
  const eq = [];
  for (const k in (L.vehicle && L.vehicle.equipment) || {})
    (L.vehicle.equipment[k] || []).forEach(x => eq.push(x.id));
  return {
    descripcion: (L.description || '').replace(/<br\s*\/?>/g, '\n'),
    equipamiento: eq,                       // lista plana, ya estructurada
    carfax: !!(L.vehicleReport && L.vehicleReport.carfax),
    vendedorTipo: (L.seller || {}).type,
    fotos: (L.images || []).length
  };
};

/* -----------------------------------------------------------------------------
   8 · KLEINANZEIGEN — FICHA DE ANUNCIO
   Bloque de datos en `#viewad-description-text`. Trae, en texto corrido:
     Schadstoffklasse (Euro 5/6) · Anzahl der Fahrzeughalter (propietarios) ·
     HU (fecha de la proxima ITV alemana) · Innenausstattung · Ausstattung (lista)
   `#viewad-extra-info` da fecha de publicacion y numero de visitas.
   AVISO: muchos anuncios NO tienen texto libre del vendedor, solo el bloque de
   datos. Eso no es senal buena ni mala: es que hay que preguntar.
   -------------------------------------------------------------------------- */
window.__klFicha = function () {
  const T = e => (e && e.textContent || '').replace(/\s+/g, ' ').trim();
  const d = T(document.querySelector('#viewad-description-text'));
  const g = re => (d.match(re) || [])[1] || null;
  return {
    precio: T(document.querySelector('#viewad-price')),
    publicado: T(document.querySelector('#viewad-extra-info')),
    euro: g(/Schadstoffklasse:\s*(Euro\s*\d)/i),
    propietarios: +g(/Anzahl der Fahrzeughalter:\s*(\d+)/i) || null,
    itvHasta: g(/HU:\s*([\d\/]+)/i),
    tapiceria: g(/Innenausstattung:\s*([^F]+?)Farbe/i),
    equipamiento: (d.split('Ausstattung')[1] || '').split('Privatanbieter')[0].split(', '),
    textoLibre: d
  };
};
