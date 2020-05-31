<?php
namespace Cubex\Context\Events;

use Packaged\Event\Events\AbstractEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLaunchedEvent extends AbstractEvent
{
  private $_input;
  private $_output;

  public function __construct(InputInterface $input = null, OutputInterface $output = null)
  {
    parent::__construct();
    $this->_input = $input;
    $this->_output = $output;
  }

  public function getType()
  {
    return static::class;
  }

  /**
   * @return InputInterface|null
   */
  public function getInput(): ?InputInterface
  {
    return $this->_input;
  }

  /**
   * @return OutputInterface|null
   */
  public function getOutput(): ?OutputInterface
  {
    return $this->_output;
  }

}
