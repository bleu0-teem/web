# Blue16 Web Setup Guide

## Environment Configuration

This application uses environment variables for secure credential management. Follow these steps to set up your environment:

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Variables Setup

1. Copy the sample environment file:
   ```bash
   cp .env.sample .env
   ```

2. Edit the `.env` file with your actual credentials:
   ```bash
   nano .env
   ```

3. Update the following variables:
   - `DB_HOST`: Your database host
   - `DB_PORT`: Your database port (default: 19008)
   - `DB_NAME`: Your database name
   - `DB_USER`: Your database username
   - `DB_PASS`: Your database password
   - `VALID_INVITE_KEY`: Your application's invite key

### 3. Verify Configuration

Run the configuration check script to ensure everything is set up correctly:

```bash
php config-check.php
```

This script will:
- ✅ Check if Composer dependencies are installed
- ✅ Verify the .env file exists
- ✅ Validate all required environment variables are set
- ✅ Test the database connection

### 4. Security Notes

- Never commit the `.env` file to version control
- The `.env` file contains sensitive information and should be kept secure
- Use different credentials for development, staging, and production environments
- The `.env` file is already included in `.gitignore` to prevent accidental commits

### 5. Migration from Hardcoded Credentials

This update moves all credentials from hardcoded values in PHP files to environment variables:

**Before:**
```php
$host = 'blue16data-blue16-ad24.b.aivencloud.com';
$pass = 'AVNS_mdnUGTzNDx4Ui4O8dTy';
```

**After:**
```php
$host = $_ENV['DB_HOST'];
$pass = $_ENV['DB_PASS'];
```

All API files now use the centralized `db_connection.php` which loads credentials from environment variables.