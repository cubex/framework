<?php
namespace Cubex\View;

abstract class TemplatedViewModel extends ViewModel
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

    $this->preRender();
    ob_start();
    try
    {
      include $tpl;
    }
    catch(\Exception $e)
    {
      ob_end_clean();
      throw $e;
    }
    return ob_get_clean();
  }

  /**
   * Hook for pre-render, allowing resource injection etc
   */
  public function preRender()
  {
  }
}
