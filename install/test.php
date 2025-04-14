<?php
/**
 * Test file to verify installation is working correctly
 */

// Check PHP version
$phpVersion = phpversion();
$requiredPhpVersion = '7.4.0';
$phpVersionOk = version_compare($phpVersion, $requiredPhpVersion, '>=');

// Check extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'curl', 'mbstring'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

// Check if directory is writable
$installDir = __DIR__;
$parentDir = dirname($installDir);
$isWritable = is_writable($parentDir);

// Check server software
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

// Output results
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .check-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .check-item:last-child {
            border-bottom: none;
        }
        .status-ok {
            color: #198754;
        }
        .status-error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Installation Test Results</h2>
            </div>
            <div class="card-body">
                <div class="check-item">
                    <h4>PHP Version</h4>
                    <p>Current: <?php echo $phpVersion; ?> | Required: <?php echo $requiredPhpVersion; ?></p>
                    <?php if ($phpVersionOk): ?>
                        <div class="alert alert-success">PHP version is compatible</div>
                    <?php else: ?>
                        <div class="alert alert-danger">PHP version is not compatible. Please upgrade to PHP <?php echo $requiredPhpVersion; ?> or higher.</div>
                    <?php endif; ?>
                </div>
                
                <div class="check-item">
                    <h4>PHP Extensions</h4>
                    <?php if (empty($missingExtensions)): ?>
                        <div class="alert alert-success">All required extensions are installed</div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            Missing required extensions:
                            <ul>
                                <?php foreach ($missingExtensions as $ext): ?>
                                    <li><?php echo $ext; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="check-item">
                    <h4>Directory Permissions</h4>
                    <?php if ($isWritable): ?>
                        <div class="alert alert-success">Directory is writable</div>
                    <?php else: ?>
                        <div class="alert alert-danger">Directory is not writable. Please set appropriate permissions.</div>
                    <?php endif; ?>
                </div>
                
                <div class="check-item">
                    <h4>Server Information</h4>
                    <p>Server Software: <?php echo $serverSoftware; ?></p>
                </div>
            </div>
            <div class="card-footer">
                <?php if ($phpVersionOk && empty($missingExtensions) && $isWritable): ?>
                    <div class="alert alert-success">
                        <strong>All checks passed!</strong> You can proceed with the installation.
                        <a href="index.php" class="btn btn-primary mt-2">Start Installation</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <strong>Some checks failed.</strong> Please fix the issues before proceeding with the installation.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
