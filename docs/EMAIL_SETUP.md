# Email Notification Setup

The application can send notification emails whenever an internal alert is created (e.g. user registration, password reset, admin actions). Emails are sent by the `sendEmail()` helper in `../includes/functions.php`.

## Configuration

1. Open **Admin → System Settings → Email & Notifications** in the web UI; this is the screen shown in your screenshot.
   The form will look blank until you supply valid credentials. Fill in the top four
   fields, which correspond to your SMTP server configuration:

   | Field            | What to enter / example                           |
   |------------------|--------------------------------------------------|
   | SMTP Host        | address of the SMTP server, e.g. `smtp.gmail.com` or `mail.yourdomain.com` |
   | SMTP Port        | usually `587` for TLS or `465` for SSL           |
   | SMTP Username    | the login name (often an email address) for the SMTP account |
   | SMTP Password    | the password for that account (stored encrypted) |

   After clicking **Save**, the values are written to the `system_settings` table
   and the `sendEmail()` helper will automatically retrieve them when sending
   messages.  Without valid settings the notification emails cannot be delivered.

   If you don't yet have an SMTP provider, sign up with one (SendGrid, Mailgun,
   Gmail/Workspace, etc.) or ask your hosting provider for connection details.

3. Optionally set `SITE_EMAIL` in `../includes/config.php`; this value is used as the `From:` address if no SMTP username is provided.

4. Make sure your hosting environment allows outbound SMTP connections. If you don't have a mail server, you can sign up for a service such as SendGrid, Mailgun, Amazon SES, or use your ISP's SMTP relay.

## Dependencies

`sendEmail()` prefers using [PHPMailer](https://github.com/PHPMailer/PHPMailer) which supports modern authentication and TLS. To install:

```bash
cd c:\x\htdocs\aksum-rental
composer require phpmailer/phpmailer
```

After installation the library will be autoloaded automatically by the code above.

If PHPMailer is not available the helper falls back to PHP's built-in `mail()` function. On Windows, you must configure `SMTP`/`smtp_port`/`sendmail_from` in `php.ini` or via the Admin settings above.

## Debugging

All delivery attempts are logged to `../logs/email.log`. Check that file for failures and inspect your SMTP credentials.

You can also manually call the helper from a script to verify:

```php
require '../includes/config.php';
sendEmail('you@example.com', 'Test', 'Hello world');
```

If messages still do not arrive, contact your mail provider or hosting support to ensure your server is allowed to send mail.

---

Once SMTP is working and credentials are saved, every call to `addNotification()` will send an email to the recipient's registered address (including password resets by admins).
