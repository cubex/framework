<?php
namespace Cubex\View;

use Cubex\Kernel\CubexKernel;

class TemplatedView extends TemplatedViewModel
{
  public function __construct(CubexKernel $callee, $template)
  {
    $this->_callingClass = $callee;
    $this->_calculateTemplateDefaults();
    $this->_templateFile = $template;
  }
}
