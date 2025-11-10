# Hosting Constraints and Lessons Learned

## File Structure Constraints
1. Project Sync Limitation
   - Only files within the project directory sync to the server
   - Cannot access directories outside project root
   - External secure storage not viable

2. Database Configuration
   - MySQL connection requires specific file locations
   - Environment variables through .env file work correctly
   - Database config must stay within project structure

## Changes Made
1. Reverted Changes:
   - Moved db_config.php back to admin/includes/
   - Moved debug_config.php back to admin/includes/
   - Updated gallery/includes/config.php to use environment variables
   - Kept secure_storage directory for future reference

2. Working Configuration:
   - Using .env file for sensitive credentials
   - Bootstrap loads environment variables
   - Gallery system uses env() helper function
   - Maintains database connection within project structure

## Future Security Recommendations
1. Within-Project Security:
   - Create protected directories inside project
   - Use .htaccess for web access control
   - Implement PHP-based access restrictions
   - Keep sensitive files in admin directory

2. Alternative Approaches:
   - Use environment variables for sensitive data
   - Implement stronger file permissions
   - Add additional security checks in PHP
   - Consider encrypted configuration files

## Testing Requirements
1. Database Changes:
   - Verify MySQL connection works
   - Test gallery functionality
   - Check admin access
   - Monitor error logs

2. File Access:
   - Ensure config files are readable
   - Verify proper permissions
   - Test security measures
   - Check error handling

## Notes for Future Changes
1. Always consider:
   - Server sync limitations
   - File location requirements
   - Project directory structure
   - Hosting environment constraints

2. Security Measures:
   - Must work within project directory
   - Cannot rely on external storage
   - Should use existing security features
   - Must maintain file accessibility
