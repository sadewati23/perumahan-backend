/**
 * Peta titik perumahan pada halaman detail.
 *
 * Data lokasi dibaca dari atribut data-* pada #peta-detail-publik sehingga
 * tidak ada skrip inline di Blade. Leaflet dimuat dari CDN yang sudah dipakai
 * portal (lihat @push('scripts') di show.blade.php); bila gagal dimuat,
 * modul ini berhenti tanpa error.
 */
export function initPetaDetail() {
    const el = document.getElementById('peta-detail-publik');
    if (!el || typeof window.L === 'undefined') return;

    const lat = Number.parseFloat(el.dataset.lat);
    const lng = Number.parseFloat(el.dataset.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

    const zoom = Number.parseInt(el.dataset.zoom ?? '16', 10);
    const map = window.L.map(el).setView([lat, lng], Number.isFinite(zoom) ? zoom : 16);

    window.L.tileLayer(el.dataset.tileUrl, {
        maxZoom: 19,
        attribution: el.dataset.attribution,
    }).addTo(map);

    window.L.marker([lat, lng])
        .addTo(map)
        .bindPopup(el.dataset.nama || '')
        .openPopup();

    window.setTimeout(() => map.invalidateSize(), 200);
}
