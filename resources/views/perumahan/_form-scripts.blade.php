<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Toggle detail lahan pemakaman ---
        const checkboxPemakaman = document.getElementById('ada-lahan-pemakaman');
        const detailPemakaman = document.getElementById('detail-pemakaman');
        checkboxPemakaman?.addEventListener('change', () => {
            detailPemakaman?.classList.toggle('hidden', !checkboxPemakaman.checked);
        });

        // --- Peta pemilih titik (Leaflet) ---
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

        // --- Peta batas polygon ---
        const mapPolygonEl = document.getElementById('map-polygon');
        const inputPolygon = document.getElementById('input-batas-polygon');
        const statusPolygon = document.getElementById('polygon-status');
        const listPolygon = document.getElementById('polygon-titik-list');
        const btnResetPolygon = document.getElementById('btn-polygon-reset');

        if (mapPolygonEl && inputPolygon && typeof window.L !== 'undefined') {
            const polygonSaved = (() => {
                try {
                    const parsed = JSON.parse(inputPolygon.value || '[]');
                    return Array.isArray(parsed) ? parsed.filter((t) => Array.isArray(t) && t.length >= 2) : [];
                } catch (e) {
                    return [];
                }
            })();

            let titikPolygon = polygonSaved.map((t) => [parseFloat(t[0]), parseFloat(t[1])])
                .filter((t) => Number.isFinite(t[0]) && Number.isFinite(t[1]));

            const mapPolygon = L.map(mapPolygonEl, { scrollWheelZoom: true })
                .setView(
                    adaTitik ? [lat, lng] : [PETA.latitude, PETA.longitude],
                    adaTitik ? PETA.zoom_detail : PETA.zoom,
                );

            L.tileLayer(PETA.tile_url, {
                maxZoom: 19,
                attribution: PETA.tile_attribution,
            }).addTo(mapPolygon);

            let polyline = null;
            let polygonLayer = null;
            const vertexes = [];

            const segarkanPolygon = () => {
                inputPolygon.value = JSON.stringify(titikPolygon);

                // Bersihkan layer lama
                if (polyline) {
                    mapPolygon.removeLayer(polyline);
                    polyline = null;
                }
                if (polygonLayer) {
                    mapPolygon.removeLayer(polygonLayer);
                    polygonLayer = null;
                }
                vertexes.forEach((m) => mapPolygon.removeLayer(m));
                vertexes.length = 0;

                if (titikPolygon.length === 0) {
                    statusPolygon.textContent = 'Belum ada titik polygon.';
                    listPolygon.innerHTML = '';
                    return;
                }

                titikPolygon.forEach((t, i) => {
                    const m = L.circleMarker(t, {
                        radius: 6,
                        color: '#4f46e5',
                        fillColor: '#6366f1',
                        fillOpacity: 1,
                        weight: 2,
                    }).addTo(mapPolygon);

                    m.bindPopup(`Titik ${i + 1} &mdash; ${t[0].toFixed(6)}, ${t[1].toFixed(6)}`);
                    m.on('click', (event) => {
                        L.DomEvent.stopPropagation(event);
                        // Klik vertex pada mode >= 3 titik menghapus titik tsb
                        if (titikPolygon.length > 3) {
                            titikPolygon.splice(i, 1);
                            segarkanPolygon();
                        }
                    });
                    vertexes.push(m);
                });

                if (titikPolygon.length === 1) {
                    statusPolygon.textContent = '1 titik terpasang. Tambah minimal 3 titik.';
                } else if (titikPolygon.length === 2) {
                    polyline = L.polyline(titikPolygon, { color: '#4f46e5', dashArray: '6 6' }).addTo(mapPolygon);
                    statusPolygon.textContent = '2 titik terpasang. Tambah 1 titik lagi.';
                } else {
                    polygonLayer = L.polygon(titikPolygon, {
                        color: '#4f46e5',
                        fillColor: '#6366f1',
                        fillOpacity: 0.18,
                        weight: 2,
                    }).addTo(mapPolygon);
                    statusPolygon.textContent = `${titikPolygon.length} titik terpasang. Klik titik pada peta untuk menghapus.`;
                }

                listPolygon.innerHTML = titikPolygon.map((t, i) =>
                    `<li>(${i + 1}) ${t[0].toFixed(6)}, ${t[1].toFixed(6)}</li>`,
                ).join('');
            };

            mapPolygon.on('click', (event) => {
                titikPolygon.push([event.latlng.lat, event.latlng.lng]);
                segarkanPolygon();
            });

            btnResetPolygon?.addEventListener('click', () => {
                titikPolygon = [];
                segarkanPolygon();
            });

            segarkanPolygon();
            setTimeout(() => mapPolygon.invalidateSize(), 200);
        }
    });
</script>
