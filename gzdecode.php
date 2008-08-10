<?php
/**
 * Class to decode a GZIP stream in PHP 5
 *
 * The MIT License
 *
 * Copyright (c) 2008 Geoffrey Sneddon
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package gzdecode
 * @version 1.0-dev
 * @copyright 2008 Geoffrey Sneddon
 * @author Geoffrey Sneddon
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
*/

/**
 * gzdecode
 *
 * @package gzdecode
 */
class gzdecode
{
	/**
	 * Compressed data
	 *
	 * @see gzdecode::$data
	 */
	private $compressed_data;
	
	/**
	 * Size of compressed data
	 */
	private $compressed_size;
	
	/**
	 * Minimum size of a valid gzip string
	 */
	private $min_compressed_size = 18;
	
	/**
	 * Current position of pointer
	 */
	private $position = 0;
	
	/**
	 * Flags (FLG)
	 */
	private $flags;
	
	/**
	 * Uncompressed data
	 *
	 * @see gzdecode::$compressed_data
	 */
	public $data;
	
	/**
	 * Modified time
	 */
	public $MTIME;
	
	/**
	 * Extra Flags
	 */
	public $XFL;
	
	/**
	 * Operating System
	 */
	public $OS;
	
	/**
	 * Subfield ID 1
	 *
	 * @see gzdecode::$extra_field
	 * @see gzdecode::$SI2
	 */
	public $SI1;
	
	/**
	 * Subfield ID 2
	 *
	 * @see gzdecode::$extra_field
	 * @see gzdecode::$SI1
	 */
	public $SI2;
	
	/**
	 * Extra field content
	 *
	 * @see gzdecode::$SI1
	 * @see gzdecode::$SI2
	 */
	public $extra_field;
	
	/**
	 * Original filename
	 */
	public $filename;
	
	/**
	 * Human readable comment
	 */
	public $comment;
	
	/**
	 * Don't allow anything to be set
	 */
	public function __set($name, $value)
	{
		trigger_error("Cannot write property $name", E_USER_ERROR);
	}
	
	/**
	 * Set the compressed string and related properties
	 */
	public function __construct($data)
	{
		$this->compressed_data = $data;
		$this->compressed_size = strlen($data);
	}
	
	public function parse()
	{
		if ($this->compressed_size >= $this->min_compressed_size)
		{
			// Check ID1, ID2, and CM
			if (substr($this->compressed_data, 0, 3) !== "\x1F\x8B\x08")
			{
				return false;
			}
			
			// Get the FLG (FLaGs)
			$this->flags = ord($this->compressed_data[3]);
		
			// FLG bits above (1 << 4) are reserved
			if ($flg > 0x1F)
			{
				return false;
			}
			
			// Advance the pointer after the above
			$this->position += 4;
			
			// MTIME
			$mtime = substr($this->compressed_data, $this->position, 4);
			// Reverse the string if we're on a big-endian arch because l is the only signed long and is machine endianness
			if (current(unpack('S', "\x00\x01")) === 1)
			{
				$mtime = strrev($mtime);
			}
			$this->MITME = current(unpack('l', $mtime));
			$this->position += 4;
			
			// Get the XFL (eXtra FLags)
			$this->XFL = ord($this->compressed_data[$this->position++]);
		
			// Get the OS (Operating System)
			$this->OS = ord($this->compressed_data[$this->position++]);
			
			// Parse the FEXTRA
			if ($this->flags & 4)
			{
				// Read subfield IDs
				$this->SI1 = $this->compressed_data[$this->position++];
				$this->SI2 = $this->compressed_data[$this->position++];
				
				// SI2 set to zero is reserved for future use
				if ($this->SI2 === "\x00")
				{
					return false;
				}
				
				// Get the length of the extra field
				$len = current(unpack('v', substr($this->compressed_data, $this->position, 2)));
				$position += 2;
				
				// Check the length of the string is still valid
				$this->min_compressed_size += $len + 4;
				if ($this->compressed_size >= $this->min_compressed_size)
				{
					// Set the extra field to the given data
					$this->extra_field = substr($this->compressed_data, $this->position, $len);
					$this->position += $len;
				}
				else
				{
					return false;
				}
			}
			
			// Parse the FNAME
			if ($this->flags & 8)
			{
				// Get the length of the filename
				$len = strspn($this->compressed_data, "\x00", $this->position);
				
				// Check the length of the string is still valid
				$this->min_compressed_size += $len + 1;
				if ($this->compressed_size >= $this->min_compressed_size)
				{
					// Set the original filename to the given string
					$this->filename = substr($this->compressed_data, $this->position, $len);
					$this->position += $len + 1;
				}
				else
				{
					return false;
				}
			}
			
			// Parse the FCOMMENT
			if ($this->flags & 16)
			{
				// Get the length of the comment
				$len = strspn($this->compressed_data, "\x00", $this->position);
				
				// Check the length of the string is still valid
				$this->min_compressed_size += $len + 1;
				if ($this->compressed_size >= $this->min_compressed_size)
				{
					// Set the original comment to the given string
					$this->comment = substr($this->compressed_data, $this->position, $len);
					$this->position += $len + 1;
				}
				else
				{
					return false;
				}
			}
			
			// Parse the FHCRC
			if ($this->flags & 2)
			{
				// Check the length of the string is still valid
				$this->min_compressed_size += $len + 2;
				if ($this->compressed_size >= $this->min_compressed_size)
				{
					// Read the CRC
					$crc = current(unpack('v', substr($this->compressed_data, $this->position, 2)));
					
					// Check the CRC matches
					if ((crc32(substr($this->compressed_data, 0, $this->position)) & 0xFFFF) === $crc)
					{
						$this->position += 2;
					}
					else
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}
			
			// Decompress the actual data
			if ($this->data = gzinflate(substr($this->compressed_data, $this->position, -8)) === false)
			{
				return false;
			}
			else
			{
				$this->position = $this->compressed_size - 8;
			}
			
			// Check CRC of data
			$crc = current(unpack('N', substr($this->compressed_data, $this->position, 4)));
			$this->position += 4;
			if (crc32($this->data) !== $crc)
			{
				return false;
			}
			
			// Check ISIZE of data
			$isize = current(unpack('N', substr($this->compressed_data, $this->position, 4)));
			$this->position += 4;
			if (strlen($this->data) & 0xFFFFFFFF !== $isize)
			{
				return false;
			}
			
			// Wow, against all odds, we've actually got a valid gzip string
			return true;
		}
		else
		{
			return false;
		}
	}
}