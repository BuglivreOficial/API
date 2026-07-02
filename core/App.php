<?php
namespace Core;

use Core\Router\Routing;
use Exception;

/**
 * @author Mateus silva do nascimento <s.mateus.d.n@gmail.com>
 */
class App extends Config
{
    /**
     * @throws Exception
     */
    public function init(): void
    {
        (new Routing())->run();
    }
}