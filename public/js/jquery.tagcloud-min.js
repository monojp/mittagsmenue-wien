/*!
 * jquery.tagcloud.js
 * A Simple Tag Cloud Plugin for JQuery
 *
 * https://github.com/addywaddy/jquery.tagcloud.js
 * created by Adam Groves
 */
(function(f){var d=function(h,g){return h-g};var a=function(h){if(h.length===4){h=h.replace(/(\w)(\w)(\w)/gi,"$1$1$2$2$3$3")}var g=/(\w{2})(\w{2})(\w{2})/.exec(h);return[parseInt(g[1],16),parseInt(g[2],16),parseInt(g[3],16)]};var c=function(g){return"#"+jQuery.map(g,function(h){var j=h.toString(16);j=(j.length===1)?"0"+j:j;return j}).join("")};var b=function(h,g){return jQuery.map(a(h.end),function(k,j){return(k-a(h.start)[j])/g})};var e=function(h,g,j){var i=jQuery.map(a(h.start),function(m,k){var l=Math.round(m+(g[k]*j));if(l>255){l=255}else{if(l<0){l=0}}return l});return c(i)};f.fn.tagcloud=function(k){var l=f.extend({},f.fn.tagcloud.defaults,k);var j=this.map(function(){return f(this).attr("rel")});j=jQuery.makeArray(j).sort(d);var g=j[0];var n=j.pop();var i=n-g;if(i===0){i=1}var h,m;if(l.size){h=(l.size.end-l.size.start)/i}if(l.color){m=b(l.color,i)}return this.each(function(){var o=f(this).attr("rel")-g;if(l.size){f(this).css({"font-size":l.size.start+(o*h)+l.size.unit})}if(l.color){f(this).css({color:e(l.color,m,o)})}})};f.fn.tagcloud.defaults={size:{start:14,end:18,unit:"pt"}}})(jQuery);