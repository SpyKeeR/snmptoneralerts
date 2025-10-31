#!/usr/bin/env php
<?php
/**
 * Pure PHP script to compile .po files to .mo files
 * No external dependencies (msgfmt, gettext) required!
 * 
 * Usage: php compile_mo.php [locale_code]
 * Example: php compile_mo.php fr_FR
 *          php compile_mo.php         (compiles all)
 */

/**
 * Compile a .po file to .mo format using pure PHP
 * Based on gettext file format specifications
 */
function compilePOtoMO($po_file, $mo_file) {
    if (!file_exists($po_file)) {
        echo "  ✗ File not found: $po_file\n";
        return false;
    }

    $entries = [];
    $current = ['msgid' => '', 'msgstr' => ''];
    $in_msgid = false;
    $in_msgstr = false;

    // Parse .po file
    $lines = file($po_file, FILE_IGNORE_NEW_LINES);
    
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (empty(trim($line)) || $line[0] === '#') {
            continue;
        }

        // Start of msgid
        if (preg_match('/^msgid\s+"(.*)"$/', $line, $matches)) {
            // Save previous entry if exists
            if (!empty($current['msgid']) && !empty($current['msgstr'])) {
                $entries[$current['msgid']] = $current['msgstr'];
            }
            
            $current = ['msgid' => stripcslashes($matches[1]), 'msgstr' => ''];
            $in_msgid = true;
            $in_msgstr = false;
            continue;
        }

        // Start of msgstr
        if (preg_match('/^msgstr\s+"(.*)"$/', $line, $matches)) {
            $current['msgstr'] = stripcslashes($matches[1]);
            $in_msgid = false;
            $in_msgstr = true;
            continue;
        }

        // Continuation line
        if (preg_match('/^"(.*)"$/', $line, $matches)) {
            $value = stripcslashes($matches[1]);
            if ($in_msgid) {
                $current['msgid'] .= $value;
            } elseif ($in_msgstr) {
                $current['msgstr'] .= $value;
            }
            continue;
        }
    }

    // Save last entry
    if (!empty($current['msgid']) && !empty($current['msgstr'])) {
        $entries[$current['msgid']] = $current['msgstr'];
    }

    // Remove header entry (empty msgid)
    unset($entries['']);

    if (empty($entries)) {
        echo "  ⚠ Warning: No translations found\n";
        return false;
    }

    // Build .mo file binary format
    $count = count($entries);
    $ids = array_keys($entries);
    $vals = array_values($entries);

    // Calculate offsets
    $keyoffset = 7 * 4 + $count * 4 * 4;
    $valoffset = $keyoffset;
    
    foreach ($ids as $id) {
        $valoffset += strlen($id) + 1;
    }

    // Build hash table (not used, but required by format)
    $koffsets = [];
    $voffsets = [];
    $ko = $keyoffset;
    $vo = $valoffset;

    foreach ($entries as $key => $value) {
        $koffsets[] = [$ko, strlen($key)];
        $ko += strlen($key) + 1;
        $voffsets[] = [$vo, strlen($value)];
        $vo += strlen($value) + 1;
    }

    // Magic number (little-endian)
    $mo = pack('V', 0x950412de);
    // File format revision
    $mo .= pack('V', 0);
    // Number of strings
    $mo .= pack('V', $count);
    // Offset of table with original strings
    $mo .= pack('V', 28);
    // Offset of table with translated strings  
    $mo .= pack('V', 28 + ($count * 8));
    // Size of hash table (0 = no hash)
    $mo .= pack('V', 0);
    // Offset of hash table (not used)
    $mo .= pack('V', 0);

    // Original strings offset/length table
    foreach ($koffsets as $offset) {
        $mo .= pack('V', $offset[1]);
        $mo .= pack('V', $offset[0]);
    }

    // Translated strings offset/length table
    foreach ($voffsets as $offset) {
        $mo .= pack('V', $offset[1]);
        $mo .= pack('V', $offset[0]);
    }

    // Original strings
    foreach ($ids as $id) {
        $mo .= $id . "\0";
    }

    // Translated strings
    foreach ($vals as $val) {
        $mo .= $val . "\0";
    }

    // Write .mo file
    if (file_put_contents($mo_file, $mo) === false) {
        echo "  ✗ Failed to write: $mo_file\n";
        return false;
    }

    return true;
}

// Main script
$locales_dir = dirname(__DIR__) . '/locales';

if (!is_dir($locales_dir)) {
    die("Locales directory not found: $locales_dir\n");
}

// Check if specific locale was requested
$target_locale = $argv[1] ?? null;

if ($target_locale) {
    // Compile specific locale
    $po_file = $locales_dir . '/' . $target_locale . '.po';
    $mo_file = $locales_dir . '/' . $target_locale . '.mo';
    
    if (!file_exists($po_file)) {
        die("Error: $po_file not found\n");
    }
    
    echo "Compiling $target_locale...\n";
    echo "  Source: $po_file\n";
    echo "  Target: $mo_file\n";
    
    if (compilePOtoMO($po_file, $mo_file)) {
        echo "  ✓ Success! Translation file compiled.\n";
        echo "\nFile size: " . number_format(filesize($mo_file)) . " bytes\n";
    } else {
        echo "  ✗ Compilation failed\n";
        exit(1);
    }
} else {
    // Compile all .po files
    $po_files = glob($locales_dir . '/*.po');

    if (empty($po_files)) {
        die("No .po files found in $locales_dir\n");
    }

    echo "Found " . count($po_files) . " translation file(s)\n\n";

    $success = 0;
    $failed = 0;

    foreach ($po_files as $po_file) {
        $mo_file = str_replace('.po', '.mo', $po_file);
        $locale = basename($po_file, '.po');
        
        echo "Compiling $locale...\n";
        
        if (compilePOtoMO($po_file, $mo_file)) {
            echo "  ✓ Success (" . number_format(filesize($mo_file)) . " bytes)\n";
            $success++;
        } else {
            echo "  ✗ Failed\n";
            $failed++;
        }
    }

    echo "\n" . str_repeat('=', 50) . "\n";
    echo "Compilation complete: $success succeeded, $failed failed\n";
}

echo "\nDone!\n";
