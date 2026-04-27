> [!WARNING]
>
> This script is not actively maintained and may contain undiscovered security flaws.

# Simple Faucet Script
Crypto Faucet integrated with banlist and VPN/Proxy Shield. It uses hCaptcha for bot protection and IPHub for VPN/proxy detection. Claims are saved to the account balance and can be withdrawn via FaucetPay.
The faucet supports the currencies Bitcoin, Litecoin, Ethereum and Binance Coin.

# Mininum Requirements

* PHP >= 8.0 (tested on 8.3)
* PHP Extensions: cURL and GD
* MySQL

# Installation

1. Download the files

2. Upload to your FTP Server

3. Upload the sql.sql using PHPMyAdmin

4. Change in includes/config.php the MySQL Connection and the website url (e.g. http://example.org/faucet)

5. Open http://yourdomain.de/admin.php and enter the following login datas:

Admin Username: admin

Admin Password: admin

Now you're on the admin site and change the configuration to run your faucet!

# Demo

A demo is avaible: http://salmen.website/Faucet/


# Fee

Free. No hidden fees are applied.

# Terms and Conditions

You have the rights to modify the code, as long as you do not remove the license.
