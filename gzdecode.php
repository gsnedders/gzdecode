<?php

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
			
			// Parse the MTIME
			if (!$this->mtime())
			{
				return false;
			}
			
			// Get the XFL (eXtra FLags)
			$this->XFL = ord($this->compressed_data[$this->position++]);
		
			// Get the OS (Operating System)
			$this->OS = ord($this->compressed_data[$this->position++]);
			
			// Parse the FEXTRA
			if (!$this->fextra())
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	
	private function mtime()
	{
		// Endianness
		static $big_endian = (current(unpack('S', "\x00\x01")) === 1) ? true : false;
		
		// MTIME
		$mtime = substr($this->compressed_data, $this->position, 4);
		if ($big_endian)
		{
			$mtime = strrev($mtime);
		}
		$this->MITME = current(unpack('l', $mtime));
		$this->position += 4;
		
		// Nothing can fail
		return true;
	}
	
	private function fextra()
	{
		if ($this->flags & 4)
		{
			// Read subfield IDs
			$this->SI1 = $this->compressed_data[$this->position++];
			$this->SI2 = $this->compressed_data[$this->position++];
			
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
		
		// Either there is no extra field or it is valid
		return true;
	}
	
	private function fname()
	{
		if ($this->flags & 8)
		{
			// Get the length of the extra field
			$len = strspn($this->compressed_data, "\x00", $this->position)));
			
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
		
		// Either there is no filename or it is valid
		return true;
	}