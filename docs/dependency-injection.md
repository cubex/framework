---
title: Dependency Injection
layout: default
nav_order: 5
---

# Dependency Injection

The `Cubex` class is the DI container for the framework. It extends `DependencyInjector` from `packaged/di-container` and provides sharing, factories, auto-resolution, and method injection.

## Container Basics

The Cubex instance itself is the DI container — there is no separate container object:

```php
$cubex = new Cubex(__DIR__, $loader);

// Share a singleton
$cubex->share(LoggerInterface::class, new FileLogger('/var/log/app.log'));

// Register a factory
$cubex->factory(
  DbConnection::class,
  fn() => new DbConnection($cubex->retrieve(Config::class))
);

// Retrieve an instance
$logger = $cubex->retrieve(LoggerInterface::class);
```

## Core Methods

### share($abstract, $concrete)

Registers a singleton binding. Subsequent calls to `retrieve()` return the same instance:

```php
$cubex->share(CacheInterface::class, new RedisCache($config));

// Both calls return the same RedisCache instance
$cache1 = $cubex->retrieve(CacheInterface::class);
$cache2 = $cubex->retrieve(CacheInterface::class);
// $cache1 === $cache2
```

### factory($abstract, callable $factory)

Registers a factory that is called each time `retrieve()` is invoked:

```php
$cubex->factory(
  RequestContext::class,
  fn() => new RequestContext(time())
);

// Each call creates a new instance
$ctx1 = $cubex->retrieve(RequestContext::class);
$ctx2 = $cubex->retrieve(RequestContext::class);
// $ctx1 !== $ctx2
```

### retrieve($abstract, $parameters = [], $shared = true, $attemptNewAbstract = true)

Resolves an abstract to a concrete instance. Resolution order:

1. Check for a shared singleton
2. Check for a registered factory
3. If `$attemptNewAbstract` is true, attempt to build a new instance via constructor injection

```php
// Resolves registered binding
$logger = $cubex->retrieve(LoggerInterface::class);

// Auto-instantiation: if UserService has no binding,
// Cubex tries to construct it, injecting dependencies
$users = $cubex->retrieve(UserService::class);
```

### resolve($class, $parameters = [])

Resolves a class with constructor injection. Dependencies are recursively resolved from the container:

```php
class UserService
{
  public function __construct(
    private DbConnection $db,
    private CacheInterface $cache
  ) {}
}

// Both DbConnection and CacheInterface are resolved from the container
$service = $cubex->resolve(UserService::class);
```

### resolveMethod($object, $method, $parameters = [])

Invokes a method on an object, injecting parameters from the container. This is used internally by `Controller` for DI-aware method dispatch:

```php
class OrderController extends Controller
{
  public function getOrder(OrderService $orders): Response
  {
    // $orders is injected by the DI container
    $id = $this->routeData()->get('id');
    return new JsonResponse($orders->find($id));
  }
}
```

## The CubexAware Pattern

Components that need access to the DI container implement `CubexAware`:

```php
use Cubex\CubexAware;
use Cubex\CubexAwareTrait;

class MyService implements CubexAware
{
  use CubexAwareTrait;

  public function doWork(): void
  {
    $db = $this->getCubex()->retrieve(DbConnection::class);
    // ...
  }
}
```

The framework automatically sets the Cubex instance on `CubexAware` objects during handler preparation and response processing.

### Interface Methods

| Method | Description |
|--------|-------------|
| `setCubex(Cubex $cubex)` | Set the Cubex container reference |
| `getCubex(): ?Cubex` | Get the Cubex container |
| `hasCubex(): bool` | Check if a Cubex reference is available |

## Auto-Wired Route Handlers

When a route returns a class name string (e.g., `DashboardController::class`), the `RouteProcessor` resolves it through the DI container:

```php
protected function _generateRoutes(): Generator
{
  // DashboardController is resolved via $cubex->retrieve()
  // Its constructor dependencies are auto-injected
  yield self::_route('/dashboard', DashboardController::class);
}
```

If the context does not have a Cubex reference, the class is instantiated directly with `new`.

## Built-In Bindings

Cubex automatically registers these bindings during construction:

| Abstract | Concrete | Type |
|----------|----------|------|
| `ClassLoader` | Composer autoloader | Shared |
| `DependencyInjector` | The Cubex instance itself | Shared |
| `Context` | Factory from `Request::createFromGlobals()` | Factory |

## Accessing the Container

From any `CubexAware` component:

```php
$cubex = $this->getCubex();
```

From a `Context` (if it is a `CubexAware` Cubex context):

```php
$cubex = Cubex::fromContext($context);
```

Or use the global singleton (if available):

```php
$cubex = Cubex::instance();
```
