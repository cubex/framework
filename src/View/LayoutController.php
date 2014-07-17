<?php
namespace Cubex\View;

use Cubex\Http\Response;
use Cubex\ICubexAware;
use Cubex\Kernel\ControllerKernel;
use Illuminate\Support\Contracts\RenderableInterface;

abstract class LayoutController extends ControllerKernel
{
  /**
   * @var Layout
   */
  protected $_layout;

  /**
   * Name of the area in your layout to insert the view model response
   *
   * @var string
   */
  protected $_contentName = 'content';

  /**
   * Set a custom layout for this controller
   *
   * @param Layout $layout
   *
   * @return $this
   */
  public function setLayout(Layout $layout)
  {
    $this->_layout = $layout;
    return $this;
  }

  /**
   * @return Layout
   */
  public function layout()
  {
    if($this->_layout === null)
    {
      $this->_layout = new Layout($this);
      $this->bindCubex($this->_layout);
    }

    return $this->_layout;
  }

  public function __toString()
  {
    return $this->layout()->render();
  }

  /**
   * Capture and view model responses and insert them into the layout
   *
   * @param $response
   * @param $capturedOutput
   *
   * @return Response|null
   */
  public function handleResponse($response, $capturedOutput)
  {
    if($response instanceof ICubexAware)
    {
      $this->bindCubex($response);
    }

    if($response instanceof RenderableInterface)
    {
      $this->layout()->insert($this->_contentName, $response);
      return new Response($this->layout());
    }

    //Convert captured responses into renderable content objects
    if($response === null)
    {
      $this->layout()->insert(
        $this->_contentName,
        new Renderable($capturedOutput)
      );
      return new Response($this->layout());
    }

    //Scalars should be assumed as content, and converted to renderables
    if(is_scalar($response))
    {
      $this->layout()->insert(
        $this->_contentName,
        new Renderable($response)
      );
      return new Response($this->layout());
    }

    //Let the kernel pickup any unhandled responses
    return parent::handleResponse($response, $capturedOutput);
  }
}
