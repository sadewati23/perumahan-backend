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

        // --- State bersama antara peta titik & peta polygon ---
        let titikPolygon = [];
        let mapPolygon = null;
        let penandaTitikPolygon = null;

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

        /**
         * Peta polygon langsung mengikuti titik yang dipilih:
         * dipusatkan ke titik baru + tampilkan penanda titik lokasi.
         */
        const fokusPetaPolygon = (newLat, newLng) => {
            if (!mapPolygon) {
                return;
            }

            // Polygon yang sudah digambar tidak dipaksa zoom ulang,
            // cukup digeser mengikuti titik.
            mapPolygon.setView(
                [newLat, newLng],
                titikPolygon.length > 0 ? mapPolygon.getZoom() : PETA.zoom_detail,
            );

            if (penandaTitikPolygon) {
                penandaTitikPolygon.setLatLng([newLat, newLng]);
            } else {
                penandaTitikPolygon = L.circleMarker([newLat, newLng], {
                    radius: 7,
                    color: '#d97706',
                    fillColor: '#f59e0b',
                    fillOpacity: 1,
                    weight: 2,
                })
                    .addTo(mapPolygon)
                    .bindPopup('Titik lokasi perumahan');
                penandaTitikPolygon.on('click', (event) => L.DomEvent.stopPropagation(event));
            }
        };

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
            fokusPetaPolygon(newLat, newLng);
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

        // --- Perbandingan luas polygon vs luas lahan inputan ---
        const inputLuasLahan = document.querySelector('input[name="luas_lahan"]');
        const elLuasPolygon = document.getElementById('polygon-luas');
        const elLuasLahan = document.getElementById('polygon-luas-lahan');
        const elSelisih = document.getElementById('polygon-selisih');

        const luasPolygonShoelace = (koordinat) => {
            // Proyeksi equirectangular lokal (akurat untuk area kecil).
            const R = 6378137;
            const rad = Math.PI / 180;
            const latRata = koordinat.reduce((sum, t) => sum + t[0], 0) / koordinat.length * rad;
            const titikProyeksi = koordinat.map(([tLat, tLng]) => [
                R * tLat * rad,
                R * tLng * rad * Math.cos(latRata),
            ]);

            let total = 0;
            for (let i = 0; i < titikProyeksi.length; i++) {
                const [y1, x1] = titikProyeksi[i];
                const [y2, x2] = titikProyeksi[(i + 1) % titikProyeksi.length];
                total += x1 * y2 - x2 * y1;
            }

            return Math.abs(total / 2);
        };

        const formatM2 = (angka) => `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(angka)} m\u00b2`;
        const formatHa = (angka) => `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(angka / 10000)} ha`;

        const segarkanPerbandingan = () => {
            if (!elLuasPolygon || !elLuasLahan || !elSelisih) {
                return;
            }

            const adaPolygon = titikPolygon.length >= 3;
            const luas = adaPolygon ? luasPolygonShoelace(titikPolygon) : null;
            const luasLahan = parseFloat(inputLuasLahan?.value);

            elLuasPolygon.textContent = luas !== null ? `${formatM2(luas)} (${formatHa(luas)})` : '-';
            elLuasLahan.textContent = Number.isFinite(luasLahan) && luasLahan > 0
                ? `${formatM2(luasLahan)} (${formatHa(luasLahan)})`
                : '-';

            if (luas === null || !Number.isFinite(luasLahan) || luasLahan <= 0) {
                elSelisih.textContent = '-';
                elSelisih.className = 'font-semibold text-slate-800';
                return;
            }

            const selisih = luas - luasLahan;
            const persen = (selisih / luasLahan) * 100;
            const teks = `${selisih >= 0 ? '+' : ''}${formatM2(Math.abs(selisih))} (${persen >= 0 ? '+' : ''}${persen.toFixed(1)}%)`;

            elSelisih.textContent = teks;
            elSelisih.className = `font-semibold ${Math.abs(persen) > 10 ? 'text-rose-600' : 'text-emerald-600'}`;
        };

        // --- Peta batas polygon ---
        const mapPolygonEl = document.getElementById('map-polygon');
        const inputPolygon = document.getElementById('input-batas-polygon');
        const statusPolygon = document.getElementById('polygon-status');
        const listPolygon = document.getElementById('polygon-titik-list');
        const btnResetPolygon = document.getElementById('btn-polygon-reset');

        if (mapPolygonEl && inputPolygon) {
            const polygonSaved = (() => {
                try {
                    const parsed = JSON.parse(inputPolygon.value || '[]');
                    return Array.isArray(parsed) ? parsed.filter((t) => Array.isArray(t) && t.length >= 2) : [];
                } catch (e) {
                    return [];
                }
            })();

            titikPolygon = polygonSaved.map((t) => [parseFloat(t[0]), parseFloat(t[1])])
                .filter((t) => Number.isFinite(t[0]) && Number.isFinite(t[1]));

            mapPolygon = L.map(mapPolygonEl, { scrollWheelZoom: true })
                .setView(
                    adaTitik ? [lat, lng] : [PETA.latitude, PETA.longitude],
                    adaTitik ? PETA.zoom_detail : PETA.zoom,
                );

            L.tileLayer(PETA.tile_url, {
                maxZoom: 19,
                attribution: PETA.tile_attribution,
            }).addTo(mapPolygon);

            // Penanda titik lokasi pada peta polygon (data awal).
            if (adaTitik) {
                penandaTitikPolygon = L.circleMarker([lat, lng], {
                    radius: 7,
                    color: '#d97706',
                    fillColor: '#f59e0b',
                    fillOpacity: 1,
                    weight: 2,
                })
                    .addTo(mapPolygon)
                    .bindPopup('Titik lokasi perumahan');
                penandaTitikPolygon.on('click', (event) => L.DomEvent.stopPropagation(event));
            }

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
                    segarkanPerbandingan();
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

                segarkanPerbandingan();
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

        inputLuasLahan?.addEventListener('input', segarkanPerbandingan);
        segarkanPerbandingan();
    });
</script>
