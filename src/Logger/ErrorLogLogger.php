<?php
namespace Cubex\Logger;

use Packaged\Context\ContextAwareTrait;
use Packaged\Context\WithContextTrait;
use Packaged\Helpers\Strings;
use Packaged\Helpers\ValueAs;
use Psr\Log\AbstractLogger;

class ErrorLogLogger extends AbstractLogger
{
  use ContextAwareTrait;
  use WithContextTrait;

  protected const MAX_LINE_LENGTH = 800;

  /**
   * @param string $level
   * @param string $message
   * @param array  $context
   *
   * @throws \Exception
   */
  public function log($level, string|\Stringable $message, array $context = []): void
  {
    $requestId = $this->hasContext() ? $this->getContext()->id() : Strings::randomString(10);
    $now = \DateTime::createFromFormat('U.u', sprintf("%.6F", microtime(true)));
    $prefix = $now->format('H:i:s.v') . ' ' . strtoupper($level) . ':';
    $prefix .= ' [' . $requestId . ']';
    $maxLineLen = self::MAX_LINE_LENGTH - (strlen($prefix) + 2);
    $n = 0;
    $lines = explode("\n", $message);
    foreach($context as $k => $v)
    {
      try
      {
        Strings::stringable($v);
        $val = ValueAs::string($v);
        if($val !== '')
        {
          $lines[] = "$k = $val";
        }
      }
      catch(\Exception $e)
      {
      }
    }
    foreach($lines as $line)
    {
      foreach(str_split($line, $maxLineLen) as $linePart)
      {
        error_log($prefix . '-' . $n . ' ' . $linePart);
        $n++;
      }
    }
  }
}
