#!/usr/bin/env php
<?php
$version=1;
$options=[
	[
		'option'=>"list",
		'varname'=>"list",
		'desc'=>"Just list files to process, don't process them",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"jobs",
		'varname'=>"jobs",
		'desc'=>"Number of parallel gallery calls",
		'type'=>'int',
		'default'=>max(trim(shell_exec("nproc"))-4,4),
	],
	[
		'option'=>"maxframes",
		'varname'=>"maxframes",
		'desc'=>"Max number of generated frames",
		'type'=>'int',
		'default'=>NULL,
	],
	[
		'option'=>"mininterval",
		'varname'=>"mininterval",
		'desc'=>"Minimum frame interval, gets doubled until frames don't exceed max",
		'type'=>'int',
		'default'=>NULL,
	],
	[
		'option'=>"interval",
		'varname'=>"interval",
		'desc'=>"Forced frame interval, ignores max frames",
		'type'=>'int',
		'default'=>NULL,
	],
	[
		'option'=>"reverse",
		'varname'=>"reverse",
		'desc'=>"Reverse file list (useful when starting two jobs)",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"update",
		'varname'=>"update",
		'desc'=>"Recreate previews with changed source or settings",
		'type'=>'bool',
		'default'=>false,
	],
	[
		'option'=>"updateversion",
		'varname'=>"updateversion",
		'desc'=>"Recreate previews if version is older than current",
		'type'=>'bool',
		'default'=>false,
	]
];
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
function nextStep($curr)
{
	global $waiting;
	global $options;
	global $types;
	global $manuallySet;
	if(!isset($waiting['type']))
	{
		if(preg_match("~^--~",$curr))
		{
			$var=substr($curr,2);
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
function array_diff_assoc_recursive($array1,$array2)
{
	foreach($array1 as $key=>$value)
	{
		if(is_array($value))
		{
			if(!isset($array2[$key]))
			{
				$difference[$key]=$value;
			}
			elseif(!is_array($array2[$key]))
			{
				$difference[$key]=$value;
			}
			else
			{
				$new_diff=array_diff_assoc_recursive($value,$array2[$key]);
				if(!empty($new_diff))
				{
					$difference[$key]=$new_diff;
				}
			}
		}
		elseif(!in_array($value,$array2))
		{
			$difference[$key]=$value;
		}
	}
	return !isset($difference)?[]:$difference;
}
function removeExcluded($tocreate,$excluded)
{
	if(is_array($tocreate))
	{
		$out=[];
		foreach($tocreate as $dir=>$videos)
		{
			if(isset($excluded[$dir]))
			{
				if(!(isset($excluded[$dir]['..'])&&$excluded[$dir]['..']))
				{
					$out[$dir]=removeExcluded($videos,$excluded[$dir]);
				}
			}
			else
			{
				$out[$dir]=$videos;
			}
		}
		return $out;
	}
	else
	{
		return $tocreate;
	}
}
function flatten($tocreate,$suffix,$path=".",$lastpath=".")
{
	if(is_array($tocreate))
	{
		$out=[];
		foreach($tocreate as $dir=>$videos)
		{
			$out=array_merge($out,flatten($videos,$suffix,$path."/".$lastpath,$dir));
		}
		return $out;
	}
	else
	{
		return [$path."/".$tocreate.$suffix];
	}
}
$options=array_column($options,NULL,"option");
$manuallySet=array_map(fn()=>false,array_column($options,NULL,"varname"));
$types=
[
	'int'=>'~^\d+$~',
];
$typedescs=
[
	'int'=>"N",
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
if($manuallySet['interval']&&($manuallySet['maxframes']||$manuallySet['mininterval']))
{
	echo "--interval and --mininterval/--maxframes are incompatible\n";
	exit(1);
}
$settings=array_filter(array_map(function($var)
{
	global $$var;
	return isset($$var)?" --".$var." ".$$var:"";
},['interval','mininterval','maxframes']));

$files=[];
foreach(explode("\n",trim(shell_exec('find -type f -iname "*.mp4" -or -iname "*_prev.jpg" -or -iname "*_prev.jpg.tmp" -or -iname ".nogallery"'))) as $f)
{
	if(pathinfo($f,PATHINFO_EXTENSION)=="mp4")
	{
		$files[]=['f'=>pathinfo($f,PATHINFO_DIRNAME)."/".pathinfo($f,PATHINFO_FILENAME),'t'=>'video'];
	}
	else if(pathinfo($f,PATHINFO_EXTENSION)=="jpg")
	{
		$files[]=['f'=>pathinfo($f,PATHINFO_DIRNAME)."/".preg_replace("~_prev$~","",pathinfo($f,PATHINFO_FILENAME)),'t'=>'prev'];
	}
	else if(pathinfo($f,PATHINFO_EXTENSION)=="tmp")
	{
		echo "\e[33mWarning: found temporary file ".$f."\e[0m\n";
	}
	else
	{
		if(dirname($f)==".")
		{
			echo ".nogallery at base dir, no files will be processed\n";
		}
		$files[]=['f'=>$f,'t'=>'exclude'];
	}
}
$videos=[];
$prevs=[];
$excluded=[];
array_multisort(array_column($files,"f"),SORT_ASC,SORT_NATURAL,$files);
foreach($files as $f)
{
	extract($f);
	switch($t)
	{
		case "video":
		{
			$curr=&$videos;
			break;
		}
		case "prev":
		{
			$curr=&$prevs;
			break;
		}
		case "exclude":
		{
			$curr=&$excluded;
			break;
		}
	}
	foreach(explode("/",pathinfo($f,PATHINFO_DIRNAME)) as $dir)
	{
		$curr=&$curr[$dir];
	}
	switch($t)
	{
		case "video":
		case "prev":
		{
			$curr[]=pathinfo($f,PATHINFO_BASENAME);
			break;
		}
		case "exclude":
		{
			$curr['..']=true; #Key that cannot be the name of a dir
			break;
		}
	}
}
foreach(flatten(array_diff_assoc_recursive($prevs,removeExcluded($videos,$excluded)),"_prev.jpg") as $wrongprev)
{
	echo "Deleting ".$wrongprev."\n";
	if(!$list)
	{
		unlink($wrongprev);
	}
}
$prevs=array_diff_assoc_recursive($prevs,array_diff_assoc_recursive($prevs,removeExcluded($videos,$excluded)));
$update??=false;
$updateversion??=false;
if(!empty($prevs)&&($update||$updateversion))
{
	$out=shell_exec("parallel -X 'convert {}[1x1+0+0] json:-' ::: ".escapeshellarg(implode("\n",flatten($prevs,"_prev.jpg")))." | sed 's/}]\[{/},{/g'");
	$json=json_decode($out,true);
	if(!isset($json))
	{
		echo $out."\nError parsing json\n";
		exit(1);
	}
	$comments=array_merge(...array_map(fn($img)=>[str_replace('[1x1+0+0]','',$img['image']['name'])=>$img['image']['properties']['comment']??NULL],$json));
	$comments=array_map(fn($img)=>$img?array_merge(...array_map(fn($i)=>[explode(": ",$i)[0]=>explode(": ",$i,2)[1]??""],explode("\n",trim($img,"\n")))):NULL,$comments);
	foreach($comments as $prev=>$comment)
	{
		if($updateversion)
		{
			if(($comment['Version']??0)<$version)
			{
				$redo[]=[$prev, "v".($comment['Version']??0)];
			}
		}
		else if($comment)
		{
			$opt=array_map(fn($o)=>" --".rtrim($o),array_filter(explode("--",$comment['Options'])));
			if(isset($comment['Source'])&&base64_decode($comment['Source'])!=basename($prev,"_prev.jpg"))
			{
				$redo[]=[$prev, "Renamed from ".base64_decode($comment['Source'])];
			}
			else if(array_diff($opt,$settings)||array_diff($settings,$opt))
			{
				$redo[]=[$prev, "Settings changed"];
			}
		}
	}
}
$todo=flatten(removeExcluded(array_diff_assoc_recursive($videos,$prevs),$excluded),".mp4");
if(!empty($redo))
{
	foreach($redo as $prev)
	{
		echo "Redoing ".$prev[0]." (".$prev[1].")\n";
	}
	if(!$list)
	{
		readline("Press enter to delete the files\n");
		foreach($redo as $prev)
		{
			unlink($prev[0]);
		}
	}
	$todo=array_merge($todo,array_map(fn($prev)=>str_replace("_prev.jpg",".mp4",$prev[0]),$redo));
	natsort($todo);
	$todo=array_values($todo);
}
$cmds="";
if($reverse)
{
	$todo=array_reverse($todo);
}
if($list)
{
	echo implode("\n",$todo)."\n";
}
else
{
	foreach($todo as $n=>$video)
	{
		$video=escapeshellarg(preg_replace("~^(\./)+~","",$video));
		$cmds.="echo ".($n+1)."/".count($todo)."\\\t".$video." && ";
		$cmds.="gallery --jobs ".(count($todo)-$n<=$jobs*2?-1:1)." ".$video.implode($settings)."\n";
	}
	$cmdFile=getTempFile("txt");
	file_put_contents($cmdFile,$cmds);
	passthru("parallel --ungroup -j".$jobs." --halt-on-error 1 < ".$cmdFile);
	unlink($cmdFile);
}
?>