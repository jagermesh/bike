# Creating app

To start new app just put following line into index.php

```
require_once('/breeze/Breeze.php');
```

# Request br()->request()

```
br()->request()->isGET();

br()->request()->isSelfReferer();
br()->request()->referer();
br()->request()->isAt('/index.html');
br()->request()->path();
br()->request()->clientIP();
br()->request()->url();
br()->request()->relativeUrl();
br()->request()->domain();
br()->request()->isLocalHost();
br()->request()->host();
br()->request()->method();
br()->request()->isMethod('GET');
br()->request()->isGET();
br()->request()->isPOST();
br()->request()->isPUT();
br()->request()->isDELETE();
br()->request()->isRedirect();
br()->request()->userAgent();
br()->request()->isMobile();
br()->request()->get('name');
br()->request()->post('name');
br()->request()->put('name');
br()->request()->isFilesUploaded();
```

# Routing br()->request()->route*()

```
br()
  ->request()
    ->route('/index.html', function() {

    })
    ->route('/index.html?page=(.+)', function($matches) {
      // you can work with  $matches[1]
    })
    ->routePOST('/index.html', function() {
      // POST
    })
    ->routePUT('/index.html', function() {
      // POST
    })
    ->routeDELETE('/index.html', function() {
      // POST
    })
;
```

# Response br()->response()

```
br()->response()->sendJSON($data);
br()->response()->sendJSONP($data);
br()->response()->redirect($url);
br()->response()->send404();
br()->response()->sendNotAuthorized();
br()->response()->sendForbidden();
br()->response()->sendNotModified();
```

# Session br()->session()

```
br()->session()->set($name, $value);
$value = br()->session()->get($name, $default);
```

# Cache br()->cache()

```
br()->cache()->set($name, $value);
$value = br()->cache()->get($name, $default);
```

# Logging br()->log()

```
br()->log()->writeln('Write something');

br()->log('The same as above');

// print callstack for current line code
br()->callstack();
```

# File System br()->fs()

```
br()->fs()->iterateDir('/var/log/', function($item) {
  br()->log($item->isDir());
  br()->log($item->name());
});

br()->fs()->iterateDir('/var/log/', '[.]log$', function($item) {
  br()->log($item->isDir());
  br()->log($item->name());
});

$s = br()->loadFromFile('/var/log/logfile.txt');

br()->saveToFile('/var/log/logfile.txt', $s);

if (br()->fs()->makeDir('/tmp/mydir/')) {

}
```
# Browser br()->browser() same as curl

```
br()->browser()->get('http://www.microsoft.com');

br()->browser()->post('http://www.microsoft.com/form.aspx', array('name' => 'value'));
```

# Config br()->config()

```
br()->config()->set('name', 'value');

br()->config()->get('name', 'default');
```

# Images br()->images()

```
br()->images()->isValid('/path/to/file.jpg');

br()->images()->generateThumbnail('/path/to/file.jpg', 120, 120);
```

# Profiler br()->profiler()

```
br()->profiler()->logStart('operationName');

br()->profiler()->logFinish('operationName');
```

# Different tools br()

```
$array = br()->removeEmptyKeys($array);
br()->isConsoleMode();
br()->getMicrotime();
br()->formatDuration();
br()->durationToTime();
br()->panic();
br()->fromJSON();
br()->toJSON();
br()->html2text();
br()->guid();
br()->encryptInt();
br()->decryptInt();
br()->getMemoryUsage();
br()->sendMail($email, $subject, $body);
```






