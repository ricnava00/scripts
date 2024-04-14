#!/usr/bin/env php
<?php
$version=2;
$parallel=2;

require_once(__DIR__.'/parallelrunner.php');
$windows=stripos(php_uname(),"microsoft")!==false;
$options=[
	[
		'option'=>"profile",
		'varname'=>"profile",
		'desc'=>"Use configuration profile",
		'type'=>'filename',
		'default'=>NULL,
	],
	[
		'option'=>"format",
		'varname'=>"forceencoding",
		'desc'=>"Force specific encoding",
		'type'=>'encoding',
		'default'=>NULL,
	],
	[
		'option'=>"fake",
		'varname'=>"fake",
		'desc'=>"Don't convert files (except when changing format), just write convert version in metadata",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"maxframerate",
		'varname'=>"maxframerate",
		'desc'=>"Set maximum framerate",
		'type'=>'int',
		'default'=>45,
	],
	[
		'option'=>"minframerate",
		'varname'=>"minframerate",
		'desc'=>"Set minimum framerate",
		'type'=>'int',
		'default'=>4,
	],
	[
		'option'=>"vsync",
		'varname'=>"vsync",
		'desc'=>"Force vsync mode",
		'type'=>'vsync',
		'default'=>NULL,
	],
	[
		'option'=>"maxres",
		'varname'=>"maxres",
		'desc'=>"Set maximum resolution",
		'type'=>'int',
		'default'=>1080,
	],
	[
		'option'=>"minres",
		'varname'=>"minres",
		'desc'=>"Set minimum resolution",
		'type'=>'int',
		'default'=>240,
	],
	[
		'option'=>"cbr",
		'varname'=>"cbr",
		'desc'=>"Use CBR instead of CQ and VBR",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"quality",
		'varname'=>"quality",
		'desc'=>"Set CQ [0-51]",
		'type'=>'int',
		'default'=>38,
	],
	[
		'option'=>"refbr",
		'varname'=>"refbr",
		'desc'=>"Set reference bitrate (Kbps for 1080p30)",
		'type'=>'int',
		'default'=>-1,
	],
	[
		'option'=>"scalebr",
		'varname'=>"scalebr",
		'desc'=>"Set relative scaling over reference bitrate",
		'type'=>'float',
		'default'=>1,
	],
	[
		'option'=>"overridemax",
		'varname'=>"overridemax",
		'desc'=>"Max bitrate growth factor over source",
		'type'=>'float',
		'default'=>1,
	],
	[
		'option'=>"maxdepth",
		'varname'=>"maxdepth",
		'desc'=>"Maximum video scan depth (default unlimited)",
		'type'=>'int',
		'default'=>-1,
	],
	[
		'option'=>"mtime",
		'varname'=>"mtime",
		'desc'=>"Maximum or minimum video age in days (default unlimited)",
		'type'=>'signedint',
		'default'=>-1,
	],
	[
		'option'=>"mmin",
		'varname'=>"mmin",
		'desc'=>"Maximum or minimum video age in minutes (default unlimited)",
		'type'=>'signedint',
		'default'=>-1,
	],
	[
		'option'=>"includegifs",
		'varname'=>"includegifs",
		'desc'=>"Also convert gif files with at least --gifminfps fps",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"gifminfps",
		'varname'=>"gifminfps",
		'desc'=>"Minimum fps of gifs to convert",
		'type'=>'int',
		'default'=>10,
	],
	[
		'option'=>"gifloops",
		'varname'=>"gifloops",
		'desc'=>"Number of gif loops",
		'type'=>'int',
		'default'=>1,
	],
	[
		'option'=>"videoloops",
		'varname'=>"videoloops",
		'desc'=>"Number of video (excluding gif) loops",
		'type'=>'int',
		'default'=>1,
	],
	[
		'option'=>"fixtimestamps",
		'varname'=>"fixtimestamps",
		'desc'=>"Sets audio and video PTS to start at 0 (run only if output has clearly misaligned tracks)",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"checkaria",
		'varname'=>"checkaria",
		'desc'=>"Skip converting a video if the corresponding .aria2 file is found",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"checkupdating",
		'varname'=>"checkupdating",
		'desc'=>"Skip converting a video if it's being written",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"skip",
		'varname'=>"skip",
		'desc'=>"Skip files with acceptable bitrate",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"skiph264",
		'varname'=>"skiph264",
		'desc'=>"Skip files that would get converted to h264",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"noprompt",
		'varname'=>"noprompt",
		'desc'=>"Automatically start converting",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"deletecorrupted",
		'varname'=>"deletecorrupted",
		'desc'=>"Delete videos without video track",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"inplace",
		'varname'=>"inplace",
		'desc'=>"Replace input file with output if conversion is successful",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"overwrite",
		'varname'=>"overwrite",
		'desc'=>"Overwrite already existing output files",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"nonvdec",
		'varname'=>"nonvdec",
		'desc'=>"Use CPU for video decode",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"nogpu",
		'varname'=>"nogpu",
		'desc'=>"Use CPU for video decode and scale",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"skiphevc",
		'varname'=>"skiphevc",
		'desc'=>"Skip converting HEVC files",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"skipav1",
		'varname'=>"skipav1",
		'desc'=>"Skip converting AV1 files",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"reconvert",
		'varname'=>"reconvert",
		'desc'=>"Reconvert also already converted",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"upgrade",
		'varname'=>"upgrade",
		'desc'=>"Reconvert also already converted, if version is older",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"warn-framerate-change",
		'varname'=>"warnFramerate",
		'desc'=>"Warn if destination framerate is different from source",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"help",
		'varname'=>"trash",
		'desc'=>"Show this help",
		'type'=>'bool',
		'default'=>false,
	],
];

function scan($maxdepth,$mtime,$mmin,$includegifs,$gifminfps,$checkaria)
{
	global $manuallySet;
	#$all=array_filter(json_decode(shell_exec("bash newbitrates".($manuallySet['maxdepth']?" ".$maxdepth:NULL)),true));
	$exts=["mp4","m4v","wmv","mov","ts","mkv","webm","avi","mpg","3gp","flv","rmvb"];
	$all=array_filter(json_decode(shell_exec('(echo -n "[["; find '.($manuallySet['maxdepth']?"-maxdepth ".$maxdepth." ":NULL).($manuallySet['mtime']?"\\( -mtime ".$mtime." -or -ctime ".$mtime." \\) ":NULL).($manuallySet['mmin']?"-mmin ".$mmin." ":NULL).'-type f -not -path "./converted/*" \( -iname "*.'.implode('" -or -iname "*.',$exts).'" \) | parallel -j200% -X mediainfo --Output=JSON {} | perl -0777 -pe \'s/\}(\n\])?\n\n(\[\n)?\{/\},\n\{/g\' | perl -0 -pe \'s/\n+\Z//\'; echo "]]") | perl -0777 -pe \'s/\[{2,3}\n?\{/[{/\' | perl -0777 -pe \'s/\}\n?\]{2,3}/}]/\''),true));
	if($includegifs)
	{
		$fakejson=trim(shell_exec('find '.($manuallySet['maxdepth']?"-maxdepth ".$maxdepth." ":NULL).'-type f -iname "*.gif" -or -iname "*.apng" | parallel "ffprobe.exe -hide_banner -of json -show_entries stream=width,height,r_frame_rate,codec_name:format=duration,filename,bit_rate {}" 2>/dev/null | tr -d \'\r\''));
		$fakejson="[".preg_replace('~^}\n~m',"},",$fakejson)."]";
		$gifs=json_decode($fakejson,true);
		$gifs=array_map(fn($g)=>[
			'media' =>
			[
				'@ref' => $g['format']['filename'],
				'track' =>
				[
					[
						'@type' => 'General',
						'Duration' => $g['format']['duration']??10,
					],
					[
						'@type' => 'Video',
						'BitRate' => $g['format']['bit_rate']??1000,
						'Format' => $g['streams'][0]['codec_name'],
						'Width' => $g['streams'][0]['width'],
						'Height' => $g['streams'][0]['height'],
						'FrameRate' => eval('return '.$g['streams'][0]['r_frame_rate'].';'),
					],
				],
			],
		],$gifs);
		$wrong=array_filter($gifs,fn($g)=>$g['media']['track'][1]['FrameRate']<$gifminfps);
		if(!empty($wrong))
		{
			echo "The following gifs aren't at least ".$gifminfps."fps:\n".implode("\n",array_map(fn($g)=>$g['media']['@ref']." (".$g['media']['track'][1]['FrameRate'].")",$wrong))."\n";
			exit(1);
		}
		$gifs=array_filter($gifs,fn($g)=>$g['media']['track'][1]['FrameRate']>=$gifminfps);
		$all=array_merge($all,$gifs);
	}
	if($checkaria)
	{
		$arias=array_filter(explode("\n",trim(shell_exec('find '.($manuallySet['maxdepth']?"-maxdepth ".$maxdepth." ":NULL).'-type f -iname "*.aria2"'))));
		echo "Before: ".count($all);
		$all=array_values(array_filter($all,fn($f)=>!in_array($f['media']['@ref'].".aria2",$arias)));
		echo ", after: ".count($all).", arias: ".count($arias)."\n";
	}
	return $all;
}

function getChangedDurations($oldbitrates,$rawnewbitrates,$gifloops,$videoloops)
{
	global $deletecorrupted;
	$ret=[];
	foreach($rawnewbitrates as $file)
	{
		if(!isset($file['media']))
		{
			echo "Missing media key, exiting\n".var_export($file,true)."\n";
			exit(1);
		}
		$file=$file['media'];
		$general=current(array_keys(array_filter($file['track'],fn($t)=>$t['@type']=="General")));
		$video=current(array_keys(array_filter($file['track'],fn($t)=>$t['@type']=="Video")));
		if($video===false)
		{
			echo "\e[31mVideo track not found in ".$file['@ref']."\e[0m\n";
			if($deletecorrupted)
			{
				unlink($file['@ref']);
			}
			continue;
		}
		$flt=array_filter($oldbitrates,fn($f)=>pathinfo($f['filename'],PATHINFO_DIRNAME)."/".pathinfo($f['filename'],PATHINFO_FILENAME).".mp4"==$file['@ref']);
		$src=current($flt);
		if(!$src)
		{
			echo "\e[31mSource not found for ".$file['@ref']."\e[0m\n";
		}
		if($src['format']=='apng')
		{
			continue;
		}
		$d=$src['duration'];
		if($src['format']=='gif')
		{
			$d*=$gifloops;
		}
		else
		{
			$d*=$videoloops;
		}
		if(abs($d-$file['track'][$general]['Duration'])>=1)
		{
			$ret[]=['filename'=>$file['@ref'],'old'=>$d,'new'=>$file['track'][$general]['Duration']];
		}
	}
	return $ret;
}
function nextStep($curr)
{
	global $waiting;
	global $options;
	global $types;
	global $manuallySet;
	if(!isset($waiting['type']))
	{
		if(str_starts_with($curr,"-"))
		{
			$var=substr($curr,1);
			if(str_starts_with($var,"-")) $var=substr($var,1);
			if(array_key_exists($var,$options))
			{
				if($options[$var]['type']=="bool")
				{
					global ${$options[$var]['varname']};
					if(!isset(${$options[$var]['varname']}))
					{
						$manuallySet[$options[$var]['varname']]=true;
						${$options[$var]['varname']}=!$options[$var]['default'];
					}
				}
				else
				{
					$waiting=[
						'name'=>$options[$var]['option'],
						'var'=>$options[$var]['varname'],
						'type'=>$options[$var]['type'],
					];
				}
			}
			else
			{
				echo "Unknown parameter ".$var."\n";
				exit(1);
			}
		}
		else
		{
			echo "Unable to parse parameter ".$curr."\n";
			exit(1);
		}
	}
	else if(preg_match($types[$waiting['type']],$curr))
	{
		global ${$waiting['var']};
		if(!isset(${$waiting['var']}))
		{
			$manuallySet[$waiting['var']]=true;
			${$waiting['var']}=$curr;
		}
		$waiting=NULL;
	}
	else
	{
		echo "Wrong parameter for ".$waiting['name'].", expected ".$waiting['type']."\n";
		exit(1);
	}
}

function getTempFile($ext=NULL)
{
	$tries=0;
	do
	{
		$outfile=tempnam(sys_get_temp_dir(),"");
		if(!$ext)
		{
			return $outfile;
		}
		if(@link($outfile,$outfile.".".$ext))
		{
			return $outfile.".".$ext;
		}
		unlink($outfile);
		$tries++;
	} while($tries<500);
	return false;
}

$options=array_column($options,NULL,"option");
$manuallySet=array_map(fn()=>false,array_column($options,NULL,"varname"));
$types=
[
	'int'=>'~^\d+$~',
	'signedint'=>'~^[+-]?\d+$~',
	'float'=>'~^(\d+\.)?\d+$~',
	'encoding'=>'~^h264|hevc|av1$~',
	'vsync'=>'~^cfr|vfr$~',
	'filename'=>'~.~',
];
$typedescs=
[
	'int'=>"N",
	'signedint'=>"[+-]N",
	'float'=>"N.n",
	'encoding'=>"h264|hevc|av1",
	'vsync'=>"cfr|vfr",
	'filename'=>"filename",
];
if(in_array("--help",$argv))
{
	echo basename(__FILE__)." [options]\n\n";
	foreach($options as $o)
	{
		echo str_pad("--".$o['option'].(isset($typedescs[$o['type']])?" ".$typedescs[$o['type']]:NULL),24," ",STR_PAD_RIGHT).$o['desc'].(($o['type']!="bool"&&isset($o['default'])&&$o['default']!=-1)?" (default: ".$o['default'].")":NULL)."\n";
	}
	exit(1);
}
for($n=1;$n<count($argv);$n++)
{
	$curr=$argv[$n];
	nextStep($curr);
}
$f=isset($profile)?preg_replace('/\.conf$/s','',$profile).".conf":"convert.conf";
if(file_exists($f))
{
	foreach(explode("\n",trim(file_get_contents($f))) as $line)
	{
		if(substr($line,0,1)=="#"||empty($line)) continue;
		foreach(explode(" ",trim("--".$line)) as $curr)
		{
			nextStep($curr);
		}
		if(isset($waiting))
		{
			echo "Missing parameter for ".$waiting['name'].", expected ".$waiting['type']."\n";
			exit(1);
		}
	}
}
else if(isset($profile))
{
	echo "Cannot find ".$f."\n";
	exit(1);
}
foreach($options as $var)
{
	if(!isset(${$var['varname']}))
	{
		${$var['varname']}=$var['default'];
	}
}
if(isset($waiting))
{
	echo "Missing parameter for ".$waiting['name'].", expected ".$waiting['type']."\n";
	exit(1);
}
#echo implode("\n",array_map(function($o){global ${$o['varname']}; return $o['varname'].": ".var_export(${$o['varname']},true);},$options))."\n"; exit(1);
if($forceencoding=="h264"&&$skiph264)
{
	echo "--skiph264 cannot be used when forcing conversion to h264\n";
	exit(1);
}
if($nonvdec&&$nogpu)
{
	echo "--nonvdec and --nogpu cannot be used simultaneously, use only --nogpu since it also disables NVDEC\n";
	exit(1);
}
if($quality>51)
{
	echo "--quality must be in range 0-51\n";
	exit(1);
}
if($cbr)
{
	if($manuallySet['quality'])
	{
		echo "--quality cannot be used with --cbr\n";
		exit(1);
	}
}
else
{
	foreach(['refbr','scalebr','overridemax'] as $inc) #Warning: checking on variable name and printing as option name. Only makes sense if option and variable have same name
	{
		if($manuallySet[$inc])
		{
			echo "--".$inc." can only be used with --cbr\n";
			exit(1);
		}
	}
}
if($manuallySet['mtime']&&$manuallySet['mmin'])
{
	echo "mtime and mmin can only be set one at a time\n";
	exit(1);
}
if($fake)
{
	$parallel=16;
}
$refarea=1920*1080;
if(!$manuallySet['refbr'])
{
	$ref=['h264'=>4*1048576/$refarea/30,
	'hevc'=>2*1048576/$refarea/30,
	'av1'=>2*1048576/$refarea/30];
}
else
{
	$b=$refbr*1024/$refarea/30;
	$ref=['h264'=>$b,
	'hevc'=>$b,
	'av1'=>$b];
}
$ref=array_map(fn($b)=>$b*$scalebr,$ref);
$bitratescale=[
'avc'=>1,
'h264'=>1,
'vp6'=>1,
'vp8'=>1,
'vp9'=>1,
'vc-1'=>1,
'gif'=>1,
'apng'=>1,
'hevc'=>1,//3/4, //If restoring, should also set a minimum like if source is at 1/2 reference then don't set bitrate to 3/4*1/2 reference
'av1'=>1,
];
$maxframerate*=1.1;
if(file_exists("bitrates.txt"))
{
	if($checkupdating)
	{
		echo "checkupdating needs to rescan twice, it can't be used with a static scan\n";
		exit(1);
	}
	$rawbitrates=json_decode(file_get_contents("bitrates.txt"),true);
}
else
{
	$rawbitrates=scan($maxdepth,$mtime,$mmin,$includegifs,$gifminfps,$checkaria);
}
if(empty($rawbitrates))
{
	echo "No video files found\n";
	exit;
}
if(isset($rawbitrates['media']))
{
	$rawbitrates=[$rawbitrates];
}
foreach($rawbitrates as $file)
{
	if(!isset($file['media']))
	{
		echo "Missing media key, exiting\n".var_export($file,true)."\n";
		exit(1);
	}
	$file=$file['media'];
	$general=current(array_keys(array_filter($file['track'],fn($t)=>$t['@type']=="General")));
	$video=current(array_keys(array_filter($file['track'],fn($t)=>$t['@type']=="Video")));
	$audio=current(array_keys(array_filter($file['track'],fn($t)=>$t['@type']=="Audio")));
	if($video===false)
	{
		echo "\e[31mVideo track not found in ".$file['@ref']."\e[0m\n";
		if($deletecorrupted)
		{
			unlink($file['@ref']);
		}
		continue;
	}
	echo "\e[31m";
	$arr['filename']=$file['@ref'];
	if(isset($file['track'][$video]['BitRate']))
	{
		$arr['bitrate']=$file['track'][$video]['BitRate'];
	}
	else
	{
		$arr['bitrate']=$file['track'][$general]['OverallBitRate'];
		if(isset($file['track'][$audio]['BitRate']))
		{
			$arr['bitrate']-=$file['track'][$audio]['BitRate'];
		}
	}
	$m=$file['track'][$video];
	$arr['duration']=$file['track'][$general]['Duration'];
	$arr['comment']=isset($file['track'][$general]['Comment'])?$file['track'][$general]['Comment']:NULL;
	if(is_array($arr['comment']))
	{
		unset($arr['comment']);
	}
	$arr['rotation']=(intdiv($m['Rotation']??0,90)+4)%4;
	if($arr['rotation']%2==0)
	{
		$arr['width']=$m['Width'];
		$arr['height']=$m['Height'];
	}
	else
	{
		$arr['width']=$m['Height'];
		$arr['height']=$m['Width'];
	}
	$arr['framerate']=isset($m['FrameRate_Original'])?$m['FrameRate_Original']:$m['FrameRate']??NULL;
	$arr['format']=$m['Format'];
	$bitrates[]=$arr;
	echo "\e[0m";
}
usort($bitrates,fn($a,$b)=>strnatcasecmp($a['filename'],$b['filename']));
if($checkupdating)
{
	echo "Waiting 5 seconds and checking new durations\n";
	sleep(5);
	$rawbitrates=scan($maxdepth,$mtime,$mmin,$includegifs,$gifminfps,$checkaria);
	if(isset($rawbitrates['media']))
	{
		$rawbitrates=[$rawbitrates];
	}
	foreach(getChangedDurations($bitrates,$rawbitrates,$gifloops,$videoloops) as $f)
	{
		echo "\e[33mDuration changed for ".$f['filename'].", skipping\e[0m\n";
		$bitrates=array_filter($bitrates,fn($b)=>$b['filename']!=$f['filename']);
	}
}
$skipped=array();
$totalduration=0;
$do=['h264','hevc','av1','h264 (skipped)','change format','skipped','not skipped','already hevc','already av1','already existing','already converted (same version)','already converted (old version)'];
$do=array_combine($do,array_fill(0,count($do),0));
$outnames=[];
foreach($bitrates as $current)
{
	if(empty($current['framerate']))
	{
		$probe=shell_exec("ffprobe.exe -v error -select_streams v -of default=noprint_wrappers=1:nokey=1 -show_entries stream=r_frame_rate ".escapeshellarg($current['filename']));
		if(preg_match("~^(\d+)(?:/(\d+))?$~",$probe,$match))
		{
			$current['framerate']=$match[1];
			if(isset($match[2]))
			{
				$current['framerate']/=$match[2];
			}
			echo $current['filename'].": \e[33mmissing framerate, found alternative ".$current['framerate']."\e[0m\n";
		}
		else
		{
			$current['framerate']=30;
			$skipframerate=true;
			echo $current['filename'].": \e[31mmissing framerate, set to ".$current['framerate']."\e[0m\n";
		}
	}
	else
	{
		$skipframerate=false;
	}
	if(empty($current['bitrate']))
	{
		$current['bitrate']=PHP_INT_MAX;
		echo $current['filename'].": \e[31mmissing bitrate, using other variables\e[0m\n";
	}
	$oldFramerate=$current['framerate'];
	for(;$current['framerate']>$maxframerate;$current['framerate']/=2);
	for(;$current['framerate']<$minframerate;$current['framerate']*=2);
	if($warnFramerate&&$oldFramerate!=$current['framerate'])
	{
		echo $current['filename'].": framerate changed from ".$oldFramerate." to ".$current['framerate']."\n";
	}
	$w=$current['width'];
	$h=$current['height'];
	if(isset($forceencoding))
	{
		$encoding=$forceencoding;
	}
	else
	{
		$encoding="av1";#($current['format']=="HEVC"||min($h,$w)>=720)?"hevc":"h264";
	}
	$maxbr=$ref[$encoding]*$w*$h*$current['framerate'];
	$changeformat=pathinfo($current['filename'],PATHINFO_EXTENSION)!="mp4";
	if($current['bitrate']<=$maxbr&&!$changeformat)
	{
		$skipped[]=array_merge($current,array('reason'=>"Ok bitrate (max ".floor($maxbr/1024)."Kbps)"));
		if($skip)
		{
			continue;
		}
	}
	if($h<$w)
	{
		$desth=max(min($h,$maxres),$minres);
		$desth=ceil($desth/2)*2;
		$scale=$desth/$h;
		$refscale=sqrt($w*$scale*$desth/$refarea);
		$destw=-2;
		$destbr=$ref[$encoding]*$w*$scale*$desth*$current['framerate'];
	}
	else
	{
		$destw=max(min($w,$maxres),$minres);
		$destw=ceil($destw/2)*2;
		$scale=$destw/$w;
		$refscale=sqrt($h*$scale*$destw/$refarea);
		$destbr=$ref[$encoding]*$h*$scale*$destw*$current['framerate'];
		$desth=-2;
	}
	if($cbr)
	{
		$constant=0.1; #Constant bitrate factor
		$linear=1-$constant; #Influence of area scaling over bitrate
		$destbr=$destbr*(($refscale**2)*$linear+$constant)/($refscale**2);
		$expbr=$current['bitrate']*(($scale**2)*$linear+$constant);
		if(!isset($bitratescale[strtolower($current['format'])]))
		{
			echo "\e[1;31mEncoding scale not found for ".strtolower($current['format'])." (".$current['filename']."), setting to 1\e[0m\n";
			$bitratescale[strtolower($current['format'])]=1;
		}
		$brEncodingScale=$bitratescale[$encoding]/$bitratescale[strtolower($current['format'])];
		if($destbr>$expbr*$brEncodingScale*$overridemax)
		{
			$destbr=$expbr*$brEncodingScale*$overridemax;
		}
		$destbr=ceil($destbr);
	}
	#echo $w."x".$h."@".$current['framerate']." (".$current['bitrate'].") => ".$w*$desth/$h."x".$desth." (".$destbr.")\n";
	#$cmd="echo ".($destbr/$current['bitrate']*100)."%\n";
	$cmd="";
	$outname=pathinfo($current['filename'],PATHINFO_DIRNAME)."/".pathinfo($current['filename'],PATHINFO_FILENAME).".mp4";
	$cver=0;
	if(isset($current['comment'])&&preg_match("~^Convert v(\d+)$~",$current['comment'],$out))
	{
		$cver=$out[1];
	}
	if((file_exists("converted/".$outname)&&filesize("converted/".$outname)&&!$overwrite))
	{
		$do['already existing']++;
	}
	else if($cver>=$version&&!$reconvert)
	{
		$do['already converted (same version)']++;
	}
	else if($cver>0&&!$upgrade&&!$reconvert)
	{
		$do['already converted (old version)']++;
	}
	else if(!$changeformat&&$current['format']=="HEVC"&&$skiphevc)
	{
		$do['already hevc']++;
	}
	else if(!$changeformat&&$current['format']=="AV1"&&$skipav1)
	{
		$do['already av1']++;
	}
	else if($encoding=="h264"&&$skiph264)
	{
		$do['h264 (skipped)']++;
	}
	else
	{
		$mrg=[];
		if($cbr)
		{
			$mrg=['bitrate'=>$destbr,'oldbitrate'=>$current['bitrate']];
		}
		$outnames[$outname][]=array_merge(['filename'=>$current['filename'],'encoding'=>$encoding,'width'=>$destw,'height'=>$desth,'framerate'=>$current['framerate'],'oldbitrate'=>$current['bitrate']],$mrg);
		$do[$encoding]++;
		if($changeformat)
		{
			if($fake)
			{
				echo $current['filename']." needs to be converted, cannot fake\n";
				exit(1);
			}
			$do['change format']++;
		}
		if($fake&&!$changeformat)
		{
			$string="ffmpeg -hide_banner -nostdin -y -i \"".$current['filename']."\" -c:a copy -c:v copy -strict unofficial -metadata comment=\"Convert v".$version."\" \"converted/".$outname."\"\n";
		}
		else
		{
			if($nogpu)
			{
				$initgpu="";
				$filter="scale=".$destw.":".$desth;
			}
			else if($nonvdec||$changeformat)
			{
				$initgpu="-init_hw_device cuda -hwaccel_output_format cuda"/*." -extra_hw_frames 8"*/;
				$filter="hwupload,scale_cuda=".$destw.":".$desth;
			}
			else
			{
				$filter="";
				if($current['rotation']!=0)
				{
					$filter="hwdownload,format=nv12,".match($current['rotation'])
					{
						1=>"transpose=1",
						2=>"transpose=1,transpose=1",
						3=>"transpose=2",
					}.",";
				}
				$initgpu="-hwaccel cuda -hwaccel_output_format cuda"/*." -extra_hw_frames 8"*/;
				$filter.="hwupload,scale_cuda=".$destw.":".$desth;
			}
			if($fixtimestamps)
			{
				$filter.=",setpts=PTS-STARTPTS -af asetpts=PTS-STARTPTS"; #Closes vf, don't add anything after!
			}
			if(in_array(strtolower($current['format']),['gif','apng']))
			{
				$initgpu.=" -stream_loop ".($gifloops-1);
				$filter="format=yuv420p,".$filter;
			}
			else
			{
				$initgpu.=" -stream_loop ".($videoloops-1);
			}
			if($cbr)
			{
				$outQuality="-b:v ".$destbr." -bufsize:v ".($destbr*2);
			}
			else
			{
				$outQuality="-cq ".$quality;
			}
			$string="ffmpeg -progress - "./*"-threads 4 ".*/"-hide_banner -nostdin -y ".$initgpu." -i \"".$current['filename']."\"".($changeformat||$fixtimestamps?NULL:" -c:a copy").($skipframerate?NULL:" -r ".$current['framerate']).($vsync?" -fps_mode ".$vsync:NULL)." -c:v ".$encoding."_nvenc -colorspace bt709 -vf ".$filter." ".$outQuality." -preset p7 -tune hq -rc-lookahead:v 32 -spatial-aq 1 -strict unofficial -metadata comment=\"Convert v".$version."\" \"converted/".$outname."\"\n";
		}
		if($windows)
		{
			$string=str_replace(array("/","%"),array("\\","%%"),$string);
		}
		$cmd.=$string;
		$cmd=[
			'cmd'=>$cmd,
			'dest'=>"converted/".$outname,
			'duration'=>$current['duration'],
			'silent'=>true,
			'stdoutFile'=>getTempFile("txt")
		];
		if($inplace)
		{
			$cmd['success']=function($cmd) use ($current,$outname)
			{
				unlink($current['filename']);
				rename("converted/".$outname,$outname);
			};
		}
		$cmd['fail']=function($cmd) use ($outname)
		{
			@unlink("converted/".$outname);
		};
		$cmd['progress']['running']=function($cmd,$defaultText,$defaultColor) use (&$speeds)
		{
			$file=file_get_contents($cmd['stdoutFile']);
			preg_match_all("/out_time_us=(\d+)/",$file,$out);
			$perc=0;
			if(!empty($out[1]))
			{
				$perc=$out[1][count($out[1])-1]/$cmd['duration']/10000;
			}
			$t=microtime(true);
			if(isset($speeds[$cmd['dest']])&&($speeds[$cmd['dest']]['size']==strlen($file)))
			{
				$speed=$speeds[$cmd['dest']]['speed'];
			}
			else
			{
				$speed=0;
				if(isset($speeds[$cmd['dest']]))
				{
					$speed=($perc-$speeds[$cmd['dest']]['perc'])/($t-$speeds[$cmd['dest']]['time']);
					if($speeds[$cmd['dest']]['speed']>0)
					{
						$speed=$speed*0.2+$speeds[$cmd['dest']]['speed']*0.8;
					}
				}
				$speeds[$cmd['dest']]['speed']=$speed;
				$speeds[$cmd['dest']]['time']=$t;
				$speeds[$cmd['dest']]['perc']=$perc;
				$speeds[$cmd['dest']]['size']=strlen($file);
			}
			$remaining="";
			if($speed>0)
			{
				$remaining=round((100-$perc)/$speed);
				if($remaining<=0) $remaining=0;
				if($remaining>=60)
				{
					$remaining=floor($remaining/60);
					if($remaining>=60)
					{
						$remaining=floor($remaining/60)."h".($remaining%60);
					}
					$remaining.="m";
				}
				else
				{
					$remaining.="s";
				}
			}
			echo "\e[".$defaultColor."m".$defaultText."\e[0m \e[32m".str_repeat("━",floor($perc/5)).($perc<100?"\e[0;2m".(floor($perc/5)>0?"╺":"━").str_repeat("━",19-floor($perc/5)):NULL)."\e[0m ".str_pad(floor($perc),2,0,STR_PAD_LEFT)."% ".$remaining."\e[K\n";
		};
		$allcmds[]=$cmd;
		$totalduration+=$current['duration'];
	}
}
$conflicts=array_filter($outnames,fn($f)=>count($f)>1);
if(!empty($conflicts))
{
	foreach($conflicts as $outname=>$infos)
	{
		array_multisort(array_column($infos,"oldbitrate"),SORT_DESC,SORT_NUMERIC,$infos);
		echo "\e[31m".count($infos)." files will be converted to \e[33m".$outname."\e[0m\n";
		foreach(array_keys(current($infos)) as $key)
		{
			$highliht[$key]=count(array_unique(array_column($infos,$key),SORT_NUMERIC))>1;
		}
		foreach($infos as $c=>$file)
		{
			echo " \e[33m".pathinfo($file['filename'],PATHINFO_FILENAME)."\e[32m.".pathinfo($file['filename'],PATHINFO_EXTENSION)."\e[33m ".
			($highliht['encoding']?"\e[1;32m":NULL).$file['encoding']."\e[0;33m (".
			($highliht['width']?"\e[32m":NULL).$file['width']."\e[33mx".
			($highliht['height']?"\e[32m":NULL).$file['height']."\e[33m ".
			($highliht['framerate']?"\e[32m":NULL).$file['framerate']."\e[33mfps (".
			($highliht['oldbitrate']?"\e[32m":NULL).floor($file['oldbitrate']/1024)."\e[33mKbps))\e[0m\n";
			if($c>0)
			{
				$todelete[]=$file['filename'];
			}
		}
		echo "\n";
	}
	echo implode(array_map(fn($f)=>"rm \"".$f."\"; ",$todelete))."\n";
	exit(1);
}
if($skip)
{
	echo count($skipped)." skipped\n";
	if(!empty($skipped))
	{
		foreach($skipped as $file)
		{
			$tmp[]=$file['reason'].": ".$file['filename']." (".$file['width']."x".$file['height']." ".$file['framerate']."fps (".floor($file['bitrate']/1024)."Kbps))";
		}
		sort($tmp);
		echo implode("\n",$tmp)."\n";
	}
}
#$do[($skip? NULL : "not ")."skipped"]=count($skipped);
if($skip)
{
	$do["skipped"]=count($skipped);
}
echo ($do['h264']+$do['hevc']+$do['av1'])." to convert".(": ".implode(", ",array_map(fn($enc,$n)=>$n." ".$enc,array_keys(array_filter($do)),array_filter($do))))."\n";
if(!empty($allcmds))
{
	array_multisort(array_column($allcmds,"duration"),SORT_DESC,SORT_NUMERIC,$allcmds);
	if(!$noprompt)
	{
		system("stty -icanon");
		do
		{
			#var_export($outnames);
			echo "Press enter to convert, q to quit, i for info, c for commands list or d for debug\n";
			$c=fread(STDIN,1);
			if($c=='i')
			{
				echo "\n".implode("\e[0m\n",array_map(fn($a)=>$a[0]['filename']." \e[32m".$a[0]['encoding']." \e[33m".$a[0]['width']."x".$a[0]['height']." \e[32m".$a[0]['framerate']."fps".(isset($a[0]['oldbitrate'])?" \e[33m".floor($a[0]['oldbitrate']/1024)."Kbps -> ".floor($a[0]['bitrate']??-1/1024)."Kbps":NULL),$outnames))."\e[0m\n";
			}
			else if($c=='d')
			{
				var_export($allcmds);
			}
			else if($c=='c')
			{
				echo implode("\n",(array_column($allcmds,'cmd')))."\n";
			}
			else if($c=="q")
			{
				echo "\n";
				exit(1);
			}
			else if($c=="\n")
			{
				break;
			}
		} while(true);
		system("stty icanon");
	}
	parallelRun($allcmds,$parallel,true);
	shell_exec("find converted -empty -delete");
}
if(!file_exists("bitrates.txt")&&is_dir("converted"))
{
	chdir("converted");
	$rawbitrates=scan($maxdepth,$mtime,$mmin,$includegifs,$gifminfps,$checkaria);
	chdir("..");
	if(isset($rawbitrates['media']))
	{
		$rawbitrates=[$rawbitrates];
	}
	foreach(getChangedDurations($bitrates,$rawbitrates,$gifloops,$videoloops) as $f)
	{
		echo "\e[1;31mDuration mismatch for ".$f['filename'].": src is ".$f['old']." while dst is ".$f['new']." (".($f['new']>$f['old']?"+":"-").round(abs($f['new']-$f['old']),2).")"."\e[0m\n";
	}
}
?>
