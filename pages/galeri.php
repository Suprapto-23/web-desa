    <?php
    session_start();
    require_once '../config/database.php';
    require_once '../config/functions.php';

    // Koneksi database
    $db = new database();
    $conn = $db->getConnection();

    // Data desa dari database
    $query = "SELECT * FROM desa_profil LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $desa_profil = $stmt->fetch(PDO::FETCH_ASSOC);

    $desa_nama = $desa_profil['nama_desa'] ?? "Desa Winduaji";
    $desa_lokasi = $desa_profil['lokasi'] ?? "Kecamatan Paninggaran, Kabupaten Pekalongan, Provinsi Jawa Tengah";

    // Data kategori dari database
    $query = "SELECT * FROM galeri_kategori";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Data galeri dari database
    $query = "SELECT g.*, gk.nama_kategori 
            FROM galeri g 
            JOIN galeri_kategori gk ON g.kategori_id = gk.id 
            WHERE g.status = 'published' AND g.tipe = 'foto'
            ORDER BY g.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $galeri = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Data video dari database
    $query = "SELECT g.*, gk.nama_kategori 
            FROM galeri g 
            JOIN galeri_kategori gk ON g.kategori_id = gk.id 
            WHERE g.status = 'published' AND g.tipe = 'video'
            ORDER BY g.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fungsi untuk memeriksa apakah file gambar ada
    function checkImage($path) {
        if (empty($path)) {
            return 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
        }
        
        // Cek apakah path adalah URL eksternal
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        // Cek apakah path dimulai dengan 'http'
        if (strpos($path, 'http') === 0) {
            return $path;
        }
        
        // Untuk path lokal, pastikan diawali dengan slash
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }
        
        // Cek apakah file ada di server
        $absolute_path = $_SERVER['DOCUMENT_ROOT'] . $path;
        if (file_exists($absolute_path)) {
            return $path;
        } else {
            // Coba path tanpa slash awal
            $relative_path = ltrim($path, '/');
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $relative_path)) {
                return '/' . $relative_path;
            } else {
                // Gunakan placeholder jika gambar tidak ditemukan
                return 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
            }
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Galeri Desa Winduaji - Kecamatan Paninggaran, Kabupaten Pekalongan</title>
        <link rel="shortcut icon" href="../assets/images/logo.png" type="image/x-icon">
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            .hero-section {
                background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('../assets/images/landingpage/bg.png');
                background-size: cover;
                background-position: center;
                color: white;
            }
            .gallery-item {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                overflow: hidden;
                border-radius: 8px;
            }
            .gallery-item:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            }
            .gallery-item img {
                transition: transform 0.5s ease;
                width: 100%;
                height: 224px; /* Fixed height for consistency */
                object-fit: cover;
            }
            .gallery-item:hover img {
                transform: scale(1.05);
            }
            .category-btn {
                transition: all 0.3s ease;
            }
            .category-btn.active, .category-btn:hover {
                background-color: #dc2626;
                color: white;
            }
            .modal {
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }
            .modal-content {
                transform: scale(0.9);
                transition: transform 0.3s ease;
            }
            .modal.open {
                opacity: 1;
                visibility: visible;
            }
            .modal.open .modal-content {
                transform: scale(1);
            }
            .video-placeholder {
                background: linear-gradient(45deg, #dc2626, #ef4444);
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>
    </head>
    <body>
      <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300"></div>
    
    <header class="w-full border-b border-gray-200 bg-white shadow-sm sticky top-0 z-50 transition-all duration-300">
        <nav class="container mx-auto flex items-center justify-between py-3 px-4 md:px-6 lg:px-8">
            <a class="flex items-center space-x-2" href="#">
                <img alt="Logo Desa <?php echo $desa_nama; ?>" class="h-12 w-auto transition-transform duration-300 hover:scale-105" height="50" src="../assets/images/logo.png" width="150" />
                <span class="text-xl font-bold text-red-600 hidden md:block"><?php echo $desa_nama; ?></span>
            </a>
            <ul class="hidden md:flex space-x-6 lg:space-x-8 text-sm font-medium">
                <li><a class="text-red-600 hover:text-red-700 transition-colors duration-200" href="../landingpage.php">Beranda</a></li>
                <li><a class="text-gray-700 hover:text-red-600 transition-colors duration-200" href="../pages/sejarah.php">Profil Desa</a></li>
                <li><a class="text-gray-700 hover:text-red-600 transition-colors duration-200" href="../pages/umkm.php">UMKM</a></li>
                <li><a class="text-gray-700 hover:text-red-600 transition-colors duration-200" href="../pages/berita.php">Berita</a></li>
                <li><a class="text-gray-700 hover:text-red-600 transition-colors duration-200" href="../pages/galeri.php">Galeri</a></li>
                <li><a class="text-gray-700 hover:text-red-600 transition-colors duration-200" href="../login.php">Admin</a></li>
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
                <img alt="Logo Desa <?php echo $desa_nama; ?>" class="h-10 w-auto" src="../assets/images/logo.png" />
                <button id="mobile-menu-close" class="text-gray-600 hover:text-red-600 transition-colors duration-200">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            <ul class="space-y-6">
                <li><a class="text-red-600 hover:text-red-700 font-medium block py-2 transition-colors duration-200" href="../landingpage.php">Beranda</a></li>
                <li><a class="text-gray-700 hover:text-red-600 font-medium block py-2 transition-colors duration-200" href="../pages/umkm.php">UMKM</a></li>
                <li><a class="text-gray-700 hover:text-red-600 font-medium block py-2 transition-colors duration-200" href="../pages/berita.php">Berita</a></li>
                <li><a class="text-gray-700 hover:text-red-600 font-medium block py-2 transition-colors duration-200" href="../pages/galeri.php">Galeri</a></li>
                <li><a class="text-gray-700 hover:text-red-600 font-medium block py-2 transition-colors duration-200" href="../login.php">Admin</a></li>
            </ul>
        </div>
    </header>

        <!-- Hero Section -->
        <section class="hero-section py-20">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Galeri <?php echo $desa_nama; ?></h1>
                <p class="text-xl mb-8">Merekam Momen dan Keindahan <?php echo $desa_nama; ?>, <?php echo $desa_lokasi; ?></p>
                <a href="#galeri" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg inline-flex items-center">
                    <span>Jelajahi Galeri</span>
                    <i class="fas fa-arrow-down ml-2"></i>
                </a>
            </div>
        </section>

        <!-- Kategori Section -->
        <section class="py-12 bg-white">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold text-center mb-12">Kategori Galeri</h2>
                <div class="flex flex-wrap justify-center gap-4 mb-12">
                    <button class="category-btn active px-6 py-2 rounded-full border border-red-600 text-red-600 font-medium" data-category="all">Semua</button>
                    <?php foreach ($kategori as $kat): ?>
                        <button class="category-btn px-6 py-2 rounded-full border border-gray-300 text-gray-700 font-medium" data-category="<?= $kat['id'] ?>">
                            <?= htmlspecialchars($kat['nama_kategori']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Galeri Section -->
        <section id="galeri" class="py-16 bg-gray-50">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold text-center mb-4">Galeri Foto</h2>
                <p class="text-center text-gray-600 mb-12 max-w-2xl mx-auto">Jelajahi koleksi foto yang merekam berbagai kegiatan, keindahan alam, và kehidupan masyarakat <?php echo $desa_nama; ?>.</p>
                
                <?php if (count($galeri) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php foreach ($galeri as $index => $item): 
                        $tanggal = new DateTime($item['tanggal_upload']);
                        $formattedDate = $tanggal->format('d F Y');
                        
                        // Gunakan thumbnail jika ada, jika tidak gunakan file utama
                       // Base path project (sesuaikan dengan nama folder project kamu)
$basePath = "/sistem_pelayanan_desa/";

$imagePath = !empty($item['thumbnail_path']) ? $item['thumbnail_path'] : $item['file_path'];
$imagePath = '../' . ltrim($imagePath, './');

                    ?>
                    <div class="gallery-item bg-white rounded-xl overflow-hidden shadow-md cursor-pointer" 
                        data-category="<?= $item['kategori_id'] ?>" 
                        data-index="<?= $index ?>">
                        <div class="h-56 overflow-hidden">
                            <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($item['judul']) ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold mb-1"><?= htmlspecialchars($item['judul']) ?></h3>
                            <p class="text-sm text-gray-600"><?= $formattedDate ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-500">Belum ada foto di galeri.</p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Video Section -->
        <section class="py-16 bg-white">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold text-center mb-12">Video <?php echo $desa_nama; ?></h2>
                
                <?php if (count($videos) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php foreach ($videos as $video): 
                        $tanggal = new DateTime($video['tanggal_upload']);
                        $formattedDate = $tanggal->format('d F Y');
                    ?>
                    <div class="rounded-xl overflow-hidden shadow-lg">
                        <div class="relative pb-[56.25%] h-0">
                            <?php if (!empty($video['embed_link'])): ?>
                                <iframe class="absolute top-0 left-0 w-full h-full" src="<?= htmlspecialchars($video['embed_link']) ?>" title="<?= htmlspecialchars($video['judul']) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            <?php else: ?>
                                <div class="absolute top-0 left-0 w-full h-full video-placeholder">
                                    <i class="fas fa-play-circle text-white text-5xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($video['judul']) ?></h3>
                            <p class="text-gray-500 text-sm mb-2"><?= $formattedDate ?></p>
                            <p class="text-gray-600"><?= htmlspecialchars($video['deskripsi'] ?? 'Video dokumentasi ' . $desa_nama) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-500">Belum ada video di galeri.</p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Modal -->
        <div class="modal fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 opacity-0 invisible" id="imageModal">
            <div class="modal-content bg-white rounded-lg overflow-hidden max-w-4xl w-full mx-4">
                <div class="relative">
                    <button class="absolute top-4 right-4 bg-white rounded-full p-2 z-10" id="closeModal">
                        <i class="fas fa-times text-gray-800"></i>
                    </button>
                    <img src="" alt="" class="w-full max-h-[70vh] object-contain" id="modalImage">
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2" id="modalTitle"></h3>
                    <p class="text-gray-600 mb-4" id="modalDate"></p>
                    <p class="text-gray-800" id="modalDescription"></p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-12">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-xl font-bold mb-4"><?php echo $desa_nama; ?></h3>
                        <p class="text-gray-400"><?php echo $desa_lokasi; ?></p>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-bold mb-4">Tautan Cepat</h3>
                        <ul class="space-y-2">
                            <li><a href="../landingpage.php" class="text-gray-400 hover:text-white">Beranda</a></li>
                            <li><a href="../landingpage.php" class="text-gray-400 hover:text-white">Profil Desa</a></li>
                            <li><a href="../pages/berita.php" class="text-gray-400 hover:text-white">Berita</a></li>
                            <li><a href="../pages/umkm.php" class="text-gray-400 hover:text-white">UMKM</a></li>
                            <li><a href="../pages/galeri.php" class="text-gray-400 hover:text-white">Galeri</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-bold mb-4">Kontak Kami</h3>
                        <ul class="space-y-2">
                            <li class="flex items-center">
                                <i class="fas fa-phone-alt mr-3 text-gray-400"></i>
                                <span class="text-gray-400">(021) 1234-5678</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-envelope mr-3 text-gray-400"></i>
                                <span class="text-gray-400">info@desawinduaji.id</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fab fa-whatsapp mr-3 text-gray-400"></i>
                                <span class="text-gray-400">+62 812 3456 7890</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-bold mb-4">Ikuti Kami</h3>
                        <div class="flex space-x-4">
                            <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600">
                                <i class="fab fa-youtube"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                    <p>&copy; 2023 <?php echo $desa_nama; ?>. All rights reserved.</p>
                </div>
            </div>
        </footer>

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
            // Data untuk gambar galeri dari PHP
            const galleryData = [
    <?php foreach ($galeri as $item):
        $tanggal = new DateTime($item['tanggal_upload']);
        $imagePath = '../' . ltrim($item['file_path'], './');
    ?>
    {
        title: "<?= addslashes($item['judul']) ?>",
        date: "<?= $tanggal->format('d F Y') ?>",
        description: "<?= addslashes($item['deskripsi'] ?? 'Tidak ada deskripsi') ?>",
        category: "<?= addslashes($item['nama_kategori']) ?>",
        image: "<?= $imagePath ?>"
    },
    <?php endforeach; ?>
];

            // JavaScript untuk interaksi galeri
            document.addEventListener('DOMContentLoaded', function() {
                const categoryButtons = document.querySelectorAll('.category-btn');
                const galleryItems = document.querySelectorAll('.gallery-item');
                const modal = document.getElementById('imageModal');
                const modalImage = document.getElementById('modalImage');
                const modalTitle = document.getElementById('modalTitle');
                const modalDate = document.getElementById('modalDate');
                const modalDescription = document.getElementById('modalDescription');
                const closeModal = document.getElementById('closeModal');
                
                // Filter kategori
                categoryButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        // Remove active class from all buttons
                        categoryButtons.forEach(btn => {
                            btn.classList.remove('active', 'bg-red-600', 'text-white');
                            btn.classList.add('border-gray-300', 'text-gray-700');
                        });
                        
                        // Add active class to clicked button
                        this.classList.remove('border-gray-300', 'text-gray-700');
                        this.classList.add('active', 'bg-red-600', 'text-white');
                        
                        const category = this.getAttribute('data-category');
                        
                        // Filter gallery items
                        galleryItems.forEach(item => {
                            if (category === 'all' || item.getAttribute('data-category') === category) {
                                item.style.display = 'block';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    });
                });
                
                // Open modal on image click
                galleryItems.forEach(item => {
                    item.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        
                        if (galleryData[index]) {
                            const data = galleryData[index];
                            modalImage.src = data.image;
                            modalImage.alt = data.title;
                            modalTitle.textContent = data.title;
                            modalDate.textContent = data.date;
                            modalDescription.textContent = data.description;
                            
                            modal.classList.add('open');
                            document.body.style.overflow = 'hidden';
                        }
                    });
                });
                
                // Close modal
                closeModal.addEventListener('click', function() {
                    modal.classList.remove('open');
                    document.body.style.overflow = 'auto';
                });
                
                // Close modal when clicking outside
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.remove('open');
                        document.body.style.overflow = 'auto';
                    }
                });
            });
        </script>
    </body>
    </html>