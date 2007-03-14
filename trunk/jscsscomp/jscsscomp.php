<?php
/**
 * jscsscomp - JavaScript and CSS files compressor 
 * Copyright (C) 2007 Maxim Martynyuk
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,
 * USA.
 * 
 * @author Maxim Martynyuk <flashkot@mail.ru>
 * @version $Id$
 */
 
header("Content-type: text/javascript; charset: UTF-8");
header("Vary: Accept-Encoding");
error_reporting(0);

$filename =  preg_replace("/[^0-9a-z\-_]+/i", "", $_GET['q']);

switch($_GET['type']){
	case 'css':
		$ext = '.css';
		break;
	case 'js':
		$ext = '.js';
		break;
	default:
		$ext = '.txt';		
}

$in_file = $filename.$ext;

if(!is_file($in_file) or !is_readable($in_file)){
	die;
}

$lmt = filemtime($in_file);

if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) and (gmdate('D, d M Y H:i:s', $lmt) . ' GMT') == $_SERVER['HTTP_IF_MODIFIED_SINCE']){
		header('Not Modified',true,304);
		header('Expires:');
		header('Cache-Control:');
		exit;
}

header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lmt) . ' GMT');

$fd = fopen($in_file, 'rb');
$content = fread($fd, filesize($in_file));
fclose($fd);

$encodings = array();

if (isset($_SERVER['HTTP_ACCEPT_ENCODING']))
	$encodings = explode(',', strtolower(preg_replace("/\s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING'])));

// Check for gzip header or northon internet securities
if ((in_array('gzip', $encodings) || in_array('x-gzip', $encodings) || isset($_SERVER['---------------'])) && function_exists('ob_gzhandler') && !ini_get('zlib.output_compression')) {
	$enc = in_array('x-gzip', $encodings) ? "x-gzip" : "gzip";

	$cache_file = $filename.$ext.'.'.filemtime($in_file).'.gz';
	
	if(is_file($cache_file) or is_readable($cache_file)){
		$fd = fopen($cache_file, 'rb');
		$content = fread($fd, filesize($cache_file));
		fclose($fd);
		header("Content-Encoding: " . $enc);
		echo $content;
		exit;
	}

	define('JSMIN_AS_LIB', true); // prevents auto-run on include
	include('jsmin_lib.php');
	$jsMin = new JSMin($content, false);
	$content = $jsMin->minify();
	
/*	$content = preg_replace('/\/\*.+\*\//U', '', $content);
	$content = preg_replace('/\/\/.+$/m', '', $content);
	$content = preg_replace('/^[\s\n\r\t]+/m', '', $content);*/
	
	$cacheData = gzencode($content, 9, FORCE_GZIP);

	// Write to file if possible
	$fp = @fopen($cache_file, "wb");
	if ($fp) {
		fwrite($fp, $cacheData);
		fclose($fp);
	}

	// Output
	header("Content-Encoding: " . $enc);
	echo $cacheData;
	exit;
}

echo $content;
?>