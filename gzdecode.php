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
	
	function parse()
	{
		if ($this->compressed_size >= $this->min_compressed_size)
		{
			// Check ID1, ID2, and CM
			if (substr($data, 0, 3) !== "\x1F\x8B\x08")
			{
				return false;
			}
			
			// Get the FLG (FLaGs)
			$this->flags = ord($data[3]);
		
			// FLG bits above four are reserved
			if ($flg > 0x1F)
			{
				return false;
			}
			
			$this->position += 4;
			
			if (!$this->mtime())
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}