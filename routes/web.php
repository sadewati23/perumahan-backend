<?php

/*
|--------------------------------------------------------------------------
| Pemuat rute (tipis)
|--------------------------------------------------------------------------
| Rute dipecah secara arsitektural (bukan perilaku) menjadi tiga berkas
| sesuai area: portal publik, autentikasi admin, dan area admin.
|
| Ketiganya di-require berurutan mengikuti urutan blok asli sehingga
| urutan registrasi rute tidak berubah. Pemuatan ini tetap berada di
| grup middleware web (dipasang withRouting di bootstrap/app.php), jadi
| TIDAK dibungkus grup middleware lagi di sini.
*/

require __DIR__.'/publik.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
