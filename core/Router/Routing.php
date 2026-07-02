<?php
namespace Core\Router;

use Core\Attributes\Router;
use Core\Config;
use Core\Router\Exception\RoutingException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;

/**
 * @author Mateus silva do nascimento <s.mateus.d.n@gmail.com>
 */
class Routing extends Config
{
    /**
     * @param string $dir
     * @param array $routes
     * @param array $controllers
     */
    public function __construct(
        private string $dir = __DIR__ . "/../../app/Controllers/",
        public array $routes = [],
        private array $controllers = []
    ) {
    }

    /**
     * @return void
     * @throws RoutingException|ReflectionException
     */
    public function run(): void
    {
        $this->scand()->attributes();
        $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!isset($this->routes[$url])) {
            throw new RoutingException("O servidor não consegue encontrar o recurso solicitado.");
        }
        if (!isset($this->routes[$url][$method])){
            throw new RoutingException("O método de requisição é conhecido pelo servidor, mas não é suportado pelo recurso de destino.O método de requisição é conhecido pelo servidor, mas não é suportado pelo recurso de destino.");
        }
    }

    /**
     * @return static
     * @throws RoutingException
     */
    public function scand(): static
    {
        if (is_dir($this->dir)) {
            $diretorio = new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS);
            $arquivos = new RecursiveIteratorIterator($diretorio, RecursiveIteratorIterator::SELF_FIRST);
            foreach ($arquivos as $item) {
                if ($item->isFile() && $item->getExtension() === 'php') {
                    $caminhoPasta = str_replace('\\', '/', $item->getPath());

                    $partesCaminho = explode('app/', $caminhoPasta);
                    $caminhoRelativo = 'app/' . $partesCaminho[1];

                    $namespaceBase = str_replace('/', '\\', $caminhoRelativo);

                    $namespaceFormatado = ucfirst($namespaceBase);

                    $nomeClasse = $item->getBasename('.php');

                    $classeCompleta = $namespaceFormatado . '\\' . $nomeClasse;

                    $this->controllers[] = $classeCompleta;
                }
            }
        } else {
            throw new RoutingException("Diretório app/Controllers não existe");
        }
        return $this;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function attributes(): void
    {
        foreach ($this->controllers as $controller) {
            $reflection = new ReflectionClass(new $controller);

            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(Router::class);

                if (empty($attributes)) {
                    continue;
                }

                foreach ($attributes as $attribute) {
                    $router = $attribute->newInstance();

                    $uri = $router->uri ?? $router->path ?? '';
                    $methodHttp = strtoupper($router->method);

                    $this->routes[$uri][$methodHttp] = [
                        'controller' => $reflection->getName(),
                        'method' => $method->getName()
                    ];
                }
            }
        }
    }

}