<?php
header('Cache-Control: no-cache, private, must-revalidate');

// Make sure the user is authenticated at koken admin
if (!isset($_COOKIE['koken_session_ci'])) {
	// koken's cookie is not present
	exit;
}

function message($status, $message_text) {
	header('Content-type: application/json');
	echo json_encode(array('status' => $status, 'message' => $message_text));
	exit;
}

$post = filter_input_array(INPUT_POST,
	array(
		'action' => array(
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array(
				'regexp' => '/^([a-z]{1,10})$/'
			)
		),
		'slug' => array(
			'filter' => FILTER_SANITIZE_URL
		)
	)
);

if ($post['action'] == 'upload') {
	if ($post['slug']) {
		$file = '../../music/' . $post['slug'] . '.mp3';
		if (array_key_exists('userfile', $_FILES) && $_FILES['userfile']['error'] == 0 ) {
			if (substr($_FILES['userfile']['name'], -3, 3) == 'mp3') {
				if ($_FILES['userfile']['size'] <= 25165824) {	// Check if max. filesize is 24MB
					// Create directory if not exists
					if (!is_dir('../../music/'))
						mkdir('../../music/', 0750, true);
					if(move_uploaded_file($_FILES['userfile']['tmp_name'], $file)) {
						// Delete JSON cache files
						$path = __DIR__ . '/json/';
						if ($handle = opendir($path)) {
							while (false !== ($subdir = readdir($handle))) {
								if ($subdir != '.' && $subdir != '..') {
									$file = $path . $subdir . '/' . $post['slug'] . '.json.gz';
									if (file_exists($file))
										unlink ($file);
									$file = $path . $subdir . '/navigation.json.gz';
									if (file_exists($file))
										unlink ($file);
								}
							}
							closedir($handle);
						}
						// Delete all .1600.jpg files in ../originals/ directory to safe space
						foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../originals/')) as $filename) {
							if (strpos($filename, '.1600.jpg')) {
								unlink($filename);
							}
						}
						// Update index.html date
						touch('../../../../www/index.html');
						message(0, $_FILES['userfile']['name'] . ' has been stored as ' . $post['slug'] . '.mp3');
					} else {
						message(1, $_FILES['userfile']['name'] . ' could not been moved from temporary location');
					}
				} else {
					message(1, 'Filesize >32MB: ' . round($_FILES['userfile']['size'] / 1048576, 2) . 'MB');
				}
			} else {
				message(1, 'Only MP3 files are allowed');
			}
		} else {
			message(1, 'Common upload error');
		}
	} else {
		message(1, 'What album to assign the file?');
	}
} else {
// Render Frontend
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8" />
<style type="text/css">
	html,body,div,span,applet,object,iframe,h1,h2,h3,h4,h5,h6,p,blockquote,pre,a,abbr,acronym,address,big,cite,code,del,dfn,em,img,ins,kbd,q,s,samp,small,strike,strong,sub,sup,tt,var,b,u,i,center,dl,dt,dd,ol,ul,li,fieldset,form,label,legend,table,caption,tbody,tfoot,thead,tr,th,td,article,aside,canvas,details,embed,figure,figcaption,footer,header,hgroup,main-menu,nav,output,ruby,section,summary,time,mark,audio,video,form,input{margin:0;padding:0;border:0;font-size:100%;font:inherit;vertical-align:baseline;}article,aside,details,figcaption,figure,footer,header,hgroup,menu,nav,section{display:block;}body{line-height:1;}ol,ul{list-style:none;}

	body {
		background-color: #3c3c3b;
		font-family: 'Roboto Condensed', sans-serif;
		-webkit-font-smoothing: antialiased;
		text-rendering: optimizeLegibility;
	}

	.set {
		background-color: #a6a5a3;
		color: #3c3c3b;
		margin: 0.5em;
		padding: 1.5em;
	}

	.set-title {
		font-weight: 600;
		letter-spacing: 0.2em;
		text-transform: uppercase;
		margin-bottom: 1.5em;
	}

	.album {
		width: 100%;
		height: 3.5em;
		display: table;
	}

	.album:nth-child(odd) {
		background-color: #b4b3b1;
	}
	
	.album:nth-child(even) {
		background-color: #9b9a97;
	}

	.album.hover {
		background-color: #bcb78f;
	}

	.album-title, .message-container {
		display: table-cell;
		vertical-align: middle;
		padding: 0 1em;
	}

	.album-title {
		width: 33%;
		text-align: right;
	}

	.message-container {
		width: 67%;
	}

	.message {
		width: 0;
		line-height: 2em;
		text-indent: 2em;
		color: #a6a5a3;
		background-color: #3c3c3b;
	}

	.message.exists {
		width: 100%;
	}

	.message.failed {
		background-color: #602520;
	}
</style>
<link href="http://fonts.googleapis.com/css?family=Roboto+Condensed:400,600" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-1.8.3.min.js" type="text/javascript"></script>
<script type="text/javascript">
	// http://github.com/dancork/jquery.event.dragout
	(function(e){var t=e.event,n=t.special,r=n.dragout={current_elem:false,setup:function(t,n,i){e("body").on("dragover.dragout",r.update_elem)},teardown:function(t){e("body").off("dragover.dragout")},update_elem:function(t){if(t.target==r.current_elem)return;if(r.current_elem){e(r.current_elem).parents().andSelf().each(function(){if(e(this).find(t.target).size()==0)e(this).triggerHandler("dragout")})}r.current_elem=t.target;t.stopPropagation()}}})(window.jQuery);

	// http://www.github.com/weixiyen/jquery-filedrop
	(function(e){function a(e){t.drop(e);u=e.dataTransfer.files;if(u===null||u===undefined){t.error(r[0]);return false}o=u.length;c();e.preventDefault();return false}function f(n,r,i){var s="--",o="\r\n",u="";e.each(t.data,function(e,t){if(typeof t==="function")t=t();u+=s;u+=i;u+=o;u+='Content-Disposition: form-data; name="'+e+'"';u+=o;u+=o;u+=t;u+=o});u+=s;u+=i;u+=o;u+='Content-Disposition: form-data; name="'+t.paramname+'"';u+='; filename="'+n+'"';u+=o;u+="Content-Type: application/octet-stream";u+=o;u+=o;u+=r;u+=o;u+=s;u+=i;u+=s;u+=o;return u}function l(e){if(e.lengthComputable){var n=Math.round(e.loaded*100/e.total);if(this.currentProgress!=n){this.currentProgress=n;t.progressUpdated(this.index,this.file,this.currentProgress);var r=(new Date).getTime();var i=r-this.currentStart;if(i>=t.refresh){var s=e.loaded-this.startData;var o=s/i;t.speedUpdated(this.index,this.file,o);this.startData=e.loaded;this.currentStart=r}}}}function c(){function g(r){if(r.target.index==undefined){r.target.index=h(r.total)}var i=new XMLHttpRequest,a=i.upload,c=u[r.target.index],d=r.target.index,m=(new Date).getTime(),g="------multipartformboundary"+(new Date).getTime(),y;newName=p(c.name);if(typeof newName==="string"){y=f(newName,r.target.result,g)}else{y=f(c.name,r.target.result,g)}a.index=d;a.file=c;a.downloadStartTime=m;a.currentStart=m;a.currentProgress=0;a.startData=0;a.addEventListener("progress",l,false);i.open("POST",t.url,true);i.setRequestHeader("content-type","multipart/form-data; boundary="+g);i.sendAsBinary(y);t.uploadStarted(d,c,o);i.onload=function(){if(i.responseText){var r=(new Date).getTime(),u=r-m,a=t.uploadFinished(d,c,jQuery.parseJSON(i.responseText),u);e++;if(e==o-n){v()}if(a===false)s=true}}}s=false;if(!u){t.error(r[0]);return false}var e=0,n=0;if(o>t.maxfiles){t.error(r[1]);return false}for(var i=0;i<o;i++){if(s)return false;try{if(d(u[i])!=false){if(i===o)return;var a=new FileReader,c=1048576*t.maxfilesize;a.index=i;if(u[i].size>c){t.error(r[2],u[i],i);n++;continue}a.onloadend=g;a.readAsBinaryString(u[i])}else{n++}}catch(m){t.error(r[0]);return false}}}function h(e){for(var t=0;t<o;t++){if(u[t].size==e){return t}}return undefined}function p(e){return t.rename(e)}function d(e){return t.beforeEach(e)}function v(){return t.afterAll()}function m(e){clearTimeout(i);e.preventDefault();t.dragEnter(e)}function g(e){clearTimeout(i);e.preventDefault();t.docOver(e);t.dragOver(e)}function y(e){clearTimeout(i);t.dragLeave(e);e.stopPropagation()}function b(e){e.preventDefault();t.docLeave(e);return false}function w(e){clearTimeout(i);e.preventDefault();t.docEnter(e);return false}function E(e){clearTimeout(i);e.preventDefault();t.docOver(e);return false}function S(e){i=setTimeout(function(){t.docLeave(e)},200)}function x(){}jQuery.event.props.push("dataTransfer");var t={},n={url:"",refresh:1e3,paramname:"userfile",maxfiles:25,maxfilesize:1,data:{},drop:x,dragEnter:x,dragOver:x,dragLeave:x,docEnter:x,docOver:x,docLeave:x,beforeEach:x,afterAll:x,rename:x,error:function(e,t,n){alert(e)},uploadStarted:x,uploadFinished:x,progressUpdated:x,speedUpdated:x},r=["BrowserNotSupported","TooManyFiles","FileTooLarge"],i,s=false,o=0,u;e.fn.filedrop=function(r){t=e.extend({},n,r);this.bind("drop",a).bind("dragenter",m).bind("dragover",g).bind("dragleave",y);e(document).bind("drop",b).bind("dragenter",w).bind("dragover",E).bind("dragleave",S)};try{if(XMLHttpRequest.prototype.sendAsBinary)return;XMLHttpRequest.prototype.sendAsBinary=function(e){function t(e){return e.charCodeAt(0)&255}var n=Array.prototype.map.call(e,t);var r=new Uint8Array(n);this.send(r.buffer)}}catch(T){}})(jQuery);

	$(function() {
		var slug = '',
			albums = $('.album'),
			mouseon = function () {
				albums.on('dragover', function (e) {
					slug = $(this).attr('id');
					$(this).addClass('hover');
				});
				albums.on('dragout', function (e) {
					$(this).removeClass('hover');
				});
			},
			mouseoff = function () {
				$('.album').off();
			}

		mouseon();

		$('body').filedrop({
			paramname: 'userfile',
			maxfiles: 1,
			maxfilesize: 24,
			allowedfileextensions: ['.mp3'],
			allowedfiletypes: ['audio/mpeg', 'audio/x-mpeg', 'audio/mp3', 'audio/x-mp3', 'audio/mpeg3', 'audio/x-mpeg3', 'audio/mpg', 'audio/x-mpg', 'audio/x-mpegaudio'],
			url: '<?php echo $_SERVER['PHP_SELF'] ?>',
			data: {
				action: 'upload',
				slug: ''
			},
			beforeEach: function(f) {
				if (slug == '') {
					return false;
				} else {
					this.data.slug = slug;
					$('#' + slug).find('.message').html();
					mouseoff();
				}
			},
			uploadFinished:function(i, f, r) {
				var album = $('#' + slug),
					message = album.find('.message'),
					addClass = (r.status === 0) ? 'exists' : 'failed';
				album.removeClass('hover');
				message.html(r.message);
				message.addClass(addClass);
				mouseon();
			},
			progressUpdated: function (i, f, p) {
				var message = $('#' + slug).find('.message'),
					value = p + '%';
				message.html(value);
				message.width(value);
			}
		});
	});
</script>
</head>
<body>
<?php
	$in = json_decode(file_get_contents('http://' . $_SERVER['SERVER_NAME'] . '/api.php?/albums/tree/'));
	$PDO = new PDO('mysql:host=localhost;dbname=usr_web439_1;charset=utf8', 'web439', 'Ac3JTCPd');
	$koken_album = $PDO->prepare('SELECT slug FROM koken_albums WHERE id = ?');
	foreach ($in as $set) {
		?><div class="set"><div class="set-title"><?php echo utf8_decode($set->title); ?></div><?php
		foreach ($set->children as $album) {
			$koken_album->execute(array($album->id));
			$result = $koken_album->fetch(PDO::FETCH_ASSOC);
			$file_exists = (file_exists('../../music/' . $result['slug'] . '.mp3')) ? true : false;
			?><div id="<?php echo $result['slug']; ?>" class="album"><div class="album-title"><?php echo utf8_decode($album->title); ?></div><div class="message-container"><div class="message<?php echo ($file_exists) ? ' exists' : ''; ?>"><?php echo ($file_exists) ? $result['slug'] . '.mp3' : '&nbsp;'; ?></div></div></div><?php
		}
		?></div><?php
	}
?>
</body>
</html>
<?php
}
?>