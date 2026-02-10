---
title: Request Lifecycle
layout: default
nav_order: 2
---

# Request Lifecycle

Cubex handles both HTTP requests and CLI commands through distinct but related lifecycles. Both begin with bootstrapping a `Cubex` instance and creating a `Context`.

## HTTP Lifecycle

The HTTP lifecycle is driven by `Cubex::handle(Handler $handler)`. Here is the full flow:

```mermaid
sequenceDiagram
    participant Entry as index.php
    participant Cubex
    participant Ctx as Context
    participant Channel as Event Channel
    participant Handler
    participant Response

    rect rgb(240, 240, 255)
    Note right of Entry: Setup
    Entry->>Cubex: new Cubex($root, $loader)
    Entry->>Cubex: handle($handler)
    Cubex->>Ctx: getContext()
    Cubex->>Ctx: initialize()
    Cubex->>Handler: setContext($ctx)
    end

    rect rgb(240, 255, 240)
    Note right of Entry: Execute
    Cubex->>Channel: PreExecuteEvent
    Cubex->>Handler: handle($ctx)
    Handler-->>Cubex: Response
    end

    rect rgb(255, 240, 240)
    Note right of Entry: Send Response
    Cubex->>Channel: ResponsePrepareEvent
    Cubex->>Response: apply cookies, prepare()
    Cubex->>Channel: ResponsePreparedEvent
    Cubex->>Channel: PreSendHeadersEvent
    Cubex->>Response: sendHeaders()
    Cubex->>Channel: PreSendContentEvent
    Cubex->>Response: sendContent()
    Cubex->>Channel: HandleCompleteEvent
    end

    Entry->>Cubex: shutdown()
    Cubex->>Channel: ShutdownEvent
```

### Bootstrap

```php
$loader = require __DIR__ . '/../vendor/autoload.php';
$cubex = new Cubex(__DIR__ . '/..', $loader);
```

The constructor:
1. Sets the project root path
2. Creates an event `Channel` named `'cubex'`
3. Shares the `ClassLoader` and `DependencyInjector` (itself) in the DI container
4. Registers a `Context` factory that creates contexts from `Request::createFromGlobals()`

### Context Preparation

When `handle()` is called, it retrieves the shared `Context` from the DI container. The context is prepared with:
- **Environment** from the `CUBEX_ENV` environment variable
- **Project root** path
- **Configuration** loaded from INI files in cascade order
- **Cubex reference** set on the context (if `CubexAware`)

### Handler Execution

The handler (typically a `Router`, `Application`, or `Controller`) receives the context and produces a `Response`. If the handler is `ContextAware`, its context is set before execution.

### Response Processing

After the handler returns a response, Cubex:
1. Fires `ResponsePrepareEvent` (listeners can modify the response)
2. Applies cookies from the context's cookie jar
3. Calls `$response->prepare($request)` to finalize headers
4. Fires `ResponsePreparedEvent`
5. Sends headers, flushes, then sends content
6. Calls `fastcgi_finish_request()` if running under PHP-FPM
7. Fires `HandleCompleteEvent`

### Exception Handling

By default, Cubex catches exceptions in production environments and re-throws them in `local` and `dev` environments. This behavior is controlled by `setThrowEnvironments()`:

```php
$cubex->setThrowEnvironments([Context::ENV_LOCAL, Context::ENV_DEV]);
```

## CLI Lifecycle

The CLI lifecycle is driven by `Cubex::cli()`:

```mermaid
sequenceDiagram
    participant Entry as cubex
    participant Cubex
    participant Ctx as Context
    participant Channel as Event Channel
    participant Console
    participant Command

    rect rgb(240, 240, 255)
    Note right of Entry: Bootstrap
    Entry->>Cubex: new Cubex($root, $loader)
    Entry->>Cubex: cli($input, $output)
    Cubex->>Ctx: getContext()
    end

    rect rgb(240, 255, 240)
    Note right of Entry: Console Setup
    Cubex->>Ctx: trigger(ConsoleLaunchedEvent)
    Cubex->>Console: create Console
    Cubex->>Ctx: trigger(ConsoleCreatedEvent)
    Cubex->>Channel: ConsoleCreateEvent
    Note over Console: Registers commands from config
    end

    rect rgb(255, 240, 240)
    Note right of Entry: Execute
    Cubex->>Channel: ConsolePrepareEvent
    Cubex->>Console: run($input, $output)
    Console->>Command: execute()
    Command-->>Console: exit code
    Console-->>Cubex: exit code
    end
```

### CLI Bootstrap

```php
$loader = require __DIR__ . '/../vendor/autoload.php';
$cubex = new Cubex(__DIR__ . '/..', $loader);
exit($cubex->cli());
```

`Cubex::cli()` creates default `ArgvInput` and `ConsoleOutput` if none are provided, then:
1. Fires `ConsoleLaunchedEvent` on the context event channel
2. Creates the `Console` application (lazy, cached)
3. Fires `ConsoleCreatedEvent` (context channel) and `ConsoleCreateEvent` (cubex channel)
4. Fires `ConsolePrepareEvent` on the cubex channel
5. Runs the console application
6. Returns the exit code (capped at 255)

### Shutdown

Call `$cubex->shutdown()` after handling completes. This fires the `ShutdownEvent` exactly once (guarded against double-shutdown). If shutdown is not called explicitly, the destructor will attempt it and log a warning.
