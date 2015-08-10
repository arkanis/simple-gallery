<?php
/*

Simple Gallery
By Stephan Soller <stephan.soller@helionweb.de>
https://github.com/arkanis/simple-gallery
MIT License

How to install:

- Upload this PHP file into that directory you want to use as image gallery. The webserver has to support PHP but gd isn't necessary.
- Make sure the webserver has write permission to the directory. It's needed to store uploaded images in there.
- Optional: Change the value of $secret_key below to something you can remember and use more easily.

How to use:

- To view the gallery open the directories URL in your browser.
- To upload or delete images append "?key=VUmD[uDP[.\//xbAJCF3MN{xAU8T\.d" to that URL. This will show the upload and delete controls.

Changelog:

2015-08-10  Initial release

*/

$secret_key = 'VUmD[uDP[.\//xbAJCF3MN{xAU8T\.d';
$thumb_quality = 85;
$image_quality = 90;
$extentions = array('jpg', 'jpeg', 'png', 'bmp', 'tif', 'svg');



// Escpae all special HTML character in the $string. The result can be safely inserted into HTML code without risking XSS attacks.
function h($string) {
	return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Exit the script with the specified HTTP response code and text message.
function http_die($http_status, $message = null) {
	header($_SERVER['SERVER_PROTOCOL'] . ' ' . $http_status);
	header('Content-Type: text/plain');
	die($message);
}


$method = $_SERVER['REQUEST_METHOD'];
$key = urldecode(@$_GET['key']);
$name = basename(trim(urldecode(@$_GET['name'])));

if ( ($method == 'PUT' or $method == 'DELETE') and $key === $secret_key and $name != '' ) {
	// Check filename
	$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
	if ( $name[0] == '.' or $name == basename(__FILE__) or !in_array($ext, $extentions) )
		http_die('403 Forbidden', "Sorry, you can only upload image files that don't start with a dot.");
	
	if ($method == 'DELETE') {
		// DELETE index.php?key=xyz&name=filename
		// Delete the file
		if ( @unlink(dirname(__FILE__) . '/' . $name) )
			http_die('204 No Content');
		else
			http_die('500 Internal Server Error', "Can't delete the image, sorry. Please make sure the webserver has the permission to delete it.");
	} else {
		// PUT index.php?key=xyz&name=filename
		// Save the uploaded data into the file
		$upload = fopen('php://input', 'rb');
		$file = @fopen(dirname(__FILE__) . '/' . $name, 'wb');
		if (!$file)
			http_die('500 Internal Server Error', "Can't upload the image, sorry. Please make sure the webserver has write permission for the directory so it can store new images.");
		
		stream_copy_to_stream($upload, $file);
		fclose($upload);
		fclose($file);
		http_die('204 No Content');
	}
} elseif ($method == 'GET' or $method == 'HEAD') {
	// GET index.php, HEAD index.php
	// Show image gallery
	$image_paths = glob(dirname(__FILE__) . '/*.{' . join(',', $extentions) . '}', GLOB_BRACE );
	natsort($image_paths);
	
	$images = array();
	foreach($image_paths as $image_path) {
		$filename = pathinfo($image_path, PATHINFO_FILENAME);
		
		if ( preg_match('/\.thumb$/', $filename) )
			continue;
		
		$thumb_basename = $filename . '.thumb.jpg';
		$image_basename = basename($image_path);
		if ( file_exists(dirname(__FILE__) . '/' . $thumb_basename) )
			$images[$image_basename] = $thumb_basename;
		else
			$images[$image_basename] = $image_basename;
	}
	
	// GET index.php?key=xyz
	// Show upload panel if key is ok
	$show_upload_panel = ($key === $secret_key);
	
	// Fall through to show the HTML output
} else {
	http_die('405 Method Not Allowed');
}

$title = ucwords(basename(dirname(__FILE__)));

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?= h($title) ?></title>
	<style>
		body { margin: 0; padding: 0; }
		h1 { font-size: 2em; margin: 0; padding: 1em; line-height: 1em; }
		ul#images { margin: 0; padding: 1em; }
		
		body.with-upload-panel ul#images { margin: 0 20em 0 0; }
		body.with-upload-panel div#upload-panel { position: absolute; top: 6em; right: 0; bottom: 0; width: 20em; box-sizing: border-box; margin-top: 1px; padding: 1em; }
		
		
		body { font-size: small; font-family: sans-serif; background: hsl(0, 0%, 7.5%); color: hsl(0, 0%, 90%); text-shadow: 1px 1px 1px black; }
		h1, div#upload-panel { background: hsl(0, 0%, 15%); color: hsl(0, 0%, 90%); }
		h1, div#upload-panel, ul#images { border-style: solid; border-color: hsl(0, 0%, 20%) hsl(0, 0%, 0%) hsl(0, 0%, 0%) hsl(0, 0%, 20%); }
		h1 { border-width: 0 0 1px 0; }
		div#upload-panel { border-width: 1px 0 0 1px; }
		ul#images { border-width: 1px 1px 0 0; }
		
		
		body > ul { list-style: none; overflow: hidden; }
		body > ul > li { float: left; margin: 0 10px 10px 0; }
		body > ul > li > a { display: block; position: relative; text-decoration: none; }
		body > ul > li > a::before { content: attr(title); position: absolute; left: 0; bottom: 0; right: 0; padding: 5px;
			background: rgba(0, 0, 0, 0.5); color: hsl(0, 0%, 85%); transition: 0.2s; }
		body > ul > li > a > img { display: block; border: none;
			height: 200px; }
		body > ul > li > a > span.delete { position: absolute; right: 0; bottom: 0; z-index: 10; padding: 5px; color: hsl(0, 0%, 85%); }
		body > ul > li > a > span.delete:hover { color: white; }
		
		body > ul > li > a:hover::before { background: rgba(0, 0, 0, 0.75); color: hsl(0, 0%, 100%); transition: 0.2s; }
		
		
		div#dropzone { font-size: 1.5em; margin: 0; padding: 2em 1em;
			text-align: center; color: hsl(0, 0%, 75%); border: 3px dashed hsl(0, 0%, 75%); border-radius: 10px; }
		input#dropfield { margin: 0.5em 0; padding: 0; }
		ul#uploads { margin: 1em 0 0 0; padding: 0; list-style: none; }
		ul#uploads > li { overflow: hidden; text-overflow: ellipsis; }
		ul#uploads > li > div { position: relative; height: 1.25em; margin: 5px 1px 1px 1px; outline: 1px solid hsl(0, 0%, 50%); border: 1px solid hsl(0, 0%, 10%); }
		ul#uploads > li > div > em { position: absolute; z-index: 2; left: 0; right: 0; top: 0; bottom: 0;
			text-align: center; font-size: 0.77em; }
		ul#uploads > li > div > span { position: absolute; z-index: 1; width: 0; top: 0; left: 0; bottom: 0;
			background: hsl(0, 0%, 35%); }
	</style>
</head>
<?php if($show_upload_panel): ?>
<body class="with-upload-panel">
<?php else: ?>
<body>
<?php endif ?>

<h1><?= h($title) ?></h1>

<ul id="images">
<?php foreach($images as $image => $thumb): ?>
	<li>
		<a href="<?= rawurlencode($image) ?>" title="<?= h(pathinfo($image, PATHINFO_FILENAME)) ?>">
			<img src="<?= rawurlencode($thumb) ?>">
<?php		if($show_upload_panel): ?>
			<span class="delete">delete</span>
<?php		endif ?>
		</a>
	</li>
<?php endforeach ?>
</ul>

<?php if($show_upload_panel): ?>
<div id="upload-panel">
	<div id="dropzone">Drag &amp; drop an image here to convert and upload it</div>
	<input type="file" id="dropfield" multiple>
	<ul id="uploads"></ul>
</div>

<script>
/*

  Basic GUI blocking jpeg encoder ported to JavaScript and optimized by 
  Andreas Ritter, www.bytestrom.eu, 11/2009.

  Example usage is given at the bottom of this file.

  ---------

  Copyright (c) 2008, Adobe Systems Incorporated
  All rights reserved.

  Redistribution and use in source and binary forms, with or without
  modification, are permitted provided that the following conditions are
  met:

  * Redistributions of source code must retain the above copyright notice,
	this list of conditions and the following disclaimer.

  * Redistributions in binary form must reproduce the above copyright
	notice, this list of conditions and the following disclaimer in the
	documentation and/or other materials provided with the distribution.

  * Neither the name of Adobe Systems Incorporated nor the names of its
	contributors may be used to endorse or promote products derived from
	this software without specific prior written permission.

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
  IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
  THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
  PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
  CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
  EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
  PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
  PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
  LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

function JPEGEncoder(quality) {
  var self = this;
	var fround = Math.round;
	var ffloor = Math.floor;
	var YTable = new Array(64);
	var UVTable = new Array(64);
	var fdtbl_Y = new Array(64);
	var fdtbl_UV = new Array(64);
	var YDC_HT;
	var UVDC_HT;
	var YAC_HT;
	var UVAC_HT;

	var bitcode = new Array(65535);
	var category = new Array(65535);
	var outputfDCTQuant = new Array(64);
	var DU = new Array(64);
	var byteout = [];
	var bytenew = 0;
	var bytepos = 7;

	var YDU = new Array(64);
	var UDU = new Array(64);
	var VDU = new Array(64);
	var clt = new Array(256);
	var RGB_YUV_TABLE = new Array(2048);
	var currentQuality;

	var ZigZag = [
			 0, 1, 5, 6,14,15,27,28,
			 2, 4, 7,13,16,26,29,42,
			 3, 8,12,17,25,30,41,43,
			 9,11,18,24,31,40,44,53,
			10,19,23,32,39,45,52,54,
			20,22,33,38,46,51,55,60,
			21,34,37,47,50,56,59,61,
			35,36,48,49,57,58,62,63
		];

	var std_dc_luminance_nrcodes = [0,0,1,5,1,1,1,1,1,1,0,0,0,0,0,0,0];
	var std_dc_luminance_values = [0,1,2,3,4,5,6,7,8,9,10,11];
	var std_ac_luminance_nrcodes = [0,0,2,1,3,3,2,4,3,5,5,4,4,0,0,1,0x7d];
	var std_ac_luminance_values = [
			0x01,0x02,0x03,0x00,0x04,0x11,0x05,0x12,
			0x21,0x31,0x41,0x06,0x13,0x51,0x61,0x07,
			0x22,0x71,0x14,0x32,0x81,0x91,0xa1,0x08,
			0x23,0x42,0xb1,0xc1,0x15,0x52,0xd1,0xf0,
			0x24,0x33,0x62,0x72,0x82,0x09,0x0a,0x16,
			0x17,0x18,0x19,0x1a,0x25,0x26,0x27,0x28,
			0x29,0x2a,0x34,0x35,0x36,0x37,0x38,0x39,
			0x3a,0x43,0x44,0x45,0x46,0x47,0x48,0x49,
			0x4a,0x53,0x54,0x55,0x56,0x57,0x58,0x59,
			0x5a,0x63,0x64,0x65,0x66,0x67,0x68,0x69,
			0x6a,0x73,0x74,0x75,0x76,0x77,0x78,0x79,
			0x7a,0x83,0x84,0x85,0x86,0x87,0x88,0x89,
			0x8a,0x92,0x93,0x94,0x95,0x96,0x97,0x98,
			0x99,0x9a,0xa2,0xa3,0xa4,0xa5,0xa6,0xa7,
			0xa8,0xa9,0xaa,0xb2,0xb3,0xb4,0xb5,0xb6,
			0xb7,0xb8,0xb9,0xba,0xc2,0xc3,0xc4,0xc5,
			0xc6,0xc7,0xc8,0xc9,0xca,0xd2,0xd3,0xd4,
			0xd5,0xd6,0xd7,0xd8,0xd9,0xda,0xe1,0xe2,
			0xe3,0xe4,0xe5,0xe6,0xe7,0xe8,0xe9,0xea,
			0xf1,0xf2,0xf3,0xf4,0xf5,0xf6,0xf7,0xf8,
			0xf9,0xfa
		];

	var std_dc_chrominance_nrcodes = [0,0,3,1,1,1,1,1,1,1,1,1,0,0,0,0,0];
	var std_dc_chrominance_values = [0,1,2,3,4,5,6,7,8,9,10,11];
	var std_ac_chrominance_nrcodes = [0,0,2,1,2,4,4,3,4,7,5,4,4,0,1,2,0x77];
	var std_ac_chrominance_values = [
			0x00,0x01,0x02,0x03,0x11,0x04,0x05,0x21,
			0x31,0x06,0x12,0x41,0x51,0x07,0x61,0x71,
			0x13,0x22,0x32,0x81,0x08,0x14,0x42,0x91,
			0xa1,0xb1,0xc1,0x09,0x23,0x33,0x52,0xf0,
			0x15,0x62,0x72,0xd1,0x0a,0x16,0x24,0x34,
			0xe1,0x25,0xf1,0x17,0x18,0x19,0x1a,0x26,
			0x27,0x28,0x29,0x2a,0x35,0x36,0x37,0x38,
			0x39,0x3a,0x43,0x44,0x45,0x46,0x47,0x48,
			0x49,0x4a,0x53,0x54,0x55,0x56,0x57,0x58,
			0x59,0x5a,0x63,0x64,0x65,0x66,0x67,0x68,
			0x69,0x6a,0x73,0x74,0x75,0x76,0x77,0x78,
			0x79,0x7a,0x82,0x83,0x84,0x85,0x86,0x87,
			0x88,0x89,0x8a,0x92,0x93,0x94,0x95,0x96,
			0x97,0x98,0x99,0x9a,0xa2,0xa3,0xa4,0xa5,
			0xa6,0xa7,0xa8,0xa9,0xaa,0xb2,0xb3,0xb4,
			0xb5,0xb6,0xb7,0xb8,0xb9,0xba,0xc2,0xc3,
			0xc4,0xc5,0xc6,0xc7,0xc8,0xc9,0xca,0xd2,
			0xd3,0xd4,0xd5,0xd6,0xd7,0xd8,0xd9,0xda,
			0xe2,0xe3,0xe4,0xe5,0xe6,0xe7,0xe8,0xe9,
			0xea,0xf2,0xf3,0xf4,0xf5,0xf6,0xf7,0xf8,
			0xf9,0xfa
		];

	function initQuantTables(sf){
			var YQT = [
				16, 11, 10, 16, 24, 40, 51, 61,
				12, 12, 14, 19, 26, 58, 60, 55,
				14, 13, 16, 24, 40, 57, 69, 56,
				14, 17, 22, 29, 51, 87, 80, 62,
				18, 22, 37, 56, 68,109,103, 77,
				24, 35, 55, 64, 81,104,113, 92,
				49, 64, 78, 87,103,121,120,101,
				72, 92, 95, 98,112,100,103, 99
			];

			for (var i = 0; i < 64; i++) {
				var t = ffloor((YQT[i]*sf+50)/100);
				if (t < 1) {
					t = 1;
				} else if (t > 255) {
					t = 255;
				}
				YTable[ZigZag[i]] = t;
			}
			var UVQT = [
				17, 18, 24, 47, 99, 99, 99, 99,
				18, 21, 26, 66, 99, 99, 99, 99,
				24, 26, 56, 99, 99, 99, 99, 99,
				47, 66, 99, 99, 99, 99, 99, 99,
				99, 99, 99, 99, 99, 99, 99, 99,
				99, 99, 99, 99, 99, 99, 99, 99,
				99, 99, 99, 99, 99, 99, 99, 99,
				99, 99, 99, 99, 99, 99, 99, 99
			];
			for (var j = 0; j < 64; j++) {
				var u = ffloor((UVQT[j]*sf+50)/100);
				if (u < 1) {
					u = 1;
				} else if (u > 255) {
					u = 255;
				}
				UVTable[ZigZag[j]] = u;
			}
			var aasf = [
				1.0, 1.387039845, 1.306562965, 1.175875602,
				1.0, 0.785694958, 0.541196100, 0.275899379
			];
			var k = 0;
			for (var row = 0; row < 8; row++)
			{
				for (var col = 0; col < 8; col++)
				{
					fdtbl_Y[k]  = (1.0 / (YTable [ZigZag[k]] * aasf[row] * aasf[col] * 8.0));
					fdtbl_UV[k] = (1.0 / (UVTable[ZigZag[k]] * aasf[row] * aasf[col] * 8.0));
					k++;
				}
			}
		}

		function computeHuffmanTbl(nrcodes, std_table){
			var codevalue = 0;
			var pos_in_table = 0;
			var HT = new Array();
			for (var k = 1; k <= 16; k++) {
				for (var j = 1; j <= nrcodes[k]; j++) {
					HT[std_table[pos_in_table]] = [];
					HT[std_table[pos_in_table]][0] = codevalue;
					HT[std_table[pos_in_table]][1] = k;
					pos_in_table++;
					codevalue++;
				}
				codevalue*=2;
			}
			return HT;
		}

		function initHuffmanTbl()
		{
			YDC_HT = computeHuffmanTbl(std_dc_luminance_nrcodes,std_dc_luminance_values);
			UVDC_HT = computeHuffmanTbl(std_dc_chrominance_nrcodes,std_dc_chrominance_values);
			YAC_HT = computeHuffmanTbl(std_ac_luminance_nrcodes,std_ac_luminance_values);
			UVAC_HT = computeHuffmanTbl(std_ac_chrominance_nrcodes,std_ac_chrominance_values);
		}

		function initCategoryNumber()
		{
			var nrlower = 1;
			var nrupper = 2;
			for (var cat = 1; cat <= 15; cat++) {
				//Positive numbers
				for (var nr = nrlower; nr<nrupper; nr++) {
					category[32767+nr] = cat;
					bitcode[32767+nr] = [];
					bitcode[32767+nr][1] = cat;
					bitcode[32767+nr][0] = nr;
				}
				//Negative numbers
				for (var nrneg =-(nrupper-1); nrneg<=-nrlower; nrneg++) {
					category[32767+nrneg] = cat;
					bitcode[32767+nrneg] = [];
					bitcode[32767+nrneg][1] = cat;
					bitcode[32767+nrneg][0] = nrupper-1+nrneg;
				}
				nrlower <<= 1;
				nrupper <<= 1;
			}
		}

		function initRGBYUVTable() {
			for(var i = 0; i < 256;i++) {
				RGB_YUV_TABLE[i]			  =  19595 * i;
				RGB_YUV_TABLE[(i+ 256)>>0]	 =  38470 * i;
				RGB_YUV_TABLE[(i+ 512)>>0]	 =   7471 * i + 0x8000;
				RGB_YUV_TABLE[(i+ 768)>>0]	 = -11059 * i;
				RGB_YUV_TABLE[(i+1024)>>0]	 = -21709 * i;
				RGB_YUV_TABLE[(i+1280)>>0]	 =  32768 * i + 0x807FFF;
				RGB_YUV_TABLE[(i+1536)>>0]	 = -27439 * i;
				RGB_YUV_TABLE[(i+1792)>>0]	 = - 5329 * i;
			}
		}

		// IO functions
		function writeBits(bs)
		{
			var value = bs[0];
			var posval = bs[1]-1;
			while ( posval >= 0 ) {
				if (value & (1 << posval) ) {
					bytenew |= (1 << bytepos);
				}
				posval--;
				bytepos--;
				if (bytepos < 0) {
					if (bytenew == 0xFF) {
						writeByte(0xFF);
						writeByte(0);
					}
					else {
						writeByte(bytenew);
					}
					bytepos=7;
					bytenew=0;
				}
			}
		}

		function writeByte(value)
		{
			byteout.push(clt[value]); // write char directly instead of converting later
		}

		function writeWord(value)
		{
			writeByte((value>>8)&0xFF);
			writeByte((value   )&0xFF);
		}

		// DCT & quantization core
		function fDCTQuant(data, fdtbl)
		{
			var d0, d1, d2, d3, d4, d5, d6, d7;
			/* Pass 1: process rows. */
			var dataOff=0;
			var i;
			const I8 = 8;
			const I64 = 64;
			for (i=0; i<I8; ++i)
			{
				d0 = data[dataOff];
				d1 = data[dataOff+1];
				d2 = data[dataOff+2];
				d3 = data[dataOff+3];
				d4 = data[dataOff+4];
				d5 = data[dataOff+5];
				d6 = data[dataOff+6];
				d7 = data[dataOff+7];

				var tmp0 = d0 + d7;
				var tmp7 = d0 - d7;
				var tmp1 = d1 + d6;
				var tmp6 = d1 - d6;
				var tmp2 = d2 + d5;
				var tmp5 = d2 - d5;
				var tmp3 = d3 + d4;
				var tmp4 = d3 - d4;

				/* Even part */
				var tmp10 = tmp0 + tmp3;	/* phase 2 */
				var tmp13 = tmp0 - tmp3;
				var tmp11 = tmp1 + tmp2;
				var tmp12 = tmp1 - tmp2;

				data[dataOff] = tmp10 + tmp11; /* phase 3 */
				data[dataOff+4] = tmp10 - tmp11;

				var z1 = (tmp12 + tmp13) * 0.707106781; /* c4 */
				data[dataOff+2] = tmp13 + z1; /* phase 5 */
				data[dataOff+6] = tmp13 - z1;

				/* Odd part */
				tmp10 = tmp4 + tmp5; /* phase 2 */
				tmp11 = tmp5 + tmp6;
				tmp12 = tmp6 + tmp7;

				/* The rotator is modified from fig 4-8 to avoid extra negations. */
				var z5 = (tmp10 - tmp12) * 0.382683433; /* c6 */
				var z2 = 0.541196100 * tmp10 + z5; /* c2-c6 */
				var z4 = 1.306562965 * tmp12 + z5; /* c2+c6 */
				var z3 = tmp11 * 0.707106781; /* c4 */

				var z11 = tmp7 + z3;	/* phase 5 */
				var z13 = tmp7 - z3;

				data[dataOff+5] = z13 + z2;	/* phase 6 */
				data[dataOff+3] = z13 - z2;
				data[dataOff+1] = z11 + z4;
				data[dataOff+7] = z11 - z4;

				dataOff += 8; /* advance pointer to next row */
			}

			/* Pass 2: process columns. */
			dataOff = 0;
			for (i=0; i<I8; ++i)
			{
				d0 = data[dataOff];
				d1 = data[dataOff + 8];
				d2 = data[dataOff + 16];
				d3 = data[dataOff + 24];
				d4 = data[dataOff + 32];
				d5 = data[dataOff + 40];
				d6 = data[dataOff + 48];
				d7 = data[dataOff + 56];

				var tmp0p2 = d0 + d7;
				var tmp7p2 = d0 - d7;
				var tmp1p2 = d1 + d6;
				var tmp6p2 = d1 - d6;
				var tmp2p2 = d2 + d5;
				var tmp5p2 = d2 - d5;
				var tmp3p2 = d3 + d4;
				var tmp4p2 = d3 - d4;

				/* Even part */
				var tmp10p2 = tmp0p2 + tmp3p2;	/* phase 2 */
				var tmp13p2 = tmp0p2 - tmp3p2;
				var tmp11p2 = tmp1p2 + tmp2p2;
				var tmp12p2 = tmp1p2 - tmp2p2;

				data[dataOff] = tmp10p2 + tmp11p2; /* phase 3 */
				data[dataOff+32] = tmp10p2 - tmp11p2;

				var z1p2 = (tmp12p2 + tmp13p2) * 0.707106781; /* c4 */
				data[dataOff+16] = tmp13p2 + z1p2; /* phase 5 */
				data[dataOff+48] = tmp13p2 - z1p2;

				/* Odd part */
				tmp10p2 = tmp4p2 + tmp5p2; /* phase 2 */
				tmp11p2 = tmp5p2 + tmp6p2;
				tmp12p2 = tmp6p2 + tmp7p2;

				/* The rotator is modified from fig 4-8 to avoid extra negations. */
				var z5p2 = (tmp10p2 - tmp12p2) * 0.382683433; /* c6 */
				var z2p2 = 0.541196100 * tmp10p2 + z5p2; /* c2-c6 */
				var z4p2 = 1.306562965 * tmp12p2 + z5p2; /* c2+c6 */
				var z3p2 = tmp11p2 * 0.707106781; /* c4 */
				var z11p2 = tmp7p2 + z3p2;	/* phase 5 */
				var z13p2 = tmp7p2 - z3p2;

				data[dataOff+40] = z13p2 + z2p2; /* phase 6 */
				data[dataOff+24] = z13p2 - z2p2;
				data[dataOff+ 8] = z11p2 + z4p2;
				data[dataOff+56] = z11p2 - z4p2;

				dataOff++; /* advance pointer to next column */
			}

			// Quantize/descale the coefficients
			var fDCTQuant;
			for (i=0; i<I64; ++i)
			{
				// Apply the quantization and scaling factor & Round to nearest integer
				fDCTQuant = data[i]*fdtbl[i];
				outputfDCTQuant[i] = (fDCTQuant > 0.0) ? ((fDCTQuant + 0.5)|0) : ((fDCTQuant - 0.5)|0);
				//outputfDCTQuant[i] = fround(fDCTQuant);

			}
			return outputfDCTQuant;
		}

		function writeAPP0()
		{
			writeWord(0xFFE0); // marker
			writeWord(16); // length
			writeByte(0x4A); // J
			writeByte(0x46); // F
			writeByte(0x49); // I
			writeByte(0x46); // F
			writeByte(0); // = "JFIF",'\0'
			writeByte(1); // versionhi
			writeByte(1); // versionlo
			writeByte(0); // xyunits
			writeWord(1); // xdensity
			writeWord(1); // ydensity
			writeByte(0); // thumbnwidth
			writeByte(0); // thumbnheight
		}

		function writeSOF0(width, height)
		{
			writeWord(0xFFC0); // marker
			writeWord(17);   // length, truecolor YUV JPG
			writeByte(8);	// precision
			writeWord(height);
			writeWord(width);
			writeByte(3);	// nrofcomponents
			writeByte(1);	// IdY
			writeByte(0x11); // HVY
			writeByte(0);	// QTY
			writeByte(2);	// IdU
			writeByte(0x11); // HVU
			writeByte(1);	// QTU
			writeByte(3);	// IdV
			writeByte(0x11); // HVV
			writeByte(1);	// QTV
		}

		function writeDQT()
		{
			writeWord(0xFFDB); // marker
			writeWord(132);	   // length
			writeByte(0);
			for (var i=0; i<64; i++) {
				writeByte(YTable[i]);
			}
			writeByte(1);
			for (var j=0; j<64; j++) {
				writeByte(UVTable[j]);
			}
		}

		function writeDHT()
		{
			writeWord(0xFFC4); // marker
			writeWord(0x01A2); // length

			writeByte(0); // HTYDCinfo
			for (var i=0; i<16; i++) {
				writeByte(std_dc_luminance_nrcodes[i+1]);
			}
			for (var j=0; j<=11; j++) {
				writeByte(std_dc_luminance_values[j]);
			}

			writeByte(0x10); // HTYACinfo
			for (var k=0; k<16; k++) {
				writeByte(std_ac_luminance_nrcodes[k+1]);
			}
			for (var l=0; l<=161; l++) {
				writeByte(std_ac_luminance_values[l]);
			}

			writeByte(1); // HTUDCinfo
			for (var m=0; m<16; m++) {
				writeByte(std_dc_chrominance_nrcodes[m+1]);
			}
			for (var n=0; n<=11; n++) {
				writeByte(std_dc_chrominance_values[n]);
			}

			writeByte(0x11); // HTUACinfo
			for (var o=0; o<16; o++) {
				writeByte(std_ac_chrominance_nrcodes[o+1]);
			}
			for (var p=0; p<=161; p++) {
				writeByte(std_ac_chrominance_values[p]);
			}
		}

		function writeSOS()
		{
			writeWord(0xFFDA); // marker
			writeWord(12); // length
			writeByte(3); // nrofcomponents
			writeByte(1); // IdY
			writeByte(0); // HTY
			writeByte(2); // IdU
			writeByte(0x11); // HTU
			writeByte(3); // IdV
			writeByte(0x11); // HTV
			writeByte(0); // Ss
			writeByte(0x3f); // Se
			writeByte(0); // Bf
		}

		function processDU(CDU, fdtbl, DC, HTDC, HTAC){
			var EOB = HTAC[0x00];
			var M16zeroes = HTAC[0xF0];
			var pos;
			const I16 = 16;
			const I63 = 63;
			const I64 = 64;
			var DU_DCT = fDCTQuant(CDU, fdtbl);
			//ZigZag reorder
			for (var j=0;j<I64;++j) {
				DU[ZigZag[j]]=DU_DCT[j];
			}
			var Diff = DU[0] - DC; DC = DU[0];
			//Encode DC
			if (Diff==0) {
				writeBits(HTDC[0]); // Diff might be 0
			} else {
				pos = 32767+Diff;
				writeBits(HTDC[category[pos]]);
				writeBits(bitcode[pos]);
			}
			//Encode ACs
			var end0pos = 63; // was const... which is crazy
			for (; (end0pos>0)&&(DU[end0pos]==0); end0pos--) {};
			//end0pos = first element in reverse order !=0
			if ( end0pos == 0) {
				writeBits(EOB);
				return DC;
			}
			var i = 1;
			var lng;
			while ( i <= end0pos ) {
				var startpos = i;
				for (; (DU[i]==0) && (i<=end0pos); ++i) {}
				var nrzeroes = i-startpos;
				if ( nrzeroes >= I16 ) {
					lng = nrzeroes>>4;
					for (var nrmarker=1; nrmarker <= lng; ++nrmarker)
						writeBits(M16zeroes);
					nrzeroes = nrzeroes&0xF;
				}
				pos = 32767+DU[i];
				writeBits(HTAC[(nrzeroes<<4)+category[pos]]);
				writeBits(bitcode[pos]);
				i++;
			}
			if ( end0pos != I63 ) {
				writeBits(EOB);
			}
			return DC;
		}

		function initCharLookupTable(){
			var sfcc = String.fromCharCode;
			for(var i=0; i < 256; i++){ ///// ACHTUNG // 255
				clt[i] = sfcc(i);
			}
		}

		this.encode = function(image,quality,toRaw) // image data object
		{
			var time_start = new Date().getTime();

			if(quality) setQuality(quality);

			// Initialize bit writer
			byteout = new Array();
			bytenew=0;
			bytepos=7;

			// Add JPEG headers
			writeWord(0xFFD8); // SOI
			writeAPP0();
			writeDQT();
			writeSOF0(image.width,image.height);
			writeDHT();
			writeSOS();

			// Encode 8x8 macroblocks
			var DCY=0;
			var DCU=0;
			var DCV=0;

			bytenew=0;
			bytepos=7;

			this.encode.displayName = "_encode_";

			var imageData = image.data;
			var width = image.width;
			var height = image.height;

			var quadWidth = width*4;
			var tripleWidth = width*3;

			var x, y = 0;
			var r, g, b;
			var start,p, col,row,pos;
			while(y < height){
				x = 0;
				while(x < quadWidth){
				start = quadWidth * y + x;
				p = start;
				col = -1;
				row = 0;

				for(pos=0; pos < 64; pos++){
					row = pos >> 3;// /8
					col = ( pos & 7 ) * 4; // %8
					p = start + ( row * quadWidth ) + col;

					if(y+row >= height){ // padding bottom
						p-= (quadWidth*(y+1+row-height));
					}

					if(x+col >= quadWidth){ // padding right
						p-= ((x+col) - quadWidth +4)
					}

					r = imageData[ p++ ];
					g = imageData[ p++ ];
					b = imageData[ p++ ];

					/* // calculate YUV values dynamically
					YDU[pos]=((( 0.29900)*r+( 0.58700)*g+( 0.11400)*b))-128; //-0x80
					UDU[pos]=(((-0.16874)*r+(-0.33126)*g+( 0.50000)*b));
					VDU[pos]=((( 0.50000)*r+(-0.41869)*g+(-0.08131)*b));
					*/

					// use lookup table (slightly faster)
					YDU[pos] = ((RGB_YUV_TABLE[r]			 + RGB_YUV_TABLE[(g +  256)>>0] + RGB_YUV_TABLE[(b +  512)>>0]) >> 16)-128;
					UDU[pos] = ((RGB_YUV_TABLE[(r +  768)>>0] + RGB_YUV_TABLE[(g + 1024)>>0] + RGB_YUV_TABLE[(b + 1280)>>0]) >> 16)-128;
					VDU[pos] = ((RGB_YUV_TABLE[(r + 1280)>>0] + RGB_YUV_TABLE[(g + 1536)>>0] + RGB_YUV_TABLE[(b + 1792)>>0]) >> 16)-128;

				}

				DCY = processDU(YDU, fdtbl_Y, DCY, YDC_HT, YAC_HT);
				DCU = processDU(UDU, fdtbl_UV, DCU, UVDC_HT, UVAC_HT);
				DCV = processDU(VDU, fdtbl_UV, DCV, UVDC_HT, UVAC_HT);
				x+=32;
				}
				y+=8;
			}

			////////////////////////////////////////////////////////////////

			// Do the bit alignment of the EOI marker
			if ( bytepos >= 0 ) {
				var fillbits = [];
				fillbits[1] = bytepos+1;
				fillbits[0] = (1<<(bytepos+1))-1;
				writeBits(fillbits);
			}

			writeWord(0xFFD9); //EOI

			if(toRaw) {
				var len = byteout.length;
				var data = new Uint8Array(len);

				for (var i=0; i<len; i++ ) {
					data[i] = byteout[i].charCodeAt();
				}

				//cleanup
				byteout = [];

				// benchmarking
				var duration = new Date().getTime() - time_start;
				console.log('Encoding time: '+ duration + 'ms');

				return data;
			}

			var jpegDataUri = 'data:image/jpeg;base64,' + btoa(byteout.join(''));

			byteout = [];

			// benchmarking
			var duration = new Date().getTime() - time_start;
			console.log('Encoding time: '+ duration + 'ms');

			return jpegDataUri
	}

	function setQuality(quality){
		if (quality <= 0) {
			quality = 1;
		}
		if (quality > 100) {
			quality = 100;
		}

		if(currentQuality == quality) return // don't recalc if unchanged

		var sf = 0;
		if (quality < 50) {
			sf = Math.floor(5000 / quality);
		} else {
			sf = Math.floor(200 - quality*2);
		}

		initQuantTables(sf);
		currentQuality = quality;
		console.log('Quality set to: '+quality +'%');
	}

	function init(){
		var time_start = new Date().getTime();
		if(!quality) quality = 50;
		// Create tables
		initCharLookupTable()
		initHuffmanTbl();
		initCategoryNumber();
		initRGBYUVTable();

		setQuality(quality);
		var duration = new Date().getTime() - time_start;
		console.log('Initialization '+ duration + 'ms');
	}

	init();

};

/* Example usage. Quality is an int in the range [0, 100]
function example(quality){
	// Pass in an existing image from the page
	var theImg = document.getElementById('testimage');
	// Use a canvas to extract the raw image data
	var cvs = document.createElement('canvas');
	cvs.width = theImg.width;
	cvs.height = theImg.height;
	var ctx = cvs.getContext("2d");
	ctx.drawImage(theImg,0,0);
	var theImgData = (ctx.getImageData(0, 0, cvs.width, cvs.height));
	// Encode the image and get a URI back, toRaw is false by default
	var jpegURI = encoder.encode(theImgData, quality);
	var img = document.createElement('img');
	img.src = jpegURI;
	document.body.appendChild(img);
}

Example usage for getting back raw data and transforming it to a blob.
Raw data is useful when trying to send an image over XHR or Websocket,
it uses around 30% less bytes then a Base64 encoded string. It can
also be useful if you want to save the image to disk using a FileWriter.

NOTE: The browser you are using must support Blobs
function example(quality){
	// Pass in an existing image from the page
	var theImg = document.getElementById('testimage');
	// Use a canvas to extract the raw image data
	var cvs = document.createElement('canvas');
	cvs.width = theImg.width;
	cvs.height = theImg.height;
	var ctx = cvs.getContext("2d");
	ctx.drawImage(theImg,0,0);
	var theImgData = (ctx.getImageData(0, 0, cvs.width, cvs.height));
	// Encode the image and get a URI back, set toRaw to true
	var rawData = encoder.encode(theImgData, quality, true);

	blob = new Blob([rawData.buffer], {type: 'image/jpeg'});
	var jpegURI = URL.createObjectURL(blob);

	var img = document.createElement('img');
	img.src = jpegURI;
	document.body.appendChild(img);
}*/


var encoder = new JPEGEncoder();

/**
 * Encodes the image with the given quality and height. When done the completion handler is called with the encoded
 * image as raw data.
 */
function encode_image(image_as_data_url, quality, height, on_completion_handler) {
	var render_image = document.createElement('img');
	render_image.onload = function(){
		if (height) {
			var w = height * (render_image.width / render_image.height);
			var h = height;
		} else {
			var w = render_image.width;
			var h = render_image.height;
		}
		
		var render_canvas = document.createElement('canvas');
		render_canvas.width = w;
		render_canvas.height = h;
		
		var context = render_canvas.getContext('2d');
		context.drawImage(render_image, 0, 0, w, h);
		var pixels = context.getImageData(0, 0, render_canvas.width, render_canvas.height);
		
		on_completion_handler( encoder.encode(pixels, quality, true) );
	};
	render_image.src = image_as_data_url;
}

/**
 * Uploads data to the server, storing it in a file with the given name.
 */
function upload_file(name, data, on_progress_handler, on_completion_handler, on_error_handler) {
	var url = window.location.pathname + window.location.search + '&name=' + encodeURIComponent(name);
	
	var xhr = new XMLHttpRequest();
	xhr.open('PUT', url);
	xhr.onreadystatechange = function(){
		if (xhr.readyState == 4) {
			if (xhr.status == 204) {
				if (on_completion_handler)
					on_completion_handler();
			} else {
				if (on_error_handler)
					on_error_handler(xhr.responseText);
			}
		}
	};
	xhr.upload.addEventListener('progress', function(e){
		if (on_progress_handler)
			on_progress_handler(e);
	});
	
	xhr.send(data);
}

/**
 * Deletes the specified file from the server.
 */
function delete_file(name, on_completion_handler, on_error_handler) {
	var url = window.location.pathname + window.location.search + '&name=' + encodeURIComponent(name);
	
	var xhr = new XMLHttpRequest();
	xhr.open('DELETE', url);
	xhr.onreadystatechange = function(){
		if (xhr.readyState == 4) {
			if (xhr.status == 204) {
				if (on_completion_handler)
					on_completion_handler();
			} else {
				if (on_error_handler)
					on_error_handler(xhr.responseText);
			}
		}
	};
	xhr.send();
}

/**
 * Encodes and uploads a thumbnail and compressed version of the specified file.
 */
function process_file(file) {
	if ( ! /^image/.test(file.type) ) {
		alert('Sorry, ' + file.name + " isn't an image.");
		return;
	}
	
	var reader = new FileReader();
	reader.onload = function(e){
		var data_url = e.target.result;
		var filename = file.name.replace(/\.[^/.]+$/, '');
		
		var li = document.createElement('li');
		li.setAttribute('title', filename);
		li.appendChild( document.createTextNode(filename) );
			var div = document.createElement('div');
			li.appendChild(div);
				var progress_bar = document.createElement('span');
				var progress_text = document.createElement('em');
				div.appendChild(progress_bar);
				div.appendChild(progress_text);
		document.getElementById('uploads').appendChild(li);
		
		var update_progress_bar = function(e){
			if (e.lengthComputable) {
				var percentage = Math.round((e.loaded * 100) / e.total);
				progress_text.nodeValue = '' + percentage + '%';
			} else {
				progress_text.nodeValue = 'uploading...';
			}
		};
		
		var remove_progress_bar = function(){
			document.getElementById('uploads').removeChild(li);
		};
		
		encode_image(data_url, <?= intval($thumb_quality) ?>, 200, function(thumbnail){
			upload_file(filename + '.thumb.jpg', thumbnail, function(e){
				// progress
				update_progress_bar(e);
			}, function(){
				// finished
				encode_image(data_url, <?= intval($image_quality) ?>, null, function(compressed){
					upload_file(filename + '.jpg', compressed, function(){
						// progress
						update_progress_bar(e);
					}, function(){
						// finished
						remove_progress_bar();
						
						var img_li = document.createElement('li');
							var a = document.createElement('a');
							a.setAttribute('title', filename);
							a.setAttribute('href', filename + '.jpg');
								var img = document.createElement('img');
								img.setAttribute('src', filename + '.thumb.jpg');
								var span = document.createElement('span');
								span.setAttribute('class', 'delete');
									span.appendChild(document.createTextNode('delete'));
							a.appendChild(img);
							a.appendChild(span);
						img_li.appendChild(a);
						document.getElementById('images').appendChild(img_li);
					});
				}, function(message){
					// error, show error message if something went wrong
					alert(message);
					remove_progress_bar();
				});
			}, function(message){
				// error, show error message if something went wrong
				alert(message);
				remove_progress_bar();
			});
		});
	};
	reader.readAsDataURL(file);
}

document.addEventListener('DOMContentLoaded', function(){
	var dropzone = document.getElementById('dropzone');
	dropzone.ondragover = dropzone.ondragenter = function(event){
		event.stopPropagation();
		event.preventDefault();
	};
	dropzone.ondrop = function(event){
		event.stopPropagation();
		event.preventDefault();
		
		var files = event.dataTransfer.files;
		for(var i=0; i < files.length; i++)
			process_file(files[i]);
		
		this.value = null;
	}
	
	document.getElementById('dropfield').addEventListener('change', function(){
		for(var i=0; i < this.files.length; i++)
			process_file(this.files[i]);
	});
	
	document.getElementById('images').addEventListener('click', function(event){
		var elem = event.target;
		if ( !(elem.nodeName == 'SPAN' && elem.className == 'delete') )
			return;
		
		event.stopPropagation();
		event.preventDefault();
		
		var a = elem.parentElement;
		var image_filename = a.getAttribute('href');
		var img = elem.previousElementSibling;
		var thumb_filename = img.getAttribute('src');
		
		delete_file(image_filename, function(){
			// success
			if (image_filename != thumb_filename)
				delete_file(thumb_filename);
			
			var li = a.parentElement;
			li.parentElement.removeChild(li);
		}, function(message){
			// error, show error message if something went wrong
			alert(message);
		});
	});
});
</script>
<?php endif ?>

</body>
</html>