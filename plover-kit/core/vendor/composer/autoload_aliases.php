<?php

// Functions and constants

namespace {

}


namespace Plover\Core {

    use BrianHenryIE\Strauss\Types\AutoloadAliasInterface;

    /**
     * @see AutoloadAliasInterface
     *
     * @phpstan-type ClassAliasArray array{'type':'class',isabstract:bool,classname:string,namespace?:string,extends:string,implements:array<string>}
     * @phpstan-type InterfaceAliasArray array{'type':'interface',interfacename:string,namespace?:string,extends:array<string>}
     * @phpstan-type TraitAliasArray array{'type':'trait',traitname:string,namespace?:string,use:array<string>}
     * @phpstan-type AutoloadAliasArray array<string,ClassAliasArray|InterfaceAliasArray|TraitAliasArray>
     */
    class AliasAutoloader
    {
        private string $includeFilePath;

        /**
         * @var AutoloadAliasArray
         */
        private array $autoloadAliases = array (
  'enshrined\\svgSanitize\\ElementReference\\Resolver' => 
  array (
    'type' => 'class',
    'classname' => 'Resolver',
    'isabstract' => false,
    'namespace' => 'enshrined\\svgSanitize\\ElementReference',
    'extends' => 'Plover\\Core\\enshrined\\svgSanitize\\ElementReference\\Resolver',
    'implements' => 
    array (
    ),
  ),
  'enshrined\\svgSanitize\\ElementReference\\Subject' => 
  array (
    'type' => 'class',
    'classname' => 'Subject',
    'isabstract' => false,
    'namespace' => 'enshrined\\svgSanitize\\ElementReference',
    'extends' => 'Plover\\Core\\enshrined\\svgSanitize\\ElementReference\\Subject',
    'implements' => 
    array (
    ),
  ),
  'enshrined\\svgSanitize\\ElementReference\\Usage' => 
  array (
    'type' => 'class',
    'classname' => 'Usage',
    'isabstract' => false,
    'namespace' => 'enshrined\\svgSanitize\\ElementReference',
    'extends' => 'Plover\\Core\\enshrined\\svgSanitize\\ElementReference\\Usage',
    'implements' => 
    array (
    ),
  ),
  'enshrined\\svgSanitize\\Exceptions\\NestingException' => 
  array (
    'type' => 'class',
    'classname' => 'NestingException',
    'isabstract' => false,
    'namespace' => 'enshrined\\svgSanitize\\Exceptions',
    'extends' => 'Plover\\Core\\enshrined\\svgSanitize\\Exceptions\\NestingException',
    'implements' => 
    array (
    ),
  ),
  'enshrined\\svgSanitize\\Helper' => 
  array (
    'type' => 'class',
    'classname' => 'Helper',
    'isabstract' => false,
    'namespace' => 'enshrined\\svgSanitize',
    'extends' => 'Plover\\Core\\enshrined\\svgSanitize\\Helper',
    'implements' => 
    array (
    ),
  ),
  'enshrined\\svgSanitize\\Sanitizer' => 
  array (
    'type' => 'class',
    'classname' => 'Sanitizer',
    'isabstract' => false,
    'namespace' => 'enshrined\\svgSanitize',
    'extends' => 'Plover\\Core\\enshrined\\svgSanitize\\Sanitizer',
    'implements' => 
    array (
    ),
  ),
  'enshrined\\svgSanitize\\data\\AllowedAttributes' => 
  array (
    'type' => 'class',
    'classname' => 'AllowedAttributes',
    'isabstract' => false,
    'namespace' => 'enshrined\\svgSanitize\\data',
    'extends' => 'Plover\\Core\\enshrined\\svgSanitize\\data\\AllowedAttributes',
    'implements' => 
    array (
      0 => 'enshrined\\svgSanitize\\data\\AttributeInterface',
    ),
  ),
  'enshrined\\svgSanitize\\data\\AllowedTags' => 
  array (
    'type' => 'class',
    'classname' => 'AllowedTags',
    'isabstract' => false,
    'namespace' => 'enshrined\\svgSanitize\\data',
    'extends' => 'Plover\\Core\\enshrined\\svgSanitize\\data\\AllowedTags',
    'implements' => 
    array (
      0 => 'enshrined\\svgSanitize\\data\\TagInterface',
    ),
  ),
  'enshrined\\svgSanitize\\data\\XPath' => 
  array (
    'type' => 'class',
    'classname' => 'XPath',
    'isabstract' => false,
    'namespace' => 'enshrined\\svgSanitize\\data',
    'extends' => 'Plover\\Core\\enshrined\\svgSanitize\\data\\XPath',
    'implements' => 
    array (
    ),
  ),
  'enshrined\\svgSanitize\\data\\AttributeInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'AttributeInterface',
    'namespace' => 'enshrined\\svgSanitize\\data',
    'extends' => 
    array (
      0 => 'Plover\\Core\\enshrined\\svgSanitize\\data\\AttributeInterface',
    ),
  ),
  'enshrined\\svgSanitize\\data\\TagInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'TagInterface',
    'namespace' => 'enshrined\\svgSanitize\\data',
    'extends' => 
    array (
      0 => 'Plover\\Core\\enshrined\\svgSanitize\\data\\TagInterface',
    ),
  ),
);

        public function __construct()
        {
            $this->includeFilePath = __DIR__ . '/autoload_alias.php';
        }

        /**
         * @param string $class
         */
        public function autoload($class): void
        {
            if (!isset($this->autoloadAliases[$class])) {
                return;
            }
            switch ($this->autoloadAliases[$class]['type']) {
                case 'class':
                        $this->load(
                            $this->classTemplate(
                                $this->autoloadAliases[$class]
                            )
                        );
                    break;
                case 'interface':
                    $this->load(
                        $this->interfaceTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                case 'trait':
                    $this->load(
                        $this->traitTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                default:
                    // Never.
                    break;
            }
        }

        private function load(string $includeFile): void
        {
            file_put_contents($this->includeFilePath, $includeFile);
            include $this->includeFilePath;
            file_exists($this->includeFilePath) && unlink($this->includeFilePath);
        }

        /**
         * @param ClassAliasArray $class
         */
        private function classTemplate(array $class): string
        {
            $abstract = $class['isabstract'] ? 'abstract ' : '';
            $classname = $class['classname'];
            if (isset($class['namespace'])) {
                $namespace = "namespace {$class['namespace']};";
                $extends = '\\' . $class['extends'];
                $implements = empty($class['implements']) ? ''
                : ' implements \\' . implode(', \\', $class['implements']);
            } else {
                $namespace = '';
                $extends = $class['extends'];
                $implements = !empty($class['implements']) ? ''
                : ' implements ' . implode(', ', $class['implements']);
            }
            return <<<EOD
                <?php
                $namespace
                $abstract class $classname extends $extends $implements {}
                EOD;
        }

        /**
         * @param InterfaceAliasArray $interface
         */
        private function interfaceTemplate(array $interface): string
        {
            $interfacename = $interface['interfacename'];
            $namespace = isset($interface['namespace'])
            ? "namespace {$interface['namespace']};" : '';
            $extends = isset($interface['namespace'])
            ? '\\' . implode('\\ ,', $interface['extends'])
            : implode(', ', $interface['extends']);
            return <<<EOD
                <?php
                $namespace
                interface $interfacename extends $extends {}
                EOD;
        }

        /**
         * @param TraitAliasArray $trait
         */
        private function traitTemplate(array $trait): string
        {
            $traitname = $trait['traitname'];
            $namespace = isset($trait['namespace'])
            ? "namespace {$trait['namespace']};" : '';
            $uses = isset($trait['namespace'])
            ? '\\' . implode(';' . PHP_EOL . '    use \\', $trait['use'])
            : implode(';' . PHP_EOL . '    use ', $trait['use']);
            return <<<EOD
                <?php
                $namespace
                trait $traitname { 
                    use $uses; 
                }
                EOD;
        }
    }

    spl_autoload_register([ new AliasAutoloader(), 'autoload' ]);
}
