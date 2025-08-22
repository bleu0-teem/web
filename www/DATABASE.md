# Database Configuration Guide

Blue16 supports both MySQL and Supabase databases. Switch between them using the `DB_TYPE` environment variable in your `.env` file.

## Quick Start

1. **Set `DB_TYPE` in `.env`:**
   - `DB_TYPE=mysql` for MySQL
   - `DB_TYPE=supabase` for Supabase
2. **Configure credentials** in `.env`
3. **Run config check:**
   ```bash
   php config-check.php
   ```
4. **Set up schema:**
   ```bash
   php migrate.php
   ```

## Database Types Comparison

| Feature              | MySQL   | Supabase |
|----------------------|---------|----------|
| Setup Complexity     | Medium  | Easy     |
| Real-time Features   | Manual  | Built-in |
| Authentication       | Custom  | Built-in |
| API Generation       | Manual  | Automatic|
| Dashboard            | phpMyAdmin/CLI | Web-based |
| Scaling              | Manual  | Automatic|
| Backup               | Manual  | Automatic|
| Security             | Manual  | Built-in RLS |

## Schema

Both databases use the same schema structure. See the main README or migrate.php for details.

## Troubleshooting
- **Connection failed:** Check credentials and network
- **Missing env vars:** Run `php config-check.php`
- **Schema errors:** Run `php migrate.php` and check permissions

## Support
- MySQL: See MySQL docs/community
- Supabase: See [Supabase docs](https://supabase.com/docs)
- For Blue16-specific issues, see the main README or open an issue on GitHub