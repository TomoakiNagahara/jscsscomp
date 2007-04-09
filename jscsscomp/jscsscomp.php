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

define('CACHE_DIR' , realpath('cache/'));
define('FILES_ENCODING' , 'UTF-8');

// get real path to the requested file
$in_file = realpath(rtrim($_SERVER['DOCUMENT_ROOT'], '\\/')
                    . '/' . ltrim($_GET['q'], '\\/'));

// requested file is wihin document root?
if(strrpos($in_file, realpath($_SERVER['DOCUMENT_ROOT'])) !== 0){
	// TODO: output correct code then file not in doc_root
	header('Not Found', true, 404);
	exit;
}

// requested file is real file and is readable?
if(!is_file($in_file) or !is_readable($in_file)){
	// TODO: output correct code then file is not exist or not readable
	header('Not Found', true, 404);
	exit;
}

$file_type = false;

// we process only files with 'js' or 'css' extensions
if(strtolower(substr($in_file, -3)) == '.js'){
	$file_type = 'js';
	$Content_type = 'text/javascript; charset: ' . FILES_ENCODING;
}elseif(strtolower(substr($in_file, -4)) == '.css'){
	$file_type = 'css';
	$Content_type = 'text/css; charset: ' . FILES_ENCODING;
}else{
	// TODO: output correct code then file extension is unknown
	header("HTTP/1.0 404 Not Found");
	exit;
}

// get file modification time and build L-M string for HTTP headers
$lmt = filemtime($in_file);
$lmt_str = gmdate('D, d M Y H:i:s', $lmt) . ' GMT';

// if file is not modified since last request send 304 HTTP header
if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) and $lmt_str == $_SERVER['HTTP_IF_MODIFIED_SINCE']){
	header('Not Modified', true, 304);
	header('Expires:');
	header('Cache-Control:');
	exit;
}

header('Content-type: ' . $Content_type);
header('Vary: Accept-Encoding');
header('Last-Modified: ' . $lmt_str);

$compress_file = false;

if (function_exists('ob_gzhandler') && ini_get('zlib.output_compression')) {
	$compress_file = false;
}elseif(!isset($_SERVER['HTTP_ACCEPT_ENCODING']) or strrpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false){
	$compress_file = false;
}else{
	$compress_file = true;
	$enc = in_array('x-gzip', explode(',', strtolower(str_replace(' ', '', $_SERVER['HTTP_ACCEPT_ENCODING'])))) ? "x-gzip" : "gzip";
}

if(!$compress_file){
	if($file_type == 'css'){
		echo file_get_contents($in_file);
		exit;
	}else{
		$cache_file = CACHE_DIR . '/' . md5($in_file) . '.' . $lmt;

		if(is_file($cache_file) and is_readable($cache_file)){
			echo file_get_contents($cache_file);
			exit;
		}
		
		include('class.JavaScriptPacker.php');
		$jsPacker = new JavaScriptPacker(file_get_contents($in_file));
 		$cacheData = $jsPacker->pack();
		
		$fp = @fopen($cache_file, "wb");
		if ($fp) {
			fwrite($fp, $cacheData);
			fclose($fp);
		}
		echo $cacheData;
		exit;
	}	
}else{
	$cache_file = CACHE_DIR . '/' . md5($in_file) . '.' . $lmt . '.gz';

	if(is_file($cache_file) and is_readable($cache_file)){
		header("Content-Encoding: " . $enc);
		echo file_get_contents($cache_file);
		exit;
	}
	
	$content = file_get_contents($in_file);
	
	if($file_type == 'js'){
		include('class.JavaScriptPacker.php');
		$jsPacker = new JavaScriptPacker($content, 0, false, false);
 		$content = $jsPacker->pack();
	}
	
	$cacheData = gzencode($content, 9, FORCE_GZIP);
	
	$fp = @fopen($cache_file, "wb");
	if ($fp) {
		fwrite($fp, $cacheData);
		fclose($fp);
	}
	
	header("Content-Encoding: " . $enc);
	echo $cacheData;
	exit;	
}
?>