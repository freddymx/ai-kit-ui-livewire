# Guía de desarrollo — ai-kit-ui-livewire

## 1. Objetivo

`freddymx/ai-kit-ui-livewire` es **solo la UI** de la suite ai-kit: un componente drop-in `<livewire:ai-kit.chat />` que permite chatear contra una base de conocimiento ya indexada, con:

- **Streaming** token-by-token sobre SSE (Server-Sent Events).
- **Markdown** GitHub-flavored renderizado de forma segura (HTML escapado).
- **Citations**: chips con título del documento · chunk · relevance %, con popup del pasaje.
- **Conversation history** por participante autenticado, con empty state.

No hay **build step**: no hay que compilar JS ni CSS. Toda la lógica RAG (ingestion, retrieval, agents, persistencia, token usage) vive en `ai-kit-core`; acá solo se consume `SolutionForest\AiKit\Services\ChatService`.

## 2. Relación con la suite

| Package | Rol | Dependencias clave |
| --- | --- | --- |
| `freddymx/ai-kit-core` | RAG engine: ingestion, retrieval, agents + CLI, HTTP API, MCP | — |
| **`freddymx/ai-kit-ui-livewire`** (este) | Componente chat Livewire 4 | `freddymx/ai-kit-core: ^0.4` |
| `freddymx/ai-kit-filament` | Admin panel Filament v5 | `freddymx/ai-kit-ui-livewire: ^0.4` y core |

- Este package **depende de core** `^0.4` (hard dependency) y **Filament depende de este package también** (`^0.4`, ver `../ai-kit-filament/composer.json`). Publicar en orden: core → ui → filament.
- Ambos (UI y Filament) comparten el mismo `SolutionForest\AiKit\Services\ChatService`. Por eso las **4 caras** — UI, CLI, API y MCP — devuelven la misma respuesta: el razonamiento no se duplica en ningún frontend.
- Ojo con el namespace: el vendor de Composer es `freddymx`, pero el namespace PHP es `SolutionForest\AiKit\Ui\` (histórico; se mantuvo para no romper upgrades). El tag Livewire sigue siendo `ai-kit.chat`.
- Hasta 0.2 el package se llamaba `solution-forest/ai-kit-ui-livewire`; desde 0.3 es `freddymx/ai-kit-ui-livewire`.

## 3. Stack y requisitos

- **PHP** `^8.5`.
- **Laravel 12 | 13** (`illuminate/support: ^12.0|^13.0`).
- **Livewire** `^4.4`.
- **`league/commonmark: ^2.4`** (GFM + Autolink).
- **`freddymx/ai-kit-core: ^0.4`**.
- Dev: **Pest `^5.0`**, **Testbench `^10.0|^11.0`**, `laravel/boost: ^2.10`, `laravel/pao: ^1.1`.
- CI corre con PHP 8.5, extensiones `zip, sqlite3, pdo_sqlite`.

`minimum-stability: dev` + `prefer-stable: true`; `dev-main` tiene branch-alias `0.4.x-dev`.

## 4. Estado actual

- Versión publicada: **v0.4.4** (tag presente en el repo).
- Tests: **11 passed / 32 assertions** (`vendor/bin/pest`).
- Capacidades implementadas: `StreamController` + ruta named `ai-kit.stream`, streaming `delta`/`done`, citations con chips y popup, historial por participante, empty state, markdown GFM con HTML escapado, dark mode (`prefers-color-scheme`) y CSS variables `--ck-*`; y una **cabecera grounded** (doble bisel, identidad + colección activa, status pill y acción **New chat**).
- Historial de fixes de alineación (commits `f5191f0`, `af09f25`, `51d562b`): se fijó `text-align:left!important` en `.ck-root` y `.ck-bubble`, y además en los **elementos markdown hijos** (`.ck-bubble p, ul, ol, li, h1–h6, blockquote, table, th, td, pre, code`). Motivo: las reglas del host sobre `p`/`li` ganaban por especificidad al valor **heredado** de `.ck-bubble`. El test `pins chat text to the left...` congela esos selectores.

## 5. Estructura del código

```
composer.json                         # metadata, deps, path repo, branch-alias, provider
src/Livewire/Chat.php                 # componente: props, send(), stream, historial (load/delete), citas
src/Http/StreamController.php         # SSE: valida request, emite delta/error/done
src/Support/Markdown.php              # CommonMark -> HTML seguro (html_input => escape)
src/AiKitUiServiceProvider.php        # loadViewsFrom, loadRoutesFrom, Livewire::component, publishes
routes/web.php                        # GET ai-kit/stream -> ai-kit.stream
resources/views/livewire/chat.blade.php # toda la UI: CSS inline (@once) + JS @script + historial/New chat
tests/Feature/ChatComponentTest.php   # render, empty state, send, citations, stream, CSS
tests/Feature/ConversationHistoryTest.php # scoping por participante, load/new/delete conversation
tests/TestCase.php, tests/Pest.php    # bootstrap Testbench
tests/Support/User.php                # modelo de usuario de test
tests/database/migrations/...         # tabla users
.github/workflows/tests.yml           # release published + workflow_dispatch
```

Detalle de `Chat.php`: props públicas `collection`, `agent`, `placeholder`, `draft`, `messages`, `conversationId`, `streaming`, `history`, `citations`. `send()` agrega el mensaje del usuario, resuelve citas (si están activas), agrega un mensaje `assistant` con `pending: true` y hace `dispatch('ai-kit:stream', ...)`; el path no-streamed llama a `ChatService::ask()` inline. `completeStream(index, content, conversationId)` cierra el ciclo del stream. La cabecera no agrega props: muestra `$collection` (o el default de config) y su botón **New chat** llama a `newConversation()`, así que funciona incluso con `:history="false"`.

El historial expone además `deleteConversation(string $id)`: hace early-return sin `history` o sin `auth()->user()`, y delega el borrado real al servicio del core `SolutionForest\AiKit\Services\ConversationService::deleteForParticipant($id, $user)`, que verifica propiedad por `participant_type` + `participant_id` y borra la conversación junto con sus mensajes (observer `deleting` registrado por `AiKitServiceProvider`). Si el id borrado era el activo (`conversationId`), llama a `newConversation()` para limpiar `messages`, `conversationId` y `draft`. La UI usa un `<div class="ck-history-row">` con el botón de carga y un botón de borrar separado (no se anidan botones): `wire:confirm` pide la confirmación y `wire:click.stop` evita que el click dispare también `loadConversation`. No hay acción de "borrar todo": el alcance es por conversación.

## 6. Configuración

Los defaults **no viven en este package**: están en `config/ai-kit.php` de **core**, bajo las keys `ai-kit.ui.*` y `ai-kit.chat.streaming`. Cada prop puede overridearse por instancia.

| Prop | Default real | Origen |
| --- | --- | --- |
| `collection` | `config('ai-kit.default_collection', 'default')` | core (`AI_KIT_COLLECTION`) |
| `agent` | `null` (usa el configurado en core) | — |
| `placeholder` | `'Ask a question…'` (hardcodeado en `Chat.php`) | — |
| `history` | `config('ai-kit.ui.history', false)` | core (`AI_KIT_UI_HISTORY`) |
| `citations` | `config('ai-kit.ui.citations', true)` | core (`AI_KIT_UI_CITATIONS`) |
| `streaming` | `config('ai-kit.chat.streaming', true)` | core (`AI_KIT_STREAMING`) |

- Ruta: `GET /ai-kit/stream`, name `ai-kit.stream`; middleware `config('ai-kit.ui.stream_middleware')`, default `['web']`.
- CSS variables en `.ck-root`: `--ck-bg`, `--ck-fg`, `--ck-muted`, `--ck-border`, `--ck-user`, `--ck-user-fg`, `--ck-assistant`, `--ck-accent`, `--ck-radius`. Se override desde la stylesheet de la app (ver README).
- **La configuración de provider/modelo NO está acá**: se hace en core (`.env` con `AI_KIT_LOCAL_*` para Ollama / LM Studio / vLLM / llama.cpp) o desde la página **AI Settings** de Filament. El componente toma el provider activo automáticamente.

## 7. Flujo de desarrollo local

```bash
cd ../ai-kit-core && composer install   # repo hermano, requerido por el path repository
cd ../ai-kit-ui-livewire && composer install
vendor/bin/pest                         # 11 tests / 32 assertions
composer validate --strict
```

- `composer.json` declara un **path repository** en `../ai-kit-core` con `symlink: true`: los cambios cross-package se prueban juntos sin publicar.
- `composer.lock` está **gitignoreado** (junto con `/vendor/` y `.phpunit.cache`); es library, no se commitea el lock.
- CI (`.github/workflows/tests.yml`): corre en `release: published` y `workflow_dispatch`, **no en cada push**. Clona el repo hermano con `git clone --depth=1 https://github.com/freddymx/ai-kit-core.git ../ai-kit-core` antes de `composer update` y `vendor/bin/pest`. El clone existe solo por el path repo local.
- Orden de publicación: **core → ui → filament**. En 0.x, SemVer trata el minor como breaking: `^0.4` significa `>=0.4.0 <0.5.0`. Por eso los tres packages se atan a la misma línea `0.4` y deben actualizarse en lockstep; un core `0.5` no satisfaría este constraint.

## 8. Comandos y flujos clave

Publicar las vistas para customizarlas:

```bash
php artisan vendor:publish --tag=ai-kit-ui-views
```

**Cómo funciona el streaming** (no es un POST Livewire):

1. `send()` dispatcha el browser event `ai-kit:stream` con `index`, `prompt` y `collection`.
2. El JS en `@script` abre un `EventSource` contra `route('ai-kit.stream')`, con query params `prompt`, `collection`, y opcionales `agent` y `conversationId`.
3. `StreamController` llama a `ChatService::stream()`; cada `TextDelta` emite un frame `event: delta` con `{"delta": "..."}`. El JS lo concatena en `buffer` y lo pinta en el bubble `data-ck-index`, disparando `ck-scroll`.
4. Al terminar emite `event: done` con `{"content": ..., "conversationId": ...}`. El JS cierra el `EventSource` y llama a `$wire.completeStream(index, content || buffer, conversationId)`. En error emite `event: error` y cierra.
5. Si `streaming` es `false`, no hay SSE: `ChatService::ask()` responde inline dentro de Livewire.

**Cómo funciona el borrado del historial** (acción por conversación, no "clear all"):

1. Cada item del sidebar es un `.ck-history-row` con dos botones hermanos: el `.ck-history-item` existente (`wire:click="loadConversation(...)"`) y el `.ck-history-delete`, que no está anidado para evitar un botón dentro de otro.
2. El botón de borrar usa `wire:confirm="Delete this conversation?"` para pedir confirmación y `wire:click.stop="deleteConversation('...')"` para que el click NO propague también la carga de la conversación.
3. `deleteConversation()` valida `history` + usuario autenticado y llama a `ConversationService::deleteForParticipant()` del core (`freddymx/ai-kit-core`, vía path repo symlink). El servicio verifica propiedad y borra conversación + mensajes en una transacción.
4. Si la conversación borrada estaba activa, `newConversation()` limpia `messages`, `conversationId` y `draft`. El trash SVG es inline (`stroke-width="1.25"`, sin librería de iconos); en mobile (`max-width:640px`) se fuerza `opacity:1` porque no hay hover, y `prefers-reduced-motion` desactiva la transición.

Página Blade mínima (fuera de Filament):

```blade
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
</head>
<body>
    <livewire:ai-kit.chat collection="handbook" :history="true" />
    @livewireScripts
</body>
</html>
```

> No aplica GIF ni placeholder de asset: el estado vacío es un markup propio (`.ck-empty`) con el copy *"No knowledge base yet"* / *"Ask anything…"*. El CSRF meta es para Livewire; el stream es `GET` vía middleware `web`.

## 9. Decisiones de diseño y gotchas

- **CSS inline + scoped**: todo el estilo vive en `chat.blade.php` con `@once` y prefijo `ck-`. Así no hay dependencias de Tailwind, ni build step, ni colisiones con el CSS del host (los tests verifican los selectores exactos).
- **Vistas publicadas**: `vendor:publish` copia los archivos a `resources/views/vendor/ai-kit-ui`. Composer **no las actualiza** en un upgrade porque son archivos del usuario. Para tomar cambios del package hay que republicar con `php artisan vendor:publish --tag=ai-kit-ui-views --force` (y revisar diffs propios).
- **`text-align` en los hijos markdown**: `text-align` se hereda, pero una regla del host sobre `p`/`li` gana por especificidad. Por eso se declara `text-align:left!important` también en `p/ul/ol/li/h1-h6/blockquote/table/th/td/pre/code`.
- **Historial y token logging requieren usuario autenticado** (el *participant*): `conversationList()`, `loadConversation()` y `deleteConversation()` retornan `[]`/early-return sin `auth()->user()`, y filtran por `participant_type` + `participant_id`. En una página pública el chat funciona pero no persiste nada. El listado se limita a 50 conversaciones.
- **El borrado nunca sale del participant**: la UI sólo ofrece borrar conversaciones listadas para el usuario actual, pero la autoridad real está en `ConversationService::deleteForParticipant()` del core (doble verificación en UI + servicio). Los mensajes se van con la conversación gracias al observer `deleting` del `AiKitServiceProvider`. No existe borrado masivo.
- **`wire:confirm` + `wire:click.stop`**: la confirmación es del lado browser (Livewire), no una validación de seguridad; la seguridad es el chequeo de propiedad en el servicio. El modificador `.stop` es necesario porque el botón de borrar convive con el botón de carga dentro de la misma fila: sin él, un click de borrado también dispararía `loadConversation()` sobre una conversación que acaba de desaparecer.
- **Visibilidad del botón de borrar**: `opacity:0` por defecto y aparece en `:hover`/`:focus-visible` de la fila; en `@media (max-width:640px)` se fuerza `opacity:1` porque en touch no hay hover. Con `prefers-reduced-motion:reduce` la transición se desactiva con `transition:none!important`.
- Otro gotcha: el route name `ai-kit.stream` se resuelve con `route()` dentro de `@script`; si la app redefine el prefijo de rutas del provider, el JS sigue funcionando porque usa el name (`@js(route(...))`).

## 10. Verificación y publicación

Checklist:

1. `vendor/bin/pest` → 11 passed / 32 assertions.
2. `composer validate --strict` → sin errores.
3. Verificar que `../ai-kit-core` esté alineado en `^0.4` (y Filament en `^0.4` de este package).
4. `git tag v0.4.x` y push del tag; el workflow dispara con `release: published`.
5. Packagist auto-updatea el tag (no hace falta PR manual); confirmar que la versión aparece en `freddymx/ai-kit-ui-livewire`.
6. Verificación con **instalación limpia**: `composer require freddymx/ai-kit-ui-livewire` en un app Laravel 12/13 fresh, dropear `<livewire:ai-kit.chat />`, probar stream + citas + historial.

## 11. Límites conocidos / próximos pasos

- Sin build step implica CSS inline en la Blade; para theming real hay que publicar vistas o overridear `--ck-*`.
- La UI **no configura modelos ni providers**: eso es responsabilidad de core / Filament AI Settings.
- Requiere **PHP 8.5** y **Livewire 4**; no hay fallback para versiones anteriores.
- Potenciales mejoras: tests browser del flujo SSE completo (hoy se tests el dispatch, no el EventSource real), y opción de streams con reconexión.
- Estado del código y documentación: **25/11/2026** (v0.4.4).
