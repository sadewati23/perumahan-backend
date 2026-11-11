/**
 * Kompres gambar di sisi BROWSER sebelum berkas dipasang ke lampiran form.
 *
 * Latar: foto kamera iPhone/Android modern berukuran 2–6 MB, sehingga sering
 * digugurkan validasi "maks 2 MB / 5 MB" SEBELUM kompresi server (FotoBersih)
 * sempat bekerja. Dengan mengompres di sini, foto tiba di server sudah kecil.
 *
 * Cara kerja (meniru App\Support\FotoBersih):
 *  - Foto kecil dari ambang biarkan apa adanya (tanpa degradasi kualitas);
 *  - Di atas ambang: decode → perkecil sisi terpanjang ke 1600 px →
 *    encode ulang ke JPEG (quality 0.85);
 *  - Bila hasil encode ternyata lebih besar dari berkas asli, berkas asli
 *    tetap dipakai;
 *  - Bila gambar tidak bisa diparse browser (mis. HEIC diutom Chrome),
 *    kembalikan berkas asli bertanda `takTerbaca` — biarkan validasi tipe
 *    yang menolaknya dengan pesan yang jelas.
 *
 * Catatan HEIC (kamera iPhone): Safari/iOS mampu mem-parse HEIC di <img>,
 * sehingga kompres mengubahnya menjadi JPEG yang diterima server.
 * Metadata EXIF ikut terbuang karena hasilnya encode ulang dari piksel.
 */

export const SISI_MAKS = 1600; // sinkron dgn App\Support\FotoBersih::SISI_MAKS
export const KUALITAS_JPEG = 0.85;
export const AMBANG_BAIT = 1536 * 1024; // 1.5 MB — di bawah ini tak disentuh

const HEIC = /^image\/hei[cf]$/i;

/**
 * @param {File} berkas
 */
export async function kompresGambar(berkas, opsi = {}) {
    const {
        sisiMaks = SISI_MAKS,
        kualitas = KUALITAS_JPEG,
        ambangBait = AMBANG_BAIT,
    } = opsi;

    const isHeic = HEIC.test(berkas.type ?? '');
    if (berkas.size <= ambangBait && !isHeic) {
        return { berkas, dioptimalkan: false };
    }

    const dekode = await cobaDekode(berkas);
    if (!dekode) {
        return { berkas, dioptimalkan: false, takTerbaca: true };
    }

    try {
        const skala = Math.min(1, sisiMaks / Math.max(dekode.lebar, dekode.tinggi));
        const lebar = Math.max(1, Math.round(dekode.lebar * skala));
        const tinggi = Math.max(1, Math.round(dekode.tinggi * skala));

        const kanvas = document.createElement('canvas');
        kanvas.width = lebar;
        kanvas.height = tinggi;

        const konteks = kanvas.getContext('2d');
        // JPEG tak punya kanal alfa: dasarkan putih bila sumber transparan (PNG).
        konteks.fillStyle = '#ffffff';
        konteks.fillRect(0, 0, lebar, tinggi);
        konteks.drawImage(dekode.sumber, 0, 0, lebar, tinggi);

        const blob = await blobKanvas(kanvas, 'image/jpeg', kualitas);
        if (!blob || blob.size === 0 || blob.size >= berkas.size) {
            return { berkas, dioptimalkan: false };
        }

        const nama = (berkas.name || 'foto').replace(/\.[^.]+$/, '') + '.jpg';
        const hasil = new File([blob], nama, { type: 'image/jpeg', lastModified: Date.now() });

        return { berkas: hasil, dioptimalkan: true, ukuranAwal: berkas.size };
    } finally {
        dekode.bersihkan();
    }
}

/**
 * Decode bergambar lewat <img> (bukan createImageBitmap) karena:
 *  - orientasi EXIF otomatis diterapkan (image-orientation: from-image),
 *  - Safari/iOS sanggup mem-parse HEIC.
 * Menudo { lebar, tinggi, sumber, bersihkan() } atau null bila gagal.
 */
async function cobaDekode(berkas) {
    if (typeof Image === 'undefined' || !URL?.createObjectURL) return null;

    try {
        const url = URL.createObjectURL(berkas);
        const sumber = new Image();
        sumber.decoding = 'async';

        const dimuat = new Promise((tepat, salah) => {
            sumber.onload = tepat;
            sumber.onerror = () => salah(new Error('Tidak dapat memuat gambar.'));
        });

        sumber.src = url;

        if (typeof sumber.decode === 'function') {
            try {
                await sumber.decode();
            } catch {
                // Sudut lama: rampungkan lewat onload di atas.
                await dimuat;
            }
        } else {
            await dimuat;
        }

        const bersihkan = () => URL.revokeObjectURL(url);

        if (!sumber.naturalWidth || !sumber.naturalHeight) {
            bersihkan();
            return null;
        }

        return {
            lebar: sumber.naturalWidth,
            tinggi: sumber.naturalHeight,
            sumber,
            bersihkan,
        };
    } catch {
        return null;
    }
}

function blobKanvas(kanvas, jenis, kualitas) {
    if (typeof kanvas.toBlob !== 'function') return Promise.resolve(null);

    return new Promise((tepat) => {
        kanvas.toBlob((blob) => tepat(blob), jenis, kualitas);
    });
}
