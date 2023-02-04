<?php
/**
* NCGR2PNG is a class that enables direct conversion from NCGR and NCLR files to png
* NCGR is a graphic tile format that stores information about a sprite
* NCLR is a color palette format that stores information about colors
* Those files can be found in the file system of Nintendo DS games
*/
class NCGR2PNG {
	
	private int $chunksize = 0;
	private int $height = 0;
	private int $width = 0;
	private int $colordepth = 0;
	private int $tiledatasize = 0;
	private string $ncgrdata = "";
	
	private array $colors;
	
	/**
	* Constructor for class NCGR2PNG
	*
	* @param string $ncgrFile  the name of the NCGR file
	* @param string $nclrFile  the name of the NCLR file
	* @access public
	*/
	public function __construct(string $ncgrFile = null, string $nclrFile = null) {
		if(!is_null($ncgrFile)) $this->setNCGR($ncgrFile);
		if(!is_null($nclrFile)) $this->setNCLR($nclrFile);
	}
	
	/**
	* Setter for the NCGR file. Takes a filename and reads all important data from the file
	*
	* @param string $ncgrFile  the name of the NCGR file
	* @access public
	*/
	public function setNCGR(string $ncgrFile) : void {
		$ncgrbytes = bin2hex(file_get_contents($ncgrFile));
		
		$this->chunksize = bindec(substr($ncgrbytes, 24, 4));
		$this->colordepth = hexdec(self::bigToLittle(substr($ncgrbytes, 56, 8)));
		$this->tiledatasize = hexdec(self::bigToLittle(substr($ncgrbytes, 80, 8))) * 2;
		$this->ncgrdata = substr($ncgrbytes, 96, $this->tiledatasize);
		
		$tileCount = hexdec(self::bigToLittle(substr($ncgrbytes, 48, 4)));
		$this->height = $tileCount * $this->chunksize;
		$this->width =  $this->tiledatasize / ($tileCount * $this->chunksize);
	}
	
	/**
	* Setter for the NCLR file. Takes a filename and reads al important data from the file
	*
	* @param string $nclrFile  the name of the NCLR file
	* @access public
	*/
	public function setNCLR(string $nclrFile) : void {
		$this->colors = array();
		
		$nclrbytes = bin2hex(file_get_contents($nclrFile));
		
		$nclrdata = substr($nclrbytes, 80, hexdec(self::bigToLittle(substr($nclrbytes, 72, 8))) * 4);
		
		for($i = 0; $i < strlen($nclrdata); $i += 4) {
			$r = ((hexdec(self::bigToLittle(substr($nclrdata, $i, 4)))      ) & 0b11111) * 8;
			$g = ((hexdec(self::bigToLittle(substr($nclrdata, $i, 4))) >>  5) & 0b11111) * 8;
			$b = ((hexdec(self::bigToLittle(substr($nclrdata, $i, 4))) >> 10) & 0b11111) * 8;
			
			$rErr = floor($r / 32);
			$gErr = floor($g / 32);
			$bErr = floor($b / 32);
			
			array_push($this->colors, array("r" => $r + $rErr, "g" => $g + $gErr, "b" => $b + $bErr));
		}
	}
	
	/**
	* Merges the current NCGR and NCLR file and saves them as a png at the given file name
	*
	* @param string $filename  the name under which the generated file will be saved
	* @access public
	*/
	public function convert(string $filename) : void {
		if(empty($this->ncgrdata)) {
			throw new Error("No NCGR file set");
		} else if(empty($this->colors)) {
			throw new Error("No NCLR file set");
		}
		
		$chunks = array();
		
		foreach(str_split($this->ncgrdata, pow($this->chunksize, 2)) as $chunk) {
			array_push($chunks, $this->createChunk(self::switchBit($chunk)));
		}
		
		$sprite = imagecreatetruecolor($this->width, $this->height);
		imageSaveAlpha($sprite, true);
		$background = imagecolorallocatealpha($sprite, 0, 0, 0, 127);
		imagefill($sprite, 0, 0, $background);
		
		for($y = 0; $y < $this->height / $this->chunksize; $y++) {
			for($x = 0; $x < $this->width / $this->chunksize; $x++) {
				$i = $y * ($this->width / $this->chunksize) + $x;
				
				imagecopy($sprite, $chunks[$i], $x * $this->chunksize, $y * $this->chunksize, 0, 0, $this->chunksize, $this->chunksize);
				imagepng($sprite, $filename . ".png");
			}
		}
		
		imagepng($sprite, $filename . ".png");
	}
	
	/**
	* Creator for chunk images
	*
	* @param string $ncgrTile  the string representation of the chunk extracted from the data section of a NCGR file
	* @return GdImage          the image representing the chunk
	* @access private
	*/
	private function createChunk(string $ncgrTile) : GdImage {
		$image = imagecreatetruecolor($this->chunksize, $this->chunksize);
		imageSaveAlpha($image, true);
		$background = imagecolorallocatealpha($image, 0, 0, 0, 127);
		imagefill($image, 0, 0, $background);
		
		for($y = 0; $y < $this->chunksize; $y++) {
			for($x = 0; $x < $this->chunksize; $x++) {
				$i = $y * $this->chunksize + $x;
				$colorNr = hexdec(substr($ncgrTile, $i, 1));
				
				if($colorNr == 0) continue;
				
				imagefilledrectangle($image, $x, $y, $x, $y, imagecolorallocate($image, $this->colors[$colorNr]["r"], $this->colors[$colorNr]["g"], $this->colors[$colorNr]["b"]));
			}
		}
		
		return $image;
	}
	
	/**
	* Helper function for converting a byte string from Big Endian to Little Endian
	*
	* @param string $bigEndian  the string in Big Endian representation
	* @return string            the string in Little Endian representation
	* @access private
	*/
	private static function bigToLittle(string $bigEndian) : string {
		return implode("", array_reverse(str_split($bigEndian, 2)));
	}
	
	/**
	* Helper function for switching the first for with the last four bit in a hexadecimal representation
	*
	* @param string $bytes  the bytes for which the switch should be applied
	* @return string        the input with its bits switched
	* @access private
	*/
	private static function switchBit(string $bytes) : string {
		return implode("", array_map(function($string) {
			return strrev($string);
		}, str_split($bytes, 2)));
	}
}
?>