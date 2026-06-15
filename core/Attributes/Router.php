<?php
namespace Core\Attributes;

use Attribute;

#[Attribute]
class Router
{
    public function __construct(
        public string $uri,
        public string $method
    ) {
    }
}