# BLUE16 Web

Welcome to **BLUE16**, a ROBLOX revival project aiming to run EVERY Roblox version possible.  
Clean design. Powerful features. Built for scalability.

---

## Features

- **Lightning Fast**: Built for speed and performance.
- **Modern UI**: Sleek and aesthetic user interface.
- **Secure**: Security best-practices baked in.
- **Easy to Maintain**: Modular and well-documented codebase.
- **Flexible Database**: Support for both MySQL and Supabase databases.

---

## Tech Stack

- **Frontend:** PHP
- **Backend:** PHP (API-first, modular)
- **Styling:** Tailwind CSS
- **Database:** MySQL or Supabase (switchable)

---

## Getting Started

1. **Clone the repository**
   ```bash
   git clone https://github.com/bleu0-teem/web.git
   cd web
   ```

2. **Install dependencies**
   ```bash
   cd www
   composer install
   ```

3. **Configure your environment**
   ```bash
   cp .env.sample .env
   # Edit .env with your database credentials
   ```

4. **Set up your database**
   ```bash
   php config-check.php  # Verify configuration
   php migrate.php       # Create database tables
   ```

5. **Choose your database**
   - **MySQL**: Set `DB_TYPE=mysql` and configure MySQL credentials
   - **Supabase**: Set `DB_TYPE=supabase` and configure Supabase credentials
   
   See [DATABASE.md](www/DATABASE.md) for detailed configuration guide.

---

## Database Support

Blue16 Web supports both traditional MySQL and modern Supabase databases. Switch between them by changing the `DB_TYPE` environment variable. No code changes required!

---

## Contributing

Contributions are welcome!  
See [CONTRIBUTING.md](CONTRIBUTING.md) for more info.

---

## FAQ

**Q: What is BLUE16?**
A: A revival project for Roblox, aiming to support all versions with a modern, secure, and scalable web interface.

**Q: Which databases are supported?**
A: MySQL and Supabase. See [DATABASE.md](www/DATABASE.md) for details.

**Q: How do I get support?**
A: Open an issue on GitHub or check the documentation.

---

## License

MIT License © [bleu0 teem omg](https://github.com/bleu0-teem)

---

<p align="center">
<img src="https://img.shields.io/github/stars/bleu0-teem/web?style=social" alt="GitHub Stars"/>
&nbsp;
<img src="https://img.shields.io/github/forks/bleu0-teem/web?style=social" alt="GitHub Forks"/>
</p>

... whos gonna star this repository anyway..?