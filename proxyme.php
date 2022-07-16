<?php
$proxyOut = "";
$oneTest = false;
if(empty($_POST["url"])){
	if(!empty($_GET["url"])){
		$_POST["url"] = $_GET["url"];
	}else{
		echo "<b>Hello, " . $_SERVER['REMOTE_ADDR'] . "! Enter an address in the url to go there through the webserver PHP proxy</b><hr>";
		echo "<form action='proxyme.php' method='POST' autocomplete='off'><input style='width:400px;' placeholder='Your url here' value='https://google.com' name='url'><input type='submit' value='Go!'></form>";
	}
}
function ext($ext, $match){ // ext($ext, "jpg"), check the extension begins with string (allows '.css?1' to be considered '.css' for HTTP headers)
	$ml = strlen($match);
	if(strlen($ext) < $ml) return false;
	for($xint = 0; $xint < $ml;$xint++){
		if($match[$xint] != $ext[$xint]) return false;
	}
	return true;
}
if(!empty($_POST["url"])){
	$ext = "";
	$l = strlen($_POST["url"]);
	for($eint = $l-1, $limit = $l-11; $eint > -1 && $eint >= $limit;$eint--){
		if($_POST["url"][$eint] == '.'){
			$ext = substr($_POST["url"], $eint+1, $l - $eint+1);
			break;
		}
	}
	// gotta set header for images to show properly
	if(ext($ext, "jpg") || ext($ext, "png") || ext($ext, "jpeg") || ext($ext, "webp") || ext($ext, "gif") || ext($ext, "bmp") || ext($ext, "svg")){
		header("Content-type: image/$ext");
	}else if(ext($ext, "css")){
		header("Content-type: text/css");
	}else if(ext($ext, "js")){
		header("Content-type: text/js");
	}
	//echo "found url... getting " . $_POST["url"] . "<hr>";
	$proxyOut = @file_get_contents($_POST["url"]); // suppress fail warning with '@'
	// regex check for links (1 means has links, 0 means no matches)
	if(preg_match("/https?/", $proxyOut) == 1 || preg_match("/src=/i", $proxyOut) == 1 || preg_match("/href=/i", $proxyOut) == 1){
		//// has links (img/script/a)
		// now replace so links referenced are swapped with usable URLs
		$out = "";
		$tmp = "";
		$domain = ""; // current website proxied, prepended to links
		$domainPos = stripos($_POST["url"], "://");
		if($domainPos >= 0){
			// it's the last '/' after '://'... http://s.com/a/b -> http://s.com/a/
			for($zint = $l-1, $stop = $domainPos + 2;$zint > $stop;$zint--){
				if($_POST["url"][$zint] == "/"){
					$domain = substr($_POST["url"], 0, $zint+1);
					break;
				}
			}/*
			for($zint = $domainPos+3;$zint < $l;$zint++){
				if($_POST["url"][$zint] == "/"){
					$domain = substr($_POST["url"], 0, $zint+1);
					echo "po len " . $l . "<br>";
					echo ", 0 - " . ($zint+1) . "<br>";
					echo "domain: $domain, zint: $zint";
					break;
				}
			}*/
			if($domain == "") $domain = $_POST["url"] . "/"; // http://gelbooru.com -> http://gelbooru.com/
			//$subdir = stripos(substr($_POST["url"], $domainPos + 3, $l - $domainPos - 3), "/");
			/*if($subdir >= 0 && $subdir != $domainPos+2){
				$domain = substr($_POST["url"], 0, $domainPos + 3 + $subdir + 1);
			}else{
				$domain = $_POST["url"] . "/"; // http://google.com -> http://google.com/
			}*/
		}
		// replace 'src=x.js' with src='proxyme.php?url=website.com/x.js'
		// which enables a page's external dependencies to load in
		for($xint = 0, $len = strlen($proxyOut);$xint < $len;$xint++){
			$tmp .= $proxyOut[$xint];
			// tmp is now src=?
			if($proxyOut[$xint] == "=" && $len >= $xint+2 && ($proxyOut[$xint+1] == "'" || $proxyOut[$xint+1] == "\"")){
				// need to change so
				$tlen = strlen($tmp);
				if($tlen >= 6 && (strtolower(substr($tmp, $tlen - 4, 4)) == "src=") || (strtolower(substr($tmp, $tlen - 5, 5)) == "href=") || (strtolower(substr($tmp, $tlen - 7, 7)) == "action=")){
				//if(strtolower($tmp) == "src=" || strtolower($tmp) == "href=" || strtolower($tmp) == "action="){
					$delimiter = $proxyOut[$xint+1]; // surrounds string 
					$endDel = -1;
					for($yint = $xint+2;$yint < $len;$yint++){
						if($proxyOut[$yint] == $delimiter){
							$endDel = $yint;
							break;
						}
					}
					if($endDel >= 0){
						$link = substr($proxyOut, $xint+2, $endDel-$xint-2);
						$xint = $endDel;

						$tmp .= $delimiter;
						if(strlen($link) > 2 && $link[0] == "/" && $link[1] == "/"){ // '//' used in css instead of http(s):// to use current protocol; '//a.com/b.css' -> 'http://a.com/b.css'
							if($l > 4 && $_POST["url"][4] == "s") $link = "https:" . $link; // currently using HTTPS
							else $link = "http:" . $link; // HTTP
						}
						if(stripos($link, "ttp") == 1) $tmp .= "http://192.168.1.91/x/proxyme.php?url=" . $link;// link starts with http, is absolute, no need for domain prepend
						else $tmp .= "http://192.168.1.91/x/proxyme.php?url=" . $domain . $link; // payload replacement
						$tmp .= $delimiter;
						/*if(!$oneTest){
							echo "<p>used: " . $delimiter . $link . $delimiter . " to make $tmp');</p>";
							echo "<p>domain: $domain, domainPos: $domainPos, delimiter: $delimiter, endDel: $endDel, link: $link, post[url]: " . $_POST["url"] . ", tmp: $tmp');</p>";
							if(stripos($link, "ttp") == 1){
								echo "<b>(and link starts with [*]http)</b>";
							}else{
								echo "<b>NO http present in url</b>";
							}
							$oneTest = true;
						}*/
					}
				}
				$out .= $tmp;
				$tmp = "";
			}
		}
		if($tmp != "") $out .= $tmp;
		$proxyOut = $out;
	}
}
if($proxyOut != "") echo $proxyOut;