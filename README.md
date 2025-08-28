# BLUE16 Website Source

<p align="center">
  <img src="https://img.shields.io/github/stars/bleu0-teem/web?style=for-the-badge&logo=github&label=Stars" alt="GitHub Stars"/>
  &nbsp;
  <img src="https://img.shields.io/github/forks/bleu0-teem/web?style=for-the-badge&logo=github&label=Forks" alt="GitHub Forks"/>
</p>

## About BLUE16

BLUE16 is an open-source project focused on reviving classic Roblox experiences. The main goal is to support running every possible version of Roblox, offering users a modern, reliable platform with a straightforward setup and robust features.

---

## Features

- **Fast Performance:** Designed to be responsive and efficient for all users.
- **Modern User Interface:** Clean and easy-to-use interface for a better experience.
- **Secure:** Implements strong security practices to protect your data and sessions.
- **Maintainable Codebase:** Modular structure with clear documentation to help contributors.
- **Dual Database Support:** Easily switch between MySQL and Supabase without changing the code.

---

## Technology Stack

- **Frontend:** PHP (component-based rendering)
- **Backend:** PHP (API-first, modular)
- **Styling:** Tailwind CSS (utility-first approach)
- **Database:** Supports both MySQL and Supabase

---

## Getting Started

Follow these steps to set up BLUE16 locally or on your server.

### Prerequisites

Make sure you have these installed:

- PHP (version 7.4 or higher recommended)
- Composer
- Git

### Installation

1. **Clone the repository:**
    ```bash
    git clone https://github.com/bleu0-teem/web.git
    cd web
    ```

2. **Install PHP dependencies:**
    Go to the `www` directory and use Composer.
    ```bash
    cd www
    composer install
    ```

3. **Configure environment variables:**
    Copy the example configuration and update it with your settings.
    ```bash
    cp .env.sample .env
    # Open .env in your editor and update your database and other settings
    ```

4. **Set up the database:**
    Check your configuration, then run database migrations.
    ```bash
    php config-check.php
    php migrate.php
    ```

### Choose Your Database

BLUE16 works with two databases:

- **MySQL:** Set `DB_TYPE=mysql` in your `.env` file and enter your MySQL details.
- **Supabase:** Set `DB_TYPE=supabase` and add your Supabase project keys.

See [DATABASE.md](www/DATABASE.md) for detailed setup instructions.

---

## Contributing

Contributions are welcome! Whether you want to fix bugs, add features, or improve documentation, your help makes the project better.

Read the [CONTRIBUTING.md](CONTRIBUTING.md) guide for details on how to get started.

---

## FAQ

**What is the purpose of BLUE16?**  
To provide a modern platform for reviving and running all historic Roblox versions, with an emphasis on security and scalability.

**Which databases are supported?**  
MySQL and Supabase are both fully supported and can be switched using environment variables.

**Where can I get support or ask questions?**  
Open an issue on GitHub or check the documentation for more information.

---

## License

This project is licensed under the [MIT License](LICENSE).

Copyright © [bleu0 teem omg](https://github.com/bleu0-teem)

---

### Support the Project
If you find BLUE16 useful, please star the repository to show your support and help the project grow.