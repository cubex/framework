<?php
namespace Cubex\Console;

use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Packaged\DocBlock\DocBlockParser;
use Packaged\Helpers\ValueAs;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extended Command to support public properties for arguments
 */
abstract class ConsoleCommand extends Command implements ContextAware
{
  use ContextAwareTrait;

  /**
   * @var InputInterface
   */
  protected $_input;
  /**
   * @var OutputInterface
   */
  protected $_output;

  /**
   * Constructor.
   *
   * @param string|null $name The name of the command;
   *                          passing null means it must be set in configure()
   *
   * @throws \LogicException When the command name is empty
   * @throws \ReflectionException
   *
   */
  public function __construct($name = null)
  {
    $reflect = new \ReflectionClass($this);
    $docBlock = new DocBlockParser($reflect->getDocComment());

    $names = [
      $name,
      $docBlock->hasTag('name') ? $docBlock->getTag('name') : null,
      strtolower(basename(str_replace('\\', '/', get_called_class()))),
    ];

    parent::__construct(ValueAs::nonempty(...$names));

    if($this->getDescription() === null)
    {
      $description = $docBlock->getTag('description');
      if($description)
      {
        $this->setDescription($description);
      }
      else
      {
        $this->setDescription($docBlock->getSummary());
      }
    }

    $this->_createFromActionableMethod($reflect);
    $this->_createOptionsFromPublic($reflect);
  }

  /**
   * Create arguments from your executeCommand method parameters
   *
   * @param \ReflectionClass $class
   *
   * @return bool|null
   * @throws \ReflectionException
   */
  protected function _createFromActionableMethod(\ReflectionClass $class)
  {
    if($class->hasMethod('executeCommand'))
    {
      $methodName = 'executeCommand';
    }
    else if($class->hasMethod('process'))
    {
      $methodName = 'process';
    }
    else
    {
      return null;
    }

    $method = $class->getMethod($methodName);

    $addedArguments = false;

    $propBlock = new DocBlockParser($method->getDocComment());
    $descriptions = [];
    foreach($propBlock->getTags() as $name => $description)
    {
      $tagName = $name;
      $descriptions[$tagName] = $description;
    }

    foreach($method->getParameters() as $paramNum => $parameter)
    {
      //Skip over the input and output args for executeCommand
      if($paramNum < 2 && $methodName == 'executeCommand')
      {
        continue;
      }
      $mode = InputArgument::REQUIRED;
      $default = null;
      if($parameter->isDefaultValueAvailable())
      {
        $mode = InputArgument::OPTIONAL;
        $default = $parameter->getDefaultValue();
      }

      $this->addArgument(
        $parameter->name,
        $parameter->isArray() ? $mode | InputArgument::IS_ARRAY : $mode,
        isset($descriptions[$parameter->name]) ? $descriptions[$parameter->name] : '',
        $default
      );

      $addedArguments = true;
    }

    return $addedArguments;
  }

  /**
   * Create options and arguments from the public properties on your command
   *
   * @param \ReflectionClass $class
   *
   * @return null
   */
  protected function _createOptionsFromPublic(\ReflectionClass $class)
  {
    $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
    if(empty($properties))
    {
      return null;
    }
    foreach($properties as $property)
    {
      $propBlock = new DocBlockParser($property->getDocComment());
      $short = null;
      $description = $propBlock->getSummary();
      $mode = InputOption::VALUE_OPTIONAL;
      if($propBlock->hasTag('short'))
      {
        $short = $propBlock->getTag('short');
      }
      if($propBlock->hasTag('description'))
      {
        $description = $propBlock->getTag('description');
      }
      if($propBlock->hasTag('valuerequired'))
      {
        $mode = InputOption::VALUE_REQUIRED;
      }
      if($propBlock->hasTag('flag'))
      {
        $mode = InputOption::VALUE_NONE;
      }
      $this->addOption($property->name, $short, $mode, $description, $property->getValue($this));
    }
    return null;
  }

  /**
   * Make sure you call parent::initialize() when extending
   *
   * @inheritdoc
   */
  protected function initialize(InputInterface $input, OutputInterface $output)
  {
    foreach($input->getArguments() + $input->getOptions() as $arg => $value)
    {
      if(property_exists($this, $arg))
      {
        $this->$arg = $value;
      }
    }
  }

  /**
   * @inheritdoc
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int|mixed|null
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->_input = $input;
    $this->_output = $output;

    $params = $input->getArguments();
    //Strip off the command name
    array_shift($params);

    //Call the execute command with $input and $output as the first args
    if(method_exists($this, 'executeCommand'))
    {
      array_unshift($params, $input, $output);
      return $this->executeCommand(...$params);
    }

    //Call the process method, without $input and $output
    if(method_exists($this, 'process'))
    {
      return $this->process(...$params);
    }

    throw new \RuntimeException(
      "Your command must contain one of the following methods:\n" .
      "process()\n" .
      'executeCommand(InputInterface $input, OutputInterface $output)' . "\n",
      500
    );
  }
}
