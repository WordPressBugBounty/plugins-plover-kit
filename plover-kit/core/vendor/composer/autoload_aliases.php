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
  'Nest\\Cache\\CacheManager' => 
  array (
    'type' => 'class',
    'classname' => 'CacheManager',
    'isabstract' => false,
    'namespace' => 'Nest\\Cache',
    'extends' => 'Plover\\Core\\Nest\\Cache\\CacheManager',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Cache\\CacheRepository' => 
  array (
    'type' => 'class',
    'classname' => 'CacheRepository',
    'isabstract' => false,
    'namespace' => 'Nest\\Cache',
    'extends' => 'Plover\\Core\\Nest\\Cache\\CacheRepository',
    'implements' => 
    array (
      0 => 'Nest\\Cache\\Contracts\\Store',
    ),
  ),
  'Nest\\Cache\\CacheServiceProvider' => 
  array (
    'type' => 'class',
    'classname' => 'CacheServiceProvider',
    'isabstract' => false,
    'namespace' => 'Nest\\Cache',
    'extends' => 'Plover\\Core\\Nest\\Cache\\CacheServiceProvider',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Cache\\Facades\\Cache' => 
  array (
    'type' => 'class',
    'classname' => 'Cache',
    'isabstract' => false,
    'namespace' => 'Nest\\Cache\\Facades',
    'extends' => 'Plover\\Core\\Nest\\Cache\\Facades\\Cache',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Cache\\Stores\\MemoryStore' => 
  array (
    'type' => 'class',
    'classname' => 'MemoryStore',
    'isabstract' => false,
    'namespace' => 'Nest\\Cache\\Stores',
    'extends' => 'Plover\\Core\\Nest\\Cache\\Stores\\MemoryStore',
    'implements' => 
    array (
      0 => 'Nest\\Cache\\Contracts\\Store',
    ),
  ),
  'Nest\\Cache\\Stores\\WordPressObjectCacheStore' => 
  array (
    'type' => 'class',
    'classname' => 'WordPressObjectCacheStore',
    'isabstract' => false,
    'namespace' => 'Nest\\Cache\\Stores',
    'extends' => 'Plover\\Core\\Nest\\Cache\\Stores\\WordPressObjectCacheStore',
    'implements' => 
    array (
      0 => 'Nest\\Cache\\Contracts\\Store',
    ),
  ),
  'Nest\\Cache\\Stores\\WordPressTransientStore' => 
  array (
    'type' => 'class',
    'classname' => 'WordPressTransientStore',
    'isabstract' => false,
    'namespace' => 'Nest\\Cache\\Stores',
    'extends' => 'Plover\\Core\\Nest\\Cache\\Stores\\WordPressTransientStore',
    'implements' => 
    array (
      0 => 'Nest\\Cache\\Contracts\\Store',
    ),
  ),
  'Nest\\Router\\Auth' => 
  array (
    'type' => 'class',
    'classname' => 'Auth',
    'isabstract' => false,
    'namespace' => 'Nest\\Router',
    'extends' => 'Plover\\Core\\Nest\\Router\\Auth',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Router\\Endpoint' => 
  array (
    'type' => 'class',
    'classname' => 'Endpoint',
    'isabstract' => false,
    'namespace' => 'Nest\\Router',
    'extends' => 'Plover\\Core\\Nest\\Router\\Endpoint',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Router\\Exception\\EndpointExistedException' => 
  array (
    'type' => 'class',
    'classname' => 'EndpointExistedException',
    'isabstract' => false,
    'namespace' => 'Nest\\Router\\Exception',
    'extends' => 'Plover\\Core\\Nest\\Router\\Exception\\EndpointExistedException',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Router\\Router' => 
  array (
    'type' => 'class',
    'classname' => 'Router',
    'isabstract' => false,
    'namespace' => 'Nest\\Router',
    'extends' => 'Plover\\Core\\Nest\\Router\\Router',
    'implements' => 
    array (
    ),
  ),
  'Composer\\Autoload\\ClassLoader' => 
  array (
    'type' => 'class',
    'classname' => 'ClassLoader',
    'isabstract' => false,
    'namespace' => 'Composer\\Autoload',
    'extends' => 'Plover\\Core\\Composer\\Autoload\\ClassLoader',
    'implements' => 
    array (
    ),
  ),
  'Composer\\Autoload\\ComposerStaticInitcd5b4b843695be842af2564b9266ddb4' => 
  array (
    'type' => 'class',
    'classname' => 'ComposerStaticInitcd5b4b843695be842af2564b9266ddb4',
    'isabstract' => false,
    'namespace' => 'Composer\\Autoload',
    'extends' => 'Plover\\Core\\Composer\\Autoload\\ComposerStaticInitcd5b4b843695be842af2564b9266ddb4',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Bootstrap' => 
  array (
    'type' => 'class',
    'classname' => 'Bootstrap',
    'isabstract' => false,
    'namespace' => 'Nest',
    'extends' => 'Plover\\Core\\Nest\\Bootstrap',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Container\\BindingResolutionException' => 
  array (
    'type' => 'class',
    'classname' => 'BindingResolutionException',
    'isabstract' => false,
    'namespace' => 'Nest\\Container',
    'extends' => 'Plover\\Core\\Nest\\Container\\BindingResolutionException',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Container\\BoundMethod' => 
  array (
    'type' => 'class',
    'classname' => 'BoundMethod',
    'isabstract' => false,
    'namespace' => 'Nest\\Container',
    'extends' => 'Plover\\Core\\Nest\\Container\\BoundMethod',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Container\\Container' => 
  array (
    'type' => 'class',
    'classname' => 'Container',
    'isabstract' => false,
    'namespace' => 'Nest\\Container',
    'extends' => 'Plover\\Core\\Nest\\Container\\Container',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Container\\EntryNotFoundException' => 
  array (
    'type' => 'class',
    'classname' => 'EntryNotFoundException',
    'isabstract' => false,
    'namespace' => 'Nest\\Container',
    'extends' => 'Plover\\Core\\Nest\\Container\\EntryNotFoundException',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Container\\Util' => 
  array (
    'type' => 'class',
    'classname' => 'Util',
    'isabstract' => false,
    'namespace' => 'Nest\\Container',
    'extends' => 'Plover\\Core\\Nest\\Container\\Util',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Nest' => 
  array (
    'type' => 'class',
    'classname' => 'Nest',
    'isabstract' => false,
    'namespace' => 'Nest',
    'extends' => 'Plover\\Core\\Nest\\Nest',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Support\\Facade' => 
  array (
    'type' => 'class',
    'classname' => 'Facade',
    'isabstract' => true,
    'namespace' => 'Nest\\Support',
    'extends' => 'Plover\\Core\\Nest\\Support\\Facade',
    'implements' => 
    array (
    ),
  ),
  'Nest\\Support\\ServiceProvider' => 
  array (
    'type' => 'class',
    'classname' => 'ServiceProvider',
    'isabstract' => true,
    'namespace' => 'Nest\\Support',
    'extends' => 'Plover\\Core\\Nest\\Support\\ServiceProvider',
    'implements' => 
    array (
    ),
  ),
  'Toolkit\\Arr' => 
  array (
    'type' => 'class',
    'classname' => 'Arr',
    'isabstract' => false,
    'namespace' => 'Toolkit',
    'extends' => 'Plover\\Core\\Toolkit\\Arr',
    'implements' => 
    array (
    ),
  ),
  'Toolkit\\Filesystem' => 
  array (
    'type' => 'class',
    'classname' => 'Filesystem',
    'isabstract' => false,
    'namespace' => 'Toolkit',
    'extends' => 'Plover\\Core\\Toolkit\\Filesystem',
    'implements' => 
    array (
    ),
  ),
  'Toolkit\\Html\\Document' => 
  array (
    'type' => 'class',
    'classname' => 'Document',
    'isabstract' => false,
    'namespace' => 'Toolkit\\Html',
    'extends' => 'Plover\\Core\\Toolkit\\Html\\Document',
    'implements' => 
    array (
    ),
  ),
  'Toolkit\\Html\\Element' => 
  array (
    'type' => 'class',
    'classname' => 'Element',
    'isabstract' => false,
    'namespace' => 'Toolkit\\Html',
    'extends' => 'Plover\\Core\\Toolkit\\Html\\Element',
    'implements' => 
    array (
    ),
  ),
  'Toolkit\\Path' => 
  array (
    'type' => 'class',
    'classname' => 'Path',
    'isabstract' => false,
    'namespace' => 'Toolkit',
    'extends' => 'Plover\\Core\\Toolkit\\Path',
    'implements' => 
    array (
    ),
  ),
  'Toolkit\\Plugin' => 
  array (
    'type' => 'class',
    'classname' => 'Plugin',
    'isabstract' => false,
    'namespace' => 'Toolkit',
    'extends' => 'Plover\\Core\\Toolkit\\Plugin',
    'implements' => 
    array (
    ),
  ),
  'Toolkit\\Responsive' => 
  array (
    'type' => 'class',
    'classname' => 'Responsive',
    'isabstract' => false,
    'namespace' => 'Toolkit',
    'extends' => 'Plover\\Core\\Toolkit\\Responsive',
    'implements' => 
    array (
    ),
  ),
  'Toolkit\\Str' => 
  array (
    'type' => 'class',
    'classname' => 'Str',
    'isabstract' => false,
    'namespace' => 'Toolkit',
    'extends' => 'Plover\\Core\\Toolkit\\Str',
    'implements' => 
    array (
    ),
  ),
  'Toolkit\\StyleEngine' => 
  array (
    'type' => 'class',
    'classname' => 'StyleEngine',
    'isabstract' => false,
    'namespace' => 'Toolkit',
    'extends' => 'Plover\\Core\\Toolkit\\StyleEngine',
    'implements' => 
    array (
    ),
  ),
  'Toolkit\\ThemeJson' => 
  array (
    'type' => 'class',
    'classname' => 'ThemeJson',
    'isabstract' => false,
    'namespace' => 'Toolkit',
    'extends' => 'Plover\\Core\\Toolkit\\ThemeJson',
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
  'Nest\\Cache\\Contracts\\Store' => 
  array (
    'type' => 'interface',
    'interfacename' => 'Store',
    'namespace' => 'Nest\\Cache\\Contracts',
    'extends' => 
    array (
      0 => 'Plover\\Core\\Nest\\Cache\\Contracts\\Store',
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
