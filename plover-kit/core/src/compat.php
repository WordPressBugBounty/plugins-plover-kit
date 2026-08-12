<?php

// Toolkit alias
class_alias( \Plover\Core\Toolkit\Str::class, \Plover\Core\Toolkits\Str::class );
class_alias( \Plover\Core\Toolkit\StyleEngine::class, \Plover\Core\Toolkits\StyleEngine::class );
class_alias( \Plover\Core\Toolkit\Plugin::class, \Plover\Core\Toolkits\Plugin::class );
class_alias( \Plover\Core\Toolkit\Path::class, \Plover\Core\Toolkits\Path::class );
class_alias( \Plover\Core\Toolkit\ThemeJson::class, \Plover\Core\Toolkits\JSON::class );
class_alias( \Plover\Core\Toolkit\Filesystem::class, \Plover\Core\Toolkits\Filesystem::class );
class_alias( \Plover\Core\Toolkit\Arr::class, \Plover\Core\Toolkits\Arr::class );
class_alias( \Plover\Core\Toolkit\Html\Document::class, \Plover\Core\Toolkits\Html\Document::class );
class_alias( \Plover\Core\Toolkit\Html\Element::class, \Plover\Core\Toolkits\Html\Element::class );
// Router alias
class_alias( \Plover\Core\Nest\Router\Router::class, \Plover\Core\Router\Router::class );
class_alias( \Plover\Core\Nest\Router\Endpoint::class, \Plover\Core\Router\Endpoint::class );
class_alias( \Plover\Core\Nest\Router\Auth::class, \Plover\Core\Router\Auth::class );
class_alias( \Plover\Core\Nest\Router\Exception\EndpointExistedException::class, \Plover\Core\Router\Router\Exception\EndpointExistedException::class );
