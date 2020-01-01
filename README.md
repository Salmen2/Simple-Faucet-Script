# Simple Faucet Script
Bitcoin Faucet integrated with banlist and VPN/Proxy Shield. It uses the service of Google reCaptcha (v2; box) and IPHub. Any claims will be saved in the account balance and can be withdrawn to ExpressCrypto, FaucetPay or directly using Block.io

# Mininum Requirements

* PHP >= 5.4 (tested on 5.6), please do not use PHP 7.0 or higher
* PHP Extensions: cURL and GMP
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
