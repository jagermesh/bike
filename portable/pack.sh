#!/bin/sh

cd ..
zip -x \*config.db.php\* -x \*_logs\* -x \*packed-version\* -x \*.svn\* -x \*.git\* -x \*.DS_Store\* -x \*saved.json\* -x \*pack.sh\* -r install.zip ./* ./.htaccess
mv install.zip ./packed-version/