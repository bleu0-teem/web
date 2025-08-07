# Database Configuration Guide

Blue16 now supports both MySQL and Supabase databases with seamless switching between them using environment variables.

## Quick Start

1. **Choose your database type** by setting `DB_TYPE` in your `.env` file:
   - `DB_TYPE=mysql` for traditional MySQL database
   - `DB_TYPE=supabase` for modern Supabase database

2. **Configure your credentials** in the `.env` file based on your choice

3. **Run the configuration check**: `php config-check.php`

4. **Set up your database schema**: `php migrate.php`

## Database Types Comparison

| Feature | MySQL | Supabase |
|---------|-------|----------|
| **Setup Complexity** | Medium | Easy |
| **Real-time Features** | Manual | Built-in |
| **Authentication** | Custom | Built-in |
| **API Generation** | Manual | Automatic |
| **Dashboard** | phpMyAdmin/CLI | Web-based |
| **Scaling** | Manual | Automatic |
| **Backup** | Manual | Automatic |
| **Security** | Manual setup | Built-in RLS |

## MySQL Configuration

### Environment Variables
```env
DB_TYPE=mysql
DB_HOST=your-database-host.com
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_username
DB_PASS=your_password
```

### Pros
- Full control over database server
- Familiar SQL operations
- Wide hosting support
- Mature ecosystem

### Cons
- Requires manual setup and maintenance
- No built-in real-time features
- Manual backup and scaling

## Supabase Configuration

### Environment Variables
```env
DB_TYPE=supabase
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_ANON_KEY=your-supabase-anon-key
```

### Getting Supabase Credentials
1. Visit [supabase.com](https://supabase.com) and create an account
2. Create a new project
3. Go to Settings > API in your project dashboard
4. Copy the "Project URL" as `SUPABASE_URL`
5. Copy the "anon public" key as `SUPABASE_ANON_KEY`

### Pros
- Zero server maintenance
- Built-in real-time subscriptions
- Automatic REST and GraphQL APIs
- Built-in authentication system
- Row Level Security (RLS)
- Automatic backups
- Web-based dashboard
- Automatic scaling

### Cons
- Vendor lock-in
- Learning curve for Supabase-specific features
- Potential costs for high usage

## Database Schema

Both databases use the same schema structure:

### Users Table
```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Invite Keys Table
```sql
CREATE TABLE invite_keys (
    id SERIAL PRIMARY KEY,
    invite_key VARCHAR(64) UNIQUE NOT NULL,
    created_by INTEGER NOT NULL,
    uses_remaining INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Legacy Invite Keys Table
```sql
CREATE TABLE invitekeys (
    id SERIAL PRIMARY KEY,
    key VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) NOT NULL,
    uses INTEGER DEFAULT 1,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Migration Between Databases

### From MySQL to Supabase
1. Export your MySQL data using `mysqldump` or phpMyAdmin
2. Update your `.env` file with Supabase credentials
3. Import your data into Supabase using the SQL editor
4. Test the connection with `php config-check.php`

### From Supabase to MySQL
1. Export your data from Supabase dashboard
2. Set up your MySQL database
3. Update your `.env` file with MySQL credentials
4. Import your data into MySQL
5. Test the connection with `php config-check.php`

## API Compatibility

The application maintains full API compatibility regardless of the database choice. All existing endpoints work the same way:

- `/api/login.php`
- `/api/register.php`
- `/api/validate_token.php`
- `/api/invites.php`
- `/api/invite_key.php`
- `/api/check_invite_key.php`

## Database Utilities

The `DatabaseUtils` class provides a unified interface for common database operations:

```php
// Get user by username or email
$user = DatabaseUtils::getUserByIdentifier($identifier);

// Create new user
$userId = DatabaseUtils::createUser($username, $email, $passwordHash);

// Validate token
$user = DatabaseUtils::validateToken($token);

// Check if username exists
$exists = DatabaseUtils::usernameExists($username);

// Get user invites
$invites = DatabaseUtils::getUserInvites($username);

// Check invite key
$result = DatabaseUtils::checkInviteKey($inviteKey);

// Create invite key
$result = DatabaseUtils::createInviteKey($key, $createdBy, $uses);
```

## Troubleshooting

### Common Issues

1. **Connection Failed**
   - Verify your credentials in `.env`
   - Check network connectivity
   - Ensure the database service is running

2. **Missing Environment Variables**
   - Run `php config-check.php` to identify missing variables
   - Copy from `.env.sample` and update values

3. **Schema Errors**
   - Run `php migrate.php` to create tables
   - Check database permissions
   - Verify SQL syntax for your database type

### Debug Mode

Enable debug logging by adding to your `.env`:
```env
APP_ENV=development
```

This will provide more detailed error messages in the logs.

## Security Considerations

### MySQL
- Use SSL connections in production
- Implement proper user permissions
- Regular security updates
- Backup encryption

### Supabase
- Enable Row Level Security (RLS)
- Use service role key only on server-side
- Configure proper authentication policies
- Monitor usage and access logs

## Performance Tips

### MySQL
- Use connection pooling
- Optimize queries with indexes
- Regular maintenance and optimization
- Monitor slow query log

### Supabase
- Use appropriate indexes
- Implement proper RLS policies
- Monitor usage dashboard
- Use connection pooling for high traffic

## Support

For database-specific issues:
- **MySQL**: Check MySQL documentation and community forums
- **Supabase**: Visit [Supabase documentation](https://supabase.com/docs) and Discord community

For Blue16 Web specific issues, please check the main README.md or create an issue in the repository.