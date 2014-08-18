<?php
namespace Cubex\View;

class MustacheViewModel extends ViewModel
{
  /**
   * Build the view response with the relevant template file
   *
   * @return string
   * @throws \Exception
   */
  public function render()
  {
    $tpl = $this->getTemplatePath('.phtml');
    if(!file_exists($tpl))
    {
      throw new \Exception("The template file '$tpl' does not exist", 404);
    }

    return (new \Mustache_Engine())->render(file_get_contents($tpl), $this);
  }
}
