# BLUE16 Websie Source

<p align="center">
  <img src="https://img.shields.io/github/stars/bleu0-teem/web?style=for-the-badge&logo=github&label=Stars" alt="GitHub Stars"/>
  &nbsp;
  <img src="https://img.shields.io/github/forks/bleu0-teem/web?style=for-the-badge&logo=github&label=Forks" alt="GitHub Forks"/>
</p>

## ✨ Unleash Roblox's Legacy: The Ultimate Revival Project

Welcome to **BLUE16** – an ambitious open-source ROBLOX revival project dedicated to the incredible goal of running **EVERY Roblox version possible**. We're building a platform that marries nostalgic functionality with modern web standards: clean design, robust features, and built for unparalleled scalability.

---

## 🚀 Key Features

Experience a revival platform engineered for the modern web:

*   **Blazing Fast Performance:** Optimized for speed and responsiveness, ensuring a smooth user experience.
*   **Sleek & Intuitive UI:** A contemporary and aesthetic user interface designed for ease of use.
*   **Robust Security by Design:** Implementing best practices to keep your data and sessions secure.
*   **Highly Maintainable Codebase:** Modular architecture and clear documentation for easy understanding and contributions.
*   **Seamless Dual Database Support:** Effortlessly switch between MySQL and Supabase without any code changes.

---

## 🛠️ Tech Stack

BLUE16 is powered by a reliable and modern stack:

*   **Frontend:** PHP (with a focus on clean, component-based rendering)
*   **Backend:** PHP (API-first, modular, and extensible)
*   **Styling:** Tailwind CSS (utility-first for rapid, consistent styling)
*   **Database:** Flexible support for both **MySQL** and **Supabase**

---

## 🏁 Getting Started

Follow these steps to get your BLUE16 website instance up and running locally or on your server.

### Prerequisites

Ensure you have the following installed on your system:

*   PHP (v7.4 or higher recommended)
*   Composer
*   Git

### Installation Steps

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/bleu0-teem/web.git
    cd web
    ```

2.  **Install PHP dependencies:**
    Navigate into the `www` directory and install composer packages.
    ```bash
    cd www
    composer install
    ```

3.  **Configure your environment:**
    Copy the example environment file and then edit it with your specific settings, especially database credentials.
    ```bash
    cp .env.sample .env
    # Open .env in your editor and configure your settings
    ```

4.  **Set up your database:**
    First, verify your configuration, then run migrations to create the necessary tables.
    ```bash
    php config-check.php  # Verifies your .env configuration
    php migrate.php       # Creates and updates database tables
    ```

### Database Selection

BLUE16 provides first-class support for two powerful database options:

*   **MySQL:** Set `DB_TYPE=mysql` in your `.env` file and provide your MySQL connection details.
*   **Supabase:** Set `DB_TYPE=supabase` in your `.env` file and configure your Supabase project keys.

For a detailed guide on database setup and configuration, please refer to the [DATABASE.md](www/DATABASE.md) documentation.

---

## 👋 Contributing

We welcome contributions from the community! Whether it's bug fixes, new features, or documentation improvements, your help is invaluable.

Please see our [CONTRIBUTING.md](CONTRIBUTING.md) guide for detailed instructions on how to get started.

---

## ❓ FAQ

**Q: What is the core mission of BLUE16?**
A: Our goal is to create a robust and modern ROBLOX revival platform capable of running all possible historic Roblox versions, offering a clean, secure, and scalable experience.

**Q: Which databases are supported out-of-the-box?**
A: We currently support both traditional MySQL and modern Supabase databases, switchable via environment variables.

**Q: Where can I get support or ask questions?**
A: Feel free to open an issue on our GitHub repository. We also encourage you to check the existing documentation.

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

Copyright © [bleu0 teem omg](https://github.com/bleu0-teem)

---

### Loved BLUE16? Show your support! ⭐
If you find this project exciting or useful, please consider starring our repository! Your support helps our open-source efforts thrive.