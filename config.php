<?php
// Configuration file for Ojol Finance App

class Config {
    // Database Configuration
    const DB_HOST = 'localhost';
    const DB_NAME = 'ojol_finance';
    const DB_USER = 'root';
    const DB_PASS = '';
    
    // App Configuration
    const APP_NAME = 'Ojol Finance Tracker';
    const APP_VERSION = '2.0';
    const CURRENCY = 'IDR';
    const TIMEZONE = 'Asia/Jakarta';
    
    // Default Settings
    const DEFAULT_PERCENTAGES = [
        'needs' => 60,
        'save' => 30,
        'emergency' => 10
    ];
    
    const DEFAULT_TARGETS = [
        'daily' => 200000,
        'weekly' => 1000000,
        'monthly' => 4000000
    ];
    
    // Platform Options
    const PLATFORMS = [
        'GrabBike' => 'GrabBike',
        'GoRide' => 'GoRide', 
        'ShopeeFood' => 'ShopeeFood',
        'GrabFood' => 'GrabFood',
        'GoFood' => 'GoFood',
        'Maxim' => 'Maxim',
        'Lainnya' => 'Lainnya'
    ];
    
    // Expense Categories
    const EXPENSE_CATEGORIES = [
        'Bensin' => 'Bensin',
        'Servis' => 'Servis',
        'Makan' => 'Makan',
        'Tagihan' => 'Tagihan',
        'Lainnya' => 'Lainnya'
    ];
    
    // Bill Categories  
    const BILL_CATEGORIES = [
        'Listrik' => 'Listrik',
        'Pulsa' => 'Pulsa',
        'Wifi' => 'Wifi',
        'Cicilan' => 'Cicilan',
        'Lainnya' => 'Lainnya'
    ];
}

// Set timezone
date_default_timezone_set(Config::TIMEZONE);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>