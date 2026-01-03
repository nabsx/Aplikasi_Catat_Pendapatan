<?php
// Load environment and dependencies
require_once 'load_env.php';
require_once 'telegram_notifier.php';

date_default_timezone_set('Asia/Jakarta');
session_start();

// Initialize Telegram Notifier
$telegram = new TelegramNotifier();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'test_connection':
                $result = $telegram->testConnection();
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                } else {
                    $_SESSION['error'] = $result['message'];
                }
                break;
                
            case 'save_telegram_settings':
                $botToken = $_POST['bot_token'] ?? '';
                $chatId = $_POST['chat_id'] ?? '';
                
                // Save to .env file
                $envPath = __DIR__ . '/.env';
                $envContent = "";
                
                if (file_exists($envPath)) {
                    $envContent = file_get_contents($envPath);
                }
                
                // Update or add bot token
                if (strpos($envContent, 'TELEGRAM_BOT_TOKEN=') !== false) {
                    $envContent = preg_replace(
                        '/TELEGRAM_BOT_TOKEN=.*/m',
                        'TELEGRAM_BOT_TOKEN=' . $botToken,
                        $envContent
                    );
                } else {
                    $envContent .= "\nTELEGRAM_BOT_TOKEN=" . $botToken;
                }
                
                // Update or add chat ID
                if (strpos($envContent, 'TELEGRAM_CHAT_ID=') !== false) {
                    $envContent = preg_replace(
                        '/TELEGRAM_CHAT_ID=.*/m',
                        'TELEGRAM_CHAT_ID=' . $chatId,
                        $envContent
                    );
                } else {
                    $envContent .= "\nTELEGRAM_CHAT_ID=" . $chatId;
                }
                
                if (file_put_contents($envPath, $envContent)) {
                    $_SESSION['success'] = "Pengaturan Telegram berhasil disimpan! Silakan refresh halaman.";
                } else {
                    $_SESSION['error'] = "Gagal menyimpan pengaturan. Pastikan file .env dapat ditulis.";
                }
                break;
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get bot info
$botInfo = $telegram->getBotInfo();
$isConfigured = $telegram->isConfigured();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Telegram - Ojol Finance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 p-4 sm:p-6 lg:p-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Back Button -->
        <a href="index.php" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 mb-6">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Dashboard
        </a>

        <!-- Notifications -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <?= $_SESSION['error'] ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                <?= $_SESSION['success'] ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 mb-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">📱</span>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                        Pengaturan Telegram
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Konfigurasi notifikasi otomatis ke Telegram
                    </p>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="text-xl">🔔</span>
                Status Notifikasi
            </h2>
            
            <div class="space-y-4">
                <?php if ($isConfigured): ?>
                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">✅</span>
                            <span class="text-green-800 font-semibold">Telegram Aktif</span>
                        </div>
                        
                        <?php if ($botInfo): ?>
                            <div class="text-sm text-green-700 space-y-1">
                                <div>Bot Name: <strong>@<?= htmlspecialchars($botInfo['username']) ?></strong></div>
                                <div>Bot ID: <strong><?= htmlspecialchars($botInfo['id']) ?></strong></div>
                                <div>Chat ID: <strong><?= htmlspecialchars(getenv('TELEGRAM_CHAT_ID')) ?></strong></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="test_connection">
                        <button type="submit" class="w-full px-6 py-3 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition">
                            🔄 Test Koneksi
                        </button>
                    </form>
                <?php else: ?>
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">⚠️</span>
                            <span class="text-yellow-800 font-semibold">Telegram Belum Dikonfigurasi</span>
                        </div>
                        <p class="text-sm text-yellow-700">
                            Isi Bot Token dan Chat ID di bawah untuk mengaktifkan notifikasi
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Configuration Form -->
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="text-xl">⚙️</span>
                Konfigurasi Bot
            </h2>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="save_telegram_settings">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        🤖 Bot Token
                    </label>
                    <input
                        type="text"
                        name="bot_token"
                        value="<?= htmlspecialchars(getenv('TELEGRAM_BOT_TOKEN') ?: '') ?>"
                        placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        required
                    />
                    <p class="text-xs text-gray-500 mt-1">
                        Dapatkan dari @BotFather di Telegram
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        👤 Chat ID
                    </label>
                    <input
                        type="text"
                        name="chat_id"
                        value="<?= htmlspecialchars(getenv('TELEGRAM_CHAT_ID') ?: '') ?>"
                        placeholder="1164425209"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        required
                    />
                    <p class="text-xs text-gray-500 mt-1">
                        ID Telegram Anda (angka)
                    </p>
                </div>
                
                <button
                    type="submit"
                    class="w-full px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-medium rounded-lg hover:from-green-600 hover:to-emerald-700 transition shadow-lg hover:shadow-xl"
                >
                    💾 Simpan Pengaturan
                </button>
            </form>
        </div>

        <!-- Setup Guide -->
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="text-xl">📖</span>
                Panduan Setup
            </h2>
            
            <div class="space-y-6">
                <!-- Step 1 -->
                <div class="border-l-4 border-blue-500 pl-4">
                    <h3 class="font-semibold text-gray-800 mb-2">1️⃣ Buat Bot Telegram</h3>
                    <ol class="list-decimal list-inside text-sm text-gray-600 space-y-1">
                        <li>Buka Telegram dan cari <code class="bg-gray-100 px-2 py-1 rounded">@BotFather</code></li>
                        <li>Kirim perintah <code class="bg-gray-100 px-2 py-1 rounded">/newbot</code></li>
                        <li>Ikuti instruksi untuk memberi nama bot</li>
                        <li>Salin <strong>Bot Token</strong> yang diberikan</li>
                    </ol>
                </div>
                
                <!-- Step 2 -->
                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="font-semibold text-gray-800 mb-2">2️⃣ Dapatkan Chat ID</h3>
                    <ol class="list-decimal list-inside text-sm text-gray-600 space-y-1">
                        <li>Buka bot Anda di Telegram</li>
                        <li>Kirim pesan apa saja (misalnya: "test")</li>
                        <li>Buka browser dan akses:
                            <div class="bg-gray-100 p-2 rounded mt-1 text-xs break-all">
                                https://api.telegram.org/bot<strong>YOUR_BOT_TOKEN</strong>/getUpdates
                            </div>
                        </li>
                        <li>Cari <code class="bg-gray-100 px-2 py-1 rounded">"chat":{"id": xxxxx}</code></li>
                        <li>Salin angka ID tersebut</li>
                    </ol>
                </div>
                
                <!-- Step 3 -->
                <div class="border-l-4 border-purple-500 pl-4">
                    <h3 class="font-semibold text-gray-800 mb-2">3️⃣ Konfigurasi di Aplikasi</h3>
                    <ol class="list-decimal list-inside text-sm text-gray-600 space-y-1">
                        <li>Masukkan Bot Token dan Chat ID di form di atas</li>
                        <li>Klik <strong>Simpan Pengaturan</strong></li>
                        <li>Klik <strong>Test Koneksi</strong> untuk verifikasi</li>
                        <li>Cek Telegram Anda untuk pesan test</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="text-xl">✨</span>
                Fitur Notifikasi
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <div class="text-2xl mb-2">💵</div>
                    <h3 class="font-semibold text-blue-800 mb-1">Pendapatan</h3>
                    <p class="text-sm text-blue-600">
                        Notifikasi otomatis setiap kali ada input pendapatan baru
                    </p>
                </div>
                
                <div class="p-4 bg-red-50 rounded-lg">
                    <div class="text-2xl mb-2">💸</div>
                    <h3 class="font-semibold text-red-800 mb-1">Pengeluaran</h3>
                    <p class="text-sm text-red-600">
                        Notifikasi otomatis setiap kali ada input pengeluaran
                    </p>
                </div>
                
                <div class="p-4 bg-yellow-50 rounded-lg">
                    <div class="text-2xl mb-2">🧾</div>
                    <h3 class="font-semibold text-yellow-800 mb-1">Tagihan</h3>
                    <p class="text-sm text-yellow-600">
                        Notifikasi penambahan tagihan dan pengingat jatuh tempo
                    </p>
                </div>
                
                <div class="p-4 bg-green-50 rounded-lg">
                    <div class="text-2xl mb-2">🎯</div>
                    <h3 class="font-semibold text-green-800 mb-1">Target</h3>
                    <p class="text-sm text-green-600">
                        Notifikasi saat target harian/mingguan/bulanan tercapai
                    </p>
                </div>
            </div>
        </div>

    </div>
</body>
</html>