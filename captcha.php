<?php


session_start();

$randN = rand(1,5);
$numero = $randN;

$circles = array();

for($i = 0; $i < $randN; $i++)
	$circles[] = "⚫";

$remainderWhite = 6 - $randN;

for($j = 0; $j < $remainderWhite; $j++)
	$circles[] = "⚪";

shuffle($circles);


// set session variable to total
$_SESSION['checker'] = $numero;

// set image size (pixels)
// imagecreate( [width], [height] )
$img = imagecreate( 170, 38 );

// choose a bg color, you can play with the rgb values
// imagecolorallocate( [image], [red], [green], [blue] )
$background = imagecolorallocate( $img, 255, 255, 255 );

//chooses the text color
// imagecolorallocate( [image], [red], [green], [blue] )
$text_colour = imagecolorallocate( $img, 33, 37, 41 );

//pulls the value passed in the URL
$text = implode("", $circles);

// place the font file in the same dir level as the php file
$font = realpath('fonts/Symbola.ttf');

//this function sets the font size, places to the co-ords
// imagettftext( [image], [size], [angle], [x], [y], [color], [fontfile], [text] )
imagettftext($img, 32, 0, 5, 30, $text_colour, $font, $text);

//alerts the browser abt the type of content i.e. png image
header( 'Content-type: image/png' );
//now creates the image
imagepng( $img );

//destroys used resources
imagecolordeallocate( $img, $text_color );
imagecolordeallocate( $img, $background );
imagedestroy( $img );