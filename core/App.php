<?php
namespace Core;

use Core\Router\Routing;

class App
{
    public function init()
    {
        (new Routing())->run();

        ///// TESTE /////

    }
}