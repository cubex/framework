<?php
namespace Cubex\View;

use Cubex\Http\Response;
use Cubex\Kernel\ControllerKernel;
use Illuminate\Support\Contracts\RenderableInterface;

abstract class LayoutController extends ControllerKernel
{
  /**
   * @var Layout
   */
  protected $_layout;

  /**
   * Disable rendering of the layout, and return the content only
   *
   * @var bool
   */
  protected $_disableLayout = false;

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
   * Check to see if the layout will be rendered
   *
   * @return bool
   */
  public function isLayoutDisabled()
  {
    return (bool)$this->_disableLayout;
  }

  /**
   * Disable rendering of the layout
   *
   * @return $this
   */
  public function disableLayout()
  {
    $this->_disableLayout = true;
    return $this;
  }

  /**
   * Re-enable layout rendering
   *
   * @return $this
   */
  public function enableLayout()
  {
    $this->_disableLayout = false;
    return $this;
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
    $response = $this->_sanitizeResponse($response);

    if($response instanceof RenderableInterface)
    {
      if($this->isLayoutDisabled())
      {
        return $response;
      }

      $this->layout()->insert($this->_contentName, $response);
      return new Response($this->layout());
    }

    //Convert captured responses into renderable content objects
    if($response === null)
    {
      if($this->isLayoutDisabled())
      {
        return new Renderable($capturedOutput);
      }

      $this->layout()->insert(
        $this->_contentName,
        new Renderable($capturedOutput)
      );
      return new Response($this->layout());
    }

    //Scalars should be assumed as content, and converted to renderables
    if(is_scalar($response))
    {
      if($this->isLayoutDisabled())
      {
        return new Renderable($response);
      }

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
