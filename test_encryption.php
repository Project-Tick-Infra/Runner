<?php
require __DIR__ . '/vendor/autoload.php';

echo "Testing Encrypted Module Load...\n";

// Ensure the TickCrypto class is available
if (!class_exists('\App\Service\TickCrypto')) {
    die("Error: TickCrypto not found.\n");
}

// Extract the encrypted file somewhere temporary to test
$zipPath = __DIR__ . '/projt-enterprise-bundle.zip';
$testExtractPath = __DIR__ . '/var/test_encryption';

if (!is_dir($testExtractPath)) {
    mkdir($testExtractPath, 0777, true);
}

$zip = new ZipArchive();
if ($zip->open($zipPath) === TRUE) {
    // Extract just one file to test
    $zip->extractTo($testExtractPath, 'src/Module/Monitor/UptimeService.php');
    $zip->close();
    
    $encryptedFile = $testExtractPath . '/src/Module/Monitor/UptimeService.php';
    
    echo "Loading Encrypted File: $encryptedFile\n";
    
    // Pure local test without any env flags or license
    unset($_SERVER['APP_ENV']); 
    
    try {
        echo "Debug APP_ENV: " . ($_SERVER['APP_ENV'] ?? 'unset') . "\n";
        print_r(\App\Service\TickCrypto::verifyLicense(dirname(__DIR__)));
        require $encryptedFile; // This will execute the eval()
        echo "SUCCESS! The file was required without errors.\n";
        
        // Let's check if the class actually exists now
        if (class_exists('\App\Module\Monitor\UptimeService')) {
            echo "SUCCESS! The UptimeService class is now available in memory.\n";
            $reflection = new \ReflectionClass('\App\Module\Monitor\UptimeService');
            echo "Class methods: \n";
            foreach ($reflection->getMethods() as $method) {
                echo " - " . $method->getName() . "\n";
            }
        } else {
            echo "ERROR: Class \\App\\Module\\Monitor\\UptimeService not found after require.\n";
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION CAUGHT: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "Failed to open zip file.\n";
}
