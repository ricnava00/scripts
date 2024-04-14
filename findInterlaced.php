#!/usr/bin/env php
<?php
if(!file_exists("interlacedConfig.json"))
{
	$json=[
		'static' =>
		[
			'int' => #Known interlaced files
			[
			],
			'prog' => #Known progressive files
			[
			],
		],
		'noScanPaths' => #Don't scan
		[
		],
		'ignorePaths' => #Scan, but skip output (usually used after handling manually)
		[
		],
	];
	file_put_contents("interlacedConfig.json",json_encode($json,JSON_PRETTY_PRINT));
	readline("JSON configuration file created, edit if needed and press enter to continue");
}
while(true)
{
	$json=json_decode(file_get_contents("interlacedConfig.json"),true);
	if(json_last_error())
	{
		readline("JSON read error, fix and press enter");
	}
	else
	{
		file_put_contents("interlacedConfig.json",json_encode($json,JSON_PRETTY_PRINT)); #Just format
		break;
	}
}
foreach(['static','noScanPaths','ignorePaths'] as $var)
{
	$$var=$json[$var];
}

function parse()
{
	global $frames;
	global $static;
	global $ignorePaths;
	foreach(array_chunk(explode("\n",trim(file_get_contents("interlaced.txt"))),4) as $f)
	{
		if(!preg_match("/from \'(.*)\'/",$f[0],$out))
		{
			echo "Misaligned \n".var_export($f,true)."\n";
			exit;
		}
		$filename=$out[1];
		$found=current(array_keys(array_filter($static,fn($t)=>in_array($filename,$t))));
		if($found!==false)
		{
			$final[$filename]=$found;
		}
		else
		{
			if(in_array(explode("/",$filename)[1],$ignorePaths)) continue;
			foreach(range(0,1) as $type)
			{
				if(!preg_match("/TFF:\s+(\d+) BFF:\s+(\d+) Progressive:\s+(\d+) Undetermined:\s+(\d+)/",$f[2+$type],$out))
				{
					echo "Cannot parse ".$f[2]."\n";
					exit;
				}
				$frames[$filename]['int'][$type]=$out[1]+$out[2];
				$frames[$filename]['prog'][$type]=$out[3];
				$frames[$filename]['unk'][$type]=$out[4];
			}
		}
	}
}
function isUnknown($frame)
{
	return $frame['unk'][1]>$frame['int'][1]+$frame['prog'][1];
}
function isInterlaced($frame)
{
	return $frame['int'][1]>$frame['prog'][1];
}
function isBoth($frame)
{
	return $frame['int'][1]>0&&$frame['prog'][1]>0&&$frame['int'][1]/$frame['prog'][1]<8&&$frame['prog'][1]/$frame['int'][1]<8;
}
if(!file_exists("interlaced.txt"))
{
	passthru('find -iname "*.mp4" '.implode(array_map(fn($p)=>'-not -path "./'.$p.'/*" ',$noScanPaths)).'-print0 | parallel -0 "half=\$(echo \$(ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {})/2 | bc); ffmpeg -hide_banner -filter:v idet -frames:v 60 -an -f rawvideo -y /dev/null -ss \$half -i {} 2>&1 | egrep \'idet|Input\'" | tee interlaced.txt');
}
parse();
$redo=array_keys(array_filter($frames,fn($f)=>isUnknown($f)&&array_sum(array_column($f,0))<240));
if(!empty($redo))
{
	echo count($redo)." unknown, retry with 240s window\n";
	passthru('echo -n '.escapeshellarg(implode("\n",$redo)).' | parallel "half=\$(echo \$(ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {})/3 | bc); ffmpeg -hide_banner -filter:v idet -frames:v 240 -an -f rawvideo -y /dev/null -ss \$half -i {} 2>&1 | egrep \'idet|Input\'" | tee -a interlaced.txt');
	parse();
}
$redo=array_keys(array_filter($frames,fn($f)=>isUnknown($f)&&array_sum(array_column($f,0))<480));
if(!empty($redo))
{
	echo count($redo)." unknown, retry with 480s window\n";
	passthru('echo -n '.escapeshellarg(implode("\n",$redo)).' | parallel "half=\$(echo \$(ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {})*2/3 | bc); ffmpeg -hide_banner -filter:v idet -frames:v 480 -an -f rawvideo -y /dev/null -ss \$half -i {} 2>&1 | egrep \'idet|Input\'" | tee -a interlaced.txt');
	parse();
}
$redo=array_keys(array_filter($frames,fn($f)=>isUnknown($f)&&array_sum(array_column($f,0))==480));
if(!empty($redo))
{
	echo count($redo)." unknown, retry with full window\n";
	passthru('echo -n '.escapeshellarg(implode("\n",$redo)).' | parallel "ffmpeg -hide_banner -filter:v idet -an -f rawvideo -y /dev/null -i {} 2>&1 | egrep \'idet|Input\'" | tee -a interlaced.txt');
	parse();
}
$redo=array_keys(array_filter($frames,fn($f)=>isBoth($f)&&array_sum(array_column($f,0))<=480));
if(!empty($redo))
{
	echo count($redo)." both, retry with full window\n";
	passthru('echo -n '.escapeshellarg(implode("\n",$redo)).' | parallel "ffmpeg -hide_banner -filter:v idet -an -f rawvideo -y /dev/null -i {} 2>&1 | egrep \'idet|Input\'" | tee -a interlaced.txt');
	parse();
}
$redo=array_keys(array_filter($frames,'isUnknown'));
natsort($redo);
if(!empty($redo))
{
	echo "\n\e[1;31mUnknown\e[0m\n".implode("\n",$redo)."\n\n";
}
$redo=array_keys(array_filter($frames,'isBoth'));
natsort($redo);
if(!empty($redo))
{
	echo "\n\e[1;31mBoth\e[0m\n".implode("\n",$redo)."\n\n";
}
$int=array_merge(array_keys(array_filter($frames,'isInterlaced')),$static['int']);
natsort($int);
if(!empty($int))
{
	echo "\n\e[1;33mInterlaced\e[0m\n".implode("\n",$int)."\n";
}
else
{
	echo "All OK\n";
}
?>