#!/bin/sh

BASEDIR=$(dirname $0)
JSDIR=$BASEDIR/../public/js/
JSDIR=$(readlink -f $JSDIR)
CSSDIR=$BASEDIR/../public/css/
CSSDIR=$(readlink -f $CSSDIR)

# concat css & js
sh $BASEDIR/concat_js_css.sh

# minimize css & js
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type css $CSSDIR/styles.css -o $CSSDIR/styles-min.css
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type js $JSDIR/scripts.js -o $JSDIR/scripts-min.js
