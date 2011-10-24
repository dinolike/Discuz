<?php

/*
	[品牌空間] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: zip.func.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_BRAND')) {
	exit('Acess Denied');
}

// vim: expandtab sw=4 ts=4 sts=4:

class zipfile {
    var $datasec      = array();

    var $ctrl_dir     = array();

    var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

    var $old_offset   = 0;


    function unix2DosTime($unixtime = 0) {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if($timearray['year'] < 1980) {
            $timearray['year']    = 1980;
            $timearray['mon']     = 1;
            $timearray['mday']    = 1;
            $timearray['hours']   = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        } // end if

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
                ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    } // end of the 'unix2DosTime()' method


    function addFile($data, $name, $time = 0) {
        $name     = str_replace('\\', '/', $name);

        $dtime    = dechex($this->unix2DosTime($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
                  . '\x' . $dtime[4] . $dtime[5]
                  . '\x' . $dtime[2] . $dtime[3]
                  . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
        $fr   .= "\x00\x00";            // gen purpose bit flag
        $fr   .= "\x08\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data);
        $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $c_len   = strlen($zdata);
        $fr      .= pack('V', $crc);             // crc32
        $fr      .= pack('V', $c_len);           // compressed filesize
        $fr      .= pack('V', $unc_len);         // uncompressed filesize
        $fr      .= pack('v', strlen($name));    // length of filename
        $fr      .= pack('v', 0);                // extra field length
        $fr      .= $name;

        // "file data" segment
        $fr .= $zdata;

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        // nijel(2004-10-19): this seems not to be needed at all and causes
        // problems in some cases (bug #1037737)
        //$fr .= pack('V', $crc);                 // crc32
        //$fr .= pack('V', $c_len);               // compressed filesize
        //$fr .= pack('V', $unc_len);             // uncompressed filesize

        // add this entry to array
        $this -> datasec[] = $fr;

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name) ); // length of filename
        $cdrec .= pack('v', 0 );             // extra field length
        $cdrec .= pack('v', 0 );             // file comment length
        $cdrec .= pack('v', 0 );             // disk number start
        $cdrec .= pack('v', 0 );             // internal file attributes
        $cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this -> old_offset ); // relative offset of local header
        $this -> old_offset += strlen($fr);

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this -> ctrl_dir[] = $cdrec;
    } // end of the 'addFile()' method


    function file() {
        $data    = implode('', $this -> datasec);
        $ctrldir = implode('', $this -> ctrl_dir);

        return
            $data .
            $ctrldir .
            $this -> eof_ctrl_dir .
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries "on this disk"
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries overall
            pack('V', strlen($ctrldir)) .           // size of central dir
            pack('V', strlen($data)) .              // offset to start of central dir
            "\x00\x00";                             // .zip file comment length
    } // end of the 'file()' method

} // end of the 'zipfile' class


    class SimpleUnzip {
// 2003-12-02 - HB >
        var $Comment = '';
// 2003-12-02 - HB <

        var $Entries = array();

        var $Name = '';

        var $Size = 0;

        var $Time = 0;

        function SimpleUnzip($in_FileName = '') {
            if($in_FileName !== '') {
                SimpleUnzip::ReadFile($in_FileName);
            }
        } // end of the 'SimpleUnzip' constructor

        function Count() {
            return count($this->Entries);
        } // end of the 'Count()' method

        function GetData($in_Index) {
            return $this->Entries[$in_Index]->Data;
        } // end of the 'GetData()' method

        function GetEntry($in_Index) {
            return $this->Entries[$in_Index];
        } // end of the 'GetEntry()' method

        function GetError($in_Index) {
            return $this->Entries[$in_Index]->Error;
        } // end of the 'GetError()' method

        function GetErrorMsg($in_Index) {
            return $this->Entries[$in_Index]->ErrorMsg;
        } // end of the 'GetErrorMsg()' method

        function GetName($in_Index) {
            return $this->Entries[$in_Index]->Name;
        } // end of the 'GetName()' method

        function GetPath($in_Index) {
            return $this->Entries[$in_Index]->Path;
        } // end of the 'GetPath()' method

        function GetTime($in_Index) {
            return $this->Entries[$in_Index]->Time;
        } // end of the 'GetTime()' method

        function ReadFile($in_FileName) {
            $this->Entries = array();

            // Get file parameters
            $this->Name = $in_FileName;
            $this->Time = filemtime($in_FileName);
            $this->Size = filesize($in_FileName);

            // Read file
            $oF = fopen($in_FileName, 'rb');
            $vZ = fread($oF, $this->Size);
            fclose($oF);

// 2003-12-02 - HB >
            // Cut end of central directory
            $aE = explode("\x50\x4b\x05\x06", $vZ);

            // Easiest way, but not sure if format changes
            //$this->Comment = substr($aE[1], 18);

            // Normal way
            $aP = unpack('x16/v1CL', $aE[1]);
            $this->Comment = substr($aE[1], 18, $aP['CL']);

            // Translates end of line from other operating systems
            $this->Comment = strtr($this->Comment, array("\r\n" => "\n",
                                                         "\r"   => "\n"));
// 2003-12-02 - HB <

            // Cut the entries from the central directory
            $aE = explode("\x50\x4b\x01\x02", $vZ);
            // Explode to each part
            $aE = explode("\x50\x4b\x03\x04", $aE[0]);
            // Shift out spanning signature or empty entry
            array_shift($aE);

            // Loop through the entries
            foreach($aE as $vZ) {
                $aI = array();
                $aI['E']  = 0;
                $aI['EM'] = '';
                // Retrieving local file header information
                $aP = unpack('v1VN/v1GPF/v1CM/v1FT/v1FD/V1CRC/V1CS/V1UCS/v1FNL', $vZ);
                // Check if data is encrypted
                $bE = ($aP['GPF'] && 0x0001) ? true : false;
                $nF = $aP['FNL'];

                // Special case : value block after the compressed data
                if($aP['GPF'] & 0x0008) {
                    $aP1 = unpack('V1CRC/V1CS/V1UCS', substr($vZ, -12));

                    $aP['CRC'] = $aP1['CRC'];
                    $aP['CS']  = $aP1['CS'];
                    $aP['UCS'] = $aP1['UCS'];

                    $vZ = substr($vZ, 0, -12);
                }

                // Getting stored filename
                $aI['N'] = substr($vZ, 26, $nF);

                if(substr($aI['N'], -1) == '/') {
                    // is a directory entry - will be skipped
                    continue;
                }

                // Truncate full filename in path and filename
                $aI['P'] = dirname($aI['N']);
                $aI['P'] = $aI['P'] == '.' ? '' : $aI['P'];
                $aI['N'] = basename($aI['N']);

                $vZ = substr($vZ, 26 + $nF);

                if(strlen($vZ) != $aP['CS']) {
                  $aI['E']  = 1;
                  $aI['EM'] = 'Compressed size is not equal with the value in header information.';
                } else {
                    if($bE) {
                        $aI['E']  = 5;
                        $aI['EM'] = 'File is encrypted, which is not supported from this class.';
                    } else {
                        switch($aP['CM']) {
                            case 0: // Stored
                                // Here is nothing to do, the file ist flat.
                                break;

                            case 8: // Deflated
                                $vZ = gzinflate($vZ);
                                break;

                            case 12: // BZIP2
// 2003-12-02 - HB >
                                if(! extension_loaded('bz2')) {
                                    if(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                                      @dl('php_bz2.dll');
                                    } else {
                                      @dl('bz2.so');
                                    }
                                }

                                if(extension_loaded('bz2')) {
// 2003-12-02 - HB <
                                    $vZ = bzdecompress($vZ);
// 2003-12-02 - HB >
                                } else {
                                    $aI['E']  = 7;
                                    $aI['EM'] = "PHP BZIP2 extension not available.";
                                }
// 2003-12-02 - HB <

                                break;

                            default:
                              $aI['E']  = 6;
                              $aI['EM'] = "De-/Compression method {$aP['CM']} is not supported.";
                        }

// 2003-12-02 - HB >
                        if(! $aI['E']) {
// 2003-12-02 - HB <
                            if($vZ === false) {
                                $aI['E']  = 2;
                                $aI['EM'] = 'Decompression of data failed.';
                            } else {
                                if(strlen($vZ) != $aP['UCS']) {
                                    $aI['E']  = 3;
                                    $aI['EM'] = 'Uncompressed size is not equal with the value in header information.';
                                } else {
                                    if(crc32($vZ) != $aP['CRC']) {
                                        $aI['E']  = 4;
                                        $aI['EM'] = 'CRC32 checksum is not equal with the value in header information.';
                                    }
                                }
                            }
// 2003-12-02 - HB >
                        }
// 2003-12-02 - HB <
                    }
                }

                $aI['D'] = $vZ;

                // DOS to UNIX timestamp
                $aI['T'] = mktime(($aP['FT']  & 0xf800) >> 11,
                                  ($aP['FT']  & 0x07e0) >>  5,
                                  ($aP['FT']  & 0x001f) <<  1,
                                  ($aP['FD']  & 0x01e0) >>  5,
                                  ($aP['FD']  & 0x001f),
                                  (($aP['FD'] & 0xfe00) >>  9) + 1980);

                $this->Entries[] = &new SimpleUnzipEntry($aI);
            } // end for each entries

            return $this->Entries;
	} // end of the 'ReadFile()' method
} // end of the 'SimpleUnzip' class

class SimpleUnzipEntry {
        var $Data = '';

        var $Error = 0;

        var $ErrorMsg = '';

        var $Name = '';

        var $Path = '';

        var $Time = 0;

        function SimpleUnzipEntry($in_Entry) {
		$this->Data     = $in_Entry['D'];
		$this->Error    = $in_Entry['E'];
		$this->ErrorMsg = $in_Entry['EM'];
		$this->Name     = $in_Entry['N'];
		$this->Path     = $in_Entry['P'];
		$this->Time     = $in_Entry['T'];
        } // end of the 'SimpleUnzipEntry' constructor
} // end of the 'SimpleUnzipEntry' class

?>