#!/bin/sh

BASEDIR=$(dirname $0)
JSDIR=$BASEDIR/../public/js/
JSDIR=$(readlink -f $JSDIR)
CSSDIR=$BASEDIR/../public/css/
CSSDIR=$(readlink -f $CSSDIR)

# concat main css
cat $CSSDIR/basic.css > $CSSDIR/styles.css
cat $CSSDIR/black-tie/jquery-ui.min.css >> $CSSDIR/styles.css
# build minimized stylesheet
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type css $CSSDIR/styles.css -o $CSSDIR/styles-min.css

# concat main js
cat $JSDIR/jquery-1.11.1.min.js > $JSDIR/scripts.js
cat $JSDIR/jquery-ui.min.js >> $JSDIR/scripts.js
cat $JSDIR/basic.js >> $JSDIR/scripts.js
cat $JSDIR/jquery.cookie.js >> $JSDIR/scripts.js
cat $JSDIR/jquery.dataTables.js >> $JSDIR/scripts.js
cat $JSDIR/jquery.ui.datepicker-de.js >> $JSDIR/scripts.js
# build minimized script
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type js $JSDIR/scripts.js -o $JSDIR/scripts-min.js