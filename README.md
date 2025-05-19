# Star Editions Fulfilment for WooCommerce

Connect your WooCommerce store with the Star Editions Fulfilment system to automatically forward orders for fulfilment and receive real-time updates.

## 🚀 Features

- Automatically send WooCommerce orders to Star Editions.
- Fulfilment updates are sent back to WooCommerce.
- Logs any fulfilment errors or issues.
- Easily configurable via the WordPress Admin interface.
- Secure API communication using an access token.
- Automatic plugin updates via GitHub integration.

---

## 📦 Installation

1. Download and install the plugin manually:
    - Upload the plugin ZIP to your WordPress site.
    - Or clone the repo into the `/wp-content/plugins/` directory.

2. Activate the plugin via WordPress admin.

3. Go to:  
   **Settings → [YourBrand] Fulfilment**  
   and configure the following:

   - ✅ Access Token  
   - ✅ Client Code  
   - ✅ Store Identifier  
   - (Optional) Notification Email

---

## ⚙️ Configuration Details

These options are stored as WordPress options:

| Key | Description |
|-----|-------------|
| `savvy_web_access_token` | The access token used for authenticating API requests. |
| `savvy_web_client_code` | The client code assigned by Star Editions. |
| `savvy_web_store_identifier` | Unique store/site ID. |
| `savvy_web_notification_email` | Optional notification email. |
| `savvy_web_registered` | Boolean indicating if the site is registered. |
| `savvy_web_updater_user` | GitHub username for plugin updates. |
| `savvy_web_updater_repo` | GitHub repository name. |

---

## 🔐 Security

All API routes require a valid `X-Savvy-Token` header matching the configured token in `savvy_web_access_token`.

---

## 🛠 API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/wp-json/savvy-web/v1/order/{id}` | ✅ | Get order details |
| GET | `/wp-json/savvy-web/v1/orders/unfulfilled` | ✅ | List unfulfilled orders |
| GET | `/wp-json/savvy-web/v1/site-info` | ✅ | Get site environment details |
| POST | `/wp-json/savvy-web/v1/updater/token` | ✅ | Update GitHub updater config |
| GET/POST | `/wp-json/savvy-web/v1/callback` | ❌ | Generic callback endpoint |

---

## 🧠 Development Notes

- Autoloader is PSR-4 styled and based in `includes/`.
- Plugin version defined as constant: `SAVVY_WEB_FULFILMENT_VERSION`.
- Logs stored in custom table: `{prefix}_savvy_web_logs`.
- Queue & retry handling not currently used; all requests are synchronous.

---

## 🔄 GitHub Updater

On registration, GitHub updater values are received from the API and stored. You can later update them via the `/updater/token` route.

> ⚠️ GitHub tokens are stored as plaintext in the WordPress DB.

---

## ✅ To-Do / Improvements

- [ ] Add support for background processing of large order volumes.
- [ ] Add retry queue for failed API calls.
- [ ] Consider encrypting GitHub token.
- [ ] Add unit tests and WordPress filters/hooks for extensibility.

---

## 🧪 Development & Testing

If you're testing locally:

- Log output is available under the **Logs** tab in the plugin settings.
- Errors are also sent to `error_log()` via `error_log('[SavvyWeb] ...')`.

---

## 🧑‍💻 Authors

Built by [Savvy Web](https://www.savvyweb.net)

Maintained by: `@savvymarcom`  
Plugin version: `1.0.0`

---

## 📄 License

MIT License — feel free to use, extend and customise as needed.
