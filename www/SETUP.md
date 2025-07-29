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

### 3. Security Notes

- Never commit the `.env` file to version control
- The `.env` file contains sensitive information and should be kept secure
- Use different credentials for development, staging, and production environments