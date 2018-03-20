<?php
function compare_by_v($a, $b) {
	if ($a['v'] == $b['v']) { return 0; }
	return ($a['v'] < $b['v']) ? -1 : 1;
}

class Caprigenus extends KokenPlugin {
	function __construct() {
		$this->database_fields = array(
			'content' => array(
				'caprigenus_background_color' => array('type' => 'VARCHAR', 'constraint' => 11),
				'caprigenus_foreground_color' => array('type' => 'VARCHAR', 'constraint' => 11)
			)
		);
		$this->register_filter('api.content.create', 'content_update');
		$this->register_filter('api.content.update', 'content_update');
		$this->register_filter('api.content.update_with_upload', 'content_update');
	}

	function album_update($album) {
		$album['caprigenus_background_color'] = 'red';
		return $album;
	}

	function content_update($content) {
		$color = $this->autocolor($content['file']);
		$content['caprigenus_foreground_color'] = $color['fg'];
		$content['caprigenus_background_color'] = $color['bg'];

		return $content;
	}
	
	private function closest($a, $v) {
		for ($i = 0; $i <= sizeof($a); $i++) {
			if ($a[$i]['v'] >= $v) return $i;
		}
		return sizeof($a) - 1;
	}

	private function autocolor($f) {
		$s = @imagecreatefromjpeg($f);
		$color = array();
	
		// create a image with maximum a dimension of 31px
		$ix = imagesx($s);
		$iy = imagesy($s);
		if ($ix > $iy) {
			$w = 31;
			$h = round((31 / $ix) * $iy);
		} else {
			$w = round((31 / $iy) * $ix);
			$h = 31;
		}
		$ys = round($h * 0.67);
		$xe = round($w * 0.33);

		$b = array();
		$c = array();
		$l = array();
		// Get colors for bottom side of image
		$i = imagecreatetruecolor($w, $h);
		// copy resized original image into the small version
		imagecopyresampled($i, $s, 0, 0, 0, 0, $w, $h, $ix, $iy);
		$n = 0; $nmax = 0;
		for($y = $ys; $y < $h; $y++) {
			for($x = 0; $x < $w; $x++) {
				// find the rgb value and save it into an array
				$rgb = imagecolorsforindex($i, imagecolorat($i, $x, $y));
				// calculate the "visual value" of the current color for later sorting
				$v = $rgb['red'] * 0.30 + $rgb['green'] * 0.59 + $rgb['blue'] * 0.11;
				// prefilter colors with values below 10 and above 240
				if ($v > 10 && $v < 240) {
					$c[$n]['r'] = $rgb['red']; $c[$n]['g'] = $rgb['green']; $c[$n]['b'] = $rgb['blue']; $c[$n]['v'] = $v;
					$n++;
				} else {
					// safe values if picture is too light or too dark
					$cmax[$nmax]['r'] = $rgb['red']; $cmax[$nmax]['g'] = $rgb['green']; $cmax[$nmax]['b'] = $rgb['blue']; $cmax[$nmax]['v'] = $v;
					$nmax++;
				}
			}
		}
		imagedestroy($i);
		// If image was too light or too dark < 10 or > 240
		if (sizeof($c) == 0) {
			$c = $cmax;
		}
		// Find color with the biggest area
		$b = array();
		for ($v = 0; $v < sizeof($c); $v++) {
			$j = round($c[$v]['v'] / 4);
			if(isset($b[$j])) {
				$b[$j] = array('c' => $j, 'v' => $b[$j]['v'] + 1);
			} else {
				$b[$j] = array('c' => $j, 'v' => 1);
			}
		}
		usort($b, "compare_by_v");
		$l = array_pop($b);
		// sort palette by "visual value"
		usort($c, "compare_by_v");
		// get the color of the biggest area
		$r = array_splice($c, $this->closest($c, $l['c'] * 4), 1);
		// sort the array by choosing the color whith the farest distance from previous color
		for($i = 0; $i < count($c); $i++) {
			// prefer more redish colors for light color
			$c[$i]['v'] = sqrt(0.59 * pow(($c[$i]['r'] - $r[0]['r']), 2) + 0.30 * pow(($c[$i]['g'] - $r[0]['g']), 2) + 0.11 * pow(($c[$i]['b'] - $r[0]['b']), 2));
		}
		usort($c, "compare_by_v");
		$r[1] = array_pop($c);

		// format rgb values
		$color['bg'] = ($r[0]) ? $r[0]['r'] . ',' . $r[0]['g'] . ',' . $r[0]['b'] : '255,0,0';
		$color['fg'] = ($r[1]) ? $r[1]['r'] . ',' . $r[1]['g'] . ',' . $r[1]['b'] : '255,255,0';

		imagedestroy($s);
		return $color;
	}
}
