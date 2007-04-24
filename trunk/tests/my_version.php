<?php
// Disable zlib compression, if present, for duration of this script.  
// So we don't double gzip 
ini_set("zlib.output_compression", "Off");

// Set the cache control header
// http 1.1 browsers MUST revalidate -- always
header("Cache-Control: must-revalidate");   

// convert request param 'q' to files list 
$files =  explode(',', $_GET['q']);
array_walk($files, 'path_trim');


echo '<pre>' . print_r($files, true);


function path_trim(&$val){
	// TODO: check what this function allow acces only to files we can show.
	
	// cut off anything wat looks like /../ folder
	$val = str_replace('../', '', trim($val, '\\/'));
	
	// check what file is with JS or CSS extension
	if(!preg_match('/\.(js|css)$/i',$val)){
		$val = '';
		return false;		
	}

	//add DOCUMENT_ROOT and return full path to a file
	$val = rtrim($_SERVER['DOCUMENT_ROOT'], '\\/') . '/' . $val;
	
	if(!is_readable($val) and !is_file($val)){
		$val = '';
		return false;
	}
}
?>