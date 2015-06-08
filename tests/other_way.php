<?php
   // Origin: http://www.hunlock.com/blogs/Supercharged_Javascript
   
   // This script has been placed in the public domain.
   // Use it and modify it however you wish.

   // Disable zlib compression, if present, for duration of this script.  
   // So we don't double gzip 
   ini_set("zlib.output_compression", "Off");

   //Set the content type header
   header("Content-Type: text/javascript; charset=UTF-8"); 

   // Set the cache control header
   // http 1.1 browsers MUST revalidate -- always
   header("Cache-Control: must-revalidate");     

   // Here we're going to extract the filename list.
   // We just split the original URL with "/" as the pivot
   $expl = explode("/",$HTTP_SERVER_VARS["REQUEST_URI"]);
   // Do a little trimming and url decoding to change %20 into spaces.
   $fileList = trim(urldecode($expl[count($expl)-1]));
   // And explode the remainder out with "," as the pivot to get our list.
   $orgFileNames = explode(",",$fileList);
   
   // $fileNames now is an array of the requested file names.

   // Go through each of the files and get its last modified time so we
   // can send a last-modified header so caching works properly
   $newestFile = 0;
   $ii=0;
   $longFilename = ''; // This is generated for the Hash
   $fileNames = Array();
   for ($i=0; ($i < count($orgFileNames)); $i++) {
      $orgFileNames[$i] = trim($orgFileNames[$i]);  // Get rid of whitespace
      if (preg_match('/\.js$/i',$orgFileNames[$i])) { // Allow only files ending in .js in the list.
         $fileNames[$ii++]=$orgFileNames[$i];         // Valid file name, so go ahead and use it.
         $longFilename .= $orgFileNames[$i];          // Build our LONG file name for the hash.
         $lastMod = @filemtime($orgFileNames[$i]);    // Get file last modified time
         if ($lastMod > $newestFile) {                // Is this the newest file?
            $newestFile = $lastMod;                   // Yup, so mark it.
         }
      } 
   }

/////////////////////////////////////////////////////////////////////////////
// Begin *BROWSER* Cache Control

   // Here we check to see if the browser is doing a cache check
   // First we'll do an etag check which is to see if we've already stored
   // the hash of the filename . '-' . $newestFile.  If we find it
   // nothing has changed so let the browser know and then die.  If we
   // don't find it (or it's a mismatch) something has changed so force
   // the browser to ignore the cache.

   $fileHash = md5($longFilename);       // This generates a key from the collective file names
   $hash = $fileHash . '-'.$newestFile;  // This appends the newest file date to the key.
   $headers = getallheaders();           // Get all the headers the browser sent us.
   if (ereg($hash, $headers['If-None-Match']))  {   // Look for a hash match
      // Our hash+filetime was matched with the browser etag value so nothing
      // has changed.  Just send the last modified date and a 304 (nothing changed) 
      // header and exit.
      header('Last-Modified: '.gmdate('D, d M Y H:i:s', $newestFile).' GMT', true, 304);
      die();
   }

   // We're still alive so save the hash+latest modified time in the e-tag.
   header("ETag: \"{$hash}\"");

   // For an additional layer of protection we'll see if the browser
   // sent us a last-modified date and compare that with $newestFile
   // If there's no change we'll send a cache control header and die.

   if (isset($headers['If-Modified-Since'])) {
      if ($newestFile <= strtotime($headers['If-Modified-Since'])) {
         // No change so send a 304 header and terminate
          header('Last-Modified: '.gmdate('D, d M Y H:i:s', $newestFile).' GMT', true, 304);
          die();
       }
   }

   // Set the last modified date as the date of the NEWEST file in the list.
   header('Last-Modified: '.gmdate('D, d M Y H:i:s', $newestFile).' GMT');

// End *BROWSER* Cache Control
/////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////
// Begin File System Cache Control

   // Attempt to open a cache file for this set.  (This is the server file-system
   // cache, not the browser cache.  From here on out we're done with the browser
   // cache. 
   $fp = @fopen("cache/$fileHash.txt","r");
   if ($fp) {
      // A cache file exists but if contents have changed delete the file pointer
      // so we re-process the files like there was no cache
      if ($newestFile>@filemtime("cache/$fileHash.txt")) { fclose($fp); $fp=false;}
   }
   if (!$fp) {
      // No file pointer exists so we create the cache files for this set.
      // for each filename in $fileNames, put the contents into $buffer
      // with two blank lines between each file.
      $buffer='';
      for ($i=0; ($i < count($fileNames)); $i++) {
         $buffer .= @file_get_contents($fileNames[$i]) . "\n\n";
      }
      // We've created our concatenated file so first we'll save it as
      // plain text for non gzip enabled browsers.
      $fp = @fopen("cache/$fileHash.txt","w");
      @fwrite($fp,$buffer);
      @fclose($fp);
      // Now we'll compress the file (maximum compression) and save
      // the compressed version.
      $fp = @fopen("cache/$fileHash.gz","w");
      $buffer = gzencode($buffer, 9, FORCE_GZIP);
      @fwrite($fp,$buffer);
      @fclose($fp);
   }

// End File System Cache Control
/////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////
// Begin Output 

   if (strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
      // Browser can handle gzip data so send it the gzip version.
      header ("Content-Encoding: gzip");
      header ("Content-Length: " . filesize("cache/$fileHash.gz"));
      readfile("cache/$fileHash.gz");
   } else {
      // Browser can't handle gzip so send it plain text version.
      header ("Content-Length: " . filesize("cache/$fileHash.txt"));
      readfile("cache/$fileHash.txt");
   }

// End Output -- End Program
/////////////////////////////////////////////////////////////////////////////

?>