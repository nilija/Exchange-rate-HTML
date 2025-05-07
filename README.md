A lightweight frontend display that retrieves and shows daily exchange rates from NBS using a simple PHP script and HTML rendering.  
Ideal for embedding in websites or internal dashboards.
# 💱 NBS Exchange Rate Updater – HTML Parser Version

This script downloads the official daily exchange rate list (exchange rate table) from the National Bank of Serbia (NBS) using **HTML parsing** and updates the **selling rate** for selected currencies into a MySQL table.

## 🔧 How It Works

- Connects to the NBS official exchange rate page (HTML).
- Parses the HTML content to extract selling rates.
- Updates the `selling_rate` column in your MySQL table.
- Intended to run automatically via a cron job (e.g. daily).

## 🧰 Requirements

- PHP 7.4+ with `mysqli` enabled
- Server with access to external URLs
- MySQL database and user with write access

## ⚙️ Configuration

Edit the PHP file to set:
- DB host, user, password, and table name
- Target currency codes (e.g. EUR, USD)

## 🕒 Cron Setup

Add to crontab to run daily at 08:00:

```bash
0 8 * * * /usr/bin/php /path/to/nbs_html_update.php
