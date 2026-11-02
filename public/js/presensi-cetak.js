// Tombol "Cetak Daftar Hadir" di halaman Presensi: langsung membuka dialog cetak tanpa pindah halaman.
// Halaman cetak dimuat di bingkai (iframe) tersembunyi; halaman itu memanggil window.print() sendiri
// setelah selesai dimuat (lihat presensi/cetak.blade.php).
(function () {
    var tombol = document.querySelector('[data-cetak-langsung]');
    if (!tombol) { return; }

    var bingkai = null;
    tombol.addEventListener('click', function (e) {
        e.preventDefault();
        if (bingkai) { bingkai.remove(); } // muat ulang agar daftar hadir selalu terbaru
        bingkai = document.createElement('iframe');
        bingkai.title = 'Cetak daftar hadir';
        bingkai.setAttribute('aria-hidden', 'true');
        bingkai.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0';
        bingkai.src = tombol.href;
        document.body.appendChild(bingkai);
    });
})();
