<?php
namespace Cubex\Context;

trait ContextAwareTrait
{
  private $_context;

  /**
   * @return Context|null
   */
  public function getContext(): ?Context
  {
    return $this->_context;
  }

  /**
   * @param Context $context
   *
   * @return ContextAwareTrait
   */
  public function setContext(Context $context)
  {
    $this->_context = $context;
    return $this;
  }

  /**
   * @return $this
   */
  public function clearContext()
  {
    $this->_context = null;
    return $this;
  }

  public function hasContext()
  {
    return $this->_context !== null;
  }

}
