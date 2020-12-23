CREATE TABLE IF NOT EXISTS `faucet_addon_list` (
`id` int(32) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `directory_name` varchar(50) NOT NULL,
  `enabled` int(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `faucet_banned_address` (
`id` int(32) unsigned NOT NULL,
  `address` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `faucet_banned_ip` (
`id` int(32) unsigned NOT NULL,
  `ip_address` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `faucet_pages` (
`id` int(32) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `timestamp_created` int(32) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `faucet_settings` (
`id` int(32) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(400) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

INSERT INTO `faucet_settings` (`id`, `name`, `value`) VALUES
(1, 'faucet_name', 'Simple Faucet Script'),
(2, 'solvemedia_challenge_key', ''),
(3, 'solvemedia_verification_key', ''),
(4, 'solvemedia_auth_hash_key', ''),
(5, 'timer', '60'),
(6, 'min_reward', '1'),
(7, 'max_reward', '100'),
(8, 'reCaptcha_privKey', ''),
(9, 'reCaptcha_pubKey', ''),
(10, 'expresscrypto_api_key', ''),
(11, 'claim_enabled', 'yes'),
(12, 'admin_username', 'admin'),
(13, 'admin_password', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918'),
(14, 'vpn_shield', 'no'),
(15, 'referral_percent', '0'),
(16, 'reverse_proxy', 'no'),
(17, 'admin_login', ''),
(18, 'expresscrypto_user_token', ''),
(19, 'faucetpay_api_token', ''),
(20, 'blockio_api_key', ''),
(21, 'blockio_pin', ''),
(22, 'iphub_api_key', ''),
(23, 'min_withdrawal_gateway', '1'),
(24, 'min_withdrawal_direct', '1'),
(25, 'bootswatch_theme', '');

CREATE TABLE IF NOT EXISTS `faucet_spaces` (
`id` int(32) unsigned NOT NULL,
  `name` varchar(15) NOT NULL,
  `space` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

INSERT INTO `faucet_spaces` (`id`, `name`, `space`) VALUES
(1, 'space_top', 'Space on the top'),
(2, 'space_left', 'Space on the left side'),
(3, 'space_right', 'Space on the right side');

CREATE TABLE IF NOT EXISTS `faucet_transactions` (
`id` int(32) unsigned NOT NULL,
  `userid` int(32) NOT NULL,
  `type` varchar(50) NOT NULL,
  `amount` decimal(10,8) NOT NULL,
  `timestamp` int(32) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `faucet_user_list` (
`id` int(32) unsigned NOT NULL,
  `account_type` int(32) NOT NULL,
  `address` varchar(75) NOT NULL,
  `ec_userid` varchar(20) NOT NULL,
  `ip_address` varchar(50) NOT NULL,
  `balance` decimal(10,8) NOT NULL,
  `joined` int(32) NOT NULL,
  `last_activity` int(32) NOT NULL,
  `referred_by` int(32) NOT NULL,
  `last_claim` int(32) NOT NULL,
  `claim_cryptokey` varchar(75) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


ALTER TABLE `faucet_addon_list`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `faucet_banned_address`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `faucet_banned_ip`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `faucet_pages`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `faucet_settings`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `faucet_spaces`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `faucet_transactions`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `faucet_user_list`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `faucet_addon_list`
MODIFY `id` int(32) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `faucet_banned_address`
MODIFY `id` int(32) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `faucet_banned_ip`
MODIFY `id` int(32) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `faucet_pages`
MODIFY `id` int(32) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `faucet_settings`
MODIFY `id` int(32) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=26;
ALTER TABLE `faucet_spaces`
MODIFY `id` int(32) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
ALTER TABLE `faucet_transactions`
MODIFY `id` int(32) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `faucet_user_list`
MODIFY `id` int(32) unsigned NOT NULL AUTO_INCREMENT;