Cubex Framework [![Build Status](https://travis-ci.org/cubex/framework.png?branch=master)](https://travis-ci.org/cubex/framework)
=========


##Configuration Options

Config options can be set in your config file of choice, the examples below are ini based examples.
The ini group represents a config section, and the values represent the config items in that section.

###Define your project entry kernel
Instance Of: \Cubex\Kernel\CubexKernel

    [kernel]
    default = \YourNamespace\YourProject


###Enable or disable gzip compression by setting

    [response]
    gzip = bool

###Changing the default router
Instance Of: \Cubex\Routing\IRouter

    [routing]
    router = \Cubex\Routing\Router

###Setting your standard 404 error
Instance Of: \Symfony\Component\HttpFoundation\Response

    [errors]
    404 = \Cubex\Responses\Error404Response
