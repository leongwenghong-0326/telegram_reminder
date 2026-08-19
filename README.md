# Telegram Reminder Management System

Core PHP 8.2 + MySQL + Cron. Admin dashboard to schedule Telegram reminders.

## 你怎么用

1. 登录后台。
2. 在「快速提醒」填写：Bot Token、chat_id、标题、时间、提醒内容。
3. 保存后等待。本机打开快速提醒页面时会每 20 秒自动检查并发送到期提醒。cPanel 请配置 Cron URL。

## 默认账号

- 用户名：`admin`
- 密码：`admin123`

## 文档

- [INSTALL.md](INSTALL.md)
- [TELEGRAM_SETUP.md](TELEGRAM_SETUP.md)
