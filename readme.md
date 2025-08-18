#  Respect the robots.txt or be blocked

Contributors: hupe13    
Tags: robots.txt, bad crawlers, bad bots  
Tested up to: 6.8  
Stable tag: 250818     
Requires at least: 6.7     
Requires PHP: 8.1     
License: GPLv2 or later

Provide a robots.txt to forbid crawling and block the crawlers if they do it anyway.

## Description

Provide a robots.txt to forbid crawling and block the crawlers if they do it anyway.

## Updates

Please check regularly the Github repository or use [leafext-update-github](https://github.com/hupe13/leafext-update-github).

## Howto

* You need some knowledge about .htaccess.

* You have a robots.txt or create a robots.txt in every server root directory as usual.

* If no robots.txt is available, the plugin offers the content of the WordPress standard robots.txt file with [do_robots()](https://developer.wordpress.org/reference/functions/do_robots/):
````
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
````

* Activate the plugin, it creates a table in the WordPress database named `wp_badcrawler` (table name with your prefix). If you activate the plugin network wide, then the database is valid for all sites. If you have more than one domain, you can have a table for every domain.

* Check the settings in Settings -> Robots

* Here you can configure the plugin and see infos about the table(s).

* New in 250818: Now the plugin provides a simple editor to add and delete table entries.

* Create a test entry in `wp_badcrawler` with any (not existing) browser name, for example "blabla".

* Write in your .htaccess in every server root directory:
````
RewriteCond %{HTTP_USER_AGENT} !WordPress [NC]
RewriteRule ^robots.txt$ /robots-check/ [flags]
````

* If your installation is in a subdirectory, specify this as well.
````
RewriteCond %{HTTP_USER_AGENT} !WordPress [NC]
RewriteRule ^robots.txt$ /subdir/robots-check/ [flags]
````

* Please check, if you need some flags like `[R]` or `[R,L]` or other or nothing. Use for example `wget` to check it:
````
wget -v -O - https://your-domain.tld/robots-check/                    # valid robots.txt enables crawling
# or if your WordPress installation is in a subdirectory:
wget -v -O - https://your-domain.tld/subdir/robots-check/             # valid robots.txt enables crawling
````
````
wget -v -O - https://your-domain.tld/robots.txt                       # valid robots.txt enables crawling
wget -v -O - https://your-domain.tld/                                 # web site
````
````
wget -v -O - -U blabla https://your-domain.tld/robots-check/          # User-agent: *  Disallow: /
# or if your WordPress installation is in a subdirectory:
wget -v -O - -U blabla https://your-domain.tld/subdir/robots-check/   # User-agent: *  Disallow: /
````
````
wget -v -O - -U blabla https://your-domain.tld/robots.txt             # User-agent: *  Disallow: /
wget -v -O - -U blabla https://your-domain.tld/                       # Status code: 403 - Forbidden
````

* Create your entries in `wp_badcrawler` (table name with your prefix).

* You can use any unique substring of the bad crawlers (type `bot`) or any unique substring of the remote agent's domain name (type `name`).

* An example is given. Edit [BadCrawler.sql](https://github.com/hupe13/respect-robotstxt-or-block/blob/main/example/BadCrawler.sql) and import it in the database. But check it, if is valid for you.

* Please check your web servers log files, to get the bad crawlers strings and check that you are not blocking the good crawlers!

* Check the debug log too.

* It's a battle against windmills! Some crawlers are always there, others come and go.
