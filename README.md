# minimalist-php-proxy
Minimalist PHP Proxy

'Proxy Me .PHP'
Uses a PHP-enabled server as a proxy meant to display webpages/images

How it works:
1) Reads the specified url
2) change HTTP header if necessary for webpage/image/CSS/etc
3) iterate through the read URL file, replace links to point to proxyme.php (example below)
4) echo output, replaced links will be loaded normally by browser through proxyme.php links

Example link replacement:
https://a.com/b.css?1 -> http://192.168.1.91/x/proxyme.php?url=https://a.com/b.css?1
//a.com/c.jpg -> http://192.168.1.91/x/proxyme.php?url=https://a.com/c.jpg


Implementation note:
Would need to change all references to "http://192.168.1.91/x/proxyme.php"
in the proxyme.php file to its location on the new server
