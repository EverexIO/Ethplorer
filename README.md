# Rebellious Explorer

Rebellious and ERC20 tokens viewer.

Easy to view tokens and custom data. Users first.

Support tokens developed with Ethereum's [ERC20 (before known as EIP20)](https://github.com/ethereum/EIPs/issues/20) standard.

Provides Widgets for websites.

[Online version at Rebellious.io](https://rebellious.io/explorer)


# Widgets for third-party websites

[Samples and instructions for widget usage](https://rebellious.io/explorer/widgets)


# Installation

Clone repository into separate webserver's directory.

```
cd /var/www
mkdir rebl_explorer
git clone https://github.com/RebelliousToken/RebelliousExplorer.git rebl_explorer
```

Make sure your web server supports .htaccess and mod_rewrite.


# Configure

Copy `service/config.sample.php` to `service/config.php` and specify service addresses.
