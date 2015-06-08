## Install ##

**note:** You need PHP5 (because of class.JavaScriptPacker.php)

1. get archive with jscsscomp

2. extract archive somethere on your disc

3. copy `jscsscomp` folder to the document root of your site

5. `jscsscomp/cache/` dir should be writable for your scripts (or caching will not work)

6. add this code to the .htaccess file in doc root
```
RewriteEngine on
	
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^(.*\.)(js|css)$ jscsscomp/jscsscomp.php?q=$1$2 [L,NC]
```
or simply copy one from the distribution

From this point all your JS and CSS files should be served compressed by gzip (if browser accepts gzip compression) or by [packer](http://dean.edwards.name/packer/) (only for JS files)

## One file trick ##
(this part is copied from [here](http://www.hunlock.com/blogs/Supercharged_Javascript))

If you use a lot of JS (or CSS) files

```
<!-- Namespace source file -->  
<script src = "yahoo.js" ></script> 
 
<!-- Dependency source files -->  
<script src = "dom.js" ></script> 
<script src = "event.js" ></script> 
<script src = "effects/dragdrop.js" ></script> 
 
<!-- Slider source file -->  
<script src = "slider.js" ></script>    
```

you can get all this files (compressed!) in one request:

```
<script src = "/jscsscomp/yahoo.js, dom.js, event.js, effects/dragdrop.js, slider.js"></script>
```

**note:** i guess you understand what mixing .css and .js files in one request is not a good idea... (;

## Doing without ModRewrite ##
If mod-rewrite is not installed, or you can't use it, write URLs in this way:

```
<script src = "/jscsscomp/jscsscomp.php?q=dom.js" ></script> 

<script src = "/jscsscomp/jscsscomp.php?q=yahoo.js, event.js, effects/dragdrop.js, slider.js"></script>
```
