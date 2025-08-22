# Blue16 Web Setup Guide

## Quick Setup

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Copy and Edit Environment Variables**
   ```bash
   cp .env.sample .env
   # Edit .env with your credentials
   ```

3. **Choose Your Database**
   - MySQL: Set `DB_TYPE=mysql` in `.env`
   - Supabase: Set `DB_TYPE=supabase` in `.env`
   - See [DATABASE.md](DATABASE.md) for details

4. **Set Up Database Schema**
   ```bash
   php migrate.php
   ```

5. **Verify Configuration**
   ```bash
   php config-check.php
   ```

## Security Notes
- Never commit `.env` to version control
- Use different credentials for dev, staging, and production

## Troubleshooting
- If you have issues, check your `.env` and database connection
- See the main README and DATABASE.md for more help