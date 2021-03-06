<?php
	include('config.php');


	#
	# build a list of maps for us to construct
	#

	$map = array();

	$dh = opendir($pngs);
	while ($file = readdir($dh)){
		if ($file != '.' && $file != '..' && is_dir("$pngs/$file")){
			process_dir($pngs, $file, $map);
		}
	}
	closedir($dh);

	function process_dir($pngs, $dir, &$map){

		$files = array();
		$dh = opendir("$pngs/$dir");
		while ($file = readdir($dh)){

			if (preg_match('!^(.*?)_?(\d\d)_(\d\d)\.png$!', $file, $m)){

				$key = StrToLower($dir.'__'.$m[1]);

				$map[$key]["$m[2]-$m[3]"] = "$pngs/$dir/$file";
				$files[] = $file;
			}
		}
		closedir($dh);


		# make preview
		$out = "";
		foreach ($files as $file){
			$out .= "<img src=\"$file\" style=\"border: 1px solid red\" >\n";
		}

		$fh = fopen("$pngs/$dir/preview.htm", 'w');
		fwrite($fh, $out);
		fclose($fh);
	}


	#
	# now build the maps
	#

	foreach ($map as $name => $files){

		$num = count($files);

		if ($num < 3) continue;

		echo "$name ($num chunks) : ";

		$out = '<html><head></head><body><h1>'.$name.'</h1>';
		$out .= "<style>body { background-image: url('$bg_url); } td { font-size: 5px }</style>\n";

		$all_x = array();
		$all_y = array();

		foreach ($files as $k => $v){
			list($x, $y) = explode('-', $k);
			$all_x[] = intval($x);
			$all_y[] = intval($y);
		}

		$min_x = min($all_x);
		$max_x = max($all_x);
		$min_y = min($all_y);
		$max_y = max($all_y);

		$w = 1 + ($max_x - $min_x);
		$h = 1 + ($max_y - $min_y);

		$out .= "$min_x - $max_x, $min_y - $max_y<br />";

		$out .= "<table border=0 cellpadding=0 cellspacing=0>\n";
		$out16 = $out;

		for ($y=0; $y<$h; $y++){

			$out .= "<tr>\n";
			$out16 .= "<tr>\n";

			for ($x=0; $x<$w; $x++){

				if (preg_match('!/WMO!', $file)){

					$key = sprintf('%02d-%02d', $min_x+$x, $max_y-$y);
				}else{
					$key = sprintf('%02d-%02d', $min_x+$x, $min_y+$y);
				}

				$file = $files[$key];

				if ($file){
					$url = str_replace($pngs, $pngs_url, $file);
					$url16 = str_replace('.png', '--16.png', $url);

					$out .= "<td><img src=\"$url\" width=256 height=256 /></td>\n";
					$out16 .= "<td><img src=\"$url16\" width=16 height=16 /></td>\n";
				}else{
					$out .= '<td>.</td>';
					$out16 .= '<td>.</td>';
				}
			}

			$out .= "</tr>\n";
			$out16 .= "</tr>\n";
		}

		$out .= "</table>\n";
		$out16 .= "</table>\n";

		$out .= '</body></html>';
		$out16 .= '</body></html>';

		$fh = fopen("$maps/{$name}.htm", 'w');
		fwrite($fh, $out);
		fclose($fh);

		$fh = fopen("$maps/{$name}--16.htm", 'w');
		fwrite($fh, $out16);
		fclose($fh);

		echo "ok\n";
		continue;



		$pw = $w * 256;
		$ph = $h * 256;

		# start with a transparent base
		#echo shell_exec("convert -size {$pw}x{$ph} null: -matte -compose Clear -composite -compose Over $maps/{$name}.png");
		echo shell_exec("convert -size {$pw}x{$ph} xc:red $maps/{$name}.png");
		echo '.';

		$c = 0;

		foreach ($files as $k => $v){
			list($x, $y) = explode('-', $k);

			$px = (intval($x) - $min_x) * 258;
			$py = ($max_y - intval($y)) * 258;

			# invert?
			$py = (intval($y) - $min_y) * 258;

			echo shell_exec("composite -geometry +{$px}+{$py} $v $maps/{$name}.png $maps/{$name}.png");
			echo '.';

			$c++;
			#if ($c==15) break;
		}

		echo " done\n";
	}
