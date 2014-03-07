#!/bin/sh

BASEDIR=$(dirname $0)

java -jar $BASEDIR/yuicompressor-2.4.8.jar --type css $BASEDIR/../public/css/styles.css -o $BASEDIR/../public/css/styles-min.css

java -jar $BASEDIR/yuicompressor-2.4.8.jar --type js $BASEDIR/../public/js/scripts.js -o $BASEDIR/../public/js/scripts-min.js
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type js $BASEDIR/../public/js/jquery.cookie.js -o $BASEDIR/../public/js/jquery.cookie-min.js
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type js $BASEDIR/../public/js/jquery.dataTables.js -o $BASEDIR/../public/js/jquery.dataTables-min.js
java -jar $BASEDIR/yuicompressor-2.4.8.jar --type js $BASEDIR/../public/js/jquery.ui.datepicker-de.js -o $BASEDIR/../public/js/jquery.ui.datepicker-de-min.js
