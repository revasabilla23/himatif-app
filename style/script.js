document.addEventListener('DOMContentLoaded', function () {
    AOS.init();
    // Dokumentasi Carousel (pastikan elemen ada)
    const carouselElement = document.querySelector('#carouselExampleCaptions');
    if (carouselElement) {
        const carousel = new bootstrap.Carousel(carouselElement, {
            interval: 2000, // 2 detik per gambar
            ride: 'carousel', // Memulai carousel secara otomatis
            wrap: true, // Memastikan carousel kembali ke foto pertama setelah mencapai foto terakhir
        });

        // Debugging: event listener untuk melacak pergeseran slide
        carouselElement.addEventListener('slide.bs.carousel', function (event) {
            console.log('Carousel sliding to:', event.to);
        });
    }

    // Campaign Carousel (pastikan elemen ada)
    const campaignCarousel = document.querySelector('#campaignCarousel');
    if (campaignCarousel) {
        const carouselInstance = new bootstrap.Carousel(campaignCarousel, {
            interval: 2000, // 2 detik per slide
            ride: 'carousel', // Memulai carousel secara otomatis
            wrap: true, // Mengaktifkan looping
        });

        // Menghilangkan bagian kosong dengan mengubah "loop" menjadi lebih lancar
        campaignCarousel.addEventListener('slide.bs.carousel', function (event) {
            // Debugging: Melacak pergeseran slide pada campaign carousel
            console.log('Campaign Carousel sliding to:', event.to);
        });

        // Mengatasi celah kosong dan looping secara mulus
        campaignCarousel.addEventListener('slid.bs.carousel', function (event) {
            var totalItems = document.querySelectorAll('#campaignCarousel .carousel-item').length;
            if (event.to === totalItems - 1) {
                // Pastikan kita mulai dari awal tanpa celah kosong
                setTimeout(function () {
                    carouselInstance.to(0); // Lompat ke slide pertama
                }, 2000); // Tunggu 2 detik agar transisi selesai
            }
        });
    }

        const donasiModal = document.getElementById('donasiModal');
        const modalBody = donasiModal.querySelector('.modal-body');
        const modalForm = donasiModal.querySelector('form');
    
        // Simpan salinan asli form untuk reset
        const originalForm = modalForm.outerHTML;
    
        // Saat modal akan ditampilkan
        donasiModal.addEventListener('show.bs.modal', function (event) {
            // Tombol yang memicu modal
            const button = event.relatedTarget;
    
            // Ambil data dari atribut tombol
            const kegiatanTitle = button.getAttribute('data-kegiatan-title');
            const kegiatanId = button.getAttribute('data-kegiatan-id');
            const kegiatanStatus = button.getAttribute('data-status');
    
            // Elemen input di modal
            const modalKegiatanInput = donasiModal.querySelector('#kegiatan');
            const modalIdInput = donasiModal.querySelector('#id_tk');
    
            // Jika status adalah "close", tampilkan pesan dan nonaktifkan form
            if (kegiatanStatus === 'close') {
                modalBody.innerHTML = `
                    <p class="text-danger text-center">Kegiatan ini sudah ditutup. Anda tidak dapat berdonasi.</p>
                `;
            } else {
                // Reset isi modal jika kegiatan terbuka
                modalBody.innerHTML = originalForm; // Kembalikan form
                const newForm = donasiModal.querySelector('form'); // Referensi baru form
                newForm.querySelector('#kegiatan').value = kegiatanTitle; // Isi judul kegiatan
                newForm.querySelector('#id_tk').value = kegiatanId; // Isi ID kegiatan
            }
        });
    
        // Reset modal saat ditutup
        donasiModal.addEventListener('hidden.bs.modal', function () {
            modalBody.innerHTML = originalForm; // Kembalikan form ke kondisi awal
        });
        
});
