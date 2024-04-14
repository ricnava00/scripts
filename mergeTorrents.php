<?php
$chunk_size_kb=128;
$chunk_size=$chunk_size_kb*1024;
$file1=$argv[1]??"file1";
$file2=$argv[2]??"file2";
$output=$argv[3]??"output";
if(!file_exists($file1))
{
	echo $file1." does not exist\n";
	exit;
}
if(!file_exists($file2))
{
	echo $file2." does not exist\n";
	exit;
}
if(file_exists($output))
{
	echo $output." already exists, refusing to replace\n";
	exit;
}

function writeBestChunk($chunk1,$chunk2,$out,$total_size,$chunk_size,$scale,&$bytes_read,&$chunks)
{
	global $last_progress_bar, $progress_width, $progress_chars, $progress_chars_count;
	if(strlen(trim($chunk1,"\x00")))
	{
		if(strlen(trim($chunk2,"\x00")))
		{
			if($chunk1===$chunk2)
			{
				$chunks['equal']+=$scale;
				fwrite($out,$chunk1);
				$bytes_read+=strlen($chunk1);
			}
			else
			{
				$newChunk_size=floor($chunk_size/128);
				if($newChunk_size>=1024)
				{
					$newchunks1=str_split($chunk1,$newChunk_size);
					$newchunks2=str_split($chunk2,$newChunk_size);
					foreach(range(0,max(count($newchunks1),count($newchunks2))-1) as $n)
					{
						writeBestChunk($newchunks1[$n],$newchunks2[$n],$out,$total_size,$newChunk_size,1/128,$bytes_read,$chunks);
					}
				}
				else
				{
					$chunks['different']+=$scale;
					echo "\n\e[1;33mChunks from ".strtoupper(dechex($bytes_read))." are different\e[0m\n";
					fwrite($out,$chunk1);
					$bytes_read+=strlen($chunk1);
				}
			}
		}
		else
		{
			$chunks['copied1']+=$scale;
			fwrite($out,$chunk1);
			$bytes_read+=strlen($chunk1);
		}
	}
	elseif(strlen(trim($chunk2,"\x00")))
	{
		$chunks['copied2']+=$scale;
		fwrite($out,$chunk2);
		$bytes_read+=strlen($chunk2);
	}
	elseif($chunk1||$chunk2)
	{
		$chunks['both_zero']+=$scale;
		fwrite($out,$chunk1);
		$bytes_read+=strlen($chunk1);
	}
	$progress=$bytes_read/$total_size;
	$progress_percent=round($progress*100);
	if($progress>=0&&$progress<=1)
	{
		$progress_bar=str_repeat($progress_chars[0],round($progress*$progress_width));
		$progress_bar.=$progress_chars[min(1,(int)($progress*$progress_width*$progress_chars_count))];
		$progress_bar.=str_repeat($progress_chars[$progress_chars_count-1],max(0,$progress_width-strlen($progress_bar)));
	}
	else
	{
		$progress_bar=str_repeat($progress_chars[$progress_chars_count-1],$progress_width);
	}
	if($progress_bar!=($last_progress_bar??null))
	{
		echo "\r[$progress_bar] $progress_percent%";
		$last_progress_bar=$progress_bar;
	}
}

$file_size1=filesize($file1);
$file_size2=filesize($file2);
$total_size=max($file_size1,$file_size2);
$bytes_read=0;
$chunks=[
'copied1'=>0,
'copied2'=>0,
'equal'=>0,
'different'=>0,
'both_zero'=>0,
];

$f1=fopen($file1,'rb');
$f2=fopen($file2,'rb');
$out=fopen($output,'wb');

$progress_width=trim(shell_exec("tput cols"))-8;
$progress_chars=['=','>',' '];
$progress_chars_count=count($progress_chars);

echo "[".str_repeat(' ',$progress_width)."] 0%";

while(true)
{
	$chunk1=fread($f1,$chunk_size);
	$chunk2=fread($f2,$chunk_size);
	if(!$chunk1&&!$chunk2)
	{
		break;
	}
	writeBestChunk($chunk1,$chunk2,$out,$total_size,$chunk_size,1,$bytes_read,$chunks);
}

fclose($f1);
fclose($f2);
fclose($out);
echo "\nProgress: 100%\n";
echo "Chunks only in $file1: ".$chunks['copied1']."\n";
echo "Chunks only in $file2: ".$chunks['copied2']."\n";
echo "Chunks equal: ".$chunks['equal']."\n";
echo "Chunks different: ".$chunks['different']."\n";
echo "Chunks both zero: ".$chunks['both_zero']."\n";
?>
