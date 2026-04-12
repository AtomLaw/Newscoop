# PHP 5.3 to 8.3 Modernization Guide

## Overview

This repository contains PHP code written for PHP 5.3 that needs to be modernized to PHP 8.3. This guide outlines the key changes required and provides examples.

## Key Breaking Changes (PHP 5.3 → 8.3)

### 1. Removed Deprecated Functions

#### ereg/eregi functions (Removed in PHP 7.0)
**Before (PHP 5.3):**
```php
if (ereg('pattern', $string)) { }
if (eregi('pattern', $string)) { }
```

**After (PHP 8.3):**
```php
if (preg_match('/pattern/', $string)) { }
if (preg_match('/pattern/i', $string)) { }  // case-insensitive
```

#### split() function (Removed in PHP 7.0)
**Before:**
```php
$parts = split(',', $string);
```

**After:**
```php
$parts = explode(',', $string);  // for simple delimiters
// or
$parts = preg_split('/,/', $string);  // for regex patterns
```

#### create_function() (Removed in PHP 7.2)
**Before:**
```php
$fn = create_function('$a, $b', 'return $a + $b;');
```

**After:**
```php
$fn = fn($a, $b) => $a + $b;
// or
$fn = function($a, $b) { return $a + $b; };
```

#### mysql_* functions (Removed in PHP 7.0)
**Before:**
```php
mysql_connect($host, $user, $pass);
mysql_select_db($db);
$result = mysql_query($sql);
```

**After:**
```php
// Use PDO
$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$stmt = $pdo->query($sql);

// Or mysqli
$mysqli = new mysqli($host, $user, $pass, $db);
$result = $mysqli->query($sql);
```

### 2. Visibility Modifiers

**Before:**
```php
class MyClass {
    var $property;  // deprecated
    function myMethod() { }
}
```

**After:**
```php
class MyClass {
    private $property;  // or public/protected
    public function myMethod(): void { }
}
```

### 3. Type Declarations

**Before:**
```php
public function process($data, $count) {
    return $result;
}
```

**After:**
```php
public function process(array $data, int $count): array {
    return $result;
}
```

### 4. Strict Types

Add at the top of each file:
```php
<?php

declare(strict_types=1);

namespace MyNamespace;
```

### 5. Namespaces

All classes should be organized into namespaces:
```php
namespace CMSImports;

use PDO;
use Exception;
```

### 6. Modern Class Syntax

**Before:**
```php
class MyClass extends ParentClass {
    var $items = array();
    
    function __construct($param) {
        $this->items = array();
    }
}
```

**After:**
```php
class MyClass extends ParentClass
{
    private array $items = [];
    
    public function __construct(string $param)
    {
        $this->items = [];
    }
}
```

### 7. Error Handling

**Before:**
```php
$result = mysql_query($sql);
if (!$result) {
    die(mysql_error());
}
```

**After:**
```php
try {
    $stmt = $pdo->query($sql);
} catch (PDOException $e) {
    error_log($e->getMessage());
    throw $e;
}
```

### 8. Reference Returns (Optional in PHP 8+)

**Before:**
```php
function &getRef() {
    return $this->value;
}
```

**After:**
```php
// References are now optional for returns
function getRef() {
    return $this->value;
}
```

## PHP 8+ Features to Consider

### Union Types (PHP 8.0)
```php
public function process(int|string $input): int|false
```

### Match Expression (PHP 8.0)
```php
$status = match($code) {
    200, 300 => 'success',
    400 => 'error',
    default => 'unknown',
};
```

### Constructor Property Promotion (PHP 8.0)
```php
class User {
    public function __construct(
        public string $name,
        private string $email,
    ) {}
}
```

### Readonly Properties (PHP 8.1)
```php
class Config {
    public readonly string $environment;
}
```

### Enums (PHP 8.1)
```php
enum Status: string {
    case PENDING = 'pending';
    case APPROVED = 'approved';
}
```

## Migration Checklist

- [ ] Add `declare(strict_types=1);` to all files
- [ ] Add namespace declarations
- [ ] Replace `var` with `private`/`public`/`protected`
- [ ] Add type hints to parameters
- [ ] Add return type declarations
- [ ] Replace deprecated functions (ereg, split, create_function)
- [ ] Replace mysql_* with PDO or mysqli
- [ ] Convert array() to [] syntax
- [ ] Update error handling to use exceptions
- [ ] Remove reference return syntax where not needed
- [ ] Add proper docblocks
- [ ] Consider using PHP 8+ features where appropriate

## Testing Recommendations

1. Run static analysis tools (PHPStan, Psalm)
2. Ensure test coverage before refactoring
3. Test with PHP 8.3 in development environment
4. Check for deprecation warnings
5. Verify database connections work correctly
6. Test all critical paths after migration

