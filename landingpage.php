<?php
session_start();

// Koneksi database (otomatis: XAMPP saat lokal, Aiven saat di Vercel)
require_once __DIR__ . '/config/database.php';
$conn = get_db_connection();

// Data desa dari database
$query = "SELECT * FROM desa_profil LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$desa_profil = $stmt->fetch(PDO::FETCH_ASSOC);

$desa_nama = $desa_profil['nama_desa'] ?? "Desa Winduaji";
$desa_lokasi = $desa_profil['lokasi'] ?? "Kecamatan Paninggaran, Kabupaten Pekalongan, Provinsi Jawa Tengah";
$desa_motto = $desa_profil['motto'] ?? "Bersama Membangun Desa yang Mandiri dan Berbudaya";
$sejarah = $desa_profil['sejarah'] ?? "Desa Winduaji, yang terletak di kecamatan Paninggaran...";

// Ambil data statistik dari tabel kependudukan
$query = "SELECT 
    COUNT(*) as total_penduduk,
    COUNT(DISTINCT no_kk) as jumlah_kk,
    COUNT(DISTINCT rw) as jumlah_rw,
    COUNT(DISTINCT rt) as jumlah_rt
FROM kependudukan 
WHERE status_hidup = 'Hidup'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Assign nilai ke variabel
$penduduk = $stats['total_penduduk'] ?? 0;
$kk = $stats['jumlah_kk'] ?? 0;
$rw = $stats['jumlah_rw'] ?? 0;
$rt = $stats['jumlah_rt'] ?? 0;

// Data UMKM dari database
$query = "SELECT u.*, uk.nama_kategori, uk.icon 
          FROM umkm u 
          JOIN umkm_kategori uk ON u.kategori_id = uk.id 
          WHERE u.status = 'published' 
          ORDER BY u.created_at DESC LIMIT 4";
$stmt = $conn->prepare($query);
$stmt->execute();
$umkm_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data galeri dari database (ambil 6 terbaru)
$query = "SELECT * FROM galeri WHERE status = 'published' ORDER BY created_at DESC LIMIT 6";
$stmt = $conn->prepare($query);
$stmt->execute();
$galeri = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data berita dari database (ambil 3 terbaru)
$query = "SELECT b.*, bk.nama_kategori 
          FROM berita b 
          JOIN berita_kategori bk ON b.kategori_id = bk.id 
          WHERE b.status = 'published' 
          ORDER BY b.tanggal_publish DESC LIMIT 3";
$stmt = $conn->prepare($query);
$stmt->execute();
$berita = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data kontak dari database
$query = "SELECT * FROM kontak LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$kontak = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title><?php echo $desa_nama; ?></title>
    <meta name="description" content="Website resmi <?php echo $desa_nama; ?>, Kecamatan Paninggaran, Pekalongan. Temukan sejarah, berita terbaru, produk UMKM, dan galeri kegiatan desa." />
    <meta name="keywords" content="Desa <?php echo $desa_nama; ?>, Pekalongan, Paninggaran, UMKM, Berita Desa, Galeri Desa, Profil Desa" />
    <meta name="author" content="<?php echo $desa_nama; ?>" />
    <meta name="google-site-verification" content="8TQltETp-k-cbqfx16-sN4QL4-D78h2Cd-U_LaBVhFA" />
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $desa_nama; ?> - Profil, Berita, UMKM">
    <meta property="og:description" content="Profil desa, sejarah, berita terkini, UMKM, dan galeri Desa <?php echo $desa_nama; ?>.">
    <meta property="og:image" content="https://desawinduaji.my.id/assets/images/logo.png">
    <meta property="og:url" content="https://desawinduaji.my.id/landingpage.php">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $desa_nama; ?>">
    <meta name="twitter:description" content="Website resmi Desa <?php echo $desa_nama; ?>.">
    <meta name="twitter:image" content="https://desawinduaji.my.id/assets/images/logo.png">

    <link rel="shortcut icon" href="./assets/images/logo.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <!-- Swiper CSS for Sliders -->
    <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
    <!-- Swiper JS -->
    <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
    <style>
        body {
            font-family: "Poppins", sans-serif;
            scroll-behavior: smooth;
        }

        .card-hover {
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #fcf8f8ff 0%, #e2e8f0 100%);
        }

        .mobile-menu {
            transition: transform 0.3s ease-in-out;
            transform: translateX(-100%);
        }

        .mobile-menu.active {
            transform: translateX(0);
        }

        .back-to-top {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        /* Animations for smooth transitions */
        .animate-fade-in {
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .swiper-slide {
            transition: transform 0.3s ease;
        }

        .swiper-slide-active {
            transform: scale(1.05);
        }

        /* Enhanced mobile optimizations */
        @media (max-width: 768px) {
            .hero-img {
                height: 300px !important;
            }
            .stat-card {
                flex-direction: column;
                text-align: center;
            }
            .grid-cols-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900 antialiased">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300"></div>
    
    <header class="w-full border-b border-gray-200 bg-white shadow-sm sticky top-0 z-50 transition-all duration-300">
        <nav class="container mx-auto flex items-center justify-between py-3 px-4 md:px-6 lg:px-8">
            <a class="flex items-center space-x-2" href="#">
                <img alt="Logo Desa <?php echo $desa_nama; ?>" class="h-12 w-auto transition-transform duration-300 hover:scale-105" height="50" src="./assets/images/logo.png" width="150" />
                <span class="text-xl font-bold text-red-600 hidden md:block"><?php echo $desa_nama; ?></span>
            </a>
            <ul class="hidden md:flex space-x-6 lg:space-x-8 text-sm font-medium">
                <li><a class="text-red-600 hover:text-red-700 transition-colors duration-200" href="./landingpage.php">Beranda</a></li>
                <li><a class="text-gray-700 hover:text-red-600 transition-colors duration-200" href="./pages/sejarah.php">Profil Desa</a></li>
                <li><a class="text-gray-700 hover:text-red-600 transition-colors duration-200" href="#umkm">UMKM</a></li>
                <li><a class="text-gray-700 hover:text-red-600 transition-colors duration-200" href="#berita">Berita</a></li>
                <li><a class="text-gray-700 hover:text-red-600 transition-colors duration-200" href="#galeri">Galeri</a></li>
                <li><a class="text-gray-700 hover:text-red-600 transition-colors duration-200" href="./login.php">Admin</a></li>
            </ul>
            <div class="md:hidden">
                <button class="text-gray-600 hover:text-red-600 focus:outline-none transition-colors duration-200" id="mobile-menu-button">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>
        </nav>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu fixed top-0 left-0 h-full w-72 bg-white shadow-2xl z-50 p-6 overflow-y-auto">
            <div class="flex justify-between items-center mb-8">
                <img alt="Logo Desa <?php echo $desa_nama; ?>" class="h-10 w-auto" src="./assets/images/logo.png" />
                <button id="mobile-menu-close" class="text-gray-600 hover:text-red-600 transition-colors duration-200">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            <ul class="space-y-6">
                <li><a class="text-red-600 hover:text-red-700 font-medium block py-2 transition-colors duration-200" href="#">Beranda</a></li>
                <li><a class="text-gray-700 hover:text-red-600 font-medium block py-2 transition-colors duration-200" href="#umkm">UMKM</a></li>
                <li><a class="text-gray-700 hover:text-red-600 font-medium block py-2 transition-colors duration-200" href="#berita">Berita</a></li>
                <li><a class="text-gray-700 hover:text-red-600 font-medium block py-2 transition-colors duration-200" href="#galeri">Galeri</a></li>
                <li><a class="text-gray-700 hover:text-red-600 font-medium block py-2 transition-colors duration-200" href="./login.php">Admin</a></li>
            </ul>
        </div>
    </header>

    <section aria-label="Hero section" class="relative w-full overflow-hidden animate-fade-in">
        <div class="swiper hero-slider">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <img alt="Pemandangan Desa <?php echo $desa_nama; ?>" class="w-full h-[400px] md:h-[600px] object-cover" src="./assets/images/landingpage/bg.png" />
            </div>
            <div class="swiper-pagination"></div>
        </div>
        <div class="absolute inset-0 bg-gradient-to-b from-black/60 to-black/40 flex flex-col justify-center items-center text-center px-4 z-10">
            <span class="bg-white/20 text-white text-xs rounded-full px-4 py-1 mb-4 backdrop-blur-sm">Website Resmi <?php echo $desa_nama; ?></span>
            <h1 class="text-white font-extrabold text-4xl md:text-6xl max-w-4xl leading-tight">Selamat Datang di Website Resmi <span class="text-red-500"><?php echo $desa_nama; ?></span></h1>
            <p class="text-gray-100 max-w-2xl mt-4 text-base md:text-lg leading-relaxed"><?php echo $desa_motto; ?></p>
            <div class="mt-8 flex flex-col md:flex-row gap-4 justify-center">
                <a class="bg-red-600 hover:bg-red-700 text-white font-semibold rounded-full px-8 py-3 inline-flex items-center justify-center transition-all duration-300 hover:shadow-lg" href="#tentang">
                    Tentang Desa <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                </a>
                <a class="border border-white/50 hover:border-white text-white bg-transparent hover:bg-white/10 font-semibold rounded-full px-8 py-3 inline-flex items-center justify-center transition-all duration-300" href="#kontak">
                    <i class="fas fa-phone-alt mr-2"></i> Hubungi Kami
                </a>
            </div>
        </div>
    </section>

    <section aria-label="Statistik desa" class="container mx-auto px-4 -mt-20 md:-mt-32 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-7xl z-20 relative animate-fade-in">
    <div class="bg-white rounded-2xl shadow-xl p-6 flex items-center space-x-4 card-hover stat-card transform hover:scale-105 transition-transform duration-300">
        <div class="bg-red-50 text-red-600 rounded-full p-4"><i class="fas fa-users fa-2x"></i></div>
        <div><p class="text-3xl font-bold text-gray-900"><?php echo number_format($penduduk, 0, ',', '.'); ?></p><p class="text-sm text-gray-600">Jiwa Penduduk</p></div>
    </div>
    <div class="bg-white rounded-2xl shadow-xl p-6 flex items-center space-x-4 card-hover stat-card transform hover:scale-105 transition-transform duration-300">
        <div class="bg-red-50 text-red-600 rounded-full p-4"><i class="fas fa-user-friends fa-2x"></i></div>
        <div><p class="text-3xl font-bold text-gray-900"><?php echo number_format($kk, 0, ',', '.'); ?></p><p class="text-sm text-gray-600">Kepala Keluarga</p></div>
    </div>
    <div class="bg-white rounded-2xl shadow-xl p-6 flex items-center space-x-4 card-hover stat-card transform hover:scale-105 transition-transform duration-300">
        <div class="bg-red-50 text-red-600 rounded-full p-4"><i class="fas fa-map-marker-alt fa-2x"></i></div>
        <div><p class="text-3xl font-bold text-gray-900"><?php echo $rw; ?></p><p class="text-sm text-gray-600">Rukun Warga</p></div>
    </div>
   <div class="bg-white rounded-2xl shadow-xl p-6 flex items-center space-x-4 card-hover stat-card transform hover:scale-105 transition-transform duration-300">
        <div class="bg-red-50 text-red-600 rounded-full p-4"><i class="fas fa-map-marker-alt fa-2x"></i></div>
        <div><p class="text-3xl font-bold text-gray-900"><?php echo $rt; ?></p><p class="text-sm text-gray-600">Rukun Tetangga</p></div>
    </div>
</section>

    

    <main class="container mx-auto max-w-6xl px-4 mt-24" id="tentang">
        <div class="text-center mb-16 animate-fade-in">
            <span class="inline-block bg-red-50 text-red-600 text-sm font-semibold rounded-full px-6 py-2 mb-4">Tentang Kami</span>
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Mengenal <?php echo $desa_nama; ?></h2>
            <p class="text-gray-600 text-lg max-w-3xl mx-auto">Perjalanan panjang sejarah dan budaya yang membentuk identitas <?php echo $desa_nama; ?></p>
            <div class="w-24 h-1 bg-red-600 mx-auto mt-6 rounded-full"></div>
        </div>
        <div class="grid md:grid-cols-2 gap-12 animate-fade-in">
            <img alt="Panoramic view of <?php echo $desa_nama; ?>" class="rounded-2xl shadow-lg w-full object-cover h-96 transform hover:scale-105 transition-transform duration-500" src="./assets/images/landingpage/bg1.png" />
            <div class="flex flex-col justify-center">
                <h3 class="text-2xl font-semibold mb-6">Tentang Desa</h3>
                <p class="text-gray-700 text-lg leading-relaxed"><?php echo $sejarah; ?></p>
            </div>
        </div>
    </main>

    <section aria-label="Visi Misi Desa" class="container mx-auto max-w-6xl px-4 mt-24">
        <div class="text-center mb-16 animate-fade-in">
            <span class="inline-block bg-red-50 text-red-600 text-sm font-semibold rounded-full px-6 py-2 mb-4">Visi & Misi</span>
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Arah Pembangunan Desa</h2>
            <p class="text-gray-600 text-lg max-w-3xl mx-auto">Komitmen kami dalam membangun masa depan yang berkelanjutan</p>
            <div class="w-24 h-1 bg-red-600 mx-auto mt-6 rounded-full"></div>
        </div>
        <div class="grid md:grid-cols-2 gap-12">
            <div class="bg-white rounded-2xl shadow-xl p-10 card-hover transform hover:scale-105 transition-transform duration-300 animate-fade-in">
                <div class="bg-red-50 text-red-600 rounded-full p-4 w-16 h-16 flex items-center justify-center mx-auto mb-6"><i class="fas fa-award fa-2x"></i></div>
                <h3 class="text-center text-2xl font-semibold mb-4">Visi Desa</h3>
                <hr class="border-red-600 w-16 mx-auto mb-6" />
                <p class="text-center text-gray-700 text-lg leading-relaxed">Mewujudkan <?php echo $desa_nama; ?> yang mandiri, sejahtera, dan berbudaya melalui pemerintahan berbasis kearifan lokal dan pembangunan berkelanjutan.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-xl p-10 card-hover transform hover:scale-105 transition-transform duration-300 animate-fade-in">
                <div class="bg-red-50 text-red-600 rounded-full p-4 w-16 h-16 flex items-center justify-center mx-auto mb-6"><i class="fas fa-file-alt fa-2x"></i></div>
                <h3 class="text-center text-2xl font-semibold mb-4">Misi Desa</h3>
                <hr class="border-red-600 w-16 mx-auto mb-6" />
                <ol class="list-decimal list-inside text-gray-700 text-lg space-y-4 leading-relaxed">
                    <li>Meningkatkan kualitas pelayanan publik yang transparan dan akuntabel</li>
                    <li>Mengembangkan ekonomi desa berbasis potensi lokal</li>
                    <li>Meningkatkan kualitas pendidikan dan kesehatan masyarakat</li>
                    <li>Melestarikan budaya dan kearifan lokal</li>
                    <li>Membangun infrastruktur desa yang berkelanjutan</li>
                </ol>
            </div>
        </div>
    </section>

    <section id="umkm" class="gradient-bg py-24 mt-24">
        <div class="container mx-auto max-w-7xl px-4">
            <div class="text-center mb-16 animate-fade-in">
                <span class="inline-block bg-red-50 text-red-600 text-sm font-semibold rounded-full px-6 py-2 mb-4">Produk UMKM</span>
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Produk Unggulan Desa</h2>
                <p class="text-gray-600 text-lg max-w-3xl mx-auto">Dukung perekonomian lokal dengan membeli produk UMKM <?php echo $desa_nama; ?></p>
                <div class="w-24 h-1 bg-red-600 mx-auto mt-6 rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php if (!empty($umkm_data)): ?>
                    <?php foreach ($umkm_data as $item): ?>
                        <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover transform hover:scale-105 transition-transform duration-300 animate-fade-in">
                            <div class="h-64 bg-gray-100 overflow-hidden">
                                <?php if (!empty($item['foto_utama_path'])): ?>
                                    <img src="<?php echo $item['foto_utama_path']; ?>" alt="<?php echo $item['nama_umkm']; ?>" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center"><i class="fas fa-image text-gray-400 text-6xl"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div class="bg-red-50 text-red-600 rounded-full p-3 mr-3"><i class="<?php echo $item['icon'] ?? 'fas fa-store'; ?> fa-lg"></i></div>
                                    <span class="text-sm font-semibold text-red-600"><?php echo $item['nama_kategori']; ?></span>
                                </div>
                                <h3 class="font-bold text-xl mb-3"><?php echo $item['nama_umkm']; ?></h3>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo $item['deskripsi'] ?? 'Tidak ada deskripsi'; ?></p>
                                <div class="flex justify-between items-center">
                                    <?php if (!empty($item['harga'])): ?>
                                        <span class="text-red-600 font-bold text-lg"><?php echo $item['harga']; ?></span>
                                    <?php endif; ?>
                                    <a href="./pages/detail-umkm.php" class="text-sm text-red-600 font-semibold hover:underline transition-colors duration-200">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full bg-white rounded-2xl shadow-xl p-12 flex flex-col items-center text-center text-gray-500 animate-fade-in">
                        <i class="fas fa-store fa-4x mb-6 text-red-600"></i>
                        <h3 class="font-bold text-2xl mb-3 text-gray-800">Belum Ada Data UMKM</h3>
                        <p class="text-lg max-w-lg">Data UMKM akan segera hadir di halaman ini</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-12">
                <a class="inline-block bg-red-600 hover:bg-red-700 text-white font-semibold rounded-full px-8 py-3 transition-all duration-300 hover:shadow-lg" href="./pages/umkm.php">
                    Lihat Semua UMKM
                </a>
            </div>
        </div>
    </section>

    <section id="galeri" class="py-24 mt-24 bg-gray-50">
        <div class="container mx-auto max-w-7xl px-4">
            <div class="text-center mb-16 animate-fade-in">
                <span class="inline-block bg-red-50 text-red-600 text-sm font-semibold rounded-full px-6 py-2 mb-4">Galeri Desa</span>
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Momen Terbaik <?php echo $desa_nama; ?></h2>
                <p class="text-gray-600 text-lg max-w-3xl mx-auto">Dokumentasi kegiatan dan keindahan <?php echo $desa_nama; ?></p>
                <div class="w-24 h-1 bg-red-600 mx-auto mt-6 rounded-full"></div>
            </div>

            <?php if (!empty($galeri)): ?>
                <div class="swiper galeri-slider">
                    <div class="swiper-wrapper">
                        <?php foreach ($galeri as $item): ?>
                            <div class="swiper-slide">
                                <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover mx-4">
                                    <div class="h-80 bg-gray-100 overflow-hidden">
                                        <?php if ($item['tipe'] == 'foto' && !empty($item['file_path'])): ?>
                                            <img src="<?php echo $item['file_path']; ?>" alt="<?php echo $item['judul']; ?>" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                                        <?php elseif ($item['tipe'] == 'video'): ?>
                                            <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                                <i class="fas fa-play-circle text-white text-6xl"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center"><i class="fas fa-image text-gray-400 text-6xl"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-6">
                                        <h3 class="font-bold text-xl mb-3"><?php echo $item['judul']; ?></h3>
                                        <p class="text-gray-600 text-sm mb-3"><?php echo date('d F Y', strtotime($item['tanggal_upload'])); ?></p>
                                        <?php if (!empty($item['deskripsi'])): ?>
                                            <p class="text-gray-700 text-sm line-clamp-2"><?php echo $item['deskripsi']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next text-red-600"></div>
                    <div class="swiper-button-prev text-red-600"></div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-xl p-12 flex flex-col items-center text-center text-gray-500 animate-fade-in">
                    <i class="fas fa-images fa-4x mb-6 text-red-600"></i>
                    <h3 class="font-bold text-2xl mb-3 text-gray-800">Belum Ada Galeri</h3>
                    <p class="text-lg max-w-lg">Galeri akan segera hadir di halaman ini</p>
                </div>
            <?php endif; ?>

            <div class="text-center mt-12">
                <a class="inline-block bg-red-600 hover:bg-red-700 text-white font-semibold rounded-full px-8 py-3 transition-all duration-300 hover:shadow-lg" href="./pages/galeri.php">
                    Lihat Semua Galeri
                </a>
            </div>
        </div>
    </section>

    <section id="berita" class="gradient-bg py-24 mt-24">
        <div class="container mx-auto max-w-7xl px-4">
            <div class="text-center mb-16 animate-fade-in">
                <span class="inline-block bg-red-50 text-red-600 text-sm font-semibold rounded-full px-6 py-2 mb-4">Berita Terkini</span>
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Berita dan Informasi Terbaru</h2>
                <p class="text-gray-600 text-lg max-w-3xl mx-auto">Update terbaru dari <?php echo $desa_nama; ?></p>
                <div class="w-24 h-1 bg-red-600 mx-auto mt-6 rounded-full"></div>
            </div>

            <div class="swiper berita-slider">
                <div class="swiper-wrapper">
                    <?php if (!empty($berita)): ?>
                        <?php foreach ($berita as $item): ?>
                            <div class="swiper-slide">
                                <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover mx-4">
                                    <div class="h-64 bg-gray-100 overflow-hidden">
                                        <?php if (!empty($item['gambar_path'])): ?>
                                            <img src="<?php echo $item['gambar_path']; ?>" alt="<?php echo $item['judul']; ?>" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center"><i class="fas fa-newspaper text-gray-400 text-6xl"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-6">
                                        <span class="text-sm font-semibold text-red-600 mb-2 block"><?php echo $item['nama_kategori']; ?></span>
                                        <h3 class="font-bold text-xl mb-3"><?php echo $item['judul']; ?></h3>
                                        <p class="text-gray-600 text-sm mb-4"><?php echo date('d F Y', strtotime($item['tanggal_publish'])); ?></p>
                                        <p class="text-gray-700 text-sm line-clamp-3 mb-4">
                                            <?php echo strip_tags(substr($item['isi'], 0, 150)); ?>...
                                        </p>
                                        <a href="#" class="inline-block text-red-600 text-sm font-semibold hover:underline transition-colors duration-200">Baca Selengkapnya</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="swiper-slide">
                            <div class="bg-white rounded-2xl shadow-xl p-12 flex flex-col items-center text-center text-gray-500 mx-4 animate-fade-in">
                                <i class="fas fa-newspaper fa-4x mb-6 text-red-600"></i>
                                <h3 class="font-bold text-2xl mb-3 text-gray-800">Belum Ada Berita</h3>
                                <p class="text-lg max-w-lg">Berita terbaru akan segera hadir di halaman ini</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-next text-red-600"></div>
                <div class="swiper-button-prev text-red-600"></div>
            </div>

            <div class="text-center mt-12">
                <a class="inline-block bg-red-600 hover:bg-red-700 text-white font-semibold rounded-full px-8 py-3 transition-all duration-300 hover:shadow-lg" href="./pages/berita.php">
                    Lihat Semua Berita
                </a>
            </div>
        </div>
    </section>

    <section aria-label="Call to action" class="bg-red-800 text-white py-24 mt-24 relative overflow-hidden animate-fade-in">
        <div class="container mx-auto max-w-5xl px-4 text-center relative z-10">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">Mari Bersama Membangun Desa</h2>
            <p class="max-w-3xl mx-auto mb-10 text-lg leading-relaxed">Bergabunglah dengan kami dalam memajukan <?php echo $desa_nama; ?>. Suara dan partisipasi Anda sangat berarti bagi masa depan desa.</p>
            <div class="flex flex-col md:flex-row justify-center gap-6">
                <a class="bg-red-600 hover:bg-red-700 rounded-full px-10 py-4 inline-flex items-center justify-center gap-3 transition-all duration-300 hover:shadow-xl" href="#kontak">
                    <i class="fas fa-phone-alt"></i> Hubungi Kami
                </a>
                <a class="border border-white hover:bg-white/10 rounded-full px-10 py-4 inline-flex items-center justify-center gap-3 transition-all duration-300" href="#">
                    <i class="fas fa-envelope"></i> Layanan Desa
                </a>
            </div>
        </div>
        <div aria-hidden="true" class="absolute top-0 left-0 w-64 h-64 rounded-full bg-red-700 opacity-20 -translate-x-1/2 -translate-y-1/2 blur-xl"></div>
        <div aria-hidden="true" class="absolute bottom-0 right-0 w-96 h-96 rounded-full bg-red-700 opacity-10 translate-x-1/3 translate-y-1/3 blur-xl"></div>
    </section>

    <footer class="bg-gray-900 text-gray-300 pt-20 pb-10">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-12">
            <div class="animate-fade-in">
                <h3 class="font-bold text-2xl mb-6 flex items-center text-white">
                    <img src="./assets/images/logo.png" class="w-8 h-10 mr-3" alt="Logo Desa"> Desa Winduaji
                </h3>
                <p class="mb-6 text-gray-400">Desa Winduaji adalah desa yang terletak di Kecamatan Paninggaran, Kabupaten Pekalongan, Jawa Tengah.</p>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200 text-xl"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200 text-xl"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200 text-xl"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200 text-xl"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="animate-fade-in">
                <h3 class="font-bold text-xl mb-6 text-white">Link Cepat</h3>
                <ul class="space-y-4">
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200 flex items-center"><i class="fas fa-chevron-right text-xs mr-3 text-red-500"></i> Profil Desa</a></li>
                    <li><a href="./pages/umkm.php" class="text-gray-400 hover:text-white transition-colors duration-200 flex items-center"><i class="fas fa-chevron-right text-xs mr-3 text-red-500"></i> UMKM Desa</a></li>
                    <li><a href="./pages/berita.php" class="text-gray-400 hover:text-white transition-colors duration-200 flex items-center"><i class="fas fa-chevron-right text-xs mr-3 text-red-500"></i> Berita</a></li>
                    <li><a href="./pages/galeri.php" class="text-gray-400 hover:text-white transition-colors duration-200 flex items-center"><i class="fas fa-chevron-right text-xs mr-3 text-red-500"></i> Galeri</a></li>
                </ul>
            </div>
            
            <div class="animate-fade-in">
                <h3 class="font-bold text-xl mb-6 text-white">Kontak Kami</h3>
                <ul class="space-y-4">
                    <li class="flex items-start"><i class="fas fa-map-marker-alt mt-1 mr-4 text-red-500 text-xl"></i><span class="text-gray-400">Jl. Raya Winduaji No. 123, Kec. Pekalongan, Kab. Pekalongan, Jawa Tengah</span></li>
                    <li class="flex items-center"><i class="fas fa-phone-alt mr-4 text-red-500 text-xl"></i><span class="text-gray-400">+62 812 9494 2548</span></li>
                    <li class="flex items-center"><i class="fas fa-envelope mr-4 text-red-500 text-xl"></i><span class="text-gray-400">info@winduaji.id</span></li>
                    <li class="flex items-center"><i class="fas fa-clock mr-4 text-red-500 text-xl"></i><span class="text-gray-400">Senin-Jumat: 08:00 - 16:00</span></li>
                </ul>
            </div>
            
            
            <div class="animate-fade-in">
                <h3 class="font-bold text-xl mb-6 text-white">Lokasi Desa</h3>
                <div class="bg-gray-800 rounded-xl overflow-hidden h-64 border border-gray-700 shadow-lg">
                    <iframe 
    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.030574473294!2d109.5566585!3d-7.161912299999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6fe3ceb99d111b%3A0x11bb0af36ed14b2f!2sBalai%20desa%20winduaji!5e0!3m2!1sid!2sid!4v1724395200000!5m2!1sid!2sid" 
    width="100%" 
    height="400" 
    style="border:0;" 
    allowfullscreen="" 
    loading="lazy" 
    referrerpolicy="no-referrer-when-downgrade">
</iframe>

                </div>
            </div>
        </div>
        
        <div class="border-t border-gray-800 pt-10 mt-12 text-center text-gray-500 text-sm">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $desa_nama; ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="fixed bottom-6 right-6 bg-red-600 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-xl hover:bg-red-700 transition-all duration-300 opacity-0 invisible back-to-top">
        <i class="fas fa-arrow-up text-xl"></i>
    </button>

    <script>
        // Mobile Menu Toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        
        function toggleMobileMenu() {
            mobileMenu.classList.toggle('active');
            mobileMenuOverlay.classList.toggle('hidden');
            document.body.classList.toggle('overflow-hidden');
        }
        
        mobileMenuButton.addEventListener('click', toggleMobileMenu);
        mobileMenuClose.addEventListener('click', toggleMobileMenu);
        mobileMenuOverlay.addEventListener('click', toggleMobileMenu);
        
        // Close mobile menu on link click
        mobileMenu.querySelectorAll('a').forEach(link => link.addEventListener('click', toggleMobileMenu));
        
        // Back to Top
        const backToTopButton = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 400) {
                backToTopButton.classList.remove('opacity-0', 'invisible');
                backToTopButton.classList.add('opacity-100');
            } else {
                backToTopButton.classList.remove('opacity-100');
                backToTopButton.classList.add('opacity-0', 'invisible');
            }
        });
        backToTopButton.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        
        // Initialize Swipers
        new Swiper('.hero-slider', {
            loop: true,
            autoplay: { delay: 5000, disableOnInteraction: false },
            pagination: { el: '.swiper-pagination', clickable: true },
            effect: 'fade',
            fadeEffect: { crossFade: true },
        });

        new Swiper('.galeri-slider', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
            breakpoints: {
                640: { slidesPerView: 2 },
                1024: { slidesPerView: 3 },
            },
        });

        new Swiper('.berita-slider', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
            breakpoints: {
                640: { slidesPerView: 2 },
                1024: { slidesPerView: 3 },
            },
        });
    </script>
</body>

</html>