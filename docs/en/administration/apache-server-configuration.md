## Apache server configuration
These instructions are supplementary to the [Server Configuration](server-configuration.md) guideline.

#### PHP Requirements
To install all necessary libraries, run these commands in a terminal:
```
sudo apt-get update
sudo apt-get install apache2 libapache2-mod-php php-mysql php-json php-gd php-zip php-imap php-mbstring php-curl php-mailparse php-xml 
sudo phpenmod imap mbstring
sudo service apache2 restart
```
