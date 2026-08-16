<script setup>
import { onMounted, ref, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

const props = defineProps({
    lat: { type: [String, Number], default: null },
    lng: { type: [String, Number], default: null },
    height: { type: String, default: '300px' },
    markerText: { type: String, default: 'Ubicación del vehículo' },
});

const mapContainer = ref(null);
let map = null;
let marker = null;

onMounted(async () => {
    if (!props.lat || !props.lng) return;

    const L = await import('leaflet');

    // Iconos de marcador empaquetados por Vite (rutas automáticas al build)
    L.Icon.Default.mergeOptions({
        iconRetinaUrl,
        iconUrl,
        shadowUrl,
    });

    map = L.map(mapContainer.value, {
        center: [parseFloat(props.lat), parseFloat(props.lng)],
        zoom: 13,
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    marker = L.marker([parseFloat(props.lat), parseFloat(props.lng)], {
        title: props.markerText,
    }).addTo(map).bindPopup(props.markerText).openPopup();

    // Invalidar tamaño tras montar para evitar paneles grises con layouts flex
    setTimeout(() => map.invalidateSize(), 100);
});

watch(() => props.lat, (newVal) => {
    if (map && newVal) {
        map.setView([parseFloat(newVal), parseFloat(props.lng)], 13);
        if (marker) {
            marker.setLatLng([parseFloat(newVal), parseFloat(props.lng)]);
        }
    }
});
</script>

<template>
    <div
        ref="mapContainer"
        :style="{ height: height, width: '100%' }"
        class="rounded-lg border"
    ></div>
</template>

<style>
.leaflet-popup-content-wrapper {
    border-radius: 8px;
}
</style>
