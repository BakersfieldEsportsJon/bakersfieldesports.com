# Admin System Documentation

## Database Migration Instructions

To safely rename the database while ensuring zero downtime:

1. Create a new database with the desired name:
```sql
CREATE DATABASE bakerwgx_bec_admin;
```

2. Copy all tables and data to the new database:
```sql
mysqldump -u bakerwgx_bec -p bakerwgx_bec_gallery | mysql -u bakerwgx_bec -p bakerwgx_bec_admin
```

3. Verify the data in the new database:
```sql
USE bakerwgx_bec_admin;
SHOW TABLES;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM photos;
```

4. Create a temporary config file (admin/includes/db-config.temp.php) with the new database name.

5. Test the site using the new database by temporarily modifying the config include path:
```php
// Test by including this in a test.php file
require_once 'admin/includes/db-config.temp.php';
```

6. Once verified, update the main config:
```php
// In admin/includes/db-config.php
define('DB_NAME', 'bakerwgx_bec_admin');
```

7. Keep the old database for 24-48 hours as a backup before removing.

## PHP Compatibility Notes

### Current PHP 7.4 Features Used

The codebase currently uses several PHP 7.4 features:

- Arrow functions
- Typed properties
- Null coalescing assignment operator (??=)
- Spread operator in arrays
- Numeric literal separator

### PHP 8.4 Migration Checklist

When upgrading to PHP 8.4, consider the following:

1. **Breaking Changes to Address:**
   - The `each()` function is deprecated and removed
   - Changes to string to number comparisons
   - Stricter type checking for internal functions
   - Changes to error handling and reporting

2. **Code Updates Needed:**
   - Replace `each()` with `foreach()` loops
   - Update string comparisons to use strict typing
   - Add explicit type declarations where missing
   - Update error handling to use try-catch blocks

3. **New Features to Consider:**
   - Constructor property promotion
   - Named arguments
   - Match expressions
   - Nullsafe operator
   - Union types
   - First-class callable syntax

4. **Testing Process:**
   - Set up a staging environment with PHP 8.4
   - Enable error reporting (E_ALL)
   - Run through all admin functionality
   - Test file uploads and image processing
   - Verify database operations
   - Check email functionality
   - Test user authentication flows

### Common Compatibility Issues

1. **String/Number Comparisons:**
   ```php
   // PHP 7.4
   if ($value == 123) // loose comparison
   
   // PHP 8.4
   if ($value === 123) // strict comparison recommended
   ```

2. **Error Handling:**
   ```php
   // PHP 7.4
   $result = @function_call();
   
   // PHP 8.4
   try {
       $result = function_call();
   } catch (Error $e) {
       // Handle error
   }
   ```

3. **Null Handling:**
   ```php
   // PHP 7.4
   $value = isset($obj->prop) ? $obj->prop->method() : null;
   
   // PHP 8.4
   $value = $obj?->prop?->method();
   ```

### Recommended Upgrade Steps

1. Enable deprecation warnings in development:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

2. Update code incrementally:
   - Start with critical security updates
   - Address deprecated feature usage
   - Implement new PHP 8.4 features
   - Test thoroughly after each change

3. Use static analysis tools:
   - PHPStan
   - Psalm
   - PHP_CodeSniffer

4. Maintain backwards compatibility:
   - Use polyfills where needed
   - Document minimum PHP version requirements
   - Keep compatibility layer for critical features

## Security Considerations

1. **Password Hashing:**
   - Currently using PASSWORD_DEFAULT
   - Compatible with PHP 8.4
   - No changes needed

2. **Session Management:**
   - Session handling remains compatible
   - Consider enabling strict session mode

3. **File Operations:**
   - File upload handling is compatible
   - Image processing libraries need testing

4. **Database Operations:**
   - PDO usage remains compatible
   - Prepared statements working as expected

## Maintenance Notes

- Regular backups are crucial during the upgrade process
- Monitor error logs for deprecation warnings
- Keep documentation updated with changes
- Test all features thoroughly after updates
