# Installation

Welcome to the official [Subrion CMS](https://subrion.org/) installation manual. Subrion CMS is easy to install and simple to manage.  
Use it as a stand-alone application or in conjunction with other applications to create entry level sites, mid-sized, or large sites. You can be confident that you will be able to invest in this system and continue to grow it to any possible level.

### Overview
This installation manual was created to help you install Subrion CMS on your web-server. The whole installation process can be divided into the following steps:

* Server/Client requirements
* Download the script
* Setup a MySQL database
* Upload the script to your server
* Make some pre-installation adjustments
* Run installation script

### Requirements

#### Server Requirements
Subrion CMS requires your web server to meet the following minimum system requirements:

* Linux/FreeBSD/Windows OS server platform
* Apache 2.0+ / nginx
* MySQL 5.1+ / MariaDB 10.x+
* PHP 5.6+ (GD lib, XML lib, FreeType installed)

LAMP is a recommended server configuration. Read more about server requirements and recommended hosting companies.

#### Client requirements
Subrion CMS has user-friendly web interface so software requirement is pretty simple - you just need a modern browser.

### Softaculous & Installatron
We are proud to be confirmed as an official application for [Softaculous Auto Install Tools](https://www.softaculous.com/apps/cms/Subrion). If your server supports Softaculous tools you can install the latest free version by one click. You can find our software among their applications. You only need to login to your cPanel and click Softaculous icon and follow the instructions. If you have any questions please contact us and we will install the script for you.

Subrion CMS can be also installed using [Installatron](http://installatron.com/subrion). Make sure it offers the latest version.

### Download
You can download the script from [Subrion website](https://subrion.org/download/). The latest version can be downloaded using [this link](https://tools.subrion.org/get/latest.zip)

Development version available here: https://github.com/intelliants/subrion/

### Database Setup
If you already have a database created (i.e. the one your site already makes use of) and you don't want to setup the software into separate database you can skip this step and proceed to script upload. Otherwise, keep up reading to learn how to create a new database on your server.

Basically, there is one of three ways to create a new database on your server:

* via cPanel
* via phpMyAdmin
* via SSH

If you are not sure what cPanel, phpMyAdmin, or SSH is, please contact your hosting company. They will give you at least one of those accounts. Below is the detailed description of each method described.

#### Method 1: cPanel
Creating new database using cPanel (your hosting account control panel) is the easiest and the fastest way and is considered the default way. If you are not experienced in installing scripts, please stick to this method.

1. Go to MySQL Databases section.
2. Enter database name and click Add Db button. This will create new database.
3. Enter username and password and click Add User button. This will create new user. Please remember these credentials since you will need them later when running installation script.
4. Add newly created user to the newly created database with ALL privileges.

#### Method 2: phpMyAdmin
phpMyAdmin is another way to create new database. But you must have enough correct permission to do that in phpMyAdmin.

1. Open phpMyAdmin in your browser. If you're not sure what the URL is, please contact your hosting company
2. Enter database name into Create new database field and click Create button. This will create new databas
3. Select newly created database and go to SQL section. Run these queries:

```sql
GRANT ALL PRIVILEGES ON database_name.* TO database_username@localhost IDENTIFIED BY 'password' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

!> **IMPORTANT** Don't forget to replace database_name, database_username, and password with actual database name, username, and password when running this query.

#### Method 3: SSH
The last alternative method is via your SSH account. This is the most sophisticated method.

Connect to your server via SSH.
Run the command below to enter MySQL environment:

> mysql -u username -p

Replace username with actual database username. You will be prompted to enter password.
Run the command below to create new database:

> CREATE DATABASE database_name;

Replace database_name with actual database name.
Run the command below to grant privileges to the user:

> GRANT ALL PRIVILEGES ON database_name.* TO database_username@localhost IDENTIFIED BY 'password' WITH GRANT OPTION;

!> **IMPORTANT** Don't forget to replace database_name, database_username, and password with actual database name, username, and password when running this query.

Run the command below:
> FLUSH PRIVILEGES;

By following the instructions of one of the methods above you should now have a new database created on your server.

### Script Upload
To upload the script to your server you need an FTP client program. FTP stands for File Transfer Protocol, and FTP client is a program that allows transferring remote files via this protocol. If you want to learn more about FTP, please search Google for it.

Connect to your server via FTP and upload the script.

### Permission Adjustments
For the installation script to run successfully you need to set some file permissions. By setting permissions you actually tell the server to allow the script make some actions with some files.

To start changing permissions do the following:

Connect to the server with either FTP or SSH client program.
Go to the directory where you uploaded the script.
Run the command below to make backup directory writable:

> chmod 777 backup/

Run the command below to make temporary directory writable:

> chmod 777 tmp/

Run the command below to make uploads directory writable:

> chmod 777 uploads/

### Setup Wizard
To install Subrion CMS you have to run installation file. In the example below we will use mysite.com as an example domain name. Whenever you see this name you have to substitute it with your actual domain name. We will also use subrion/ as the script root directory. If your directory differs from this one you also have to change it to your actual directory.

To start installation, run the installation script by typing the following URL in your browser: http://www.mysite.com/subrion/ (remember to substitute mysite.com with your actual domain name and subrion/ with your directory name).

Subrion installation process is divided into three simple steps. They are: Pre-installation check, License, Configuration. Let's review these steps in details.

a. Pre-installation check
This step checks your server configuration and explains if your server suits all the requirements for running the software. Besides, you can download Server Requirements Checker from Subrion CMS downloads area. Just run it on your server and you will understand if you can go on using the Subrion software.

Pre-installation check is divided into several groups:

* Server Configuration
* Recommended Settings
* Directory & File Permissions

Let's review all these groups in details.

*Server Configuration*
Name    Usage
MySQL Version   MySQL version that is used on your server. Subrion CMS requires MySQL 4.0 or above.
PHP Version Version of PHP on your server. It should be greater than 5.0
XML Support XML lib is used to parse RSS feeds.
MySQL Support   This module is used to connect to MySQL database engine from PHP scripts.
GD Extension    This extension is used to generate captcha and for images and banners resizing. We highly recommend installing it in case you do not have it.
Mbstring extension  Used for UTF8 strings operations.

*Recommended Settings*
We highly recommend setting correct values for all settings here. Anyhow, the script will still operate in case you have some of them configured in a different way. Safe Mode, Allow URL Fopen must match required configuration.

*Directory & File Permissions*
Set correct permissions for all your directories and configuration file. Installation process can not be completed until you set writable permissions for tmp/directory.

b. Subrion CMS License
Please read Subrion CMS License Agreement. By clicking Next button you confirm you agree with the terms of use mentioned in the license agreement.

c. Configuration
This step configures your script. It's divided into several groups: Database configuration, Common Configuration, Administrator configuration.

Database Configuration
Here you should input correct database details. Also you can configure your Subrion CMS tables prefix.

Common Configuration
No need to change any values here. They are generated automatically.

Administrator Configuration
Please set your admin panel username and password. Later you can change all these values in admin panel. Also set your email. It will be assigned to default admin account that is created during installation.

After you fill in the forms click Next button. Your script should be successfully installed.

!> **IMPORTANT** You MUST remove install/modules/module.install.php file after installation.


### Support

If you have any problems during installation process you can [request a free installation](https://subrion.org/desk/index.php?/Tickets/Submit). If you have any feature requests, tips how to improve our software don't hesitate to contact us via [issue tracker](https://github.com/intelliants/subrion/issues). You can also reach us via the following email addresses:

* Technical Department - support@subrion.org

We do try to reply within 24 hours. Thanks for your patience!