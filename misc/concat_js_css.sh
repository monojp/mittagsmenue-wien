#!/bin/sh

BASEDIR=$(dirname $0)
JSDIR=$BASEDIR/../public/js/
JSDIR=$(readlink -f $JSDIR)
CSSDIR=$BASEDIR/../public/css/
CSSDIR=$(readlink -f $CSSDIR)

# concat css
cat $CSSDIR/basic.css > $CSSDIR/styles.css
cat $CSSDIR/jquery-ui.min.css >> $CSSDIR/styles.css
cat $CSSDIR/jquery.dataTables.min.css >> $CSSDIR/styles.css

# concat js
cat $JSDIR/jquery-2.1.3.min.js > $JSDIR/scripts.js
cat $JSDIR/jquery-ui.min.js >> $JSDIR/scripts.js
cat $JSDIR/basic.js >> $JSDIR/scripts.js
cat $JSDIR/jquery.cookie.js >> $JSDIR/scripts.js
cat $JSDIR/jquery.dataTables.min.js >> $JSDIR/scripts.js
