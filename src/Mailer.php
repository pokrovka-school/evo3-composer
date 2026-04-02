<?php
namespace ProjectSoft;

use DocumentParser;
use APIhelpers;
use PHPMailer\PHPMailer\Exception as phpmailerException;

class Mailer {
	
	private $modx;
	
	public function __construct (DocumentParser $modx, array $cfg = [])
	{
		$this->modx = $modx;
	}

}