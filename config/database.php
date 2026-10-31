<?php
// config/database.php
// Otomatis: XAMPP (localhost) saat lokal, Aiven saat env DB_HOST diisi (Vercel)

// Environment: set APP_ENV=production di Vercel
define('ENVIRONMENT', getenv('APP_ENV') ?: 'development');

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', getenv('VERCEL') ? 'php://stderr' : __DIR__ . '/../logs/php_errors.log');
}

// Parameter koneksi (env Vercel diutamakan, fallback ke XAMPP)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'desa_winduaji');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');
define('DB_TIMEOUT', 30);

// SSL hanya aktif saat memakai database online (Aiven)
function db_ssl_options(): array
{
    if (!getenv('DB_HOST')) {
        return [];
    }
    return [
        PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/ca.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
}

function get_db_connection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_TIMEOUT            => DB_TIMEOUT,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ] + db_ssl_options();

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET time_zone = '+07:00'");
            $pdo->exec("SET SQL_MODE = 'STRICT_ALL_TABLES'");
        } catch (PDOException $e) {
            error_log('[' . date('Y-m-d H:i:s') . '] Database connection error: ' . $e->getMessage());

            if (ENVIRONMENT === 'production') {
                header('HTTP/1.1 503 Service Unavailable');
                die('Maaf, sistem sedang mengalami gangguan. Silakan coba lagi nanti.');
            }
            die('Database Error: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}

function db_prepare(string $sql): PDOStatement
{
    return get_db_connection()->prepare($sql);
}

// Koneksi global ($pdo) untuk file yang memakainya
$pdo = get_db_connection();

// Versi OOP: new Database() -> getConnection()
class Database
{
    public function getConnection()
    {
        return get_db_connection();
    }
}