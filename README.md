# Telegram Reminder — Scheduled Telegram Reminder System

Core PHP 8.2 + MySQL + Cron. Admin dashboard to schedule reminders that are sent automatically via Telegram Bot.

## Quick start (XAMPP)

1. Place the project in `C:\xampp\htdocs\telegram_reminder2`
2. Copy `config/config.example.php` to `config/config.php` and fill in your database credentials
3. Start **Apache** and **MySQL** in XAMPP
4. Open http://localhost/telegram_reminder2/admin/login.php
5. On first visit, the database and tables are created automatically (or import `schema.sql` manually)
6. Default login: `admin` / `admin123` — change the password after login
7. Go to **Telegram settings**, enter your Bot Token and Chat ID, then click **Send test message**
8. Go to **Quick reminder**, fill in title, send time, and message content, then save
9. Due reminders are sent automatically. With the admin panel open locally, checks run every **20 seconds**. On production, configure Cron (see below)

Forgot password: open `admin/forgot_password.php`. When `APP_DEBUG` is `true`, the reset link is shown on screen for local testing.

## Features

- Admin login only (no public registration)
- Dashboard with reminder stats, upcoming queue, recent logs, and system status
- **Quick reminder** — token, chat_id, title, time, and multiple message blocks
- **Saved content** — store reusable text and fill reminders with one click
- Emoji picker and content templates
- Multiple recipients and multiple messages (sent in order with configurable delay)
- Reminder list with search and filters (all / today / pending / sent / failed / last 7 days)
- **Telegram users** — save recipient names and chat IDs; test delivery from the list
- **Send logs** — delivery result per message (sent / failed, time, error)
- **Telegram settings** — save Bot Token, default Chat ID, and send test messages
- **Admins** — add admin accounts and change your password
- Status values: `pending`, `sent`, `failed`, `partially_sent`
- Cron / CLI dispatch engine plus automatic browser polling while the admin UI is open
- English and Chinese UI (switch in the top-right corner)
- Password reset via email link (or on-screen link in debug mode)

## Screenshots

| Admin login | Dashboard |
|:---:|:---:|
| ![Admin login](docs/screenshots/01-admin-login.png) | ![Dashboard](docs/screenshots/02-dashboard.png) |

| Quick reminder | Reminders |
|:---:|:---:|
| ![Quick reminder](docs/screenshots/03-quick-reminder.png) | ![Reminders](docs/screenshots/04-reminders.png) |

| Telegram users | Send logs |
|:---:|:---:|
| ![Telegram users](docs/screenshots/05-telegram-users.png) | ![Send logs](docs/screenshots/06-send-logs.png) |

| Telegram settings | Admins |
|:---:|:---:|
| ![Telegram settings](docs/screenshots/07-telegram-settings.png) | ![Admins](docs/screenshots/08-admins.png) |

More detail: [docs/SCREENSHOTS.md](docs/SCREENSHOTS.md)

## Folder structure

```
telegram_reminder2/
├── admin/                   Admin pages (login, dashboard, reminders, …)
├── api/                     JSON API (reminders, users, logs, settings)
├── assets/css & js          Bootstrap UI + app scripts
├── config/                  config.php, database.php
├── cron/send_reminders.php  Send due reminders
├── docs/                    Screenshots
├── includes/                Auth, Telegram, layout, helpers
├── lang/                    en.php, zh.php
├── storage/                 settings.json, cron.lock, cron.log
├── schema.sql               MySQL tables
├── index.php                Redirect to login or dashboard
├── INSTALL.md
└── TELEGRAM_SETUP.md
```

## 1. XAMPP setup

1. Copy this folder to `C:\xampp\htdocs\telegram_reminder2`
2. Start **Apache** and **MySQL**
3. Copy `config/config.example.php` → `config/config.php`
4. Edit database settings (typical XAMPP):
   - Host: `localhost`
   - Database: `telegram_reminder_db`
   - User: `root`
   - Password: your MySQL password (or empty)
5. Set `APP_URL` to your local URL, e.g. `http://localhost/telegram_reminder2`
6. Change `CRON_SECRET_KEY` to a long random string
7. Open: http://localhost/telegram_reminder2/admin/login.php
8. Login with `admin` / `admin123`

**Alternative (phpMyAdmin):** import `schema.sql`, then create `config/config.php` manually.

## 2. Create a reminder

In **Quick reminder**:

1. **Bot Token** — from [@BotFather](https://t.me/BotFather) (leave blank if already saved)
2. **Chat ID** — your Telegram chat id (group ids are negative)
3. **Title** — reminder name shown in the list
4. **Send time** — when to deliver
5. **Message content** — one or more messages, sent in order

Click **Save** and wait. Status appears on Dashboard and in **Reminders**.

Saved content on the right can be reused later — write text, click **Save this**, then click a saved item to fill the message field.

## 3. Telegram bot

See [TELEGRAM_SETUP.md](TELEGRAM_SETUP.md) for step-by-step setup.

Short version:

1. Open [@BotFather](https://t.me/BotFather) → `/newbot` → copy token
2. Send `/start` to your bot
3. Visit `https://api.telegram.org/botYOUR_TOKEN/getUpdates` and find `"chat":{"id": number}`
4. In this app: **Telegram settings** → paste token + chat id → **Save** → **Send test message**

You can also save recipients under **Telegram users** and pick them when creating reminders.

## 4. Cron / automatic sending

The engine sends reminders whose `scheduled_time` has passed and status is `pending`. Run it **every 1 minute** on production.

While any admin page is open in the browser, the app also calls the cron URL every **20 seconds** — useful for local XAMPP without Task Scheduler.

### Windows Task Scheduler (XAMPP)

1. Task Scheduler → Create Basic Task
2. Trigger: Daily → Properties → repeat every **1 minute**, indefinitely
3. Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\telegram_reminder2\cron\send_reminders.php YOUR_CRON_SECRET`
4. Adjust paths to match your XAMPP install

Or call the web URL every minute:

```text
http://localhost/telegram_reminder2/cron/send_reminders.php?key=YOUR_CRON_SECRET
```

### Linux / cPanel crontab

```text
* * * * * curl -fsS "https://your-domain/cron/send_reminders.php?key=YOUR_CRON_SECRET"
```

CLI:

```text
php cron/send_reminders.php YOUR_CRON_SECRET
```

Invalid or missing key returns **403**.

Dashboard also has **Run now** and a copyable cron URL under **System status**.

## 5. Forgot password

Open `admin/forgot_password.php` and enter your username or email.

- If mail is configured (`MAIL_FROM` in `config/config.php`), a reset link is emailed (valid 30 minutes).
- If `APP_DEBUG` is `true`, the reset link is shown on screen for local testing.

You can also change your password while logged in under **Admins → Change my password**.

## 6. Default ports / PHP

- Local URL: http://localhost/telegram_reminder2/
- PHP **cURL** must be enabled (`extension=curl` in `php.ini`)
- Timezone: `Asia/Kuala_Lumpur` in `config/config.php`
- PHP 8.2+ recommended (`declare(strict_types=1)`)

To reset the database: drop `telegram_reminder_db` in phpMyAdmin and visit the login page again.

## System flow

1. Admin logs in
2. Admin saves Telegram token / chat id (optional: saved recipients)
3. Admin creates a reminder with title, time, messages, and recipients
4. Cron (or browser poll) runs `cron/send_reminders.php`
5. Due reminders are sent via Telegram API (multi-message with delay)
6. Results stored in `message_logs`; reminder status updated
7. Dashboard, Reminders, and Send logs reflect the outcome

## Security notes

- Admin-only access; sessions after login
- Passwords hashed with `password_hash()` (bcrypt)
- CSRF tokens on forms and API POST requests
- Login throttling after repeated failures
- Cron requires `CRON_SECRET_KEY` when called via HTTP
- Bot token stored in `storage/settings.json` — do not commit production secrets
- Set `APP_DEBUG` to `false` on production

## More docs

- [INSTALL.md](INSTALL.md) — XAMPP and cPanel deployment
- [TELEGRAM_SETUP.md](TELEGRAM_SETUP.md) — BotFather and chat_id
- [docs/SCREENSHOTS.md](docs/SCREENSHOTS.md) — UI walkthrough with captions

## License

This project is licensed under the [MIT License](LICENSE).
