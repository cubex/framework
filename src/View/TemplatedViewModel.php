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
    ob_start();
    try
    {
      include $this->getTemplatePath('.phtml');
    }
    catch(\Exception $e)
    {
      ob_end_clean();
      throw $e;
    }
    return ob_get_clean();
  }
}
