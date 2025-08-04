# Blue16 Web Setup Guide

## Environment Configuration

This application uses environment variables for secure credential management and supports both MySQL and Supabase databases. Follow these steps to set up your environment:

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

### 3. Database Configuration

You can choose between MySQL and Supabase databases by setting the `DB_TYPE` environment variable.

#### Option A: MySQL Database (Traditional)

Set the following variables in your `.env` file:

```env
DB_TYPE=mysql
DB_HOST=your-database-host.com
DB_PORT=19008
DB_NAME=defaultdb
DB_USER=your-db-username
DB_PASS=your-db-password
```

#### Option B: Supabase Database (Recommended)

Set the following variables in your `.env` file:

```env
DB_TYPE=supabase
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_ANON_KEY=your-supabase-anon-key
```

To get your Supabase credentials:
1. Go to [Supabase](https://supabase.com) and create a new project
2. In your project dashboard, go to Settings > API
3. Copy the "Project URL" as `SUPABASE_URL`
4. Copy the "anon public" key as `SUPABASE_ANON_KEY`

#### Common Configuration

Regardless of database type, also set:

```env
VALID_INVITE_KEY=your-invite-key-here
APP_ENV=production
```

### 4. Database Schema Setup

#### For MySQL:
Create the required tables in your MySQL database:

```sql
-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Invite keys table
CREATE TABLE invite_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invite_key VARCHAR(64) UNIQUE NOT NULL,
    created_by INT NOT NULL,
    uses_remaining INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Legacy invite keys table (if needed)
CREATE TABLE invitekeys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) NOT NULL,
    uses INT DEFAULT 1,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### For Supabase:
1. Go to your Supabase project dashboard
2. Navigate to the SQL Editor
3. Run the same SQL schema as above, or use the Table Editor to create tables manually
4. Make sure to enable Row Level Security (RLS) if needed for your use case

### 5. Verify Configuration

Run the configuration check script to ensure everything is set up correctly:

```bash
php config-check.php
```

This script will:
- ✅ Check if Composer dependencies are installed
- ✅ Verify the .env file exists
- ✅ Validate all required environment variables are set (based on DB_TYPE)
- ✅ Test the database connection (MySQL or Supabase)

### 6. Security Notes

- Never commit the `.env` file to version control
- The `.env` file contains sensitive information and should be kept secure
- Use different credentials for development, staging, and production environments
- The `.env` file is already included in `.gitignore` to prevent accidental commits

### 7. Migration from Hardcoded Credentials

This update moves all credentials from hardcoded values in PHP files to environment variables and adds support for Supabase:

**Before:**
```php
$host = 'your-database-host.com';
$pass = 'your-database-password';
```

**After:**
```php
$host = $_ENV['DB_HOST'];
$pass = $_ENV['DB_PASS'];
```

All API files now use the centralized `db_connection.php` which loads credentials from environment variables and automatically handles both MySQL and Supabase connections based on the `DB_TYPE` setting.

### 8. Switching Between Databases

To switch between MySQL and Supabase:

1. Update the `DB_TYPE` in your `.env` file:
   - Set to `mysql` for MySQL database
   - Set to `supabase` for Supabase database

2. Ensure the corresponding database credentials are set in your `.env` file

3. Run the configuration check to verify the setup:
   ```bash
   php config-check.php
   ```

4. No code changes are required - the application will automatically use the appropriate database connection based on your configuration.

### 9. Benefits of Supabase

- **Real-time capabilities**: Built-in real-time subscriptions
- **Authentication**: Built-in user authentication and authorization
- **Auto-generated APIs**: Automatic REST and GraphQL APIs
- **Dashboard**: User-friendly web interface for database management
- **Scalability**: Automatic scaling and performance optimization
- **Security**: Built-in Row Level Security (RLS)
- **Backup**: Automatic backups and point-in-time recovery