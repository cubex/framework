<?php
namespace Cubex\Console;

use Cubex\Cubex;
use Cubex\ICubexAware;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag\ParamTag;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extended Command to support public properties for arguments
 */
abstract class ConsoleCommand extends Command implements ICubexAware
{
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

    //Always ensure the command has a name
    if($this->getName() === null)
    {
      $this->setName(strtolower(class_basename(get_called_class())));
    }

    if($this->getDescription() === null)
    {
      $description = head($docBlock->getTagsByName('description'));
      if($description)
      {
        $this->setDescription($description->getDescription());
      }
      else
      {
        $this->setDescription($docBlock->getShortDescription());
      }
    }

    $argsAdded = $this->createFromActionableMethod($reflect);
    $this->createOptionsFromPublic($reflect, $argsAdded);
  }

  /**
   * Create arguments from your executeCommand method parameters
   *
   * @param \ReflectionClass $class
   *
   * @return bool|null
   */
  protected function createFromActionableMethod(\ReflectionClass $class)
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

    $propBlock    = new DocBlock($method);
    $descriptions = [];
    foreach($propBlock->getTags() as $tag)
    {
      if($tag instanceof ParamTag)
      {
        $tagName = substr($tag->getVariableName(), 1);
        $descriptions[$tagName] = $tag->getDescription();
      }
    }

    foreach($method->getParameters() as $paramNum => $parameter)
    {
      //Skip over the input and output args for executeCommand
      if($paramNum < 2 && $methodName == 'executeCommand')
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
    $this->_input  = $input;
    $this->_output = $output;

    $params = $input->getArguments();
    //Strip off the command name
    array_shift($params);

    //Call the execute command with $input and $output as the first args
    if(method_exists($this, 'executeCommand'))
    {
      array_unshift($params, $input, $output);
      return call_user_func_array([$this, 'executeCommand'], $params);
    }

    //Call the process method, without $input and $output
    if(method_exists($this, 'process'))
    {
      return call_user_func_array([$this, 'process'], $params);
    }

    throw new \RuntimeException(
      "Your command must contain one of the following methods:\n" .
      "process()\n" .
      'executeCommand(InputInterface $input, OutputInterface $output)' . "\n",
      500
    );
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

  /**
   * Set the cubex application
   *
   * @param Cubex $app
   *
   * @throws \Exception
   */
  public function setCubex(Cubex $app)
  {
    throw new \Exception("Cubex is controlled by the application");
  }
}
