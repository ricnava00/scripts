<?php
$RequestVerificationToken='FILL';
$cookies='FILL';
function addPixel($x,$y,$color)
{
	global $svg,$json,$size;
	$scale=ceil($size/3);
	$os=-150+$size/2;
	$json.=',{"id":"s'.rand(100000000000000000,999999999999999999).'","name":"'.($x.$y).'","type":"path","y":'.($y*$size+$os).',"x":'.($x*$size+$os).',"scaleY":'.$scale.',"scaleX":'.$scale.',"invertedY":false,"invertedX":false,"rotation":0,"opacity":100,"index":0,"color":"#'.$color.'","isFilled":true,"internal":false,"locked":false,"tBold":false,"tItalic":false,"fontFamily":null,"borderColor":"#a1a1a1","borderSize":0,"gradientStyle":"Fill","slug":"rectangles/01","width":300,"height":300.01}';
	$svg.='<path fill="#'.$color.'" stroke="#a1a1a1" d="M0,-0.007H300V299.99H1.547C1.547,299.99,1.438,299.898,1.328,299.99C1.283,300.028,1.094,299.99,1.094,299.99H0L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99L0,299.99V-0.007Z" fill-opacity="1" stroke-opacity="1" stroke-width="0" stroke-miterlimit="10" transform="matrix('.($scale/100).',0,0,'.($scale/100).','.($x*$size).','.($y*$size-0.0001).')"></path>';
}

$size=16;
$svg='<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" version="1.1"><defs></defs><rect x="0" y="0" width="512" height="512" rx="0" ry="0" fill="#ececec" stroke="#a1a1a1" fill-opacity="1" stroke-opacity="1" stroke-width="0" stroke-miterlimit="10"></rect>';
$json='[{"id":"background","name":"Background","type":"square","y":0,"x":0,"scaleY":100,"scaleX":100,"invertedY":false,"invertedX":false,"rotation":0,"opacity":100,"index":0,"color":"#ececec","isFilled":true,"internal":true,"locked":false,"tBold":false,"tItalic":false,"fontFamily":null,"borderColor":"#a1a1a1","borderSize":0,"gradientStyle":"Fill","slug":"rectangles/square","width":512,"height":512}';
$im=new Imagick("in.png");
$im->resizeImage(512/$size,512/$size,imagick::FILTER_LANCZOS,0.2,true);
$im->extentImage(512/$size,512/$size,($im->getImageWidth()-512/$size)/2,($im->getImageHeight()-512/$size)/2);
#$im->posterizeImage(2,false);
$imageIterator=$im->getPixelIterator();
foreach ($imageIterator as $row => $pixels)
{
	foreach ($pixels as $column => $pixel)
	{
		$color=$pixel->getColor();
		$color=sprintf('%02s%02s%02s',dechex($color['r']),dechex($color['g']),dechex($color['b']));
		if($color!="10f5f4")
		{
			addPixel($column,$row,$color);
		}
	}
	$imageIterator->syncIterator();
}
$im->resizeImage(512,512,imagick::FILTER_POINT,1);
$im->writeImage("out.png");
$svg.='</svg>';
$json.=']';
$svg=base64_encode($svg);
$json=base64_encode($json);
$ch=curl_init('https://socialclub.rockstargames.com/emblems/save');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:84.0) Gecko/20100101 Firefox/84.0',
	'Accept: application/json, text/javascript, */*; q=0.01',
	'Accept-Language: it-IT,it;q=0.8,en-US;q=0.5,en;q=0.3',
	'Referer: https://socialclub.rockstargames.com/',
	'Content-Type: application/json',
	'X-Requested-With: XMLHttpRequest',
	'Origin: https://socialclub.rockstargames.com',
	'Connection: keep-alive',
	'__RequestVerificationToken: '.$RequestVerificationToken.'',
	'Cookie: '.$cookies.''
));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"crewId":"0","emblemId":"jR9fBGlm","parentId":"","svgData":"'.$svg.'","layerData":"'.$json.'","hash":"2f96b36d2b35dcf66d523828cb101ddd49f14c4e"}');
$out=curl_exec($ch);
var_export($out);
echo "\n";
?>
