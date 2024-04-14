<?php
#converts (basic) svg to touches to run through adb shell
#in.html is a formatted svg path as in "L 10 20", one line per command, with spaces
#cat in.html | sed -r 's/Q [0-9\.]+ [0-9\.]+/L/g' | sed -r 's/A [0-9\.]+ [0-9\.]+ [0-9\.]+ [0-9\.]+ [0-9\.]+/L/g' > out.html
$lines=explode("\n",file_get_contents("out.html"));
foreach($lines as $line)
{
		if(trim($line)!="Z")
		{
			$exp=explode(" ",trim($line));
			$cmd=$exp[0];
			$x=$exp[1]??$x;
			$y=$exp[2]??$y;
			$xs[]=$x;
			$ys[]=$y;
		}
}
$xcenter=720;
$ycenter=1200;
$xmax=max($xs);
$ymax=max($ys);
$xmin=min($xs);
$ymin=min($ys);
$xscale=2000/($xmax-$xmin);
$yscale=1200/($ymax-$ymin);
$scale=min($xscale,$yscale);
$xavg=($xmin+$xmax)/2;
$yavg=($ymin+$ymax)/2;
$count=0;
$sh="";
foreach($lines as $line)
{
	$exp=explode(" ",trim($line));
	$cmd=$exp[0];
	if($cmd=="Z")
	{
		$sh.="input tap ".$sx." ".$sy." && wait; ";
		$count=0;
		continue;
	}
	else
	{
		$initx=$exp[1]??$initx;
		$inity=$exp[2]??$inity;
		$sx=$initx;
		$sy=$inity;
		$x=($sy-$yavg)*$scale+$xcenter;
		$y=$ycenter-($sx-$xavg)*$scale;
		$sx=$x;
		$sy=$y;
		if($cmd=="L")
		{
			$dist=sqrt(($x-$oldx)**2+($y-$oldy)**2);
			$count++;
			$avg=floor($dist/5);
			if($avg!=0)
			{
				for($w=0;$w<$avg;$w++)
				{
					#$sh.="input tap ".(($oldx*($avg-$w)+$x*$w)/$avg)." ".(($oldy*($avg-$w)+$y*$w)/$avg)." & usleep ".($count*10000)." && ";
					$sh.="input tap ".(($oldx*($avg-$w)+$x*$w)/$avg)." ".(($oldy*($avg-$w)+$y*$w)/$avg)." & ";
					$count++;
					if($count>10)
					{
						$sh.="wait; ";
						$count=0;
					}
				}
				$oldx=$x;
				$oldy=$y;
			}
		}
		if($cmd=="M")
		{
			$oldx=$x;
			$oldy=$y;
		}
	}
}
echo $sh."\n";
?>