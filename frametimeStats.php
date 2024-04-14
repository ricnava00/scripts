<?php
$raw=explode("\n",trim(shell_exec("ffprobe -loglevel error -select_streams v:0 -show_entries packet=pts_time,flags -of csv=print_section=0 test.mp4 | sed 's/,.*//'")));
$delays=array_map(fn($a,$b)=>strval(round($b-$a,5)),array_slice($raw,0,-1),array_slice($raw,1));
$count=array_count_values($delays);
asort($count);
var_export($count);
?>