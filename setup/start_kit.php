<?php

echo "⚙️ Setting up project...\n";

$frameworkPath = __DIR__ . '/vendor/rose/framework';
$publicSource = "$frameworkPath/public";  // Source: The framework's `public/` directory
$targetPath = __DIR__;  // Destination: The root of your project

// Copy everything inside `public/` to the root directory
if (is_dir($publicSource)) {
    moveFilesToRoot($publicSource, $targetPath);
    echo "✅ Copied all framework files **directly** into the project root.\n";
} else {
    echo "❌ The 'public' directory does not exist in the framework.\n";
}

// Move files and folders from `public/` to the project root
function moveFilesToRoot($source, $destination) {
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $srcFile = "$source/$file";
        $destFile = "$destination/$file";

        if (is_dir($srcFile)) {
            if (!is_dir($destFile)) {
                mkdir($destFile, 0755, true);
            }
            moveFilesToRoot($srcFile, $destFile);
        } else {
            copy($srcFile, $destFile);
        }
    }
}

echo "🎉 Project setup complete! Everything is now in the project root.\n";
