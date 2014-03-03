<?php
namespace Cubex\Console;

use phpDocumentor\Reflection\DocBlock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extended Command to support public properties for arguments
 */
abstract class ConsoleCommand extends Command
{
  /**
   * Constructor.
   *
   * @param string|null $name The name of the command;
   *                          passing null means it must be set in configure()
   *
   * @throws \LogicException When the command name is empty
   *
   * @api
   */
  public function __construct($name = null)
  {
    $reflect  = new \ReflectionClass($this);
    $docBlock = new DocBlock($reflect);

    try
    {
      parent::__construct($name);
    }
    catch(\Exception $e)
    {
      if($docBlock->hasTag('name'))
      {
        $this->setName(
          head($docBlock->getTagsByName('name'))->getDescription()
        );
      }
    }

    if($this->getDescription() === null)
    {
      $description = head($docBlock->getTagsByName('description'));
      if($description)
      {
        $this->setDescription($description->getDescription());
      }
    }

    $argsAdded = $this->createFromExecuteCommandMethod($reflect);
    $this->createOptionsFromPublic($reflect, $argsAdded);

    if($this->getName() === null)
    {
      throw new \LogicException('The command name cannot be empty.');
    }
  }

  /**
   * Create arguments from your executeCommand method parameters
   *
   * @param \ReflectionClass $class
   *
   * @return bool|null
   */
  protected function createFromExecuteCommandMethod(\ReflectionClass $class)
  {
    $method = $class->getMethod('executeCommand');
    if($method->class == 'Cubex\Console\ConsoleCommand')
    {
      return null;
    }

    $addedArguments = false;

    $propBlock    = new DocBlock($method);
    $descriptions = [];
    foreach($propBlock->getTags() as $tag)
    {
      /**
       * @var $tag \phpDocumentor\Reflection\DocBlock\Tag\ParamTag
       */
      $tagName                = substr($tag->getVariableName(), 1);
      $descriptions[$tagName] = $tag->getDescription();
    }

    foreach($method->getParameters() as $paramNum => $parameter)
    {
      //Skip over the input and output args
      if($paramNum < 2)
      {
        continue;
      }
      $mode    = InputArgument::REQUIRED;
      $default = null;
      if($parameter->isDefaultValueAvailable())
      {
        $mode    = InputArgument::OPTIONAL;
        $default = $parameter->getDefaultValue();
      }

      $description = '';
      if(isset($descriptions[$parameter->getName()]))
      {
        $description = $descriptions[$parameter->getName()];
      }

      $this->addArgument(
        $parameter->getName(),
        $parameter->isArray() ? $mode | InputArgument::IS_ARRAY : $mode,
        $description,
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
   * @param                  $argsAdded
   *
   * @return null
   */
  protected function createOptionsFromPublic(
    \ReflectionClass $class, $argsAdded
  )
  {
    $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
    if(empty($properties))
    {
      return null;
    }
    foreach($properties as $property)
    {
      $propBlock = new DocBlock($property);

      $short       = null;
      $description = $propBlock->getShortDescription();
      $mode        = InputOption::VALUE_OPTIONAL;

      if($propBlock->hasTag('short'))
      {
        $short = head($propBlock->getTagsByName('short'))->getDescription();
      }

      if($propBlock->hasTag('description'))
      {
        $description = head(
          $propBlock->getTagsByName('description')
        )->getDescription();
      }

      if($propBlock->hasTag('required') && $argsAdded !== true)
      {
        $this->addArgument(
          $property->getName(),
          InputArgument::REQUIRED,
          $description
        );
        continue;
      }

      if($propBlock->hasTag('valuerequired'))
      {
        $mode = InputOption::VALUE_REQUIRED;
      }

      if($propBlock->hasTag('flag'))
      {
        $mode = InputOption::VALUE_NONE;
      }

      $this->addOption(
        $property->getName(),
        $short,
        $mode,
        $description,
        $property->getValue($this)
      );
    }
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
    $params = $input->getArguments();
    array_shift($params);
    array_unshift($params, $input, $output);
    return call_user_func_array([$this, 'executeCommand'], $params);
  }

  /**
   * Extend this method if you wish to define your
   * input arguments with method parameters
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @throws \RuntimeException
   */
  protected function executeCommand(
    InputInterface $input, OutputInterface $output
  )
  {
    throw new \RuntimeException("This command has nothing to do");
  }

  /**
   * Gets the application instance for this command.
   *
   * @return Console An Application instance
   *
   * @api
   */
  public function getApplication()
  {
    return parent::getApplication();
  }

  /**
   * @return \Cubex\Cubex
   */
  public function getCubex()
  {
    return $this->getApplication()->getCubex();
  }
}
