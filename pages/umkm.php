<?php
require_once '../config/database.php';
require_once '../config/functions.php';

$db   = new Database();
$conn = $db->getConnection();

$error           = '';
$umkm_data       = [];
$kategori_umkm   = [];
$kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$q               = trim($_GET['q'] ?? '');

try {
    $stmt = $conn->prepare("SELECT * FROM umkm_kategori ORDER BY nama_kategori");
    $stmt->execute();
    $kategori_umkm = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Gagal memuat kategori UMKM.";
}

$sql = "SELECT u.*, uk.nama_kategori, uk.icon
        FROM umkm u
        LEFT JOIN umkm_kategori uk ON u.kategori_id = uk.id
        WHERE u.status = 'published'";
$params = [];

if ($kategori_filter > 0) {
    $sql .= " AND u.kategori_id = :kategori_id";
    $params[':kategori_id'] = $kategori_filter;
}
if ($q !== '') {
    $sql .= " AND (u.nama_umkm LIKE :q OR u.deskripsi LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
$sql .= " ORDER BY u.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $umkm_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Gagal memuat data UMKM.";
}

$kontak_data = [];
try {
    $stmt = $conn->prepare("SELECT * FROM kontak LIMIT 1");
    $stmt->execute();
    $kontak_data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    try {
        $stmt = $conn->prepare("SELECT * FROM no_telp LIMIT 1");
        $stmt->execute();
        $nt = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($nt) {
            $kontak_data = [
                'telepon'         => $nt['no_telp'] ?? '',
                'whatsapp_number' => $nt['no_telp'] ?? '',
            ];
        }
    } catch (Exception $e2) {
    }
}

function cleanPhoneNumber($phone) {
    $n = preg_replace('/[^0-9]/', '', (string)$phone);
    if (strpos($n, '0') === 0) {
        $n = '62' . substr($n, 1);
    }
    return $n;
}

function formatHarga($harga) {
    if ($harga === null || $harga === '') return '';
    if (is_numeric($harga)) return 'Rp ' . number_format((float)$harga, 0, ',', '.');
    return htmlspecialchars($harga);
}

function fotoUrl($path) {
    if (empty($path)) return '';
    if (preg_match('#^https?://#', $path)) return $path;
    return '../' . ltrim($path, './');
}

$total = count($umkm_data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UMKM Desa Winduaji - Kecamatan Paninggaran, Kabupaten Pekalongan</title>
    <meta name="description" content="Produk UMKM unggulan Desa Winduaji, Kecamatan Paninggaran, Kabupaten Pekalongan.">
    <link rel="shortcut icon" href="../assets/images/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; scroll-behavior: smooth; }

        .hero-section {
            background: linear-gradient(rgba(0,0,0,.6), rgba(0,0,0,.6)), url('../assets/images/landingpage/bg.png');
            background-size: cover; background-position: center; color: #fff;
        }

        /* Kartu */
        .umkm-card {
            border: 1px solid #f1f5f9;
            transition: transform .3s ease, box-shadow .3s ease;
        }
        .umkm-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 32px -10px rgba(0,0,0,.18);
        }

        /* Area foto: tinggi tetap, ada jarak di sekeliling */
        .umkm-img {
    position: relative; height: 240px; overflow: hidden;
    background: #f8fafc; padding: 12px;
}
.umkm-img img {
    width: 100%; height: 100%;
    object-fit: contain; border-radius: 12px;
}

        /* Badge di atas foto */
        .badge-kategori {
            position: absolute; top: 12px; left: 12px; z-index: 2;
            background: rgba(255,255,255,.92); color: #dc2626;
            font-size: 11px; font-weight: 600; padding: 5px 12px;
            border-radius: 999px; box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }
        .badge-harga {
            position: absolute; bottom: 12px; right: 12px; z-index: 2;
            background: #dc2626; color: #fff;
            font-size: 13px; font-weight: 600; padding: 5px 14px;
            border-radius: 999px; box-shadow: 0 4px 12px rgba(220,38,38,.4);
        }

        .desc-clamp {
            display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
        }
        .title-clamp {
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }

        /* Kategori */
        .category-btn { transition: all .25s ease; }
        .category-btn:hover { background-color: #dc2626; border-color: #dc2626; color: #fff; }
        .category-btn.active { background-color: #dc2626; border-color: #dc2626; color: #fff; box-shadow: 0 4px 12px rgba(220,38,38,.3); }

        .whatsapp-btn { background-color: #25D366; transition: background-color .3s ease; }
        .whatsapp-btn:hover { background-color: #128C7E; }

        .detail-btn { border: 1px solid #fecaca; color: #dc2626; transition: all .25s ease; }
        .detail-btn:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

        /* Menu mobile */
        .mobile-menu { transition: transform .3s ease-in-out; transform: translateX(-100%); }
        .mobile-menu.active { transform: translateX(0); }
    </style>
</head>
<body class="text-gray-900 antialiased">

    <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="../landingpage.php" class="flex items-center">
                <img src="../assets/images/logo.png" alt="Logo Desa Winduaji" class="w-12 h-12 mr-3 object-contain">
                <span class="text-xl font-bold text-red-600">Desa Winduaji</span>
            </a>

            <nav class="hidden md:flex space-x-8 text-sm font-medium">
                <a href="../landingpage.php" class="text-gray-700 hover:text-red-600">Beranda</a>
                <a href="../pages/sejarah.php" class="text-gray-700 hover:text-red-600">Profil Desa</a>
                <a href="../pages/umkm.php" class="text-red-600 font-semibold">UMKM</a>
                <a href="../pages/berita.php" class="text-gray-700 hover:text-red-600">Berita</a>
                <a href="../pages/galeri.php" class="text-gray-700 hover:text-red-600">Galeri</a>
                <a href="../login.php" class="text-gray-700 hover:text-red-600">Admin</a>
            </nav>

            <button id="mobile-menu-button" class="md:hidden text-gray-700 focus:outline-none" aria-label="Buka menu">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>

        <div id="mobile-menu" class="mobile-menu fixed top-0 left-0 h-full w-72 bg-white shadow-2xl z-50 p-6 overflow-y-auto">
            <div class="flex justify-between items-center mb-8">
                <img src="../assets/images/logo.png" alt="Logo" class="h-10 w-auto">
                <button id="mobile-menu-close" class="text-gray-600 hover:text-red-600" aria-label="Tutup menu">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <ul class="space-y-4 font-medium">
                <li><a href="../landingpage.php" class="block py-2 text-gray-700 hover:text-red-600">Beranda</a></li>
                <li><a href="../pages/sejarah.php" class="block py-2 text-gray-700 hover:text-red-600">Profil Desa</a></li>
                <li><a href="../pages/umkm.php" class="block py-2 text-red-600 font-semibold">UMKM</a></li>
                <li><a href="../pages/berita.php" class="block py-2 text-gray-700 hover:text-red-600">Berita</a></li>
                <li><a href="../pages/galeri.php" class="block py-2 text-gray-700 hover:text-red-600">Galeri</a></li>
                <li><a href="../login.php" class="block py-2 text-gray-700 hover:text-red-600">Admin</a></li>
            </ul>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero-section py-20">
        <div class="container mx-auto px-4 text-center">
            <span class="inline-block bg-white bg-opacity-20 text-xs rounded-full px-4 py-1 mb-4">Produk Lokal Desa Winduaji</span>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">UMKM Desa Winduaji</h1>
            <p class="text-lg md:text-xl mb-8 max-w-2xl mx-auto">Produk Unggulan Khas Kecamatan Paninggaran, Kabupaten Pekalongan</p>
            <a href="#produk" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg inline-flex items-center">
                <span>Jelajahi Produk</span>
                <i class="fas fa-arrow-down ml-2"></i>
            </a>
        </div>
    </section>

    <!-- Pencarian & Kategori -->
    <section class="py-10 bg-white border-b border-gray-100">
        <div class="container mx-auto px-4">
            <form method="GET" class="max-w-xl mx-auto mb-8 flex shadow-sm rounded-full">
                <?php if ($kategori_filter > 0): ?>
                    <input type="hidden" name="kategori" value="<?php echo $kategori_filter; ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>"
                       placeholder="Cari produk UMKM..."
                       class="flex-1 border border-gray-300 rounded-l-full px-5 py-3 focus:outline-none focus:border-red-500">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 rounded-r-full" aria-label="Cari">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <div class="flex flex-wrap justify-center gap-3">
                <a href="?<?php echo $q !== '' ? 'q=' . urlencode($q) : ''; ?>"
                   class="category-btn px-6 py-2 rounded-full border font-medium text-sm <?php echo $kategori_filter === 0 ? 'active' : 'border-gray-300 text-gray-700'; ?>">
                    Semua
                </a>
                <?php foreach ($kategori_umkm as $kat): ?>
                    <a href="?kategori=<?php echo $kat['id']; ?><?php echo $q !== '' ? '&q=' . urlencode($q) : ''; ?>"
                       class="category-btn px-6 py-2 rounded-full border font-medium text-sm <?php echo $kategori_filter === (int)$kat['id'] ? 'active' : 'border-gray-300 text-gray-700'; ?>">
                        <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Produk -->
    <section id="produk" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-3">Produk Unggulan</h2>
            <div class="w-20 h-1 bg-red-600 mx-auto mb-4 rounded-full"></div>
            <p class="text-center text-gray-600 mb-2 max-w-2xl mx-auto">
                Temukan berbagai produk UMKM unggulan dari warga Desa Winduaji yang dibuat dengan bahan-bahan pilihan dan proses tradisional.
            </p>
            <p class="text-center text-sm text-gray-500 mb-10">
                Menampilkan <?php echo $total; ?> produk<?php echo $q !== '' ? ' untuk "' . htmlspecialchars($q) . '"' : ''; ?>
            </p>

            <?php if ($error): ?>
                <div class="max-w-2xl mx-auto mb-8 bg-red-50 text-red-700 rounded-lg p-4 text-center">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($umkm_data)): ?>
                <div class="bg-white rounded-2xl p-12 text-center shadow-sm">
                    <i class="fas fa-store-alt text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 mb-4">Belum ada produk UMKM yang sesuai.</p>
                    <a href="umkm.php" class="text-red-600 font-medium hover:underline">Lihat semua produk</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($umkm_data as $umkm):
                        $foto  = fotoUrl($umkm['foto_utama_path'] ?? '');
                        $harga = formatHarga($umkm['harga'] ?? '');
                    ?>
                        <article class="umkm-card bg-white rounded-2xl overflow-hidden shadow-md flex flex-col">
                            <div class="umkm-img">
                                <span class="badge-kategori">
                                    <?php if (!empty($umkm['icon'])): ?>
                                        <i class="<?php echo htmlspecialchars($umkm['icon']); ?> mr-1"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($umkm['nama_kategori'] ?? 'Umum'); ?>
                                </span>

                                <?php if ($foto): ?>
                                    
                                    <img src="<?php echo htmlspecialchars($foto); ?>"
                                         alt="<?php echo htmlspecialchars($umkm['nama_umkm']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-store-alt text-5xl text-gray-300"></i>
                                    </div>
                                <?php endif; ?>

                                <?php if ($harga): ?>
                                    <span class="badge-harga"><?php echo $harga; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="p-5 flex flex-col flex-1">
                                <h3 class="text-lg font-bold mb-2 title-clamp"><?php echo htmlspecialchars($umkm['nama_umkm']); ?></h3>

                                <p class="text-gray-600 text-sm mb-4 desc-clamp">
                                    <?php echo htmlspecialchars($umkm['deskripsi'] ?? 'Tidak ada deskripsi'); ?>
                                </p>

                                <div class="flex items-center gap-2 mt-auto pt-4 border-t border-gray-100">
                                    <?php if (!empty($umkm['no_telp'])):
                                        $wa    = cleanPhoneNumber($umkm['no_telp']);
                                        $pesan = urlencode('Saya tertarik dengan produk ' . $umkm['nama_umkm'] . ' dari Desa Winduaji');
                                    ?>
                                        <a href="https://wa.me/<?php echo $wa; ?>?text=<?php echo $pesan; ?>" target="_blank" rel="noopener"
                                           class="whatsapp-btn flex-1 text-white text-sm font-medium px-3 py-2 rounded-lg flex items-center justify-center">
                                            <i class="fab fa-whatsapp mr-2"></i><span>Hubungi</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="flex-1 text-gray-400 text-xs text-center">Tidak ada kontak</span>
                                    <?php endif; ?>

                                    <a href="detail-umkm-single.php?id=<?php echo (int)$umkm['id']; ?>"
                                       class="detail-btn flex-1 text-sm font-medium px-3 py-2 rounded-lg flex items-center justify-center">
                                        <span>Detail</span><i class="fas fa-arrow-right ml-2 text-xs"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-16 bg-red-600 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-4">Tertarik dengan Produk Kami?</h2>
            <p class="text-lg mb-8 max-w-2xl mx-auto">Pesan sekarang juga dan dapatkan produk UMKM khas Desa Winduaji dengan kualitas terbaik.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <?php if (!empty($kontak_data['whatsapp_number'])): ?>
                    <a href="https://wa.me/<?php echo cleanPhoneNumber($kontak_data['whatsapp_number']); ?>" target="_blank" rel="noopener"
                       class="whatsapp-btn px-6 py-3 rounded-lg font-semibold flex items-center justify-center">
                        <i class="fab fa-whatsapp text-xl mr-2"></i><span>Hubungi via WhatsApp</span>
                    </a>
                <?php endif; ?>
                <?php if (!empty($kontak_data['email'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($kontak_data['email']); ?>"
                       class="bg-white text-red-600 px-6 py-3 rounded-lg font-semibold flex items-center justify-center">
                        <i class="fas fa-envelope mr-2"></i><span>Kirim Email</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">Desa Winduaji</h3>
                    <p class="text-gray-400">Kecamatan Paninggaran, Kabupaten Pekalongan, Provinsi Jawa Tengah</p>
                </div>

                <div>
                    <h3 class="text-xl font-bold mb-4">Tautan Cepat</h3>
                    <ul class="space-y-2">
                        <li><a href="../landingpage.php" class="text-gray-400 hover:text-white">Beranda</a></li>
                        <li><a href="../pages/berita.php" class="text-gray-400 hover:text-white">Berita</a></li>
                        <li><a href="../pages/umkm.php" class="text-gray-400 hover:text-white">UMKM</a></li>
                        <li><a href="../pages/galeri.php" class="text-gray-400 hover:text-white">Galeri</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xl font-bold mb-4">Kontak Kami</h3>
                    <ul class="space-y-2">
                        <?php if (!empty($kontak_data['telepon'])): ?>
                            <li class="flex items-center">
                                <i class="fas fa-phone-alt mr-3 text-gray-400"></i>
                                <span class="text-gray-400"><?php echo htmlspecialchars($kontak_data['telepon']); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($kontak_data['email'])): ?>
                            <li class="flex items-center">
                                <i class="fas fa-envelope mr-3 text-gray-400"></i>
                                <span class="text-gray-400"><?php echo htmlspecialchars($kontak_data['email']); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($kontak_data['whatsapp_number'])): ?>
                            <li class="flex items-center">
                                <i class="fab fa-whatsapp mr-3 text-gray-400"></i>
                                <span class="text-gray-400"><?php echo htmlspecialchars($kontak_data['whatsapp_number']); ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xl font-bold mb-4">Ikuti Kami</h3>
                    <div class="flex space-x-4">
                        <?php if (!empty($kontak_data['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($kontak_data['facebook_url']); ?>" target="_blank" rel="noopener"
                               class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($kontak_data['instagram_url'])): ?>
                            <a href="<?php echo htmlspecialchars($kontak_data['instagram_url']); ?>" target="_blank" rel="noopener"
                               class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($kontak_data['youtube_url'])): ?>
                            <a href="<?php echo htmlspecialchars($kontak_data['youtube_url']); ?>" target="_blank" rel="noopener"
                               class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($kontak_data['whatsapp_number'])): ?>
                            <a href="https://wa.me/<?php echo cleanPhoneNumber($kontak_data['whatsapp_number']); ?>" target="_blank" rel="noopener"
                               class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600"><i class="fab fa-whatsapp"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> Desa Winduaji. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        const menu    = document.getElementById('mobile-menu');
        const overlay = document.getElementById('mobile-menu-overlay');

        function toggleMenu() {
            menu.classList.toggle('active');
            overlay.classList.toggle('hidden');
            document.body.classList.toggle('overflow-hidden');
        }

        document.getElementById('mobile-menu-button').addEventListener('click', toggleMenu);
        document.getElementById('mobile-menu-close').addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
    </script>
</body>
</html>