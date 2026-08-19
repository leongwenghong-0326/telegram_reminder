# Telegram Bot 设置（@BotFather）

1. Telegram 搜索并打开 **@BotFather**。
2. 发送 `/newbot`，按提示起名。用户名必须以 `bot` 结尾。
3. 复制返回的 Token（形如 `1234567890:AAHxxxx`）。
4. 打开你的机器人，发送 `/start`。
5. 浏览器访问：

```text
https://api.telegram.org/bot这里换成TOKEN/getUpdates
```

6. 在 JSON 里找 `"chat": { "id": 1566077604 }`，这个数字就是你的 **chat_id**。
7. 回到系统「快速提醒」或「Telegram 设置」，填入 Token 和 chat_id。

群组 chat_id 一般为负数。单条消息最长 4096 字。
