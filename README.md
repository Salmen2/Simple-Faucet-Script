# FaucetHub-Faucet
Bitcoin Faucet integrated with banlist and VPN/Proxy Shield. Use the reCaptcha version 2 and BitCaptcha. Payments to the Account and withdraw over FaucetHub


# Mininum Requirements

- PHP >= 5.4 (tested on 5.6), please do not use PHP 7.0 or higher

- MySQL

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

A small fee of 1 Satoshi will be sent to the owner for each claim. Remvoing license can be done by paying a one-time fee.

# Terms and Conditions

You have the rights to modify the code, as long you do not remove the fees/license.
