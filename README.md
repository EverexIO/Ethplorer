[![Gitpod ready-to-code](https://img.shields.io/badge/Gitpod-ready--to--code-blue?logo=gitpod)](https://gitpod.io/#https://github.com/amilabs/Ethplorer)

# Ethplorer
Ethereum tokens (based on EIP20 standard) and transactions viewer

Mainnet and testnet are supported.

[Online version at Ethplorer.io](https://ethplorer.io)

# Installation

Clone repository into separate webserver's directory.
```
cd /var/www
mkdir ethplorer
git clone https://github.com/amilabs/ethplorer.git ethplorer
```

Make sure your web server supports .htaccess and mod_rewrite.

# Configure

Copy cfg/config.ethplorer.sample.js to cfg/config.ethplorer.js and specify service addresses.

