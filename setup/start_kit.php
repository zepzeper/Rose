<?php

echo "⚙️ Setting up project...\n";

$frameworkPath = __DIR__ . '/vendor/rose/framework';
$targetPath = __DIR__; // Root of your project

// Define the source directory (framework's public) and target directory (root of project)
$publicSource = "$frameworkPath/public";

// Copy everything from the framework's `public/` directory to the project's root
if (is_dir($publicSource)) {
    recurseCopy($publicSource, $targetPath);
    echo "✅ Copied all framework files to the root of the project.\n";
} else {
    echo "❌ Missing 'public' directory in the framework.\n";
}

// Recursively copy directories and files
function recurseCopy($source, $destination) {
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    $files = scandir($source);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $srcFile = "$source/$file";
        $destFile = "$destination/$file";

        if (is_dir($srcFile)) {
            recurseCopy($srcFile, $destFile);
        } else {
            copy($srcFile, $destFile);
        }
    }
}

echo "🎉 Project setup complete! All files are now in the root directory.\n";
