# 安装与部署

## 本地 XAMPP

1. 启动 Apache 和 MySQL。
2. 项目路径：`C:\xampp\htdocs\project\telegram_reminder`
3. 打开 `config/config.php`，确认数据库账号密码（本机已按你的 MySQL 配好）。
4. 首次访问登录页会自动建库建表。
5. 登录：http://localhost/telegram_reminder/admin/login.php  
   账号 `admin` / `admin123`

## cPanel

1. 上传全部文件。
2. 创建 MySQL 数据库，把账号写进 `config/config.php`。
3. 修改 `APP_URL`、`CRON_SECRET_KEY`，生产环境设 `APP_DEBUG` 为 `false`。
4. Cron（每分钟）：

```text
curl -fsS "https://你的域名/cron/send_reminders.php?key=你的密钥"
```

CLI：

```text
php cron/send_reminders.php 你的密钥
```

无密钥返回 403。
