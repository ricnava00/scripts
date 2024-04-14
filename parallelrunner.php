<?php
function non_block_read($fd, &$data)
{
	$read=[$fd];
	$write=[];
	$except=[];
	$result=stream_select($read,$write,$except,0);
	if($result===false) throw new Exception('stream_select failed');
	if($result===0) return false;
	$data=trim(fgets($fd));
	return true;
}
function parallelRun($cmds,$parallel,$alwaysUpdate=false)
{
	$windows=stripos(php_uname(),"microsoft")!==false;
	if(!empty(array_filter($cmds,fn($cmd)=>(isset($cmd['stdoutFile'])||isset($cmd['stderrFile']))&&!($cmd['silent']??false))))
	{
		echo "\e[1;31mParallelRunner error: output redirection is only supported in silent mode\e[0m\n";
		exit(1);
	}
	$pause=false;
	updateLog($cmds,$pause,false);
	$n=-1;
	$count=-1;
	$procs=array_fill(0,$parallel,NULL);
	while(!empty(array_filter($procs))||$count<count($cmds)-1)
	{
		if(non_block_read(STDIN,$x))
		{
			if(in_array($x,['p',"pause"]))
			{
				$pause=!$pause;
				updateLog($cmds,$pause,false);
			}
		}
		$n=($n+1)%$parallel;
		if(!empty($procs[$n]))
		{
			$p=$procs[$n];
			if(file_exists("/proc/".$p['pid']))
			{
				#echo time()."\n";
			}
			else
			{
				$res=trim(fread($p['tmpfile'],1024));
				fclose($p['tmpfile']);
				unset($procs[$n]);
				$cmd=&$cmds[$p['count']];
				#echo "Finished ".$cmd['cmd']." on ".$n."\n";
				$cmd['status']=$res;
				#echo $cmd['status']."\n";
				if(isset($cmd['dest'])&&!file_exists($cmd['dest']))
				{
					$cmd['failstr']="Destination file not found";
				}
				if($cmd['status']==1||isset($cmd['failstr']))
				{
					if(isset($cmd['fail']))
					{
						$cmd['fail']($cmd);
					}
				}
				else if($cmd['status']==0)
				{
					if(isset($cmd['success']))
					{
						$cmd['success']($cmd);
					}
				}
				unset($cmd);
				if($windows)
				{
					unlink("cmd".$n.".bat");
				}
				if(!$alwaysUpdate)
				{
					updateLog($cmds,$pause);
				}
			}
		}
		if(!$pause&&empty($procs[$n])&&$count<count($cmds)-1)
		{
			$tmpfile=tmpfile();
			$count++;
			$cmdinfo=$cmds[$count];
			$cmd=$cmdinfo['cmd'];
			#echo "Starting ".$cmd." on ".$n."\n";
			if(isset($cmdinfo['dest'])&&!is_dir(pathinfo($cmdinfo['dest'],PATHINFO_DIRNAME)))
			{
				mkdir(pathinfo($cmdinfo['dest'],PATHINFO_DIRNAME),0777,true);
			}
			if($windows)
			{
				$f="cmd".$n.".bat";
				file_put_contents($f,"@echo off\nchcp 65001 >NUL\n".$cmd."\nexit %errorcode%");
				shell_exec("unix2dos -q ".$f);
				$cmd='cmd.exe /c "'.(!($cmdinfo['silent']??false)?'start /min /w ':NULL).$f.' && exit %errorcode%"';
				$finalcmd="(".$cmd."; echo $? > ".stream_get_meta_data($tmpfile)['uri'].") >".($cmdinfo['stdoutFile']??"/dev/null")." 2>".($cmdinfo['stderrFile']??"/dev/null")." & echo $!";
			}
			else
			{
				if($cmd['silent'])
				{
					echo "Silent for linux not implemented yet\n";
					exit;
				}
				$finalcmd="(gnome-terminal --wait -- bash -c ".escapeshellarg(trim($cmd)."; echo $? > ".stream_get_meta_data($tmpfile)['uri']).") >".($cmdinfo['stdoutFile']??"/dev/null")." 2>".($cmdinfo['stderrFile']??"/dev/null")." & echo $!";
			}
			unset($out);
			exec($finalcmd,$out,$exit);
			$arr=[
				'pid'=>$out[0],
				'tmpfile'=>$tmpfile,
				'count'=>$count,
			];
			#echo "with PID ".$out[0]."\n";
			$procs[$n]=$arr;
			$cmds[$count]['status']=2;
			if(!$alwaysUpdate)
			{
				updateLog($cmds,$pause);
			}
		}
		if($alwaysUpdate)
		{
			updateLog($cmds,$pause);
		}
		usleep(100000);
	}
}
function updateLog($cmds,$pause,$clear=true)
{
	global $lastprinted;
	$colors=['fail'=>31,
	'done'=>32,
	'running'=>33,
	'unknown'=>"1;33",
	'queued'=>0];
	$priority=['running','fail','unknown','queued','done'];
	$toptypes=['done','fail','unknown'];
	$bottomtypes=['queued'];
	if($pause)
	{
		$header="\e[5mPAUSED\e[0m\n";
	}
	else
	{
		$header="";
	}
	$lines=shell_exec("tput lines")-3-substr_count($header,"\n");
	if($clear)
	{
		#echo "\e[".min(count($cmds),$lastprinted?:PHP_INT_MAX)."A"; #Broken when pausing
		echo "\e[".($lastprinted??count($cmds))."A";
	}
	$lastprinted=substr_count($header,"\n");
	echo $header;
	$running=false;
	foreach($cmds as $n=>$cmd)
	{
		$e=explode("\n",trim($cmd['cmd']));
		$cmd['cmd']=$e[count($e)-1];
		if((isset($cmd['status'])&&$cmd['status']==1)||isset($cmd['failstr']))
		{
			$type='fail';
		}
		else if(isset($cmd['status']))
		{
			switch($cmd['status'])
			{
				case 0:
				{
					$type='done';
					break;
				}
				case 2:
				{
					$running=true;
					$type='running';
					break;
				}
				default:
				{
					$type='unknown';
					break;
				}
			}
		}
		else
		{
			$type='queued';
		}
		$toprint[]=['cmd'=>$cmd,'text'=>isset($cmd['dest'])?$cmd['dest']:$cmd['cmd'],'type'=>$type];
	}
	$typecount=array_count_values(array_column($toprint,'type'));
	$total=count($cmds);
	foreach(array_reverse($priority) as $type)
	{
		$todelete[$type]=min(isset($typecount[$type])?$typecount[$type]:0,max($total-$lines,0));
		$total-=$todelete[$type];
	}
	$deleted=$todelete;
	foreach($toprint as $n=>$p)
	{
		if(in_array($p['type'],$toptypes)&&$todelete[$p['type']]>0)
		{
			unset($toprint[$n]);
			$todelete[$p['type']]--;
		}
	}
	foreach(array_reverse($toprint,true) as $n=>$p)
	{
		if(in_array($p['type'],$bottomtypes)&&$todelete[$p['type']]>0)
		{
			unset($toprint[$n]);
			$todelete[$p['type']]--;
		}
	}
	$top=[];
	foreach($toptypes as $type)
	{
		if($deleted[$type])
		{
			$top[]="\e[".$colors[$type]."m".$deleted[$type]." ".$type."\e[0m";
		}
	}
	if(!empty($top))
	{
		echo "(".implode(", ",$top).")\e[K\n";
		$lastprinted++;
	}
	foreach($toprint as $p)
	{
		$cmd=$p['cmd'];
		if(isset($cmd['progress'])&&is_callable($cmd['progress']))
		{
			$cmd['progress']($cmd,$p['type'],$p['text'],$colors[$p['type']]);
		}
		else if(isset($cmd['progress'][$p['type']]))
		{
			$cmd['progress'][$p['type']]($cmd,$p['text'],$colors[$p['type']]);
		}
		else
		{
			echo "\e[".$colors[$p['type']]."m".$p['text']."\e[0m\e[K\n";
		}
	}
	$lastprinted+=count($toprint);
	$bottom=[];
	foreach($bottomtypes as $type)
	{
		if($deleted[$type])
		{
			$bottom[]="\e[".$colors[$type]."m".$deleted[$type]." ".$type."\e[0m";
		}
	}
	if(!empty($bottom))
	{
		echo "(".implode(", ",$bottom).")\e[K\n";
		$lastprinted++;
	}
	if(!$running)
	{
		foreach($cmds as $cmd)
		{
			if((isset($cmd['status'])&&$cmd['status']==1)||isset($cmd['failstr']))
			{
				echo "\e[31mFailed\e[0m ".(isset($cmd['dest'])?$cmd['dest']:$cmd['cmd']).":\n".(isset($cmd['dest'])?"Command: ".trim($cmd['cmd'])."\n":NULL)."Exit code: ".$cmd['status'].(isset($cmd['failstr'])?"\nReason: ".$cmd['failstr']:NULL)."\n\n";
			}
		}
	}
	echo "\e[K";
}
?>