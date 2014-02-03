var intervalVotes=false;var venueDivDisplay=null;var usedGeolocation=false;var oldVoteData=null;var voting_over_interval_multiplier=1;var venues_ajax_query=Array();function isMobileDevice(){return/Android|webOS|iPhone|iPad|iPod|BlackBerry|MIDP|Nokia|J2ME/i.test(navigator.userAgent)}$.extend({keys:function(c){var b=[];$.each(c,function(a){b.push(a)});return b}});function vote_helper(c,a,b){$.ajax({type:"POST",url:"vote.php",data:{action:c,identifier:a,note:b},dataType:"json",success:function(d){if(intervalVotes){clearInterval(intervalVotes)}if(typeof d.voting_over!="undefined"&&d.voting_over||!d||typeof d.alert!="undefined"){voting_over_interval_multiplier+=0.1}else{voting_over_interval_multiplier=1}intervalVotes=setInterval(function(){vote_get()},Math.floor(5000*voting_over_interval_multiplier));if(typeof JSON!="undefined"&&JSON.stringify(oldVoteData)==JSON.stringify(d)){return}if(typeof d.alert!="undefined"){alert(d.alert)}else{if(typeof d.html!="undefined"){$("#dialog_ajax_data").html(d.html);$("#dialog").show();$("#dialog").effect("highlight")}else{if(intervalVotes){$("#dialog").hide()}}}oldVoteData=d},error:function(){alert("Fehler beim Setzen des Votes.")}})}function vote_up(a){vote_helper("vote_up",a,null)}function vote_down(a){vote_helper("vote_down",a,null)}function vote_special(a){vote_helper("vote_special",a,null)}function vote_set_note(a){vote_helper("vote_set_note",null,a)}function vote_get(){vote_helper("vote_get",null)}function vote_delete(){vote_helper("vote_delete",null)}function positionHandler(a){lat=a.coords.latitude;lng=a.coords.longitude;$.ajax({type:"POST",url:"locator.php",data:{action:"latlngToAddress",lat:lat,lng:lng},dataType:"json",success:function(b){if(b&&typeof b.address!="undefined"&&b.address){usedGeolocation=true;$("#location").html(b.address);$("#locationInput").val(b.address);sortVenuesAfterPosition(lat,lng)}},error:function(){sortVenuesAfterPosition(lat,lng)}})}function positionErrorHandler(a){sortVenuesAfterPosition($("#lat").html(),$("#lng").html())}function distanceLatLng(g,m,f,k){g=parseFloat(g);m=parseFloat(m);f=parseFloat(f);k=parseFloat(k);var e=Math.PI/180;g*=e;m*=e;f*=e;k*=e;var d=6372.797;var h=f-g;var i=k-m;var l=Math.sin(h/2)*Math.sin(h/2)+Math.cos(g)*Math.cos(f)*Math.sin(i/2)*Math.sin(i/2);var j=2*Math.atan2(Math.sqrt(l),Math.sqrt(1-l));var b=d*j;return b}function sortVenuesAfterPosition(d,c){$("#lat").html(d);$("#lng").html(c);var a=new Array();$.each($('[class="venueDiv"]'),function(){var g=$(this).prop("id");var e=$(this).children(".lat").html();var f=$(this).children(".lng").html();a[g]=distanceLatLng(d,c,e,f)});var b=$('[class="venueDiv"]');b.sort(function(g,f){var e=$(g).prop("id");var j=$(f).prop("id");var i=a[e];var h=a[j];if(i>h){return 1}else{if(i<h){return -1}else{return 0}}});$("#venueContainer").html(b);$(document).trigger("locationReady")}function setLocation(a,b){if(!a||b){if((isMobileDevice()||b)&&navigator.geolocation){navigator.geolocation.getCurrentPosition(positionHandler,positionErrorHandler,{timeout:5000})}else{sortVenuesAfterPosition($("#lat").html(),$("#lng").html());usedGeolocation=false;$(document).trigger("locationReady")}$.removeCookie("location");return}usedGeolocation=false;$.ajax({type:"POST",url:"locator.php",data:{action:"addressToLatLong",address:a},dataType:"json",success:function(c){if(c&&typeof c.lat!="undefined"&&c.lat&&typeof c.lng!="undefined"&&c.lng){$("#location").html(a);$("#locationInput").val(a);sortVenuesAfterPosition(c.lat,c.lng);$.cookie("location",a,{expires:7})}else{$("#locationInput").val($("#location").html());alert("Keine Geo-Daten zu dieser Adresse gefunden.")}},error:function(){alert("Fehler beim Abrufen der Geo-Position. Bitte Internetverbindung überprüfen.")}})}function setLocationDialog(a){$("#setLocationDialog").dialog({modal:true,title:"Adresse festlegen",buttons:{Ok:function(){var b=$("#locationInput").val();setLocation(b,false);$("#setLocationDialog").dialog("close");$(this).dialog("close")},Abbrechen:function(){$(this).dialog("close")}},width:"380"});$(a).tooltip("close")}function setDistance(a){if(typeof a!="undefined"){$("#sliderDistance").slider("option","value",a);$("#distance").val(a);$.cookie("distance",a,{expires:7})}get_venues_distance()}function showLocation(d){var g=$("#lat").html()+","+$("#lng").html();var f="http://maps.googleapis.com/maps/api/staticmap?center="+g+"&zoom=15&language=de&size=400x300&sensor=false&markers=color:red|"+g;var a="ABCDEFGHIJKLMNOPQRSTUVWXYZ";var c=0;var b="";$.each($('[class="venueDiv"]:visible'),function(){var h=$(this).children(".lat").html();var i=$(this).children(".lng").html();var j=$(this).children('[class="title"]').children("a").html();f+="&markers=color:red|label:"+a[c]+"|"+h+","+i;if(c<5){b+=a[c]+": "+j+"<br />"}c++;if(c>=a.length){c=0}});var e='<img width="400" height="300" src="'+f+'"></img>';e+='<br /><div class="locationMapLegend" style="">'+b+"</div>";alert(e,$("#location").html(),false,425);$(d).tooltip("close")}function get_venues_distance(){var d=$("#lat").html();var b=$("#lng").html();var e=$("#distance").val();var a="ABCDEFGHIJKLMNOPQRSTUVWXYZ";var c=0;$.each($('[class="venueDiv"]'),function(){var h=$(this).children(".lat").html();var k=$(this).children(".lng").html();var j=$(this);var f="";var i=distanceLatLng(d,b,h,k);var g=Math.floor(Number((i).toFixed(2))*1000);if(i>=1){f="~ "+i.toFixed(1)+" km"}else{f="~ "+g+" m"}if(g>e){$(this).hide()}else{$(this).show()}j.children('[name="distance"]').remove();j.append("<div name='distance'>Distanz: "+f+"</div>")});if($('[class="venueDiv"]:visible').length<1){$("#noVenueFoundNotifier").show()}else{$("#noVenueFoundNotifier").hide()}}function setNoteDialog(){$("#setNoteDialog").dialog({modal:true,title:"Notiz erstellen",buttons:{Ok:function(){var a=$("#noteInput").val();vote_set_note(a);$("#setNoteDialog").dialog("close");$(this).dialog("close")}},width:"400"})}function handle_href_reference_details(c,a,b){$.ajax({type:"POST",url:"nearplaces.php",data:{action:"details",id:c,reference:a,sensor:isMobileDevice()},dataType:"json",async:false,success:function(d){if(typeof d.alert!="undefined"){alert(d.alert)}if(typeof d.result.website!="undefined"){window.open(d.result.website,"_blank")}else{window.open("https://www.google.com/#q="+b,"_blank")}},error:function(){alert("Fehler beim Abholen der Restaurants in der Nähe.")}})}function get_alt_venues(d,c,a,b,e){if(b.length>=10){return e(new Array())}$.ajax({type:"POST",url:"nearplaces.php",data:{action:"nearbysearch_full",lat:d,lng:c,radius:a,sensor:isMobileDevice()},dataType:"json",success:function(f){if(typeof f.alert!="undefined"){alert(f.alert)}else{return e(f)}},error:function(){alert("Fehler beim Abholen der Restaurants in der Nähe.")}})}function init_venues_alt(){var c=$("#lat").html();var a=$("#lng").html();$("#table_voting_alt").hide();$("#div_voting_alt_loader").show();var b=new Array();get_alt_venues(c,a,650,b,function(d){b=b.concat(d);get_alt_venues(c,a,$("#distance").val(),d,function(e){b=b.concat(e);data=new Array();$(b).each(function(i,j){var l=distanceLatLng(c,a,j.lat,j.lng);var h=Math.floor(Number((l).toFixed(2))*1000);var k="-";if(j.rating){k=j.rating}var g="<a href='"+j.maps_href+"' target='_blank'><span class='icon sprite sprite-icon_pin_map' title='Google Maps Route'></span></a>";if($("#show_voting").length){g+="<a href='javascript:void(0)' onclick='vote_up(\""+j.name+"\")'><span class='icon sprite sprite-icon_hand_pro' title='Vote Up'></span></a>						<a href='javascript:void(0)' onclick='vote_down(\""+j.name+"\")'><span class='icon sprite sprite-icon_hand_contra' title='Vote Down'></span></a>"}data[i]=new Array(j.href,h,k,g)});var f=$("#table_voting_alt").dataTable({aaData:data,bSort:true,bDestroy:true,aaSorting:[[1,"asc"],[2,"desc"]],bLengthChange:false,iDisplayLength:8,bInfo:false,aoColumns:[{sTitle:"Name"},{sTitle:"Distanz [m]",sClass:" center"},{sTitle:"Rating",sClass:" center"},{sTitle:"Aktionen",sClass:" center"}],oLanguage:{sZeroRecords:"Leider nichts gefunden :(",sSearch:"Filter:",oPaginate:{sPrevious:"Vorherige Seite",sNext:"Nächste Seite"}}});$("#div_voting_alt_loader").hide();$("#table_voting_alt").show();if(f.length>0){f.fnAdjustColumnSizing()}$("#setAlternativeVenuesDialog").dialog("option","position","center")})})}function setAlternativeVenuesDialog(){init_venues_alt();$("#setAlternativeVenuesDialog").dialog({modal:true,title:"Lokale in der Nähe",buttons:{"Schließen":function(){$(this).dialog("close")}},width:"750"})}function updateVoteSettingsDialog(){$("#vote_reminder").attr("disabled",$("#voted_mail_only").is(":checked"))}function setVoteSettingsDialog(){$("#setVoteSettingsDialog").dialog({modal:true,title:"Spezial-Votes & Einstellungen",buttons:{"Speichern / Schließen":function(){$.ajax({type:"POST",url:"emails.php",data:{action:"email_config_set",email:$("#email").val(),vote_reminder:$("#vote_reminder").is(":checked"),voted_mail_only:$("#voted_mail_only").is(":checked")},dataType:"json",success:function(a){if(typeof a.alert!="undefined"){alert(a.alert)}},error:function(){alert("Fehler beim Setzen der Vote-Einstellungen.")}});$(this).dialog("close")}},width:"440"})}$(document).ready(function(){$.widget("ui.tooltip",$.ui.tooltip,{options:{content:function(){return $(this).prop("title")}}});var a=false;$(document).on("locationReady",function(){a=true;head.ready("cookie",function(){var b=$.cookie("distance");if(typeof b=="undefined"){b=5000}$("#sliderDistance").slider({value:b,min:100,max:10000,step:100,slide:function(c,d){setDistance(d.value)}});$("#socialShare").show();$("#loadingContainer").hide();setDistance(b)});$('[name="lat_lng_link"]').each(function(c,d){var b=$(this).prop("href");b=b.replace("@@lat_lng@@",$("#lat").html()+","+$("#lng").html());$(this).prop("href",b)});$("a").tooltip();$("span").tooltip();$("div").tooltip();$("input").tooltip()});head.ready("cookie",function(){var b=$.cookie("location");if(typeof b!="undefined"&&b&&b.length){setLocation(b,false)}else{setLocation(null,false)}if($("#overlay_info_version").length){var d=$.trim($("#overlay_info_version").html());var c=$.trim($.cookie("overlay_info_version"));if(d!=c){$("#overlay_info").show("slide",{direction:"up"});setTimeout(function(){$.cookie("overlay_info_version",d,{expires:7});$("#overlay_info").hide("slide",{direction:"up"})},10000)}}});setTimeout(function(){if(!a){$(document).trigger("locationReady")}},10000);if($("#show_voting").length){vote_get()}$("#datePicker").datepicker({dateFormat:"yy-mm-dd",minDate:new Date(2012,10,30),maxDate:"1M",onSelect:function(c,b){document.location=window.location.protocol+"//"+window.location.host+window.location.pathname+"?date="+c}});$("#distance").on("input change",function(){setDistance($(this).val())});$("#dateHeader").click(function(b){$("#dateHeader").tooltip("close");$("#datePicker").datepicker("show");$("#datePicker").datepicker("setDate",$("#date_GET").val())});$("#locationForm").submit(function(c){var b=$("#locationInput").val();setLocation(b,false);$("#setLocationDialog").dialog("close");c.preventDefault()});$("#noteForm").submit(function(c){var b=$("#noteInput").val();vote_set_note(b);$("#setNoteDialog").dialog("close");c.preventDefault()});$("#setVoteSettingsDialog").change(function(){updateVoteSettingsDialog()});updateVoteSettingsDialog()});window.alert=function(c,a,d,b){if(typeof a=="undefined"){var a="Hinweis"}if(typeof d=="undefined"||d){c='<table><tr><td><span class="ui-icon ui-icon-alert" style="margin-right: 5px"></span></td><td>'+c+"</td></tr></table>"}if(typeof b=="undefined"){b=300}$(document.createElement("div")).attr({title:a,"class":"alert"}).html(c).dialog({title:a,resizable:false,modal:true,width:b,buttons:{OK:function(){$(this).dialog("close")}}})};