<?php
/**
 * Telegram Notification Service
 * Handles sending notifications to Telegram via Bot API
 */

class TelegramNotifier {
    private $botToken;
    private $chatId;
    private $apiUrl;
    private $isEnabled;
    
    public function __construct() {
        // Load configuration from environment or config file
        $this->botToken = getenv('TELEGRAM_BOT_TOKEN') ?: '';
        $this->chatId = getenv('TELEGRAM_CHAT_ID') ?: '';
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        
        // Check if Telegram notification is enabled
        $this->isEnabled = !empty($this->botToken) && !empty($this->chatId);
    }
    
    /**
     * Check if Telegram notification is properly configured
     */
    public function isConfigured() {
        return $this->isEnabled;
    }
    
    /**
     * Send notification when income is added
     */
    public function notifyIncome($data) {
        if (!$this->isEnabled) {
            return false;
        }
        
        $amount = number_format($data['amount'], 0, ',', '.');
        $date = date('d/m/Y', strtotime($data['date']));
        $platform = $data['platform'] ?? 'Lainnya';
        $note = $data['note'] ?? '-';
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;
        
        $message = "💵 *PENDAPATAN BARU*\n\n";
        $message .= "📅 Tanggal: `{$date}`\n";
        $message .= "💰 Nominal: `Rp {$amount}`\n";
        $message .= "🏢 Platform: `{$platform}`\n";
        $message .= "📝 Catatan: `{$note}`\n";
        
        if ($startTime && $endTime) {
            $message .= "⏰ Jam Kerja: `{$startTime} - {$endTime}`\n";
            
            // Calculate work duration
            $start = new DateTime($startTime);
            $end = new DateTime($endTime);
            $duration = $start->diff($end);
            $hours = $duration->h + ($duration->i / 60);
            
            if ($hours > 0) {
                $perHour = $data['amount'] / $hours;
                $message .= "⚡ Efisiensi: `Rp " . number_format($perHour, 0, ',', '.') . "/jam`\n";
            }
        }
        
        $message .= "\n✅ Input berhasil disimpan!";
        
        return $this->sendMessage($message);
    }
    
    /**
     * Send notification when expense is added
     */
    public function notifyExpense($data) {
        if (!$this->isEnabled) {
            return false;
        }
        
        $amount = number_format($data['amount'], 0, ',', '.');
        $date = date('d/m/Y', strtotime($data['date']));
        $category = $data['category'] ?? 'Lainnya';
        $description = $data['description'] ?? '-';
        
        $message = "💸 *PENGELUARAN BARU*\n\n";
        $message .= "📅 Tanggal: `{$date}`\n";
        $message .= "💰 Nominal: `Rp {$amount}`\n";
        $message .= "🏷️ Kategori: `{$category}`\n";
        $message .= "📝 Keterangan: `{$description}`\n";
        $message .= "\n✅ Pengeluaran berhasil dicatat!";
        
        return $this->sendMessage($message);
    }
    
    /**
     * Send notification when bill is added
     */
    public function notifyBill($data) {
        if (!$this->isEnabled) {
            return false;
        }
        
        $amount = number_format($data['amount'], 0, ',', '.');
        $dueDate = date('d/m/Y', strtotime($data['due_date']));
        $name = $data['name'];
        $category = $data['category'] ?? 'Lainnya';
        
        $message = "🧾 *TAGIHAN BARU*\n\n";
        $message .= "📋 Nama: `{$name}`\n";
        $message .= "💰 Nominal: `Rp {$amount}`\n";
        $message .= "🏷️ Kategori: `{$category}`\n";
        $message .= "📅 Jatuh Tempo: `{$dueDate}`\n";
        $message .= "\n⏰ Jangan lupa bayar tepat waktu!";
        
        return $this->sendMessage($message);
    }
    
    /**
     * Send daily summary notification
     */
    public function notifyDailySummary($summary) {
        if (!$this->isEnabled) {
            return false;
        }
        
        $date = date('d F Y');
        $income = number_format($summary['income'], 0, ',', '.');
        $expense = number_format($summary['expense'], 0, ',', '.');
        $net = number_format($summary['net'], 0, ',', '.');
        $targetProgress = number_format($summary['target_progress'], 1);
        
        $message = "📊 *RINGKASAN HARIAN*\n";
        $message .= "📅 {$date}\n\n";
        $message .= "💵 Pendapatan: `Rp {$income}`\n";
        $message .= "💸 Pengeluaran: `Rp {$expense}`\n";
        $message .= "💰 Bersih: `Rp {$net}`\n";
        $message .= "🎯 Progress Target: `{$targetProgress}%`\n";
        
        if ($summary['target_progress'] >= 100) {
            $message .= "\n🎉 *Target harian tercapai!*";
        } else {
            $remaining = number_format($summary['target'] - $summary['income'], 0, ',', '.');
            $message .= "\n💪 Butuh `Rp {$remaining}` lagi untuk capai target!";
        }
        
        return $this->sendMessage($message);
    }
    
    /**
     * Send bill reminder notification
     */
    public function notifyBillReminder($bills) {
        if (!$this->isEnabled || empty($bills)) {
            return false;
        }
        
        $message = "⚠️ *PENGINGAT TAGIHAN*\n\n";
        $message .= "Tagihan yang perlu dibayar:\n\n";
        
        foreach ($bills as $bill) {
            $amount = number_format($bill['amount'], 0, ',', '.');
            $dueDate = date('d/m/Y', strtotime($bill['due_date']));
            $daysLeft = ceil((strtotime($bill['due_date']) - time()) / 86400);
            
            $message .= "📋 *{$bill['name']}*\n";
            $message .= "   💰 Rp {$amount}\n";
            $message .= "   📅 {$dueDate}";
            
            if ($daysLeft <= 0) {
                $message .= " ❗ *JATUH TEMPO HARI INI*\n";
            } elseif ($daysLeft == 1) {
                $message .= " ⚠️ Besok jatuh tempo\n";
            } else {
                $message .= " ({$daysLeft} hari lagi)\n";
            }
            
            $message .= "\n";
        }
        
        $message .= "🔔 Segera bayar untuk menghindari denda!";
        
        return $this->sendMessage($message);
    }
    
    /**
     * Send target achieved notification
     */
    public function notifyTargetAchieved($targetType, $amount) {
        if (!$this->isEnabled) {
            return false;
        }
        
        $formattedAmount = number_format($amount, 0, ',', '.');
        $targetName = [
            'harian' => 'Harian',
            'mingguan' => 'Mingguan',
            'bulanan' => 'Bulanan'
        ][$targetType] ?? $targetType;
        
        $message = "🎉 *SELAMAT!*\n\n";
        $message .= "Target {$targetName} tercapai!\n";
        $message .= "💰 `Rp {$formattedAmount}`\n\n";
        $message .= "Terus semangat dan tingkatkan lagi! 💪";
        
        return $this->sendMessage($message);
    }
    
    /**
     * Test Telegram connection
     */
    public function testConnection() {
        if (!$this->isEnabled) {
            return [
                'success' => false,
                'message' => 'Telegram bot belum dikonfigurasi'
            ];
        }
        
        $message = "🔔 *TEST NOTIFIKASI*\n\n";
        $message .= "Telegram bot berhasil terhubung!\n";
        $message .= "Waktu: `" . date('d/m/Y H:i:s') . "`\n\n";
        $message .= "✅ Notifikasi siap digunakan!";
        
        $result = $this->sendMessage($message);
        
        return [
            'success' => $result['success'],
            'message' => $result['success'] 
                ? 'Koneksi berhasil! Cek Telegram Anda.' 
                : 'Gagal mengirim notifikasi: ' . $result['error']
        ];
    }
    
    /**
     * Send message to Telegram
     */
    private function sendMessage($text) {
        if (!$this->isEnabled) {
            return ['success' => false, 'error' => 'Not configured'];
        }
        
        $data = [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true
        ];
        
        try {
            $ch = curl_init($this->apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("Telegram cURL Error: " . $curlError);
                return ['success' => false, 'error' => $curlError];
            }
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && isset($result['ok']) && $result['ok']) {
                return ['success' => true];
            } else {
                $errorMsg = $result['description'] ?? 'Unknown error';
                error_log("Telegram API Error: " . $errorMsg);
                return ['success' => false, 'error' => $errorMsg];
            }
            
        } catch (Exception $e) {
            error_log("Telegram Exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get bot information
     */
    public function getBotInfo() {
        if (!$this->isEnabled) {
            return null;
        }
        
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/getMe";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if (isset($result['ok']) && $result['ok']) {
                return $result['result'];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Get Bot Info Error: " . $e->getMessage());
            return null;
        }
    }
}