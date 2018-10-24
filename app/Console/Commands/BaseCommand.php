<?php namespace App\Console\Commands;

use Illuminate\Console\Command;

class BaseCommand extends Command
{

	public function line($string, $style = null, $verbosity = null)
	{
		$string = date('[Y-m-d H:i:s] ') . $string;
		parent::line($string, $style, $verbosity);
	}

}
