<?php
namespace Core\Router;

use Core\Attributes\Router;
use Core\Config;
use ReflectionClass;

/**
 * Resumo de Routing
 * @method void scand() Faz uma varredura na pasta app/Controllers listando todos os controllers e atribuir a $controllers
 * @method void attributes() Pegar todas as rotas registradas nos controllers e atribuir a $routes
 * @method void generateCache() Pegar as rotas atribuidas a $this->routes e gerar um arquivo cache de rotas no diretorio core/Cache
 */
class Routing extends Config
{
    public function __construct(
        private string $dir = __DIR__ . "/../../app/Controllers/",
        public array $routes = [],
        private array $controllers = []
    ) {
    }
    public function run()
    {
        if (self::APP_PRODUCTION) {
            if (!file_exists('/../Cache/rotas.php')) {
                throw new \Exception("Cache de rotas não existe");
            }
            exit;
        }
        $this->scand()->attributes();
        dump($this->routes);
    }

    private function generateCache()
    {

    }
    public function scand()
    {
        if (is_dir($this->dir)) {
            $diretorio = new \RecursiveDirectoryIterator($this->dir, \RecursiveDirectoryIterator::SKIP_DOTS);
            $arquivos = new \RecursiveIteratorIterator($diretorio, \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($arquivos as $item) {
                if ($item->isFile() && $item->getExtension() === 'php') {
                    $caminhoPasta = str_replace('\\', '/', $item->getPath());

                    // Isola a estrutura a partir de 'app/'
                    $partesCaminho = explode('app/', $caminhoPasta);
                    $caminhoRelativo = 'app/' . $partesCaminho[1]; // Ex: app/Controllers/Api

                    //Converte o caminho em formato de Namespace (Ex: app\Controllers\Api)
                    $namespaceBase = str_replace('/', '\\', $caminhoRelativo);

                    //Se o projeto usa "App" com maiúscula no autoload (PSR-4), ajustamos aqui:
                    $namespaceFormatado = ucfirst($namespaceBase);

                    //Pega o nome da classe (Nome do arquivo sem o .php)
                    $nomeClasse = $item->getBasename('.php');

                    //Monta a Classe Completa com o Namespace (Fully Qualified Class Name)
                    $classeCompleta = $namespaceFormatado . '\\' . $nomeClasse;

                    // Armazena a classe completa para uso posterior
                    $this->controllers[] = $classeCompleta;
                }
            }
        } else {
            throw new \Exception("Diretório app/Controllers não existe");
        }
        return $this;
    }

    public function attributes()
    {
        foreach ($this->controllers as $controller) {
            $reflection = new ReflectionClass(new $controller);
            $possuiAlgumaRota = false;

            foreach ($reflection->getMethods() as $method) {
                // 1. Busca APENAS os atributos do tipo Router aplicados neste método específico
                $attributes = $method->getAttributes(Router::class);

                // 2. Se o método não tiver esse atributo, pula para o próximo método
                if (empty($attributes)) {
                    continue;
                }

                // Se chegou aqui, encontramos ao menos uma rota válida no Controller
                $possuiAlgumaRota = true;

                foreach ($attributes as $attribute) {
                    $router = $attribute->newInstance();

                    // CORREÇÃO: Certifique-se de usar os mesmos nomes de propriedades da sua classe Router ($router->path ou $router->uri)
                    $uri = $router->uri ?? $router->path ?? '';

                    $this->routes[$uri][$router->method] = [
                        'controller' => $reflection->getName(),
                        'method' => $method->getName()
                    ];
                }
            }

            // 3. Validação opcional: Se após ler todos os métodos nenhum tinha rotas, dispara o erro
            //if (!$possuiAlgumaRota) {
            //    throw new \Exception("A classe {$reflection->getName()} não possui nenhum método configurado com o atributo Router.");
            //}
        }
    }

}