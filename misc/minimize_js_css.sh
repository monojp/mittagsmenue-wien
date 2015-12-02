#!/bin/sh

BASEDIR=$(dirname $0)
JSDIR=$BASEDIR/../public/js/
JSDIR=$(readlink -f $JSDIR)
CSSDIR=$BASEDIR/../public/css/
CSSDIR=$(readlink -f $CSSDIR)

# strip vendor prefixes from css file
sed -i.bak s/-webkit-//g $CSSDIR/basic.css && rm "${CSSDIR}/basic.css.bak"
sed -i.bak s/-moz-//g $CSSDIR/basic.css && rm "${CSSDIR}/basic.css.bak"
sed -i.bak s/-o-//g $CSSDIR/basic.css && rm "${CSSDIR}/basic.css.bak"

# minimize css
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type css $CSSDIR/throbber.css -o $CSSDIR/throbber.min.css
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type css $CSSDIR/basic.css -o $CSSDIR/basic.min.css

# minimize js
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type js $JSDIR/head.load.js -o $JSDIR/head.load.min.js
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type js $JSDIR/basic.js -o $JSDIR/basic.min.js
