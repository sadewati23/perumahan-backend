<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Toggle detail lahan pemakaman ---
        const checkboxPemakaman = document.getElementById('ada-lahan-pemakaman');
        const detailPemakaman = document.getElementById('detail-pemakaman');
        checkboxPemakaman?.addEventListener('change', () => {
            detailPemakaman?.classList.toggle('hidden', !checkboxPemakaman.checked);
        });

        const mapEl = document.getElementById('map-picker');
        if (!mapEl || typeof window.L === 'undefined') {
            return;
        }

        const PETA = @json(config('peta'));

        const latInput = document.getElementById('input-latitude');
        const lngInput = document.getElementById('input-longitude');

        let lat = parseFloat(latInput?.value);
        let lng = parseFloat(lngInput?.value);
        const adaTitik = Number.isFinite(lat) && Number.isFinite(lng);

        // --- Peta pemilih titik (Leaflet) ---
        const map = L.map(mapEl, { scrollWheelZoom: true })
            .setView(
                adaTitik ? [lat, lng] : [PETA.latitude, PETA.longitude],
                adaTitik ? PETA.zoom_detail : PETA.zoom,
            );

        L.tileLayer(PETA.tile_url, {
            maxZoom: 19,
            attribution: PETA.tile_attribution,
        }).addTo(map);

        let marker = null;
        if (adaTitik) {
            marker = L.marker([lat, lng]).addTo(map);
        }

        const setPoint = (newLat, newLng, move) => {
            latInput.value = newLat.toFixed(7);
            lngInput.value = newLng.toFixed(7);
            if (marker) {
                marker.setLatLng([newLat, newLng]);
            } else {
                marker = L.marker([newLat, newLng]).addTo(map);
            }
            if (move) {
                map.setView([newLat, newLng], Math.max(map.getZoom(), PETA.zoom_detail));
            }
        };

        map.on('click', (event) => setPoint(event.latlng.lat, event.latlng.lng, false));

        const syncFromInput = () => {
            const newLat = parseFloat(latInput.value);
            const newLng = parseFloat(lngInput.value);
            if (Number.isFinite(newLat) && Number.isFinite(newLng)) {
                setPoint(newLat, newLng, true);
            }
        };
        latInput?.addEventListener('change', syncFromInput);
        lngInput?.addEventListener('change', syncFromInput);

        document.getElementById('btn-lokasi-saya')?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Browser tidak mendukung geolokasi.');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => setPoint(pos.coords.latitude, pos.coords.longitude, true),
                () => alert('Gagal mengambil lokasi. Pastikan izin lokasi diaktifkan.'),
            );
        });

        setTimeout(() => map.invalidateSize(), 200);
    });
</script>
