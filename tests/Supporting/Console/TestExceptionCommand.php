<?php
namespace Cubex\Tests\Supporting\Console;

use Cubex\Console\ConsoleCommand;
use Exception;

class TestExceptionCommand extends ConsoleCommand
{
  /**
   * @throws Exception
   */
  public function process()
  {
    throw new Exception("Exception Command", 500);
  }
}
