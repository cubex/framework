<?php
namespace Cubex\View;

use Cubex\Http\Request;

/**
 * Include the correct phtml template for the view, but searching for
 * replacements and extensions based on the request information
 */
class BrandedTemplateView extends TemplatedViewModel
{
  /**
   * File pattern match for files which will be included before the template
   *
   * @var array
   */
  protected $_prependFiles = [
    '/_%LOCALE.%DOMAIN.%TLD.pre',
    '/%DOMAIN.%TLD.pre',
    '/_%LOCALE.%DOMAIN.pre',
    '/%DOMAIN.pre',
    '/pre',
  ];

  /**
   * File pattern match for files which will be included after the main template
   *
   * @var array
   */
  protected $_appendFiles = [
    '/_%LOCALE.%DOMAIN.%TLD.post',
    '/%DOMAIN.%TLD.post',
    '/_%LOCALE.%DOMAIN.post',
    '/%DOMAIN.post',
    '/post',
  ];

  /**
   * File pattern match for files which will replace the entire template
   *
   * @var array
   */
  protected $_replaceFiles = [
    '/_%LOCALE.%DOMAIN.%TLD',
    '/%DOMAIN.%TLD',
    '/_%LOCALE.%DOMAIN',
    '/%DOMAIN',
  ];

  /**
   * File pattern match for the default file to include
   *
   * @var array
   */
  protected $_standardFiles = [
    '/_%LOCALE',
    ''
  ];

  /**
   * @var Request
   */
  protected $_request;

  /**
   * Storage for micro optimisation
   *
   * @var array
   */
  protected $_requestReplacements;

  public function render()
  {
    ob_start();
    try
    {
      foreach($this->_processFiles() as $file)
      {
        include $file;
      }
    }
    catch(\Exception $e)
    {
      ob_end_clean();
      throw $e;
    }
    return ob_get_clean();
  }

  /**
   * Search for a list of files to process
   *
   * @return array
   */
  protected function _processFiles()
  {
    if($this->isCubexAvailable())
    {
      $this->_request = $this->getCubex()->make('request');
    }

    if($this->_request === null)
    {
      $this->_request = Request::createFromGlobals();
    }

    if($this->_requestReplacements === null)
    {
      $this->_requestReplacements = [
        $this->_request->subDomain(),
        $this->_request->domain(),
        $this->_request->tld(),
        strtolower(substr($this->_request->getLocale(), 0, 2))
      ];
    }

    $files = [];

    //Search for a full replacement file
    $replacements = $this->_locateFiles($this->_replaceFiles, null);
    if($replacements !== null)
    {
      return $replacements;
    }

    //Find prepending files
    $files = array_merge($files, $this->_locateFiles($this->_prependFiles));
    //Include the standard file
    $files = array_merge($files, $this->_locateFiles($this->_standardFiles));
    //Find appending files
    $files = array_merge($files, $this->_locateFiles($this->_appendFiles));

    return $files;
  }

  /**
   * Search the filesystem for files matching the array of patterns
   *
   * @param       $array
   * @param array $default
   *
   * @return array
   */
  protected function _locateFiles($array, $default = array())
  {
    foreach($array as $variant)
    {
      $variant = str_replace(
        [
          '%SUBDOMAIN',
          '%DOMAIN',
          '%TLD',
          '%LOCALE'
        ],
        $this->_requestReplacements,
        $variant
      );

      $file = $this->getTemplatePath($variant . '.phtml');

      if(file_exists($file))
      {
        return [$file];
      }
    }
    return $default;
  }
}
