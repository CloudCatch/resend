<?php
/**
 * Fix Mozart-prefixed files that are missing namespace declarations.
 * This script adds namespace declarations to files that Mozart didn't properly namespace.
 */

// Calculate the plugin directory from the script's location
$plugin_dir = dirname(dirname(__FILE__));
$resend_file = $plugin_dir . '/vendor-prefixed/Resend/Resend.php';

if (!file_exists($resend_file)) {
    echo "Error: Could not find {$resend_file}\n";
    exit(1);
}

$content = file_get_contents($resend_file);

// Check if the file already has a namespace declaration
if (strpos($content, 'namespace CloudCatch\\Resend\\Dependencies\\Resend;') === false 
    && strpos($content, 'namespace ') === false) {
    
    // Add the namespace declaration after the opening PHP tag
    $fixed_content = preg_replace(
        '/^<\?php\s*\n/m',
        "<?php\n\nnamespace CloudCatch\\Resend\\Dependencies\\Resend;\n\n",
        $content,
        1
    );
    
    if ($fixed_content !== $content) {
        file_put_contents($resend_file, $fixed_content);
        echo "Fixed namespace in vendor-prefixed/Resend/Resend.php\n";
    }
} else {
    echo "Namespace already present or file structure unexpected.\n";
}
?>

