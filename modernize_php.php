<?php

declare(strict_types=1);

/**
 * PHP 5.3 to 8.3 Modernization Script
 * 
 * This script helps identify and modernize common PHP 5.3 patterns to PHP 8.3
 */

$patterns = [
    // Replace deprecated ereg/eregi with preg_match
    'ereg_functions' => [
        'pattern' => '/\b(eregi?)(\s*)\(/',
        'replacement' => '// TODO: Replace $1 with preg_match (case-insensitive if eregi)',
        'description' => 'ereg/eregi functions removed in PHP 7, use preg_match'
    ],
    
    // Replace deprecated split with explode or preg_split
    'split_function' => [
        'pattern' => '/\bsplit\s*\(/',
        'replacement' => '// TODO: Replace split with explode or preg_split',
        'description' => 'split() removed in PHP 7, use explode() or preg_split()'
    ],
    
    // Replace deprecated create_function with anonymous functions
    'create_function' => [
        'pattern' => '/\bcreate_function\s*\(/',
        'replacement' => '// TODO: Replace create_function with anonymous function (fn)',
        'description' => 'create_function() removed in PHP 7.2, use anonymous functions'
    ],
    
    // Replace var with public/private/protected for properties
    'var_keyword' => [
        'pattern' => '/^\s*var\s+\$/m',
        'replacement' => '    private $',
        'description' => 'var keyword deprecated, use visibility modifiers'
    ],
    
    // Replace reference returns (function &name) - not needed in PHP 8
    'reference_return' => [
        'pattern' => '/function\s+&\s+(\w+)/',
        'replacement' => 'function &$1 (references now optional in PHP 8+)',
        'description' => 'Reference returns syntax changed'
    ],
    
    // Replace old array() syntax with short [] syntax (optional modernization)
    'array_syntax' => [
        'pattern' => '/array\s*\(\s*\)/',
        'replacement' => '[]',
        'description' => 'Short array syntax (PHP 5.4+)'
    ],
];

echo "PHP 5.3 to 8.3 Modernization Patterns:\n";
echo str_repeat("=", 60) . "\n\n";

foreach ($patterns as $name => $info) {
    echo "Pattern: $name\n";
    echo "Description: {$info['description']}\n";
    echo "Search: {$info['pattern']}\n";
    echo "Replace: {$info['replacement']}\n";
    echo str_repeat("-", 60) . "\n";
}

echo "\nKey Modernization Recommendations:\n";
echo str_repeat("=", 60) . "\n";
echo "1. Add declare(strict_types=1); at top of files\n";
echo "2. Add namespace declarations\n";
echo "3. Use type hints (string, int, bool, array, etc.)\n";
echo "4. Use return type declarations\n";
echo "5. Replace var with public/private/protected\n";
echo "6. Convert classes to use modern syntax\n";
echo "7. Replace deprecated functions (ereg, split, create_function)\n";
echo "8. Use PDO or mysqli instead of mysql_* functions\n";
echo "9. Use anonymous functions instead of create_function\n";
echo "10. Add proper error handling with try-catch blocks\n";
echo "11. Use scalar type declarations\n";
echo "12. Consider using PHP 8 features (union types, match expressions, etc.)\n";

