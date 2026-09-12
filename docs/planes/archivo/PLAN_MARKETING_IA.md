# Plan de Implementación: Marketing con IA

## Objetivo

Crear un sistema completo de generación de anuncios con IA para coches, permitiendo publicar en portales de compraventa y redes sociales.

---

## Flujos a Implementar

### Flujo A: Marketing desde Ficha del Coche (`/cars/{car}/marketing`)

**Uso**: Generación rápida de anuncios para un coche específico.

```
Usuario en Show.vue → Click "Marketing" → /cars/{car}/marketing
├── Selecciona canal (tabs)
├── Click "Generar con IA"
├── Edita resultado
├── Copia / Guarda borrador
└── Marca como publicado
```

### Flujo B: Página Marketing Central (`/marketing`)

**Uso**: Gestión global de todos los anuncios.

```
Usuario → /marketing
├── Lista de coches con estado de marketing
├── Filtros: [Todos] [Sin anuncio] [Borrador] [Publicado]
├── Click en coche → /cars/{car}/marketing (redirige a Flujo A)
└── Vista resumen de anuncios por canal
```

---

## Estructura de Base de Datos

### Tabla: `car_marketing_contents`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| car_id | bigint | FK → cars |
| channel | string | milanuncios, coches_net, wallapop, tiktok, instagram, facebook |
| title | string | Título del anuncio |
| description | text | Descripción completa |
| hashtags | json | Array de hashtags |
| photo_tips | json | Array de tips para fotos |
| status | string | draft, published, archived |
| generated_at | timestamp | Cuándo se generó |
| published_at | timestamp | Cuándo se publicó |
| created_at | timestamp | |
| updated_at | timestamp | |

### Índices
- `car_id` + `channel` (único)
- `status`
- `channel`

---

## Archivos a Crear

### 1. Migración
```
database/migrations/2026_08_01_000001_create_car_marketing_contents_table.php
```

### 2. Modelo
```
app/Models/CarMarketingContent.php
```

### 3. Controladores
```
app/Http/Controllers/CarMarketingController.php      # Flujo A (por coche)
app/Http/Controllers/MarketingController.php         # Flujo B (central)
```

### 4. Servicios
```
app/Services/CarMarketingService.php                 # Lógica de generación
app/Services/MarketingChannels/                      # Prompts por canal
    ├── MilanunciosChannel.php
    ├── CochesNetChannel.php
    ├── TikTokChannel.php
    ├── InstagramChannel.php
    └── WallapopChannel.php
```

### 5. Vistas Vue
```
resources/js/Pages/Cars/Marketing.vue                # Flujo A
resources/js/Pages/Marketing/Index.vue               # Flujo B
resources/js/Components/Marketing/
    ├── ChannelSelector.vue
    ├── GeneratedContent.vue
    ├── PhotoTips.vue
    └── PublishHistory.vue
```

### 6. Rutas
```
routes/web.php
```

---

## Implementación Detallada

### Paso 1: Migración

```php
Schema::create('car_marketing_contents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('car_id')->constrained()->cascadeOnDelete();
    $table->string('channel');
    $table->string('title')->nullable();
    $table->text('description')->nullable();
    $table->json('hashtags')->nullable();
    $table->json('photo_tips')->nullable();
    $table->string('status')->default('draft');
    $table->timestamp('generated_at')->nullable();
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
    
    $table->unique(['car_id', 'channel']);
    $table->index('status');
    $table->index('channel');
});
```

### Paso 2: Modelo CarMarketingContent

```php
class CarMarketingContent extends Model
{
    protected $fillable = [
        'car_id', 'channel', 'title', 'description',
        'hashtags', 'photo_tips', 'status', 'generated_at', 'published_at'
    ];

    protected $casts = [
        'hashtags' => 'array',
        'photo_tips' => 'array',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
```

### Paso 3: Servicio CarMarketingService

**Método principal**: `generateForChannel(Car $car, string $channel)`

**Datos que envía a la IA**:
- Fotos del coche (URLs)
- Datos técnicos: marca, modelo, año, km, precio, equipamiento
- Valoración IA (si existe): verdict, market_avg, pros, cons
- Canal destino

**Respuesta esperada**:
```json
{
    "title": "BMW X5 2020 - Impecable estado",
    "description": "...",
    "hashtags": ["#bmw", "#x5", "#importacion"],
    "photo_tips": [
        "Usa la foto delantera como portada",
        "Añade foto del interior en segunda posición"
    ]
}
```

### Paso 4: Prompts por Canal

**Milanuncios**:
- Título: SEO, 60 caracteres, marca + modelo + año + destacado
- Descripción: Técnica, equipamiento, historia, precio negociable
- Sin hashtags
- Tips: Orden de fotos, portada óptima

**Coches.net**:
- Título: Marca Modelo Año - Precio
- Descripción: Equipamiento destacado, garantías, financiación
- Sin hashtags
- Tips: Fotos profesionales, todos los ángulos

**TikTok**:
- Título: Hook viral, pregunta o dato curioso
- Descripción: Storytelling corto, emocional
- Hashtags: Trending + nicho
- Tips: Texto overlay, transiciones, música sugerida

**Instagram**:
- Título: Primera línea caption (emocional)
- Descripción: Caption completo con emojis
- Hashtags: 20-30 por nicho
- Tips: Reels vs Post, horario publicación, grid

**Wallapop**:
- Título: Casual, directo
- Descripción: Conversacional, local
- Hashtags: No aplica
- Tips: Fotos naturales, no profesionales

### Paso 5: Flujo A - CarMarketingController

**Rutas**:
```php
Route::get('/cars/{car}/marketing', [CarMarketingController::class, 'show'])
    ->name('cars.marketing');
Route::post('/cars/{car}/marketing/generate', [CarMarketingController::class, 'generate'])
    ->name('cars.marketing.generate');
Route::post('/cars/{car}/marketing/save', [CarMarketingController::class, 'save'])
    ->name('cars.marketing.save');
Route::post('/cars/{car}/marketing/publish', [CarMarketingController::class, 'publish'])
    ->name('cars.marketing.publish');
```

**Métodos**:
- `show()`: Renderiza Marketing.vue con datos del coche y contenidos existentes
- `generate()`: Llama al servicio IA, devuelve JSON
- `save()`: Guarda borrador
- `publish()`: Marca como publicado

### Paso 6: Flujo B - MarketingController

**Rutas**:
```php
Route::get('/marketing', [MarketingController::class, 'index'])
    ->name('marketing.index');
```

**Métodos**:
- `index()`: Lista coches con estado de marketing, estadísticas

### Paso 7: Vista Marketing.vue (Flujo A)

**Estructura**:
```vue
<template>
    <AuthenticatedLayout>
        <!-- Header con info del coche -->
        <CarHeader :car="car" />
        
        <!-- Tabs de canales -->
        <ChannelTabs v-model="activeChannel" />
        
        <!-- Panel de generación -->
        <GeneratePanel 
            :car="car" 
            :channel="activeChannel"
            @generate="generateContent"
        />
        
        <!-- Resultado editable -->
        <GeneratedContent 
            v-if="generatedContent"
            :content="generatedContent"
            @save="saveDraft"
            @publish="markPublished"
        />
        
        <!-- Historial -->
        <PublishHistory :contents="existingContents" />
    </AuthenticatedLayout>
</template>
```

### Paso 8: Vista Index.vue (Flujo B)

**Estructura**:
```vue
<template>
    <AuthenticatedLayout>
        <!-- Filtros -->
        <FilterBar v-model="filters" />
        
        <!-- Tabla de coches -->
        <CarsTable 
            :cars="cars"
            @select="goToCarMarketing"
        />
        
        <!-- Estadísticas -->
        <StatsCards :stats="stats" />
    </AuthenticatedLayout>
</template>
```

### Paso 9: Integración en Show.vue

Añadir botón "Marketing" en el header de acciones:
```vue
<Link :href="route('cars.marketing', car.id)" 
      class="btn-marketing">
    <MegaphoneIcon class="h-4 w-4" />
    Marketing
</Link>
```

---

## Canales Soportados (MVP)

| Canal | Prioridad | Tipo |
|-------|-----------|------|
| Milanuncios | Alta | Portal |
| Coches.net | Alta | Portal |
| Wallapop | Media | Portal |
| Instagram | Alta | Red Social |
| TikTok | Media | Red Social |
| Facebook | Baja | Red Social |

---

## Estados del Anuncio

| Estado | Descripción |
|--------|-------------|
| draft | Borrador, editable |
| published | Publicado en el canal |
| archived | Archivado (no visible) |

---

## Checklist de Implementación

### Backend
- [x] Migración `car_marketing_contents`
- [x] Modelo `CarMarketingContent`
- [x] Relación en modelo `Car`
- [x] `CarMarketingService` con prompts por canal
- [x] `CarMarketingController` (Flujo A)
- [x] `MarketingController` (Flujo B)
- [x] Rutas web

### Frontend
- [x] Página `Cars/Marketing.vue`
- [x] Página `Marketing/Index.vue`
- [x] Botón Marketing en `Show.vue`

### Testing
- [x] Test generación de anuncio
- [x] Test guardado de borrador
- [x] Test marcado como publicado
- [x] Test permisos (solo org del coche)

### Briefing PDF
- [x] Método `briefing()` en `CarMarketingController`
- [x] Vista Blade `jj-import/briefing.blade.php` (mismo diseño que folleto)
- [x] Ruta `cars.marketing.briefing`
- [x] Botón "Briefing PDF" en `Marketing.vue`

### Documentación
- [ ] Actualizar README con nueva funcionalidad
- [ ] Documentar prompts por canal

---

## Notas Técnicas

1. **Fotos**: La IA no puede ver las fotos directamente, pero sí puede analizar la cantidad, orden y sugerir mejoras basadas en mejores prácticas del canal.

2. **Publicación**: El usuario copia el contenido y publica manualmente. No hay integración API con los portales (MVP).

3. **Historial**: Solo se guarda un contenido por coche + canal. Si se regenera, se sobrescribe el borrador anterior.

4. **Multi-idioma**: Los prompts generan contenido en español, pero se puede extender.

---

## Fecha de Creación

2026-08-01
