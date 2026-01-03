<?php
// PERBAIKAN: Set timezone di awal file
date_default_timezone_set('Asia/Jakarta');

// Start session untuk notifikasi
session_start();

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ojol_finance');

// Koneksi Database
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // PERBAIKAN: Set timezone untuk MySQL connection
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage() . "<br>Pastikan database 'ojol_finance' sudah dibuat!");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (!empty($_POST['amount']) && $_POST['amount'] > 0) {
                    // PERBAIKAN: Validasi tanggal dengan DateTime
                    $inputDate = $_POST['date'];
                    $today = date('Y-m-d');
                    
                    // Gunakan strtotime untuk perbandingan yang lebih akurat
                    if (strtotime($inputDate) > strtotime($today)) {
                        $_SESSION['error'] = "Tanggal tidak boleh lebih dari hari ini! (Hari ini: " . date('d/m/Y') . ")";
                        break;
                    }
                    
                    // Validasi jam (end_time harus > start_time)
                    if (!empty($_POST['start_time']) && !empty($_POST['end_time'])) {
                        if ($_POST['end_time'] <= $_POST['start_time']) {
                            $_SESSION['error'] = "Jam selesai harus setelah jam mulai!";
                            break;
                        }
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO entries (date, amount, note, platform, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['date'],
                        floatval($_POST['amount']),
                        !empty($_POST['note']) ? $_POST['note'] : '-',
                        $_POST['platform'] ?? 'Lainnya',
                        $_POST['start_time'] ?? null,
                        $_POST['end_time'] ?? null
                    ]);
                    $_SESSION['success'] = "Pendapatan berhasil ditambahkan!";
                }
                break;
            
            case 'add_expense':
                if (!empty($_POST['amount']) && $_POST['amount'] > 0) {
                    // PERBAIKAN: Validasi tanggal
                    $inputDate = $_POST['date'];
                    $today = date('Y-m-d');
                    
                    if (strtotime($inputDate) > strtotime($today)) {
                        $_SESSION['error'] = "Tanggal tidak boleh lebih dari hari ini! (Hari ini: " . date('d/m/Y') . ")";
                        break;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO expenses (date, amount, category, description) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['date'],
                        floatval($_POST['amount']),
                        $_POST['category'] ?? 'Lainnya',
                        !empty($_POST['description']) ? $_POST['description'] : '-'
                    ]);
                    $_SESSION['success'] = "Pengeluaran berhasil ditambahkan!";
                }
                break;
            
            case 'add_bill':
                if (!empty($_POST['amount']) && $_POST['amount'] > 0) {
                    $stmt = $pdo->prepare("INSERT INTO bills (name, amount, due_date, category) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        floatval($_POST['amount']),
                        $_POST['due_date'],
                        $_POST['category'] ?? 'Lainnya'
                    ]);
                    $_SESSION['success'] = "Tagihan berhasil ditambahkan!";
                }
                break;
            
            case 'set_target':
                if (!empty($_POST['amount']) && $_POST['amount'] > 0) {
                    // Deactivate old targets of same type
                    $stmt = $pdo->prepare("UPDATE targets SET is_active = FALSE WHERE target_type = ?");
                    $stmt->execute([$_POST['target_type']]);
                    
                    // Insert new target
                    $stmt = $pdo->prepare("INSERT INTO targets (target_type, amount, start_date) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $_POST['target_type'],
                        floatval($_POST['amount']),
                        $_POST['start_date']
                    ]);
                    $_SESSION['success'] = "Target berhasil diset!";
                }
                break;
            
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM entries WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = "Entri berhasil dihapus!";
                break;
            
            case 'delete_expense':
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = "Pengeluaran berhasil dihapus!";
                break;
            
            case 'delete_bill':
                $stmt = $pdo->prepare("DELETE FROM bills WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = "Tagihan berhasil dihapus!";
                break;
            
            case 'pay_bill':
                $stmt = $pdo->prepare("UPDATE bills SET is_paid = TRUE WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = "Tagihan berhasil ditandai sebagai lunas!";
                break;
            
            case 'clear_all':
                // Hapus SEMUA data termasuk pendapatan, pengeluaran, tagihan, target
                $pdo->exec("TRUNCATE TABLE entries");
                $pdo->exec("TRUNCATE TABLE expenses");
                $pdo->exec("TRUNCATE TABLE bills"); 
                $pdo->exec("TRUNCATE TABLE targets");
                
                $_SESSION['success'] = "Semua data berhasil dihapus!";
                break;
            
            case 'clear_entries_only':
                // Hanya hapus data pendapatan
                $pdo->exec("TRUNCATE TABLE entries");
                $_SESSION['success'] = "Data pendapatan berhasil dihapus!";
                break;
            
            case 'clear_expenses_only':
                // Hanya hapus data pengeluaran  
                $pdo->exec("TRUNCATE TABLE expenses");
                $_SESSION['success'] = "Data pengeluaran berhasil dihapus!";
                break;
            
            case 'save_settings':
                $percNeeds = intval($_POST['perc_needs']);
                $percSave = intval($_POST['perc_save']); 
                $percEmergency = intval($_POST['perc_emergency']);
                
                $total = $percNeeds + $percSave + $percEmergency;
                
                if ($total !== 100) {
                    $_SESSION['error'] = "Total persentase harus 100%! Saat ini: $total%";
                    break;
                }
                
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute(['perc_needs', $percNeeds, $percNeeds]);
                $stmt->execute(['perc_save', $percSave, $percSave]);
                $stmt->execute(['perc_emergency', $percEmergency, $percEmergency]);
                
                $_SESSION['success'] = "Pengaturan persentase berhasil disimpan!";
                break;
            
            case 'export':
                $filterMonth = $_POST['filter_month'] ?? '';
                
                if ($filterMonth) {
                    $stmt = $pdo->prepare("SELECT * FROM entries WHERE DATE_FORMAT(date, '%Y-%m') = ? ORDER BY date DESC, created_at DESC");
                    $stmt->execute([$filterMonth]);
                } else {
                    $stmt = $pdo->query("SELECT * FROM entries ORDER BY date DESC, created_at DESC");
                }
                
                $entries = $stmt->fetchAll();
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="ojol_entries_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['ID', 'Tanggal', 'Nominal', 'Catatan', 'Platform', 'Dibuat']);
                foreach ($entries as $entry) {
                    fputcsv($output, [
                        $entry['id'],
                        $entry['date'],
                        $entry['amount'],
                        $entry['note'],
                        $entry['platform'],
                        $entry['created_at']
                    ]);
                }
                fclose($output);
                exit;
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . ($_GET['filter'] ? '?filter=' . $_GET['filter'] : ''));
    exit;
}

// Get settings from database
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$percNeeds = $settings['perc_needs'] ?? 60;
$percSave = $settings['perc_save'] ?? 30;
$percEmergency = $settings['perc_emergency'] ?? 10;

// Filter entries
$filterMonth = $_GET['filter'] ?? '';

if ($filterMonth) {
    $stmt = $pdo->prepare("SELECT * FROM entries WHERE DATE_FORMAT(date, '%Y-%m') = ? ORDER BY date DESC, created_at DESC");
    $stmt->execute([$filterMonth]);
} else {
    $stmt = $pdo->query("SELECT * FROM entries ORDER BY date DESC, created_at DESC");
}

$visibleEntries = $stmt->fetchAll();

// Get expenses
$expensesStmt = $pdo->query("SELECT * FROM expenses ORDER BY date DESC, created_at DESC");
$expenses = $expensesStmt->fetchAll();

// Get bills
$billsStmt = $pdo->query("SELECT * FROM bills ORDER BY due_date ASC");
$bills = $billsStmt->fetchAll();

// Get targets
$targetsStmt = $pdo->query("SELECT * FROM targets WHERE is_active = TRUE");
$targetsData = $targetsStmt->fetchAll();
$targets = [];
foreach ($targetsData as $target) {
    $targets[$target['target_type']] = $target;
}

// PERBAIKAN: Calculate totals - HANYA UNTUK HARI INI dengan timezone yang benar
$today = date('Y-m-d');

// Hitung pendapatan hari ini saja
$todayStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as today_total FROM entries WHERE date = ?");
$todayStmt->execute([$today]);
$todayIncome = $todayStmt->fetch()['today_total'];

// Hitung pengeluaran hari ini saja  
$todayExpenseStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as today_expenses FROM expenses WHERE date = ?");
$todayExpenseStmt->execute([$today]);
$todayExpenses = $todayExpenseStmt->fetch()['today_expenses'];

// Net income hari ini
$netIncomeToday = $todayIncome - $todayExpenses;

// Hitung pembagian 60-30-10 berdasarkan hari ini saja
$needs = round(($percNeeds / 100) * $netIncomeToday);
$save = round(($percSave / 100) * $netIncomeToday);
$emergency = $netIncomeToday - $needs - $save;

// Calculate platform statistics
$platformStats = $pdo->query("
    SELECT platform, COUNT(*) as count, SUM(amount) as total 
    FROM entries 
    GROUP BY platform 
    ORDER BY total DESC
")->fetchAll();

// Calculate daily statistics for chart
$dailyStats = $pdo->query("
    SELECT date, SUM(amount) as total 
    FROM entries 
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY date 
    ORDER BY date ASC
")->fetchAll();

// Get statistics
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total_entries,
        COALESCE(SUM(amount), 0) as total_all_time,
        COALESCE(AVG(amount), 0) as avg_amount,
        COALESCE(SUM(CASE WHEN date = CURDATE() THEN amount ELSE 0 END), 0) as today_total
    FROM entries
");
$stats = $statsStmt->fetch();

// Calculate work hours and efficiency for TODAY only
$efficiencyStmt = $pdo->prepare("
    SELECT 
        COALESCE(AVG(amount / TIMESTAMPDIFF(HOUR, start_time, end_time)), 0) as avg_per_hour,
        COALESCE(SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)), 0) as total_hours,
        COUNT(*) as total_entries_with_time
    FROM entries 
    WHERE date = ? AND start_time IS NOT NULL AND end_time IS NOT NULL
");
$efficiencyStmt->execute([$today]);
$efficiency = $efficiencyStmt->fetch();

// Jika tidak ada data jam kerja hari ini, tampilkan pesan
$hasWorkTimeData = $efficiency['total_entries_with_time'] > 0;

// Calculate target progress untuk HARI INI
$dailyTarget = $targets['harian']['amount'] ?? 0;
$weeklyTarget = $targets['mingguan']['amount'] ?? 0;
$monthlyTarget = $targets['bulanan']['amount'] ?? 0;

$todayProgress = $dailyTarget > 0 ? min(100, ($todayIncome / $dailyTarget) * 100) : 0;

// Hitung total mingguan untuk progress (opsional)
$weeklyStmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) as weekly_total 
    FROM entries 
    WHERE YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)
");
$weeklyTotal = $weeklyStmt->fetch()['weekly_total'];
$weeklyProgress = $weeklyTarget > 0 ? min(100, ($weeklyTotal / $weeklyTarget) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Catat Pendapatan Ojol</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
        
        <!-- PERBAIKAN: Tambahkan debug info (hapus setelah masalah selesai) 
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs">
            <strong>Debug Info:</strong> 
            Timezone PHP: <?= date_default_timezone_get() ?> | 
            Tanggal Sekarang: <?= date('Y-m-d H:i:s') ?> | 
            Tanggal Server: <?= date('d/m/Y H:i:s') ?>
        </div>-->
        
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
                    <span class="text-2xl">🏍️</span>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                        Aplikasi Catat Pendapatan Ojol
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Catat penghasilan harianmu dan kelola keuangan dengan mudah
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-500">Total Semua Waktu</div>
                    <div class="text-lg font-bold text-blue-600">Rp <?= number_format($stats['total_all_time'] ?? 0, 0, ',', '.') ?></div>
                    <div class="text-xs text-gray-400"><?= $stats['total_entries'] ?? 0 ?> entri</div>
                </div>
            </div>
        </div>

        <!-- Target & Efficiency Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Daily Target -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-gray-700">🎯 Target Harian</span>
                    <span class="text-lg font-bold text-blue-600">Rp <?= number_format($dailyTarget, 0, ',', '.') ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                    <div class="bg-green-500 h-2 rounded-full transition-all duration-500" 
                         style="width: <?= $todayProgress ?>%"></div>
                </div>
                <div class="text-xs text-gray-500 flex justify-between">
                    <span>Progress: <?= number_format($todayProgress, 1) ?>%</span>
                    <span>Rp <?= number_format($todayIncome, 0, ',', '.') ?> / Rp <?= number_format($dailyTarget, 0, ',', '.') ?></span>
                </div>
                
                <?php if ($todayProgress >= 100): ?>
                    <div class="mt-2 p-2 bg-green-100 border border-green-200 rounded-lg text-center">
                        <span class="text-green-700 text-sm font-semibold">🎉 Target tercapai!</span>
                    </div>
                <?php elseif ($todayProgress > 0): ?>
                    <div class="mt-2 text-xs text-gray-500 text-center">
                        Butuh: Rp <?= number_format($dailyTarget - $todayIncome, 0, ',', '.') ?> lagi
                    </div>
                <?php endif; ?>
            </div>

            <!-- Efficiency Card (Harian) -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-gray-700">⚡ Efisiensi Hari Ini</span>
                    <?php if ($hasWorkTimeData): ?>
                        <span class="text-lg font-bold <?= ($efficiency['avg_per_hour'] ?? 0) >= 25000 ? 'text-green-600' : 'text-orange-600' ?>">
                            Rp <?= number_format($efficiency['avg_per_hour'] ?? 0, 0, ',', '.') ?>/jam
                        </span>
                    <?php else: ?>
                        <span class="text-xs text-gray-500">Isi jam kerja</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($hasWorkTimeData): ?>
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs text-gray-600">
                            <span>Total Jam Kerja:</span>
                            <span class="font-medium"><?= number_format($efficiency['total_hours'] ?? 0, 1) ?> jam</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600">
                            <span>Entri dengan waktu:</span>
                            <span class="font-medium"><?= $efficiency['total_entries_with_time'] ?> entri</span>
                        </div>
                        
                        <!-- Efficiency Rating -->
                        <?php
                        $efficiencyRate = $efficiency['avg_per_hour'] ?? 0;
                        $rating = '';
                        $ratingColor = '';
                        
                        if ($efficiencyRate >= 30000) {
                            $rating = 'Sangat Baik 🎉';
                            $ratingColor = 'text-green-600';
                        } elseif ($efficiencyRate >= 20000) {
                            $rating = 'Baik 👍';
                            $ratingColor = 'text-blue-600';
                        } elseif ($efficiencyRate >= 15000) {
                            $rating = 'Cukup ✅';
                            $ratingColor = 'text-orange-600';
                        } else {
                            $rating = 'Perlu Ditingkatkan 💪';
                            $ratingColor = 'text-red-600';
                        }
                        ?>
                        
                        <div class="flex justify-between text-xs <?= $ratingColor ?> font-semibold mt-2 pt-2 border-t border-gray-200">
                            <span>Rating:</span>
                            <span><?= $rating ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-2">
                        <div class="text-3xl mb-2">⏰</div>
                        <p class="text-xs text-gray-500 mb-2">Isi jam mulai & selesai</p>
                        <p class="text-xs text-gray-400">Untuk melihat efisiensi kerja hari ini</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-semibold text-gray-700">🚀 Aksi Cepat</span>
                    <span class="text-xl">⚡</span>
                </div>
                <div class="space-y-2">
                    <a href="#form-input" class="block w-full px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-600 transition text-center">
                        ➕ Tambah Pendapatan
                    </a>
                    <a href="#form-pengeluaran" class="block w-full px-4 py-2 bg-red-500 text-white text-sm font-medium rounded-lg hover:bg-red-600 transition text-center">
                        💸 Tambah Pengeluaran
                    </a>
                    <button onclick="copyTodaySummary()" class="w-full px-4 py-2 bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-600 transition">
                        📋 Copy Ringkasan
                    </button>
                </div>
            </div>
        </div>

        <!-- Form Input Pendapatan -->
        <div id="form-input" class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="text-xl">➕</span>
                Tambah Pendapatan Baru
            </h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            📅 Tanggal
                        </label>
                        <input
                            type="date"
                            name="date"
                            value="<?= date('Y-m-d') ?>"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            💰 Nominal (Rp)
                        </label>
                        <input
                            type="number"
                            name="amount"
                            placeholder="50000"
                            min="0"
                            step="1000"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            🏢 Platform
                        </label>
                        <select name="platform" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <option value="GrabBike">GrabBike</option>
                            <option value="GoRide">GoRide</option>
                            <option value="ShopeeFood">ShopeeFood</option>
                            <option value="GrabFood">GrabFood</option>
                            <option value="GoFood">GoFood</option>
                            <option value="Maxim">Maxim</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            📝 Catatan (opsional)
                        </label>
                        <input
                            type="text"
                            name="note"
                            placeholder="Shift pagi, bonus"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ⏰ Jam Mulai
                        </label>
                        <input
                            type="time"
                            name="start_time"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                        <p class="text-xs text-gray-500 mt-1">Wajib untuk hitung efisiensi</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ⏰ Jam Selesai
                        </label>
                        <input
                            type="time"
                            name="end_time"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                        <p class="text-xs text-gray-500 mt-1">Wajib untuk hitung efisiensi</p>
                    </div>
                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-600 hover:to-indigo-700 transition shadow-lg hover:shadow-xl"
                        >
                            ✅ Tambah Entri
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Form Input Pengeluaran -->
        <div id="form-pengeluaran" class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="text-xl">💸</span>
                Tambah Pengeluaran
            </h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_expense">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            📅 Tanggal
                        </label>
                        <input
                            type="date"
                            name="date"
                            value="<?= date('Y-m-d') ?>"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            💰 Nominal (Rp)
                        </label>
                        <input
                            type="number"
                            name="amount"
                            placeholder="25000"
                            min="0"
                            step="1000"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            🏷️ Kategori
                        </label>
                        <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <option value="Bensin">Bensin</option>
                            <option value="Servis">Servis</option>
                            <option value="Makan">Makan</option>
                            <option value="Tagihan">Tagihan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            📝 Keterangan
                        </label>
                        <input
                            type="text"
                            name="description"
                            placeholder="Bensin 3L"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                    </div>
                </div>
                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-red-500 to-pink-600 text-white font-medium rounded-lg hover:from-red-600 hover:to-pink-700 transition shadow-lg hover:shadow-xl"
                    >
                        💸 Tambah Pengeluaran
                    </button>
                </div>
            </form>
        </div>

        <!-- Dashboard Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            <!-- Today's Income Card -->
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium opacity-90">
                        Pendapatan Hari Ini
                    </span>
                    <span class="text-2xl">💵</span>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp <?= number_format($todayIncome, 0, ',', '.') ?>
                </div>
                <div class="text-sm opacity-75">
                    <?= count(array_filter($visibleEntries, function($entry) use ($today) { 
                        return $entry['date'] == $today; 
                    })) ?> entri hari ini
                </div>
                <?php if ($todayIncome > 0): ?>
                    <div class="text-xs opacity-75 mt-2">
                        Net: Rp <?= number_format($netIncomeToday, 0, ',', '.') ?>
                        (Setelah pengeluaran)
                    </div>
                <?php endif; ?>
            </div>

            <!-- Today's Expenses Card -->
            <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium opacity-90">
                        Pengeluaran Hari Ini
                    </span>
                    <span class="text-2xl">💸</span>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp <?= number_format($todayExpenses, 0, ',', '.') ?>
                </div>
                <div class="text-sm opacity-75">
                    <?= count(array_filter($expenses, function($expense) use ($today) { 
                        return $expense['date'] == $today; 
                    })) ?> pengeluaran
                </div>
            </div>

            <!-- Today's Net Income Card -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium opacity-90">
                        Bersih Hari Ini
                    </span>
                    <span class="text-2xl">💰</span>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp <?= number_format($netIncomeToday, 0, ',', '.') ?>
                </div>
                <div class="text-sm opacity-75">
                    Profit hari ini
                </div>
            </div>

            <!-- Breakdown Card (HARI INI) -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-semibold text-gray-700">Pembagian Hari Ini</span>
                    <span class="text-xl">📊</span>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">🏠 Kebutuhan (<?= $percNeeds ?>%)</span>
                        <span class="font-semibold text-gray-800">Rp <?= number_format($needs, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">🐷 Tabungan (<?= $percSave ?>%)</span>
                        <span class="font-semibold text-gray-800">Rp <?= number_format($save, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">🚨 Darurat (<?= $percEmergency ?>%)</span>
                        <span class="font-semibold text-gray-800">Rp <?= number_format($emergency, 0, ',', '.') ?></span>
                    </div>
                </div>
                <?php if ($netIncomeToday > 0): ?>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="text-xs text-gray-500 text-center">
                            Berdasarkan pendapatan bersih hari ini
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Daily Quick Summary -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 mb-6">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-2xl">📅</span>
                <div>
                    <h3 class="font-semibold text-yellow-800">Ringkasan Hari Ini</h3>
                    <p class="text-sm text-yellow-600"><?= date('d F Y') ?></p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
                <div class="bg-white rounded-lg p-3 border border-yellow-100">
                    <div class="text-lg font-bold text-blue-600">Rp <?= number_format($todayIncome, 0, ',', '.') ?></div>
                    <div class="text-xs text-gray-600">Pendapatan</div>
                </div>
                <div class="bg-white rounded-lg p-3 border border-yellow-100">
                    <div class="text-lg font-bold text-red-600">Rp <?= number_format($todayExpenses, 0, ',', '.') ?></div>
                    <div class="text-xs text-gray-600">Pengeluaran</div>
                </div>
                <div class="bg-white rounded-lg p-3 border border-yellow-100">
                    <div class="text-lg font-bold text-green-600">Rp <?= number_format($netIncomeToday, 0, ',', '.') ?></div>
                    <div class="text-xs text-gray-600">Bersih</div>
                </div>
                <div class="bg-white rounded-lg p-3 border border-yellow-100">
                    <div class="text-lg font-bold <?= $todayProgress >= 100 ? 'text-green-600' : 'text-orange-600' ?>">
                        <?= number_format($todayProgress, 0) ?>%
                    </div>
                    <div class="text-xs text-gray-600">Target Harian</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Platform Chart -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Pendapatan per Platform</h3>
                <canvas id="platformChart" height="250"></canvas>
            </div>

            <!-- Daily Income Chart -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📈 Tren Pendapatan 30 Hari</h3>
                <canvas id="incomeChart" height="250"></canvas>
            </div>
        </div>

        <!-- Tabs for Entries, Expenses, and Bills -->
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 mb-6">
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="showTab('entries')" class="tab-button py-2 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600">
                        📋 Entri Pendapatan
                    </button>
                    <button onclick="showTab('expenses')" class="tab-button py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        💸 Pengeluaran
                    </button>
                    <button onclick="showTab('bills')" class="tab-button py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        🧾 Tagihan
                    </button>
                </nav>
            </div>

            <!-- Entries Tab -->
            <div id="entries-tab" class="tab-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Daftar Pendapatan</h3>
                    <div class="flex gap-2">
                        <form method="GET" class="flex gap-2">
                            <input
                                type="month"
                                name="filter"
                                value="<?= $filterMonth ?>"
                                onchange="this.form.submit()"
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm"
                            />
                            <a
                                href="<?= $_SERVER['PHP_SELF'] ?>"
                                class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition flex items-center justify-center"
                            >
                                Reset
                            </a>
                        </form>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catatan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Kerja</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($visibleEntries)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            <div class="text-4xl mb-2">📭</div>
                                            <div>Belum ada entri pendapatan.</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($visibleEntries as $entry): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('d/m/Y', strtotime($entry['date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                Rp <?= number_format($entry['amount'], 0, ',', '.') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?= $entry['platform'] ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <?= htmlspecialchars($entry['note']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?= $entry['start_time'] && $entry['end_time'] ? 
                                                    $entry['start_time'] . ' - ' . $entry['end_time'] : 
                                                    '-' ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <button
                                                    onclick="copyToClipboard('Tanggal: <?= date('d/m/Y', strtotime($entry['date'])) ?> - Nominal: Rp <?= number_format($entry['amount'], 0, ',', '.') ?> - Platform: <?= $entry['platform'] ?> - Catatan: <?= htmlspecialchars($entry['note']) ?>')"
                                                    class="text-blue-600 hover:text-blue-900 mr-3"
                                                >
                                                    📋
                                                </button>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                                    <button
                                                        type="submit"
                                                        class="text-red-600 hover:text-red-900"
                                                        onclick="return confirm('Hapus entri ini?')"
                                                    >
                                                        ❌
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Expenses Tab -->
            <div id="expenses-tab" class="tab-content hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Pengeluaran</h3>
                <div class="overflow-x-auto">
                    <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($expenses)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                            <div class="text-4xl mb-2">💸</div>
                                            <div>Belum ada pengeluaran.</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($expenses as $expense): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('d/m/Y', strtotime($expense['date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">
                                                Rp <?= number_format($expense['amount'], 0, ',', '.') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?= $expense['category'] ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <?= htmlspecialchars($expense['description']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="delete_expense">
                                                    <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                                                    <button
                                                        type="submit"
                                                        class="text-red-600 hover:text-red-900"
                                                        onclick="return confirm('Hapus pengeluaran ini?')"
                                                    >
                                                        ❌
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bills Tab -->
            <div id="bills-tab" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Daftar Tagihan</h3>
                    <button onclick="showAddBillForm()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        ➕ Tambah Tagihan
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jatuh Tempo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($bills)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            <div class="text-4xl mb-2">🧾</div>
                                            <div>Belum ada tagihan.</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bills as $bill): ?>
                                        <tr class="hover:bg-gray-50 transition <?= $bill['is_paid'] ? 'bg-green-50' : '' ?>">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($bill['name']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                Rp <?= number_format($bill['amount'], 0, ',', '.') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?= $bill['category'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?= date('d/m/Y', strtotime($bill['due_date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $bill['is_paid'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                    <?= $bill['is_paid'] ? 'Lunas' : 'Belum Bayar' ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                                <?php if (!$bill['is_paid']): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="pay_bill">
                                                        <input type="hidden" name="id" value="<?= $bill['id'] ?>">
                                                        <button
                                                            type="submit"
                                                            class="text-green-600 hover:text-green-900"
                                                            onclick="return confirm('Tandai tagihan sebagai lunas?')"
                                                        >
                                                            ✅
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="delete_bill">
                                                    <input type="hidden" name="id" value="<?= $bill['id'] ?>">
                                                    <button
                                                        type="submit"
                                                        class="text-red-600 hover:text-red-900"
                                                        onclick="return confirm('Hapus tagihan ini?')"
                                                    >
                                                        ❌
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Bill Form (Hidden by default) -->
        <div id="add-bill-form" class="bg-white rounded-2xl shadow-xl p-6 mb-6 hidden">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">➕ Tambah Tagihan Baru</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_bill">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Tagihan
                        </label>
                        <input
                            type="text"
                            name="name"
                            placeholder="Listrik, Pulsa, dll"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nominal (Rp)
                        </label>
                        <input
                            type="number"
                            name="amount"
                            placeholder="150000"
                            min="0"
                            step="1000"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Kategori
                        </label>
                        <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <option value="Listrik">Listrik</option>
                            <option value="Pulsa">Pulsa</option>
                            <option value="Wifi">Wifi</option>
                            <option value="Cicilan">Cicilan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Jatuh Tempo
                        </label>
                        <input
                            type="date"
                            name="due_date"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                    </div>
                </div>
                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-600 hover:to-indigo-700 transition shadow-lg hover:shadow-xl"
                    >
                        💾 Simpan Tagihan
                    </button>
                    <button
                        type="button"
                        onclick="hideAddBillForm()"
                        class="px-6 py-3 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 transition"
                    >
                        ❌ Batal
                    </button>
                </div>
            </form>
        </div>

        <!-- Settings Section -->
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="text-xl">⚙️</span>
                Pengaturan
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Percentage Settings -->
                <div>
                    <h3 class="text-md font-semibold text-gray-700 mb-3">Pembagian Persentase</h3>
                    <form method="POST" class="space-y-4" onsubmit="return validatePercentage()">
                        <input type="hidden" name="action" value="save_settings">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Kebutuhan (%)
                                </label>
                                <input
                                    type="number"
                                    id="perc_needs"
                                    name="perc_needs"
                                    value="<?= $percNeeds ?>"
                                    min="0"
                                    max="100"
                                    required
                                    oninput="updatePercentageTotal()"
                                    onchange="autoCalculatePercentage('needs')"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tabungan (%)
                                </label>
                                <input
                                    type="number"
                                    id="perc_save"
                                    name="perc_save"
                                    value="<?= $percSave ?>"
                                    min="0"
                                    max="100"
                                    required
                                    oninput="updatePercentageTotal()"
                                    onchange="autoCalculatePercentage('save')"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Darurat (%)
                                </label>
                                <input
                                    type="number"
                                    id="perc_emergency"
                                    name="perc_emergency"
                                    value="<?= $percEmergency ?>"
                                    min="0"
                                    max="100"
                                    required
                                    oninput="updatePercentageTotal()"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                />
                            </div>
                            
                            <!-- Total Display -->
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <div id="percentage-total" class="text-center text-lg font-semibold">
                                    Total: <?= ($percNeeds + $percSave + $percEmergency) ?>%
                                </div>
                            </div>
                            
                            <button
                                type="submit"
                                class="w-full px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-medium rounded-lg hover:from-green-600 hover:to-emerald-700 transition shadow-lg hover:shadow-xl"
                            >
                                💾 Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Target Settings -->
                <div>
                    <h3 class="text-md font-semibold text-gray-700 mb-3">Set Target</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="set_target">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Target Harian (Rp)
                                </label>
                                <input
                                    type="number"
                                    name="amount"
                                    placeholder="200000"
                                    min="0"
                                    step="1000"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                />
                                <input type="hidden" name="target_type" value="harian">
                                <input type="hidden" name="start_date" value="<?= date('Y-m-d') ?>">
                            </div>
                            <button
                                type="submit"
                                class="w-full px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-600 hover:to-indigo-700 transition shadow-lg hover:shadow-xl"
                            >
                                🎯 Set Target Harian
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-semibold text-red-600 mb-4 flex items-center gap-2">
                ⚠️ Area Berbahaya
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Hapus Data Pendapatan Saja -->
                <div class="border border-red-200 rounded-lg p-4">
                    <h4 class="font-semibold text-red-700 mb-2">🗑️ Hapus Pendapatan</h4>
                    <p class="text-sm text-gray-600 mb-3">Hanya hapus data pendapatan</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="clear_entries_only">
                        <button
                            type="submit"
                            class="w-full px-4 py-2 bg-red-100 text-red-700 font-medium rounded-lg hover:bg-red-200 transition"
                            onclick="return confirm('Hapus SEMUA data pendapatan? Tindakan ini tidak dapat dibatalkan!')"
                        >
                            Hapus Pendapatan
                        </button>
                    </form>
                </div>

                <!-- Hapus Data Pengeluaran Saja -->
                <div class="border border-red-200 rounded-lg p-4">
                    <h4 class="font-semibold text-red-700 mb-2">💸 Hapus Pengeluaran</h4>
                    <p class="text-sm text-gray-600 mb-3">Hanya hapus data pengeluaran</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="clear_expenses_only">
                        <button
                            type="submit"
                            class="w-full px-4 py-2 bg-red-100 text-red-700 font-medium rounded-lg hover:bg-red-200 transition"
                            onclick="return confirm('Hapus SEMUA data pengeluaran? Tindakan ini tidak dapat dibatalkan!')"
                        >
                            Hapus Pengeluaran
                        </button>
                    </form>
                </div>

                <!-- Hapus Semua Data -->
                <div class="border border-red-300 rounded-lg p-4 bg-red-50">
                    <h4 class="font-semibold text-red-800 mb-2">💣 Hapus Semua Data</h4>
                    <p class="text-sm text-red-600 mb-3">Hapus semua data termasuk pendapatan, pengeluaran, dan tagihan</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="clear_all">
                        <button
                            type="submit"
                            class="w-full px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition shadow-lg"
                            onclick="return confirm('⚠️  HAPUS SEMUA DATA? ⚠️\n\nIni akan menghapus:\n• Semua data pendapatan\n• Semua data pengeluaran  \n• Semua data tagihan\n• Semua target\n\nTindakan ini TIDAK DAPAT DIBATALKAN!')"
                        >
                            💣 Hapus Semua Data
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800 text-center">
                    ⚠️ Backup data Anda sebelum menghapus! Data yang dihapus tidak dapat dikembalikan.
                </p>
            </div>
        </div>

    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            // Add active class to clicked button
            event.target.classList.remove('border-transparent', 'text-gray-500');
            event.target.classList.add('border-blue-500', 'text-blue-600');
        }

        // Bill form functionality
        function showAddBillForm() {
            document.getElementById('add-bill-form').classList.remove('hidden');
        }

        function hideAddBillForm() {
            document.getElementById('add-bill-form').classList.add('hidden');
        }

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Data berhasil disalin ke clipboard!');
            }, function(err) {
                console.error('Gagal menyalin: ', err);
            });
        }

        // Percentage validation
        function validatePercentage() {
            const needs = parseInt(document.getElementById('perc_needs').value) || 0;
            const save = parseInt(document.getElementById('perc_save').value) || 0;
            const emergency = parseInt(document.getElementById('perc_emergency').value) || 0;
            const total = needs + save + emergency;

            if (total !== 100) {
                alert('❌ Total persentase harus 100%! Saat ini: ' + total + '%\n\n' +
                      'Kebutuhan: ' + needs + '%\n' +
                      'Tabungan: ' + save + '%\n' + 
                      'Darurat: ' + emergency + '%');
                return false;
            }
            
            if (needs < 0 || save < 0 || emergency < 0) {
                alert('❌ Persentase tidak boleh negatif!');
                return false;
            }
            
            if (needs > 100 || save > 100 || emergency > 100) {
                alert('❌ Persentase tidak boleh lebih dari 100%!');
                return false;
            }
            
            return true;
        }

        // Real-time validation
        function updatePercentageTotal() {
            const needs = parseInt(document.getElementById('perc_needs').value) || 0;
            const save = parseInt(document.getElementById('perc_save').value) || 0;
            const emergency = parseInt(document.getElementById('perc_emergency').value) || 0;
            const total = needs + save + emergency;
            
            const totalElement = document.getElementById('percentage-total');
            if (totalElement) {
                totalElement.textContent = 'Total: ' + total + '%';
                totalElement.className = total === 100 ? 'text-green-600 font-bold text-center text-lg font-semibold' : 'text-red-600 font-bold text-center text-lg font-semibold';
            }
        }

        // Auto-calculate remaining percentage
        function autoCalculatePercentage(field) {
            const needs = parseInt(document.getElementById('perc_needs').value) || 0;
            const save = parseInt(document.getElementById('perc_save').value) || 0;
            const emergency = parseInt(document.getElementById('perc_emergency').value) || 0;
            
            const total = needs + save + emergency;
            const remaining = 100 - total;
            
            if (field === 'needs' && remaining >= 0) {
                document.getElementById('perc_emergency').value = remaining;
            } else if (field === 'save' && remaining >= 0) {
                document.getElementById('perc_emergency').value = remaining;
            }
            
            updatePercentageTotal();
        }

        // Copy today's summary to clipboard
        function copyTodaySummary() {
            const today = new Date().toLocaleDateString('id-ID', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            const pendapatan = 'Rp <?= number_format($todayIncome, 0, ',', '.') ?>';
            const pengeluaran = 'Rp <?= number_format($todayExpenses, 0, ',', '.') ?>';
            const bersih = 'Rp <?= number_format($netIncomeToday, 0, ',', '.') ?>';
            const efisiensi = '<?= $hasWorkTimeData ? "Rp " . number_format($efficiency["avg_per_hour"] ?? 0, 0, ",", ".") . "/jam" : "Belum ada data" ?>';
            const progress = '<?= number_format($todayProgress, 1) ?>%';
            
            const summary = `📊 RINGKASAN HARIAN - ${today}

💵 Pendapatan: ${pendapatan}
💸 Pengeluaran: ${pengeluaran}
💰 Bersih: ${bersih}
⚡ Efisiensi: ${efisiensi}
🎯 Progress Target: ${progress}

#OjolFinance #HariIni`;

            navigator.clipboard.writeText(summary).then(() => {
                alert('📋 Ringkasan hari ini berhasil disalin!\n\nBisa dibagikan ke grup atau disimpan.');
            }).catch(() => {
                alert('❌ Gagal menyalin ringkasan');
            });
        }

        // Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Platform Chart
            const platformCtx = document.getElementById('platformChart').getContext('2d');
            const platformChart = new Chart(platformCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_column($platformStats, 'platform')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($platformStats, 'total')) ?>,
                        backgroundColor: [
                            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', 
                            '#8B5CF6', '#EC4899', '#6B7280'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Income Chart
            const incomeCtx = document.getElementById('incomeChart').getContext('2d');
            const incomeChart = new Chart(incomeCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_map(function($stat) {
                        return date('d M', strtotime($stat['date']));
                    }, $dailyStats)) ?>,
                    datasets: [{
                        label: 'Pendapatan Harian',
                        data: <?= json_encode(array_column($dailyStats, 'total')) ?>,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>