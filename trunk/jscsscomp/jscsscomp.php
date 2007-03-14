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

$in_file = realpath(rtrim($_SERVER['DOCUMENT_ROOT'], '\\/').'/'.ltrim($_SERVER['REQUEST_URI'], '\\/'));

if(strrpos($in_file, realpath($_SERVER['DOCUMENT_ROOT'])) !== 0){
	header("HTTP/1.0 404 Not Found");
	exit;
}

if(!is_file($in_file) or !is_readable($in_file)){
	header("HTTP/1.0 404 Not Found");
	exit;
}

$file_type = false;

if(strtolower(substr($in_file, -3)) == '.js'){
	$file_type = 'js';
	$Content_type = 'Content-type: text/javascript; charset: UTF-8';
}elseif(strtolower(substr($in_file, -4)) == '.css'){
	$file_type = 'css';
	$Content_type = 'Content-type: text/css; charset: UTF-8';
}else{
	header("HTTP/1.0 404 Not Found");
	exit;
}

$lmt = filemtime($in_file);

if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) and (gmdate('D, d M Y H:i:s', $lmt) . ' GMT') == $_SERVER['HTTP_IF_MODIFIED_SINCE']){
	header('Not Modified',true,304);
	header('Expires:');
	header('Cache-Control:');
	exit;
}

header($Content_type);
header('Vary: Accept-Encoding');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lmt) . ' GMT');

$compress_file = false;

if (function_exists('ob_gzhandler') && ini_get('zlib.output_compression')) {
	$compress_file = false;
}elseif(!isset($_SERVER['HTTP_ACCEPT_ENCODING']) or strrpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')){
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
			echo file_get_contents($in_file);
			exit;
		}
		
		define('JSMIN_AS_LIB', true); // prevents auto-run on include
		include('jsmin_lib.php');
		$jsMin = new JSMin(file_get_contents($in_file), false);
		$cacheData = $jsMin->minify();
		
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
		echo file_get_contents($in_file);
		exit;
	}
	
	$content = file_get_contents($in_file);
	
	if($file_type == 'js'){
		define('JSMIN_AS_LIB', true); // prevents auto-run on include
		include('jsmin_lib.php');
		$jsMin = new JSMin($content, false);
		$content = $jsMin->minify();
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