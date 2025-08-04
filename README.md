# 💙 blue16-web

Welcome to **blue16-web**, a ROBLOX revival, that aims to run EVERY roblox version possible.  
Clean design. Powerful features. Built for scalability.

---

## 🚀 Features

- ⚡ **Lightning Fast**: Built for speed and performance.
- 🎨 **Modern UI**: Sleek and aesthetic user interface.
- 🔒 **Secure**: Security best-practices baked in.
- ♻️ **Easy to Maintain**: Modular and well-documented codebase.
- 🗄️ **Flexible Database**: Support for both MySQL and Supabase databases.

---

- **Frontend:** [PHP](https://www.php.net/)
- **Backend:** ... Just Static, i guess.
- **Styling:** [Tailwind CSS](https://tailwindcss.com/)

---

## 🧑‍💻 Getting Started

1. **Clone the repository**
   ```bash
   git clone https://github.com/blue16-team/blue16-web.git
   cd blue16-web
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

## 🗄️ Database Support

Blue16 Web supports both traditional MySQL and modern Supabase databases:

### MySQL
- Full control over your database server
- Traditional SQL operations
- Wide hosting support

### Supabase (Recommended)
- Zero server maintenance
- Built-in real-time features
- Automatic REST/GraphQL APIs
- Built-in authentication
- Web-based dashboard

Switch between databases by simply changing the `DB_TYPE` environment variable. No code changes required!

---

## 🤝 Contributing

Contributions are welcome!  
See [CONTRIBUTING.md](CONTRIBUTING.md) for more info.

---

## 📄 License

MIT License © [BLUE16 TEAM](https://github.com/blue16-team)

---

<p align="center">
<img src="https://img.shields.io/github/stars/blue16-team/blue16-web?style=social" alt="GitHub Stars"/>
&nbsp;
<img src="https://img.shields.io/github/forks/blue16-team/blue16-web?style=social" alt="GitHub Forks"/>
</p>