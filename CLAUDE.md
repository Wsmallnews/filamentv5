<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Test every code change by adding or updating a test.
- Run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== filament/filament/core rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->columnSpan(1),
            TextInput::make('last_name')
                ->columnSpan(1),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

=== wsmallnews/category/core rules ===

## Category 包（wsmallnews/category）

`wsmallnews/category` 是基于 `wsmallnews/filament-nestedset` 的分类管理插件，支持多层级分类、多租户和分类类型管理。命名空间根为 `Wsmallnews\Category`，Blade 视图前缀为 `sn-category`，配置文件为 `config/sn-category.php`。

### 核心架构

- 依赖 `wsmallnews/filament-nestedset`（`NestedsetPage` 基类）
- **Base**（`Wsmallnews\Category\Filament\Pages\Category\Base`）：继承 `Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage` 的抽象页面类，负责配置、schema 定义、分类类型管理
- **CategoryPage**（`Wsmallnews\Category\Filament\Pages\Category\CategoryPage`）：继承 Base 的具体页面类，注册到 Filament 面板
- **Category Widget**（`Wsmallnews\Category\Filament\Pages\Category\Widgets\Category`）：Filament Widget 变体

### 分类类型（CategoryType）

每个分类页面绑定一个 `CategoryType`，定义分类的层级限制和作用域：

```php
// 自动创建分类类型（当 canManage = false 时）
$categoryType = CategoryType::create([
    'name' => Str::title($scopeType),
    'level' => $level,
    'status' => CategoryTypeStatus::Normal,
    'scope_type' => $scopeType,
    'scope_id' => $scopeId,
    'team_id' => $tenantId,
]);
```

### 创建分类页面

```bash
php artisan make:filament-nestedset-page
```

生成的页面类继承 `Base`，需设置 `$model` 和 `$scopeType`。

#### 静态属性

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$model` | `?string` | `null` | 分类模型类名，**必须设置** |
| `$scopeType` | `?string` | `null` | 作用域类型，**必须设置** |
| `$scopeId` | `int` | `0` | 作用域 ID（0 = 全局） |
| `$level` | `?int` | `null` | 嵌套层级限制 |
| `$canManage` | `bool` | `false` | 是否显示分类类型管理表单 |
| `$navigationIcon` | `string\|BackedEnum\|null` | `Heroicon::OutlinedBars3BottomLeft` | 导航图标 |
| `$navigationSort` | `?int` | `1` | 导航排序 |

#### 可覆盖方法

```php
// 自定义 schema（create 和 edit 共用）
public function schema(array $arguments): array { return []; }

// create 和 edit 分别定义
public function createSchema(array $arguments): array { return []; }
public function editSchema(array $arguments): array { return []; }

// Infolist 附加属性展示
public function infolistSchema(): array { return []; }

// 自定义节点标签
public function getRecordLabel(Model $record): HtmlString|string { ... }

// 自定义嵌套集查询条件
public function getEloquentQuery($query) { return $query; }

// 额外的 scope 参数
public function nestedScoped(): array { return []; }
```

### 关键可覆盖方法

Base 页面自动通过 `nestedScoped()` 将 `scope_type`、`scope_id`、`type_id` 注入 nestedset 查询，不要手动重复添加这些 scope。`$categoryType` 会自动从配置的 `scopeType` / `scopeId` 解析或创建。

Base 页面覆盖了 `getRecordLabel()`（返回 `$record->name_label`）和 `getHeaderActions()` / `getNestedsetActions()`（仅返回 createAction 和 fixNestedsetAction）。

### 模型 scope 要求

`Category` 模型的 `getScopeAttributes()` 返回 `['scope_type', 'scope_id', 'type_id']`，多租户时追加 `'team_id'`。不要将 `type_id` 忽略，否则 scoped 查询会遗漏分类类型过滤。

### 模型要求

模型必须 use `Kalnoy\Nestedset\NodeTrait`，并且实现 `getScopeAttributes()`：

```php
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait;

    public function getScopeAttributes(): array
    {
        return ['team_id', 'scope_type', 'scope_id', 'type_id'];
    }
}
```

### CategoryType 资源

`CategoryTypeResource` 提供分类类型的 CRUD 管理，继承自 support 包的 Scopeable 体系：

```php
use Wsmallnews\Category\Filament\Resources\CategoryTypes\BaseResource;

// BaseResource 已提供：
// - use Scopeable（applyScopeableToQuery 自动过滤）
// - form() → CategoryTypeForm
// - table() → CategoryTypesTable
// - getWidgets() → CategoryWidget
// - getEloquentQuery() → 带 scope + 软删除
```

可配置的具体实现：

```php
use Wsmallnews\Category\Filament\Resources\CategoryTypes\CategoryTypeResource;

// 在 PanelProvider 中注册
$panel->resources([CategoryTypeResource::class]);
```

### 分类模型

`Category` 继承 `SupportModel`，实现 `HasSnSubject` 接口和 `NodeTrait`：

```php
use Wsmallnews\Category\Models\Category;

// 核心特性：
// - use NodeTrait（嵌套集）
// - use HasActivityLog（活动日志）
// - use InteractsWithMedia（Spatie 媒体库）
// - implements HasSnSubject（preference 包集成）
// - getScopeAttributes() 返回 ['scope_type', 'scope_id', 'type_id', 'team_id']
```

### 辅助函数

| 函数 | 说明 |
|---|---|
| `has_category()` | 前端是否有当前分类（从 request attributes 读取） |
| `current_category()` | 前端当前分类 Model |

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| Page 基类 | `Wsmallnews\Category\Filament\Pages\Category\Base` |
| Page 实现 | `Wsmallnews\Category\Filament\Pages\Category\CategoryPage` |
| Widget | `Wsmallnews\Category\Filament\Pages\Category\Widgets\Category` |
| Schema Form | `Wsmallnews\Category\Filament\Pages\Category\Schemas\CategoryForm` |
| Schema Infolist | `Wsmallnews\Category\Filament\Pages\Category\Schemas\CategoryInfolist` |
| CategoryType Resource | `Wsmallnews\Category\Filament\Resources\CategoryTypes\CategoryTypeResource` |
| CategoryType BaseResource | `Wsmallnews\Category\Filament\Resources\CategoryTypes\BaseResource` |
| 模型 | `Wsmallnews\Category\Models\Category` |
| 分类类型模型 | `Wsmallnews\Category\Models\CategoryType` |
| CategoryPlugin | `Wsmallnews\Category\CategoryPlugin` |
| Utils | `Wsmallnews\Category\Support\Utils` |
| ServiceProvider | `Wsmallnews\Category\CategoryServiceProvider` |

### 常见错误

- **模型必须 use `NodeTrait`**，否则 `mount()` 抛出 `NestedsetException`。
- **`$level` 设置为 `1` 时只能有根节点**，至少 `2` 才能选择父级。
- **`$scopeType` 必须设置**，否则无法正确过滤分类数据。
- **多租户 scope 需要模型定义 `getScopeAttributes()`**，返回的字段必须包含 `team_id`。

=== wsmallnews/cms/core rules ===

## CMS 包（wsmallnews/cms）

`wsmallnews/cms` 是基于 `wsmallnews/filament-nestedset` 的内容管理系统插件，支持导航管理、文章管理、页面管理和多租户。命名空间根为 `Wsmallnews\Cms`，Blade 视图前缀为 `sn-cms`，配置文件为 `config/sn-cms.php`。

### 核心架构

- 依赖 `wsmallnews/filament-nestedset`（`NestedsetPage` 基类）
- **Base**（`Wsmallnews\Cms\Filament\Pages\Navigation\Base`）：继承 `Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage` 的抽象页面类，负责配置、schema 定义、导航类型管理
- **NavigationPage**（`Wsmallnews\Cms\Filament\Pages\Navigation\NavigationPage`）：继承 Base 的具体页面类，注册到 Filament 面板
- **Navigation Widget**（`Wsmallnews\Cms\Filament\Pages\Navigation\Widgets\Navigation`）：Filament Widget 变体

### 导航类型（NavigationType）

每个导航页面绑定一个 `NavigationType`，定义导航的层级限制和作用域：

```php
// 自动创建导航类型（当 canManage = false 时）
$navigationType = NavigationType::create([
    'name' => Str::title($scopeType),
    'level' => $level,
    'status' => NavigationTypeStatus::Normal,
    'scope_type' => $scopeType,
    'scope_id' => $scopeId,
    'team_id' => $tenantId,
]);
```

### 创建导航页面

```bash
php artisan make:filament-nestedset-page
```

生成的页面类继承 `Base`，需设置 `$model` 和 `$scopeType`。

#### 静态属性

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$model` | `?string` | `null` | 导航模型类名，**必须设置** |
| `$scopeType` | `?string` | `null` | 作用域类型，**必须设置** |
| `$scopeId` | `int` | `0` | 作用域 ID（0 = 全局） |
| `$level` | `?int` | `null` | 嵌套层级限制 |
| `$canManage` | `bool` | `false` | 是否显示导航类型管理表单 |
| `$navigationIcon` | `string\|BackedEnum\|null` | `Heroicon::OutlinedBars3BottomLeft` | 导航图标 |
| `$navigationSort` | `?int` | `1` | 导航排序 |

#### 可覆盖方法

```php
// 自定义 schema（create 和 edit 共用）
public function schema(array $arguments): array { return []; }

// create 和 edit 分别定义
public function createSchema(array $arguments): array { return []; }
public function editSchema(array $arguments): array { return []; }

// Infolist 附加属性展示
public function infolistSchema(): array { return []; }

// 自定义节点标签
public function getRecordLabel(Model $record): HtmlString|string { ... }

// 自定义嵌套集查询条件
public function getEloquentQuery($query) { return $query; }

// 额外的 scope 参数
public function nestedScoped(): array { return []; }
```

### 关键可覆盖方法

Base 页面自动通过 `nestedScoped()` 将 `scope_type`、`scope_id`、`type_id` 注入 nestedset 查询，不要手动重复添加这些 scope。`$navigationType` 会自动从配置的 `scopeType` / `scopeId` 解析或创建。

Base 页面覆盖了 `getRecordLabel()`（返回 `$record->name_label`）、`getHeaderActions()` / `getNestedsetActions()`（仅返回 createAction 和 fixNestedsetAction）、以及 `getEloquentQuery()`（追加 `->with(['content'])`）。

### 模型要求

模型必须 use `Kalnoy\Nestedset\NodeTrait`，并且实现 `getScopeAttributes()`：

```php
use Kalnoy\Nestedset\NodeTrait;

class Navigation extends Model
{
    use NodeTrait;

    public function getScopeAttributes(): array
    {
        return ['team_id', 'scope_type', 'scope_id', 'type_id'];
    }
}
```

`Navigation` 模型的 `getScopeAttributes()` 返回 `['scope_type', 'scope_id', 'type_id']`，多租户时追加 `'team_id'`。不要将 `type_id` 忽略，否则 scoped 查询会遗漏导航类型过滤。

### Post 资源

PostResource 提供文章的 CRUD 管理，支持 Scopeable、定时调度和评论/点赞集成：

```php
use Wsmallnews\Cms\Filament\Resources\Posts\BaseResource;

// BaseResource 已提供：
// - use Scopeable（applyScopeableToQuery 自动过滤）
// - form() → PostForm（含 mediaImageUpload、richEditor、markdownEditor）
// - table() → PostsTable（含 modelColumn、morphColumn、ScheduledTask 相关操作）
// - 图标、slug、导航排序、翻译标签
```

可配置的具体实现：

```php
use Wsmallnews\Cms\Filament\Resources\Posts\PostResource;

// 在 PanelProvider 中注册
$panel->resources([PostResource::class]);
```

### NavigationType 资源

NavigationTypeResource 提供导航类型的 CRUD 管理：

```php
use Wsmallnews\Cms\Filament\Resources\NavigationTypes\BaseResource;

// BaseResource 已提供：
// - use Scopeable（applyScopeableToQuery 自动过滤）
// - form() → NavigationTypeForm
// - table() → NavigationTypesTable
// - getWidgets() → Navigation Widget
```

### Tags 资源

CMS 包继承 support 包的 Tags 资源，按 `article` 类型过滤：

```php
use Wsmallnews\Cms\Filament\Resources\Tags\TagResource;

// 继承自 Wsmallnews\Support\Filament\Resources\Tags\BaseResource
// getTagType() 返回 'article'
```

### 文章模型

`Post` 继承 `SupportModel`，集成评论、点赞、浏览、媒体库和标签：

```php
use Wsmallnews\Cms\Models\Post;

// 核心特性：
// - extends SupportModel（scopeTenant、snScope）
// - implements HasSnSubject（preference 包集成）
// - use Commentable（评论系统）
// - use Preferenceable + Viewable（点赞/浏览）
// - use HasActivityLog（活动日志）
// - use InteractsWithMedia（Spatie 媒体库）
// - use HasTags（Spatie 标签）
// - use SoftDeletes
```

### Livewire 组件

CMS 包提供丰富的前端 Livewire 组件：

| 组件 | 注册名 | 说明 |
|---|---|---|
| `Livewire\Components\Navigation\Navigation` | `sn-cms-navigation` | 导航菜单 |
| `Livewire\Components\Navigation\NavigationNestedset` | `sn-cms-navigation-nestedset` | 嵌套集导航 |
| `Livewire\Components\Navigation\Brothers` | `sn-cms-navigation-brothers` | 同级导航 |
| `Livewire\Components\Navigation\Content` | `sn-cms-navigation-content` | 导航内容 |
| `Livewire\Components\Navigation\Breadcrumb` | `sn-cms-navigation-breadcrumb` | 面包屑 |
| `Livewire\Components\Post\Post` | `sn-cms-post` | 文章详情 |
| `Livewire\Components\Post\Posts` | `sn-cms-posts` | 文章列表（分页） |
| `Livewire\Components\Post\IndexPosts` | `sn-cms-index-posts` | 首页文章列表 |

所有组件继承 `Wsmallnews\Cms\Livewire\Components\Base`（→ `Wsmallnews\Support\Livewire\Base`），使用 `Scopeable` trait。

### 定时调度

文章支持定时发布/下架，通过 ScheduledTask Facade 注册：

```php
use Wsmallnews\Support\Facades\ScheduledTask;

// 在 ServiceProvider 中注册
ScheduledTask::registers('sn_post', [
    'publish' => ['label' => '发布', 'handler' => PublishHandler::class],
    'unpublish' => ['label' => '下架', 'handler' => UnpublishHandler::class],
]);

// 在表单中嵌入调度器
ScheduledTask::scheduleRepeater('sn_post');
```

### Utils 工具类

`Wsmallnews\Cms\Support\Utils` — 全部为静态方法：

| 方法 | 说明 |
|---|---|
| `getConfig(?string $name, $default)` | 读取 `sn-cms` 配置（dot notation） |
| `getScopeableContext()` | 从配置创建 ScopeableContext 值对象 |
| `getScopeable()` | 返回 `['scope_type' => '...', 'scope_id' => 0]` |
| `getScopeType()` | 获取默认 scope_type |
| `getScopeId()` | 获取默认 scope_id |
| `getPanelRegister($type)` | 获取面板注册配置（pages/resources） |
| `getModel(string $name, bool $shouldException = true)` | 获取配置的模型类名 |
| `getPostModel()` | `getModel('post')` 快捷方式 |
| `getNavigationModel()` | `getModel('navigation')` 快捷方式 |
| `getNavigationTypeModel()` | `getModel('navigation_type')` 快捷方式 |
| `getFileDirectory(?string $type)` | 获取文件目录（自动追加日期） |
| `route($name, $params, $absolute)` | CMS 内部路由（自动添加路由前缀 + 租户参数） |

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| Navigation Page 基类 | `Wsmallnews\Cms\Filament\Pages\Navigation\Base` |
| Navigation Page 实现 | `Wsmallnews\Cms\Filament\Pages\Navigation\NavigationPage` |
| Navigation Widget | `Wsmallnews\Cms\Filament\Pages\Navigation\Widgets\Navigation` |
| Navigation Schema Form | `Wsmallnews\Cms\Filament\Pages\Navigation\Schemas\NavigationForm` |
| Post Resource | `Wsmallnews\Cms\Filament\Resources\Posts\PostResource` |
| Post BaseResource | `Wsmallnews\Cms\Filament\Resources\Posts\BaseResource` |
| NavigationType Resource | `Wsmallnews\Cms\Filament\Resources\NavigationTypes\NavigationTypeResource` |
| Tags Resource | `Wsmallnews\Cms\Filament\Resources\Tags\TagResource` |
| GeneralSetting Page | `Wsmallnews\Cms\Filament\Pages\GeneralSetting` |
| 导航模型 | `Wsmallnews\Cms\Models\Navigation` |
| 导航类型模型 | `Wsmallnews\Cms\Models\NavigationType` |
| 文章模型 | `Wsmallnews\Cms\Models\Post` |
| CmsPlugin | `Wsmallnews\Cms\CmsPlugin` |
| Utils | `Wsmallnews\Cms\Support\Utils` |
| ServiceProvider | `Wsmallnews\Cms\CmsServiceProvider` |

### 常见错误

- **模型必须 use `NodeTrait`**，否则 `mount()` 抛出 `NestedsetException`。
- **`$level` 设置为 `1` 时只能有根节点**，至少 `2` 才能选择父级。
- **`$scopeType` 必须设置**，否则无法正确过滤导航数据。
- **多租户 scope 需要模型定义 `getScopeAttributes()`**，返回的字段必须包含 `team_id`。
- **`CanPagination` 已包含 `WithPagination`**，不要在 Livewire 组件中重复 `use WithPagination`。
- **counter 字段使用 JSON 格式**，模型中需配合 support 包的 `CounterCast` 使用。
- **`Utils` 所有方法都是静态的**，使用 `Utils::getConfig()` 而非 `(new Utils)->getConfig()`。

=== wsmallnews/comment/core rules ===

## Comment 包（wsmallnews/comment）

`wsmallnews/comment` 是一个多态评论系统，支持文本域、Markdown、富文本三种内容类型，深度集成 Filament 面板和 Livewire 前端。命名空间根为 `Wsmallnews\Comment`，Blade 视图前缀为 `sn-comment`，配置文件为 `config/sn-comment.php`。

### 核心架构

通过 `sn_comments` 表（主表）+ `sn_comment_contents` 表（格式化内容表）实现评论存储：

```
sn_comments                          sn_comment_contents
├── id                               ├── id
├── parent_id (自关联)                ├── contentable_type (多态)
├── commentable_type (多态关联主体)    ├── contentable_id
├── commentable_id                    ├── content (rich text/markdown)
├── commenter_type (多态评论者)        ├── content_type
├── commenter_id                     ├── team_id
├── be_replyer_type (多态被回复者)     ├── timestamps
├── be_replyer_id
├── content_type (text/markdown/richtext)
├── content (textarea 内容，格式化时为 null)
├── counter (JSON: comment_num, like_num)
├── status (normal/unaudited/hidden)
├── images (JSON 数组)
├── options (JSON)
├── team_id
├── timestamps, softDeletes
```

**三组多态关联：**
- **commentable（评论主体）**：被评论的内容实体，使用 `Commentable` trait
- **commenter（评论者）**：发表评论的用户/模型，使用 `Commenter` trait  
- **beReplyer（被回复者）**：被回复的评论者，使用 `BeReplyer` trait

**自关联树形结构**：通过 `parent_id` 实现评论嵌套，所有子评论直接挂在顶层评论下（扁平化，非多层级树）。

### ContentType 支持

评论内容支持三种格式，通过 `ContentType` 枚举控制：

| 类型 | 存储位置 | 组件 | 说明 |
|---|---|---|---|
| `Textarea` | `sn_comments.content` | `Textarea` | 纯文本，默认类型 |
| `Richtext` | `sn_comment_contents.content` | `RichEditor` | 富文本，支持文件附件 |
| `Markdown` | `sn_comment_contents.content` | `MarkdownEditor` | Markdown 格式 |

格式化内容（Richtext/Markdown）通过 `commentContent()` 多态关联到 `sn_comment_contents` 表。评论模型 scope `with('commentContent')` 预加载格式化内容。

`CommentAction` trait 中的 `configureAction()` 根据 `$this->contentType` 自动选择对应的表单组件：

```php
$schemas = match ($this->contentType) {
    ContentType::Richtext => $this->getRichtextComponents($parentComment),
    ContentType::Markdown => $this->getMarkdownComponents($parentComment),
    default => $this->getTextareaComponents($parentComment),
};
```

### CommentStatus 枚举

`Wsmallnews\Comment\Enums\CommentStatus`（BackedEnum: string）：

| 状态 | 值 | 颜色 | 图标 |
|---|---|---|---|
| `Normal` | `normal` | success | Heroicon::Eye |
| `Unaudited` | `unaudited` | warning | Heroicon::DocumentCheck |
| `Hidden` | `hidden` | gray | Heroicon::EyeSlash |

评论模型的三个 scope：`normal()`、`unaudited()`、`hidden()`。

默认状态由 `Utils::getDefaultCommentStatus()` 获取，对应配置 `sn-comment.default_status`。

### Model traits（关联侧）

评论系统通过三个 trait 建立模型的多态关联：

#### Commentable（被评论的内容模型）

`Wsmallnews\Comment\Models\Concerns\Commentable`：

```php
use Wsmallnews\Comment\Models\Concerns\Commentable;

$post->comments;                    // MorphMany 关联，获取所有评论
$post->comments()->normal();        // 带 scope 查询正常评论
```

#### Commenter（评论者模型）

`Wsmallnews\Comment\Models\Concerns\Commenter`：

```php
use Wsmallnews\Comment\Models\Concerns\Commenter;

$user->comments;                    // MorphMany 关联，获取用户所有评论
```

#### BeReplyer（被回复者模型）

`Wsmallnews\Comment\Models\Concerns\BeReplyer`：

```php
use Wsmallnews\Comment\Models\Concerns\BeReplyer;

$user->beReplyerComments;           // MorphMany 关联，被回复的评论列表
```

### Comment 模型

`Wsmallnews\Comment\Models\Comment`（继承 `SupportModel`，可通过 `config('sn-comment.models.comment')` 替换）。

**核心属性和关系：**

```php
class Comment extends SupportModel
{
    use Likeable;                   // 评论可被点赞（依赖 preference 包）
    use Preferenceable;
    use SoftDeletes;

    protected $table = 'sn_comments';

    protected $casts = [
        'counter' => CounterCast::class,
        'images' => 'array',
        'options' => 'array',
        'status' => CommentStatus::class,
        'content_type' => ContentType::class,
    ];

    // MorphTo 关联
    commentable()       // 评论主体
    commenter()         // 评论者
    beReplyer()         // 被回复者

    // 自关联树
    children()          //  HasMany 子评论（按 created_at asc 排序）
    parent()            //  BelongsTo 父评论

    // 格式化内容
    commentContent()    //  MorphOne 关联 CommentContent

    // Query Scopes
    scopeNormal()
    scopeUnaudited()
    scopeHidden()
}
```

**计数器字段**（`counter` JSON 列，需配合 `CounterCast`）：
- `comment_num`：子评论数量
- `like_num`：点赞数量

当回复某条评论时，系统自动给上级评论的 `counter->comment_num` 加 1（通过 `incrementJson`）。

**点赞功能**：Comment 模型 use 了 `Likeable` trait（来自 preference 包），允许用户点赞评论。`toggleLike()` 操作在 Livewire 和 Filament 组件中均可使用。

### CommentContent 模型

`Wsmallnews\Comment\Models\CommentContent`（继承 `SupportModel`，可通过 `config('sn-comment.models.comment_content')` 替换）。

```php
class CommentContent extends SupportModel
{
    protected $table = 'sn_comment_contents';

    protected $casts = [
        'content_type' => ContentType::class,
    ];

    contentable()       // MorphTo 多态关联
    team()              // BelongsTo 租户
}
```

`contentable` 的多态映射为 `'sn-comment' => Comment::class`，在 `CommentServiceProvider::packageBooted()` 中通过 `Relation::enforceMorphMap` 注册。

### Livewire 前端组件

三个前端组件均继承 `Wsmallnews\Comment\Livewire\Components\Base`（→ `Wsmallnews\Support\Livewire\Base`，使用 `Scopeable` trait）。

#### Comments（评论列表 + 添加评论）

`Wsmallnews\Comment\Livewire\Components\Comments`，注册名 `sn-comment-components-comments`：

```php
use CanAddComment;          // $canAddComment = true
use CanBeContained;         // 支持容器模式
use CanPagination;          // 分页（已包含 WithPagination，不要重复 use）
use CommentAction;          // 评论/回复/删除/审核操作
use HasAuth;                // 认证用户
use HasCommentStatus;       // $commentStatus
use HasContentType;         // $contentType
use HasProperties;          // 自定义属性传递
use WithoutUrlPagination;   // 非 URL 分页
```

属性：
- `$commentable`（Model）：评论主体模型
- `$parentId`（int，默认 0）：父级评论 ID
- `$loadChildren`（bool，默认 false）：是否自动加载子评论
- `$comments`（Collection）：评论集合

**使用示例：**

```blade
{{-- 在某文章页面显示评论 --}}
<livewire:sn-comment-components-comments
    :commentable="$post"
    content-type="textarea"
    :can-add-comment="true"
    :empty-label="'暂无评论'"
/>

{{-- 富文本模式评论 --}}
<livewire:sn-comment-components-comments
    :commentable="$article"
    content-type="richtext"
    comment-status="normal"
/>
```

#### Comment（单条评论展示 + 展开子评论 + 点赞）

`Wsmallnews\Comment\Livewire\Components\Comment`，注册名 `sn-comment-components-comment`：

属性：
- `$commentable`（Model）：评论主体
- `$comment`（CommentModel）：当前评论实例
- `$loadChildren`（bool，默认 false）：是否展开子评论

方法：
- `startLoadChildren()`：展开子评论
- `hiddenChildren()`：收起子评论
- `toggleLike()`：点赞/取消点赞，未登录时发送失败通知

```blade
{{-- 往往在 comments 组件内部递归使用 --}}
<livewire:sn-comment-components-comment
    :commentable="$commentable"
    :comment="$comment"
    :load-children="false"
/>
```

#### Base（公共基类）

`Wsmallnews\Comment\Livewire\Components\Base`：

```php
class Base extends BaseComponent    // Wsmallnews\Support\Livewire\Base
{
    use Scopeable;
}
```

所有前端评论组件通过 Base 获得 scope 能力。

### Filament 资源

CommentResource 提供评论的 CRUD 管理，支持 Scopeable 和插件配置：

```php
use Wsmallnews\Comment\Filament\Resources\Comments\BaseResource;

// BaseResource 已提供：
// - use Scopeable（applyScopeableToQuery 自动过滤）
// - table() → CommentTable（含 contentColumn、morphColumn、morphFilter 等）
// - infolist() → 评论详情展示
// - getEloquentQuery() → 带 scope + 预加载关联
```

可配置的具体实现：

```php
use Wsmallnews\Comment\Filament\Resources\Comments\CommentResource;

// 在 PanelProvider 中注册
$panel->resources([CommentResource::class]);
```

### Filament 面板组件

Filament 面板中的评论组件直接继承 `Filament\Pages\BasePage`，更适合面板环境使用。

#### Comments（面板评论列表）

`Wsmallnews\Comment\Filament\Pages\Comment\Components\Comments`，注册名 `sn-comment-fi-comments`：

使用与 Livewire 版本相同的 traits，但直接继承 `BasePage`。视图为 `sn-comment::filament.pages.comment.components.comments`。

额外属性：
- `$commenter`（?Model）：按评论者筛选
- `$beReplyer`（?Model）：按被回复者筛选

`getViewData()` 按优先级构建查询：
1. `$commentable` → `commentable->comments()`
2. `$commenter` → `commenter->comments()`
3. `$beReplyer` → `beReplyer->beReplyComments()`
4. 默认 → `CommentModel::query()`（所有评论）

#### Comment（面板单条评论）

`Wsmallnews\Comment\Filament\Pages\Comment\Components\Comment`，注册名 `sn-comment-fi-comment`：

与 Livewire 版本功能相同，但继承 `BasePage`。视图为 `sn-comment::filament.pages.comment.components.comment`。

在 mount 时自动设置认证用户：`$this->authUser(Filament::auth()->user())`。

#### 面板 Widget 包装器

`Wsmallnews\Comment\Filament\Pages\Comment\Widgets\Comment`，视图为 `sn-comment::filament.pages.comment.widgets.comment`。在 Filament 页面中作为 Widget 嵌入渲染。

```blade
{{-- 在 Filament 页面中使用 --}}
<x-filament-widgets::widgets>
    <livewire:sn-comment-fi-comments
        :commentable="$record"
        content-type="richtext"
    />
</x-filament-widgets::widgets>
```

### Filament 页面

#### Base（页面基类）

`Wsmallnews\Comment\Filament\Pages\Comment\Base`：

```php
abstract class Base extends Page
{
    use Scopeable;

    protected static ?string $slug = 'comments';
    protected static ?int $navigationSort = 1;
    protected string $view = 'sn-comment::filament.pages.comment.comment-page';

    // 可被子类覆盖的静态属性
    protected static ?string $emptyLabel = null;
    protected static ?string $emptyTipLabel = null;
    protected static ContentType $contentType = ContentType::Textarea;
    protected static ?CommentStatus $commentStatus = null;

    // 静态方法（翻译默认值）
    getModelLabel()           -> __('sn-comment::comment.comment_page.model_label')
    getPluralModelLabel()    -> __('sn-comment::comment.comment_page.plural_model_label')
    getTitle()               -> __('sn-comment::comment.comment_page.title')
    getNavigationLabel()     -> __('sn-comment::comment.comment_page.navigation_label')
    getNavigationGroup()     -> __('...global_default.navigation_group')
    getContentType()         -> Textarea
    getCommentStatus()       -> null
    getEmptyLabel()          -> __('...comment_page.no_comments')
    getEmptyTipLabel()       -> __('...comment_page.no_comments_description')
    getProperties()          -> ['emptyLabel' => ..., 'emptyTipLabel' => ...]
}
```

#### CommentPage（面板页面）

`Wsmallnews\Comment\Filament\Pages\Comment\CommentPage`（final class）：

继承 `Base`，注册到 Filament 面板。使用 `BelongsToParent`、`BelongsToTenant`、`HasGlobalSearch`、`HasLabels`、`HasNavigation`、`HasCustomProperties` traits。

核心方法覆盖：
- `getScopeType()` → 优先从 `CommentPlugin` 自定义属性读取
- `getScopeId()` → 同上
- `getContentType()` → 优先从自定义属性读取
- `getCommentStatus()` → 仅从自定义属性读取
- `getEmptyLabel()` / `getEmptyTipLabel()` → 优先从自定义属性，fallback 到 parent

`getEssentialsPlugin()` 返回 `CommentPlugin::get()`。

### Livewire Concerns（Traits）

#### CommentAction（核心操作逻辑）

`Wsmallnews\Comment\Livewire\Concerns\CommentAction`：

提供 8 个 Action 方法和 3 个私有表单组件方法：

**Filament Actions（面板用）：**
- `filamentCommentAction()`：CreateAction，添加评论
- `filamentReplyAction()`：CreateAction（link 样式），回复评论
- `filamentDeleteAction()`：删除评论（使用 `ActionComponents::deleteAction`）
- `filamentStatusAction()`：审核评论状态（Radio 切换）

**通用 Actions（前端用）：**
- `commentAction()`：CreateAction，添加评论
- `replyAction()`：CreateAction（link 样式），回复评论

**私有方法：**
- `configureAction(CreateAction, $type)`：核心配置方法，处理创建/回复的完整流程
- `getTextareaComponents($parentComment)`：文本域 + 图片上传
- `getRichtextComponents($parentComment)`：富文本编辑器（通过 commentContent 关联）
- `getMarkdownComponents($parentComment)`：Markdown 编辑器（通过 commentContent 关联）

`configureAction()` 的关键逻辑：

1. 根据 `$this->contentType` 选择表单组件
2. 处理回复场景：自动填充 `parent_id`、`be_replyer_*` 字段
3. 创建模型时关联 `commentable`、`commenter`
4. 提供额外字段：`commenter_name`、`commenter_avatar_url`、`status`、`content_type`
5. 回复时自动递增上级评论的 `counter->comment_num`
6. sticky modal、根据内容类型设置宽度

```php
// configureAction 中的 using 回调关键逻辑
$parentCommentId = $arguments['id'] ?? null;
$parentComment = $parentCommentId ? Utils::getCommentModel()::find($parentCommentId) : null;

if ($parentComment) {
    $data['parent_id'] = $parentComment->parent_id ?: $parentComment->id;
    $data['be_replyer_type'] = $parentComment->commenter_type;
    $data['be_replyer_id'] = $parentComment->commenter_id;
    $data['be_replyer_name'] = $parentComment->commenter_name;
    $data['be_replyer_avatar_url'] = $parentComment->commenter_avatar_url;
}
```

#### 其他 Concerns

| Trait | 文件 | 说明 |
|---|---|---|
| `CanAddComment` | `Livewire\Concerns\CanAddComment` | `$canAddComment = true`，控制组件是否允许用户添加评论 |
| `CanComment` | `Livewire\Concerns\CanComment` | `$canComment = true`，控制是否可评论（与 `CanAddComment` 独立） |
| `HasCommentStatus` | `Livewire\Concerns\HasCommentStatus` | `$commentStatus = null`，用于设置评论发布后的默认状态 |

### CommentPlugin

`Wsmallnews\Comment\CommentPlugin`（implements `Filament\Contracts\Plugin`）。

使用 traits（来自 `BezhanSalleh\PluginEssentials` 和 support 包）：
- `BelongsToParent`、`BelongsToTenant`
- `HasGlobalSearch`、`HasLabels`、`HasNavigation`
- `HasPluginDefaults`、`WithMultipleResourceSupport`
- `HasCustomProperties`

**插件 ID**：`sn-comment`

**`register()` 方法**：从 `Utils::getPanelRegister('pages')` 注册页面。

**`getPluginDefaults()`**（所有翻译使用闭包延迟求值）：

```php
[
    'navigationGroup' => fn () => __('...global_default.navigation_group'),
    'globallySearchable' => false,
    'resources' => [
        CommentPage::class => [
            'modelLabel' => fn () => __('...model_label'),
            'pluralModelLabel' => fn () => __('...plural_model_label'),
            'navigationLabel' => fn () => __('...navigation_label'),
            'navigationIcon' => Heroicon::OutlinedChatBubbleLeft,
            'activeNavigationIcon' => Heroicon::ChatBubbleLeft,
            'navigationSort' => 1,
        ],
    ],
]
```

### Service Provider

`Wsmallnews\Comment\CommentServiceProvider`（继承 `PackageServiceProvider`）：

**配置：**
- 名称：`sn-comment`
- 视图前缀：`sn-comment`
- 命令：`CommentInstallCommand`
- 迁移：`create_sn_comments_table`、`create_sn_comment_contents_table`
- 翻译：自动加载 `resources/lang/` 目录
- 视图：`hasViews('sn-comment')`

**`packageBooted()` 中的注册：**

```php
// 模型别名
Relation::enforceMorphMap(['sn-comment' => Utils::getCommentModel()]);

// Filament 面板组件（BasePage 继承）
Livewire::component('sn-comment-fi-comments', Comments::class);
Livewire::component('sn-comment-fi-comment', Comment::class);

// Livewire 前端组件
Livewire::component('sn-comment-components-comments', ComponentsComments::class);
Livewire::component('sn-comment-components-comment', ComponentsComment::class);

// Stubs 发布
// Filament 资源注册
// 图标注册
```

### 配置

`config/sn-comment.php`：

```php
return [
    'scopeable' => [
        'scope_type' => 'sn-comment',       // 默认作用域类型
        'scope_id' => 0,                     // 0 = 全局
    ],
    'default_content_type' => ContentType::Textarea,
    'default_status' => CommentStatus::Normal,
    'models' => [
        'comment' => Models\Comment::class,         // 可替换
        'comment_content' => Models\CommentContent::class,  // 可替换
    ],
    'panel_register' => [
        'pages' => [CommentPage::class],    // 面板注册的页面
    ],
    'file_directory' => 'sn/comment/',       // 文件上传基础目录
];
```

### Utils 工具类

`Wsmallnews\Comment\Support\Utils` — 全部为静态方法：

| 方法 | 说明 |
|---|---|
| `getConfig(?string $name, $default)` | 读取 `sn-comment` 配置（dot notation） |
| `getScopeableContext()` | 从配置创建 ScopeableContext 值对象 |
| `getScopeable()` | 返回 `['scope_type' => '...', 'scope_id' => 0]` |
| `getScopeType()` | 获取默认 scope_type |
| `getScopeId()` | 获取默认 scope_id |
| `getDefaultContentType()` | 获取默认内容类型 |
| `getDefaultCommentStatus()` | 获取默认评论状态 |
| `getPanelRegister($type)` | 获取面板注册配置（pages/resources） |
| `getModel(string $name, bool $shouldException = true)` | 获取配置的模型类名，`false` 时不抛异常 |
| `getCommentModel()` | `getModel('comment')` 快捷方式 |
| `getCommentContentModel()` | `getModel('comment_content')` 快捷方式 |
| `getFileDirectory(?string $type)` | 获取文件目录（自动追加日期），如 `sn/comment/comments/20260530` |

### Testing

`Wsmallnews\Comment\Testing\TestsComment` trait，用于测试中辅助评论相关操作。

### Facade

`Wsmallnews\Comment\Facades\Comment`，accessor 为 `\Wsmallnews\Comment\Comment::class`（空壳类，仅用于 Facade 注册）。

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| Comment 模型 | `Wsmallnews\Comment\Models\Comment` |
| CommentContent 模型 | `Wsmallnews\Comment\Models\CommentContent` |
| Commenter trait | `Wsmallnews\Comment\Models\Concerns\Commenter` |
| Commentable trait | `Wsmallnews\Comment\Models\Concerns\Commentable` |
| BeReplyer trait | `Wsmallnews\Comment\Models\Concerns\BeReplyer` |
| Livewire 前端组件 | `Wsmallnews\Comment\Livewire\Components\` |
| Livewire Base | `Wsmallnews\Comment\Livewire\Components\Base` |
| Livewire Concerns | `Wsmallnews\Comment\Livewire\Concerns\` |
| Filament 页面组件 | `Wsmallnews\Comment\Filament\Pages\Comment\Components\` |
| Filament Widgets | `Wsmallnews\Comment\Filament\Pages\Comment\Widgets\` |
| Filament 页面 | `Wsmallnews\Comment\Filament\Pages\Comment\Base` / `CommentPage` |
| CommentPlugin | `Wsmallnews\Comment\CommentPlugin` |
| CommentStatus | `Wsmallnews\Comment\Enums\CommentStatus` |
| Utils | `Wsmallnews\Comment\Support\Utils` |
| Facade | `Wsmallnews\Comment\Facades\Comment` |
| 异常 | `Wsmallnews\Comment\Exceptions\CommentException` |

### 常见错误

- **评论主体模型必须 use `Commentable` trait**，否则 `$post->comments()` 关联查询不存在。
- **评论者模型需要 `getFilamentName()` 方法**，`CommentAction::configureAction()` 在创建评论时会调用 `$user->getFilamentName()` 填充 `commenter_name` 字段。
- **`CanPagination` 已包含 `WithPagination`**，不要在 Livewire/Filament 组件中重复 `use WithPagination`。
- **counter 字段使用 JSON 格式**，模型中需配合 support 包的 `CounterCast` 使用：`'counter' => CounterCast::class`。使用 `incrementJson('counter->comment_num')` 而非直接赋值。
- **格式化内容需预加载关联**：RichText/Markdown 模式下，评论列表查询需 `.when($this->isFormattedContent(), fn($q) => $q->with('commentContent'))`，否则 commentContent 为 null。
- **`content` 字段在格式化模式下为 null**：Richtext/Markdown 内容存储在 `sn_comment_contents` 表，`sn_comments.content` 仅在 Textarea 模式下使用。
- **面板组件 mount 时需设置 authUser**：`$this->hasAuthUser() || $this->authUser(Filament::auth()->user())`，否则 `CommentAction` 中的认证检查会失败。
- **`sn-comment` morph map**：在 `CommentServiceProvider::packageBooted()` 中通过 `Relation::enforceMorphMap` 注册，确保所有多态查询使用别名而非全类名。
- **`Utils::getModel()` 默认会抛异常**，传递 `false` 作为第二个参数以允许返回 `null`。
- **`Utils` 所有方法都是静态的**，使用 `Utils::getConfig()` 而非 `(new Utils)->getConfig()`。

=== wsmallnews/filament-nestedset/core rules ===

## Nestedset 包（wsmallnews/filament-nestedset）

`wsmallnews/filament-nestedset` 是基于 [kalnoy/nestedset](https://github.com/lazychaser/laravel-nestedset) 的 Filament 嵌套集树形管理插件，支持 Filament v4/v5、多语言、多租户和 Tabs 筛选。命名空间根为 `Wsmallnews\FilamentNestedset`，Blade 视图前缀为 `sn-filament-nestedset`，配置文件为 `config/sn-filament-nestedset.php`。

### 核心架构

依赖 `kalnoy/nestedset` 的 `NodeTrait` 实现嵌套集模型，通过 `scoped` 特性支持多租户和 Tabs 筛选。当前采用 **Page + Widget + Livewire Component** 架构：

- **NestedsetPage**（`Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage`）：继承 Filament `Page` 的抽象管理页面，使用 `InteractsWithNestedset`，提供 CRUD、拖拽排序、修复树、scope 查询和默认页面视图。
- **Filament Widget**（`Wsmallnews\FilamentNestedset\Filament\Pages\Widgets\Nestedset`）：继承 `Filament\Widgets\Widget` 的抽象 Widget，同样使用 `InteractsWithNestedset`，适合嵌入自定义 Filament 页面。
- **Frontend Livewire Component**（`Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset`）：继承 `Livewire\Component` 的前端只读树形展示组件。

### NestedsetPage（管理页面基类）

`Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage` 继承 `Filament\Pages\Page`，使用 `Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset`。该 concern 又使用 `HasNestedsetActions` 和 Filament `HasTabs`。

#### 创建页面

```bash
php artisan make:filament-nestedset-page
```

生成的页面类继承 `NestedsetPage`，需设置 `$model`，通常也会设置 `$recordTitleAttribute`。

#### 静态属性（类级别配置，通过子类覆盖）

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$model` | `?string` | `null` | 嵌套集模型类名，**必须设置** |
| `$modelLabel` | `?string` | `null` | 模型标签，为空时自动从 model 推断 |
| `$recordTitleAttribute` | `string` | `'name'` | 节点标题字段名 |
| `$level` | `?int` | `null` | 嵌套集层级限制，`null` = 不限制 |
| `$emptyLabel` | `?string` | 翻译文本 | 树为空时的提示文本 |
| `$emptyTipLabel` | `?string` | 翻译文本 | 树为空时的辅助提示 |
| `$tabFieldName` | `?string` | `null` | Tabs 筛选的字段名 |
| `$infolistAlignment` | `Alignment` | `Alignment::Right` | Infolist 对齐方式 |
| `$infolistHiddenEndpoint` | `string` | `'md'` | Infolist 显示的最小断点 |
| `$isScopedToTenant` | `bool` | `true` | 是否关联 Filament 当前租户 |
| `$navigationIcon` | `string\|BackedEnum\|null` | `Heroicon::OutlinedBars3BottomRight` | 导航图标（继承自 Page） |

#### 实例属性

| 属性 | 类型 | 说明 |
|---|---|---|
| `$activeTab` | `?string` | 当前选中的 Tab（`#[Url]` 绑定） |
| `$view` | `string` | 页面 Blade 视图路径，默认 `sn-filament-nestedset::filament.pages.nestedset-page` |

#### 可覆盖方法

```php
// 自定义 schema（create 和 edit 共用）
protected function schema(array $arguments): array { return []; }

// create 和 edit 分别定义
protected function createSchema(array $arguments): array { return []; }
protected function editSchema(array $arguments): array { return []; }

// Infolist 附加属性展示
protected function infolistSchema(): array { return []; }

// 自定义节点标签，支持 HtmlString
protected function getRecordLabel(Model $record): HtmlString | string { ... }

// 自定义嵌套集查询条件；用于继续收窄已经 scoped 的查询
protected function getEloquentQuery($query) { return $query->where('status', 'normal'); }

// 额外的 scope 参数（kalnoy/nestedset scoped）
protected function nestedScoped(): array { return ['category_id' => 5]; }

// 动态层级限制
protected static function getLevel(): ?int { return static::$level; }

// Tabs 配置
public function getTabs(): array
{
    return [
        'web' => Tab::make()->label('Website Navigation'),
        'shop' => Tab::make()->label('Shop Navigation'),
    ];
}
```

### 查询与 scope 规则

`InteractsWithNestedset::getQuery()` 会按以下顺序构建查询：

1. 当 `static::isScopedToTenant()` 为 `true` 且 `Filament::getTenant()` 存在时，加入 `team_id => tenant id`。
2. 当 `static::getTabFieldName()` 非空时，加入 `tabFieldName => $this->activeTab`。
3. 合并 `nestedScoped()` 返回的自定义 scope。使用 `array_merge()`，所以自定义 scope 与租户/Tab scope 使用相同 key 时会覆盖前面的值。
4. 有任何 scope 时使用 `Model::scoped($scopes)`；没有 scope 时使用 `(new $model)->newScopedQuery()`。
5. 最后调用 `getEloquentQuery($query)->defaultOrder()`。

### 操作 Actions

页面/Widget 提供以下内置 Actions：

| Action | 返回类型 | 说明 |
|---|---|---|
| `createAction()` | `Action`（实际为 `CreateAction`） | 创建节点（header action） |
| `createChildAction()` | `Action`（实际为 `CreateAction`） | 创建子节点（行内） |
| `editAction()` | `Action`（实际为 `EditAction`） | 编辑节点，通过 scoped query 解析记录 |
| `deleteAction()` | `Action`（实际为 `DeleteAction`） | 删除节点，受 `allow_delete_parent` / `allow_delete_root` 配置控制 |
| `moveNodeAction()` | `Action` | 拖拽排序确认，受 `$level` 层级限制控制 |
| `fixNestedsetAction()` | `Action` | 对当前 scoped 查询执行 `fixTree()` 修复树结构 |

`createAction()` 会把当前 scoped query model attributes 合并到提交数据中，用于自动带上 `team_id`、Tab 字段、自定义 scope 字段等；创建时会从 `parent_id` 或 `parentId` argument 中解析父节点，并在保存前移除 `parent_id`。

### 模型要求

模型必须 use `Kalnoy\Nestedset\NodeTrait`，否则 `mount()` / `mountInteractsWithNestedset()` 会抛出 `NestedsetException`。

```php
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait;

    // 多租户 / Tabs / 自定义 scope 支持：定义 scope attributes
    public function getScopeAttributes(): array
    {
        return ['team_id', 'type'];
    }
}
```

### Filament Widget（Nestedset）

`Wsmallnews\FilamentNestedset\Filament\Pages\Widgets\Nestedset` 继承 `Filament\Widgets\Widget`，实现 `HasActions` 和 `HasSchemas`，并使用：

- `Filament\Actions\Concerns\InteractsWithActions`
- `Filament\Schemas\Concerns\InteractsWithSchemas`
- `Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset`

默认 `$columnSpan = 'full'`，默认视图为 `sn-filament-nestedset::filament.pages.widgets.nestedset`。

### Nestedset Livewire 组件（树形展示）

`Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset` 继承 `Livewire\Component`，提供可嵌入前端页面的只读树形展示。

#### 实例属性（可通过 Blade 属性传入）

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$model` | `?string` | `null` | 嵌套集模型类名 |
| `$recordTitleAttribute` | `string` | `'name'` | 节点标题字段名 |
| `$showLevel` | `?string` | `null` | 显示的层级限制 |
| `$emptyLabel` | `?string` | `''` | 树为空时的提示文本 |
| `$view` | `?string` | `'sn-filament-nestedset::livewire.components.nestedset'` | 组件视图 |
| `$recordView` | `?string` | `'sn-filament-nestedset::components.nestedset-record'` | 节点记录视图 |

#### 可覆盖方法

```php
// 自定义节点标签
public function getRecordLabel(Model $record): HtmlString | string { ... }

// 自定义节点跳转链接
public function getRecordUrl(Model $record): string | HtmlString | null { return null; }

// 自定义节点激活状态
public function getHasActive(Model $record): bool { return false; }

// 自定义数据源（必须覆盖或设置 $model）
public function getNestedset(): Collection { ... }

// 自定义查询条件
protected function getEloquentQuery($query) { return $query; }

// 额外 scope 参数
protected function nestedScoped(): array { return []; }
```

默认 `getNestedset()` 会执行 `getQuery()->withDepth()->get()`，当 `$showLevel` 非空时保留 `depth <= showLevel` 的记录，然后调用 `toTree()`。

#### 事件

| 事件 | 触发时机 |
|---|---|
| `sn-filament-nestedset-leaf-click` | 点击叶子节点 |
| `sn-filament-nestedset-node-click` | 点击非叶子节点 |

#### 使用示例

```php
use Livewire\Attributes\On;
use Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset;

class Categories extends Nestedset
{
    public ?string $model = Category::class;

    public string $recordTitleAttribute = 'name_label';

    #[On('sn-filament-nestedset-leaf-click')]
    public function clickCategory($recordId): void
    {
        $this->categoryId = $recordId;
    }
}
```

### 表单字段

#### KalnoyNestedsetSelectTree

`Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree` 继承 `codewithdennis/filament-select-tree` 的 `SelectTree`，支持嵌套集层级限制。

```php
use Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree;

KalnoyNestedsetSelectTree::make('parent_id')
    ->level(2)              // 限制可选层级（null = 不限制）
    ->searchable()
    ->enableBranchNode()    // 允许选择非叶子节点
    ->withCount()
    ->query(fn () => Category::query(), titleAttribute: 'name', parentAttribute: 'parent_id');
```

`level(1)` 只加载根节点；`level(2+)` 会加载 `depth < level` 的非根节点；`level(null)` 不限制层级。

### 配置

`config/sn-filament-nestedset.php`：

```php
return [
    'allow_delete_parent' => false,                   // 是否允许删除有子节点的节点
    'allow_delete_root' => false,                     // 是否允许删除根节点
    'create_action_modal_show_parent_select' => true, // 创建弹窗是否显示父级选择
    'show_create_child_node_action_in_row' => true,   // 行内是否显示“创建子节点”按钮
    'autoload_assets' => true,                        // 是否自动加载 CSS（自定义主题时关闭）
];
```

### 多租户支持

基于 `kalnoy/nestedset` 的 `scoped` 特性。模型需定义 `getScopeAttributes()` 返回 scope 字段数组。页面默认 `$isScopedToTenant = true`，自动将 `team_id` 加入 scope。

如果面板支持多租户但当前页面不需要，设置 `$isScopedToTenant = false`。

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| Page 基类 | `Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage` |
| Filament Widget | `Wsmallnews\FilamentNestedset\Filament\Pages\Widgets\Nestedset` |
| InteractsWithNestedset | `Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset` |
| HasNestedsetActions | `Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\HasNestedsetActions` |
| 前端 Livewire 组件 | `Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset` |
| 表单字段 | `Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree` |
| Artisan 命令 | `Wsmallnews\FilamentNestedset\Commands\MakeNestedsetPageCommand` |
| 异常 | `Wsmallnews\FilamentNestedset\Exceptions\NestedsetException` |
| ServiceProvider | `Wsmallnews\FilamentNestedset\FilamentNestedsetServiceProvider` |

### 常见错误

- **模型必须 use `NodeTrait`**，否则 `mount()` / `mountInteractsWithNestedset()` 抛出 `NestedsetException`。
- **`$level` 设置为 `1` 时只能有根节点**，至少 `2` 才能选择父级（`createAction` 中 `getLevel() >= 2` 才显示父级选择字段）。
- **多租户 / Tabs / 自定义 scope 需要模型定义 `getScopeAttributes()`**，返回的字段必须包含对应 scope 字段，如 `team_id`、Tab 字段、`nestedScoped()` 字段。
- **`nestedScoped()` 与租户/Tab 使用相同 key 时会覆盖前面的 scope**，这是当前 `array_merge()` 行为。
- **`getEloquentQuery()` 应继续收窄已经 scoped 的查询**，不要绕过 `Model::scoped($scopes)`，否则多租户、Tabs 或自定义 scope 可能失效。
- **Livewire 前端组件必须覆盖 `getNestedset()` 或设置 `$model`**，否则抛出异常。
- **`autoload_assets` 关闭后需在自定义主题 CSS 中手动引入**：`@import '../../../../vendor/wsmallnews/filament-nestedset/resources/css/index.css'`。
- **拖拽移动节点受 `$level` 限制**，超过层级限制时操作会被取消并提示。

=== wsmallnews/preference/core rules ===

## Preference 包（wsmallnews/preference）

`wsmallnews/preference` 是一个多态偏好/互动追踪系统，提供点赞（like）、关注（follow）、浏览（view）三种互动类型的完整功能。命名空间根为 `Wsmallnews\Preference`，Blade 视图前缀为 `sn-preference`，配置文件为 `config/sn-preference.php`。

### 核心架构

通过单张 `sn_preferences` 表 + 多态关联实现三种互动类型：

- **preferencer（操作者）**：执行点赞/关注/浏览的用户或模型，必须实现 `HasSnIdentifiable` 接口
- **preferenceable（目标）**：被操作的内容实体，必须实现 `HasSnSubject` 接口
- **计数器**：所有操作自动维护 JSON 计数器（`counter->like_num`、`counter->follow_num`、`counter->followed_num`、`counter->view_num`），配合 support 包的 `CounterCast` 使用

### 依赖的接口（来自 support 包）

preference 包的 Blade 组件依赖以下两个接口获取展示数据：

- `Wsmallnews\Support\Contracts\HasSnIdentifiable` — 操作者侧接口（`getSnId()`、`getSnName()`、`getSnAvatarUrl()`、`getSnEmail()`、`getSnHrefUrl()`）
- `Wsmallnews\Support\Contracts\HasSnSubject` — 目标侧接口（`getSnSubjectId()`、`getSnSubjectTitle()`、`getSnSubjectDescription()`、`getSnSubjectCoverUrl()`、`getSnSubjectHrefUrl()`）

User 模型可直接 use `Wsmallnews\Support\Concerns\UserIdentifiable` trait 来实现 `HasSnIdentifiable`。`HasSnSubject` 没有默认 trait，每个模型需自行实现。

### hasLink 机制

三个基础 Blade 组件（`sn-preference::components.preference`、`preferenceable`、`preferencer`）均接受 `hasLink` prop（默认 `false`）。**注意：`isLink` 已改名为 `hasLink`，旧属性名不再有效。**

- **`hasLink=true` 且 `getSnHrefUrl()`/`getSnSubjectHrefUrl()` 返回非空 URL**：组件渲染为 `<a>` 标签，可点击跳转
- **`hasLink=true` 但 URL 为空**：组件渲染为 `<div>`，点击时通过 `wire:click.stop` 分发 Livewire 事件（`sn-preference-preferencer-click` 或 `sn-preference-preferenceable-click`），由父组件处理
- **`hasLink=false`（默认）**：组件渲染为普通 `<div>`，无交互

```blade
{{-- 可点击跳转的偏好列表项 --}}
<x-sn-preference::preferenceable
    :preference="$item"
    :preferenceable="$item->preferenceable"
    :has-link="true"
/>

{{-- 无链接，点击时分发事件 --}}
<x-sn-preference::preferencer
    :preference="$item"
    :preferencer="$item->preferencer"
    :has-link="true"
/>
```

### Model traits（操作者侧 — Preferencer）

模型必须实现 `HasSnIdentifiable` 接口后才能使用以下 traits。所有操作自动维护 JSON 计数器。

#### Follower（关注）

`Wsmallnews\Preference\Models\Concerns\Preferencer\Follower`：

```php
use Wsmallnews\Preference\Models\Concerns\Preferencer\Follower;

// 核心操作
$user->follow($post);           // 关注，返回 Preference 记录
$user->unfollow($post);         // 取消关注，返回 bool
$user->toggleFollow($post);     // 切换关注状态

// 状态查询
$user->isFollowing($post);          // 是否已关注
$user->isMutualFollowed($post);     // 是否互相关注

// 关联和统计
$user->followingUsers();        // MorphToMany，我关注的用户列表
$user->followingCount();        // 我关注的用户数量
$user->follows();               // MorphMany 关联（type='follow'）

// 批量附加关注状态
$user->attachFollowStatus($posts);  // 为集合中的每个模型设置 has_followed 属性
```

互相关注时，系统会在 `options` JSON 列中自动写入 `followed_at` 时间戳。

#### Liker（点赞）

`Wsmallnews\Preference\Models\Concerns\Preferencer\Liker`：

```php
use Wsmallnews\Preference\Models\Concerns\Preferencer\Liker;

// 核心操作
$user->like($post);             // 点赞，返回 Preference 记录
$user->unlike($post);           // 取消点赞，返回 bool
$user->toggleLike($post);       // 切换点赞状态

// 状态查询
$user->hasLiked($post);         // 是否已点赞

// 关联
$user->likes();                 // MorphMany 关联（type='like'）

// 批量附加点赞状态
$user->attachLikeStatus($posts);    // 为集合中的每个模型设置 has_liked 属性
```

#### Viewer（浏览）

`Wsmallnews\Preference\Models\Concerns\Preferencer\Viewer`：

```php
use Wsmallnews\Preference\Models\Concerns\Preferencer\Viewer;

// 核心操作
$user->view($post);             // 记录浏览，返回 Preference 记录（重复浏览时更新 updated_at）

// 状态查询
$user->hasViewed($post);        // 是否已浏览

// 删除记录
$user->deleteView($post);       // 删除单条浏览记录
$user->clearAllViews($type);    // 清空所有浏览记录（不限租户和 scope）
$user->clearScopeableViews(['scope_type' => 'post', 'scope_id' => 0], $type);  // 按 scope 清空

// 关联
$user->views();                 // MorphMany 关联（type='view'）

// 批量附加浏览状态
$user->attachViewStatus($posts);    // 为集合中的每个模型设置 has_viewed 属性
```

### Model traits（目标侧 — Preferenceable）

被操作的内容模型使用以下 traits。

#### Followable（被关注）

`Wsmallnews\Preference\Models\Concerns\Preferenceable\Followable`：

```php
use Wsmallnews\Preference\Models\Concerns\Preferenceable\Followable;

// 状态查询
$post->isFollowedBy($user);         // 是否被某用户关注
$post->isMutualFollowedWith($user); // 是否与某用户互关

// 关联和统计
$post->userFollowers();             // MorphToMany，粉丝列表（仅 User 类型）
$post->followersCount();            // 粉丝数量
$post->follows();                   // MorphMany 关联（type='follow'）
```

#### Likeable（被点赞）

`Wsmallnews\Preference\Models\Concerns\Preferenceable\Likeable`：

```php
use Wsmallnews\Preference\Models\Concerns\Preferenceable\Likeable;

// 状态查询
$post->isLikedBy($user);        // 是否被某用户点赞

// 关联
$post->userLikers();            // MorphToMany，点赞用户列表（仅 User 类型）
$post->likes();                 // MorphMany 关联（type='like'）
```

#### Viewable（被浏览）

`Wsmallnews\Preference\Models\Concerns\Preferenceable\Viewable`：

```php
use Wsmallnews\Preference\Models\Concerns\Preferenceable\Viewable;

$post->view($user);             // 记录浏览（$user 为 null 时只增加计数不记录）
$post->isViewedBy($user);       // 是否被某用户浏览过
$post->userViewers();           // MorphToMany，浏览用户列表（仅 User 类型）
$post->views();                 // MorphMany 关联（type='view'）
```

### Preference 模型

`Wsmallnews\Preference\Models\Preference`（可通过 `config('sn-preference.models.preference')` 替换）。

表 `sn_preferences` 关键字段：

| 字段 | 类型 | 说明 |
|---|---|---|
| `id` | bigint | 主键 |
| `team_id` | bigint, nullable | 多租户 |
| `scope_type` | string, nullable | 作用域类型 |
| `scope_id` | bigint, default 0 | 作用域 ID（0 = 全局） |
| `type` | string | 偏好类型：`'follow'`、`'like'`、`'view'` |
| `preferencer_type` | string | 操作者多态类型 |
| `preferencer_id` | bigint | 操作者多态 ID |
| `preferenceable_type` | string | 目标多态类型 |
| `preferenceable_id` | bigint | 目标多态 ID |
| `options` | json | 额外数据（互关时存 `followed_at` 等） |
| `created_at` / `updated_at` / `deleted_at` | timestamps | 软删除 |

**查询作用域：**

```php
Preference::withType('follow')              // 按 type 筛选
    ->withPreferencer($user)                // 按操作者筛选
    ->withPreferenceable($post)             // 按目标筛选
    ->withPreferenceType($postOrClass);     // 按目标类型筛选
```

### Livewire 组件

#### 前端组件（带管理功能）

三个组件均继承 `Wsmallnews\Preference\Livewire\Components\Base`（→ `Wsmallnews\Support\Livewire\Base`，使用 `Scopeable` trait）：

| 组件 | 注册名 | 功能 |
|---|---|---|
| `Livewire\Components\Follows` | `sn-preference-components-follows` | 关注列表，支持取消关注和批量操作 |
| `Livewire\Components\Likes` | `sn-preference-components-likes` | 点赞列表，支持取消点赞和批量操作 |
| `Livewire\Components\Views` | `sn-preference-components-views` | 浏览列表，支持删除和批量删除 |

**通用属性和特性：**

- `$preferencer` / `$preferenceable` / `$listType`（`'preferencer'`、`'preferenceable'`、默认全部）
- 均使用 `CanPagination`（**已包含 `WithPagination`，不要重复 use**）、`HasAuth`、`HasProperties`、`CanBeContained`、`CanManage`
- 管理模式下支持单选/全选和批量操作

**使用示例：**

```blade
{{-- 显示某用户的所有关注 --}}
<livewire:sn-preference-components-follows
    :preferencer="$user"
    list-type="preferencer"
    :can-manage="true"
/>

{{-- 显示某文章的所有点赞用户 --}}
<livewire:sn-preference-components-likes
    :preferenceable="$post"
    list-type="preferenceable"
/>
```

#### Filament 面板组件（只读）

| 组件 | 注册名 |
|---|---|
| `Filament\Pages\Preference\Components\Follows` | `sn-preference-fi-follows` |
| `Filament\Pages\Preference\Components\Likes` | `sn-preference-fi-likes` |
| `Filament\Pages\Preference\Components\Views` | `sn-preference-fi-views` |

这三个组件无管理功能，用于 Filament 面板页面中嵌入展示。

#### Filament Widget 包装器

`Wsmallnews\Preference\Filament\Pages\Preference\Widgets\` 下的 `Follows`、`Likes`、`Views`，接受 `$record` 和 `$widgetType`（`'preferenceable'` 或 `'preferencer'`），内部渲染对应的 Filament 面板组件。

```blade
{{-- 在 Filament 页面中使用 --}}
<x-filament-widgets::widgets>
    @foreach ($widgets as $widget)
        {{ $this->makeFilamentWidget($widget) }}
    @endforeach
</x-filament-widgets::widgets>
```

### 配置

`config/sn-preference.php`：

```php
return [
    'scopeable' => [
        'scope_type' => 'sn-preference',  // 默认作用域类型
        'scope_id' => 0,                  // 0 = 全局
    ],
    'models' => [
        'preference' => Models\Preference::class,  // 可替换模型
    ],
    'file_directory' => 'sn/preference/',  // 文件存储目录
];
```

### Utils 工具类

`Wsmallnews\Preference\Support\Utils` — 全部为静态方法：

| 方法 | 说明 |
|---|---|
| `getConfig(?string $name, $default)` | 读取 `sn-preference` 配置（dot notation） |
| `getScopeableContext()` | 从配置创建 ScopeableContext 值对象 |
| `getScopeable()` | 返回 `['scope_type' => '...', 'scope_id' => 0]` |
| `getScopeType()` | 获取默认 scope_type |
| `getScopeId()` | 获取默认 scope_id |
| `getModel(string $name, bool $shouldException = true)` | 获取配置的模型类名，`false` 时不抛异常 |
| `getPreferenceModel()` | `getModel('preference')` 快捷方式 |
| `getFileDirectory(?string $type)` | 获取文件目录（自动追加日期），如 `sn/preference/image/20260527` |

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| Preference 模型 | `Wsmallnews\Preference\Models\Preference` |
| 操作者侧 traits | `Wsmallnews\Preference\Models\Concerns\Preferencer\` |
| 目标侧 traits | `Wsmallnews\Preference\Models\Concerns\Preferenceable\` |
| Livewire 组件 | `Wsmallnews\Preference\Livewire\Components\` |
| Livewire Base | `Wsmallnews\Preference\Livewire\Components\Base` |
| Livewire Traits | `Wsmallnews\Preference\Livewire\Concerns\` |
| Filament 页面组件 | `Wsmallnews\Preference\Filament\Pages\Preference\Components\` |
| Filament 挂件 | `Wsmallnews\Preference\Filament\Pages\Preference\Widgets\` |
| Utils | `Wsmallnews\Preference\Support\Utils` |
| Facade | `Wsmallnews\Preference\Facades\Preference` |
| 异常 | `Wsmallnews\Preference\Exceptions\` |

### 常见错误

- **preferencer 模型必须实现 `HasSnIdentifiable` 接口**，否则 Blade 组件渲染会失败。User 模型可直接 use `UserIdentifiable` trait。
- **preferenceable 模型必须实现 `HasSnSubject` 接口**，否则 Blade 组件渲染会失败。`HasSnSubject` 没有默认 trait，需自行实现全部 5 个方法。
- **`getSnHrefUrl()` 和 `getSnSubjectHrefUrl()` 返回 `null` 时不显示跳转链接**，点击会分发 Livewire 事件。如需跳转，请返回有效的 URL 字符串。
- **`isLink` 已改名为 `hasLink`**，旧属性名不再有效，使用 `isLink` 的代码需更新。
- **`CanPagination` 已包含 `WithPagination`**，不要在 Livewire 组件中重复 `use WithPagination`。
- **counter 字段使用 JSON 格式**，模型中需配合 support 包的 `CounterCast` 使用：`'counter' => CounterCast::class`。
- **`scope_id = 0` 表示全局作用域**，不要用 `where('scope_id', 0)` 直接查询——使用模型 trait 提供的 `scopeScopeId(0)`，它内部使用 `whereIn`。
- **preferenceable 模型必须 use 对应的 Preferenceable trait**（如 `Followable`），否则 preferencer 侧操作会抛出 `InvalidArgumentException`。
- **`Follower::follow()` 不允许关注自己**，会抛出 `InvalidArgumentException('Cannot follow yourself.')`。
- **`Utils` 所有方法都是静态的**，使用 `Utils::getConfig()` 而非 `(new Utils)->getConfig()`。
- **`Utils::getModel()` 默认会抛异常**，传递 `false` 作为第二个参数以允许返回 `null`。

=== wsmallnews/support/core rules ===

## Support Package（wsmallnews/support）

`wsmallnews/support` 是 Wsmallnews 生态的基础 Filament 插件包，为其他扩展包（cms、user、category、comment 等）提供可复用的基类、工厂方法和工具。命名空间根为 `Wsmallnews\Support`，Blade 视图前缀为 `sn-support`，配置文件为 `config/sn-support.php`。

### 组件工厂

项目提供了三类工厂类，封装了通用配置，后续会持续扩充。**优先使用工厂方法而非手动实例化 Filament 组件**，工厂会自动应用 `config('sn-support.form_components')` 中的默认配置。

#### FormComponents

```php
use Wsmallnews\Support\Filament\Forms\FormComponents;

// Spatie Media Library 图片/文件上传
FormComponents::mediaImageUpload('avatar', 'avatars');
FormComponents::mediaFileUpload('attachment', 'documents');

// 直接存储到 disk 图片/文件上传
FormComponents::plainImageUpload('cover');
FormComponents::plainFileUpload('report');

// Markdown / 富文本编辑器
FormComponents::markdownEditor('description');
FormComponents::richEditor('content');
```

所有上传组件自动应用：`disk`（取自 `filesystem_disk` 或 Filament 默认盘）、`visibility`、`downloadable`、`openable`、`reorderable`、`appendFiles`、`maxFiles`、`maxSize`、`imagePreviewHeight`。

编辑器组件自动应用：`fileAttachmentsDisk`、`fileAttachmentsVisibility`、`fileAttachmentsMaxSize`、`maxLength`，以及 Markdown/RichText 各自的 `toolbarButtons` 等。

#### FilterComponents

```php
use Wsmallnews\Support\Filament\Filters\FilterComponents;

// 获取 created_at + updated_at 时间区间筛选器
FilterComponents::createUpdateRangeFilter();

// 单个时间区间筛选器
FilterComponents::dateTimeRangeFilter('published_at', '发布时间');
```

#### ActionComponents

```php
use Wsmallnews\Support\Filament\Actions\ActionComponents;

// 自定义标准操作，可以用在非 filament table 页面
ActionComponents::deleteAction();
ActionComponents::editAction();

// 二态切换（枚举须恰好 2 个 case）
ActionComponents::toggleAction(MemberStatus::class, 'status');

// 多态切换（枚举 > 2 个 case，弹出 Select 选择）
ActionComponents::switchAction(ProductStatus::class, 'status');

// 自动错误处理的批量操作
ActionComponents::bulkAction(name: 'bulk_enable', process: function ($action, $record) {
    $record->update(['status' => MemberStatus::Active]);
});

// 包裹 record/toolbar actions（根据配置决定是否用 ActionGroup 包裹）
ActionComponents::recordActions([ViewAction::make(), DeleteAction::make()]);
ActionComponents::toolbarActions([DeleteBulkAction::make()]);
```

#### ColumnComponents

`Wsmallnews\Support\Filament\Tables\ColumnComponents` 提供表格列工厂，用于展示关联模型信息：

```php
use Wsmallnews\Support\Filament\Tables\ColumnComponents;

// 多态关联列（左侧图片 + 右侧标题/描述 + 类型 badge）
ColumnComponents::morphColumn(
    'causer_type',                      // 列名（必须模态类型字段）
    '操作人',                           // 标签
    fn ($record) => $record->causer,    // 获取关联模型
    fn ($record) => $record->causer_type, // 获取多态类型
    fn ($record) => $record->causer_id,   // 获取多态 ID
);

// 普通关联列（无类型 badge）
ColumnComponents::relationColumn(
    'user.name',
    '关联用户',
    fn ($record) => $record->user,
);

// 模型列（简单展示，无多态/关联逻辑）
ColumnComponents::modelColumn('name', '名称', fn ($record) => $record);

// 内容列（Textarea 直接展示，Richtext/Markdown 点击弹框）
ColumnComponents::contentColumn('content', '内容');
```

### SupportModel 基类

`Wsmallnews\Support\Models\SupportModel` 是所有 support 包模型的基类，提供 scopeable 和多租户感知：

```php
use Wsmallnews\Support\Models\SupportModel;

class Post extends SupportModel
{
    // 自动获得：
    // scopeTenant()    — 按当前租户过滤
    // scopeSnScope()   — 组合 scopeable + tenant 过滤
    // getModelLabel()  — 默认返回类名
}
```

各扩展包的模型应继承 `SupportModel`（如 Post、Category、Comment、Member、Product 等），而非直接继承 Laravel 的 `Model`。

### Filament 插件系统

support 提供了一套可配置的 Filament 插件架构，让扩展包的 Resource/Page 可通过配置覆盖：

```php
use Wsmallnews\Support\Filament\Concerns\RegistersConfigurable;

class CmsPlugin implements Plugin
{
    use RegistersConfigurable;

    public function register(Panel $panel): void
    {
        $this->registerConfigurableResources($panel);
        $this->registerConfigurablePages($panel);
    }
}
```

Resource 使用 `CanBeConfigured` + `ResourceConfiguration`：

```php
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Resources\ResourceConfiguration;

final class PostResource extends BaseResource
{
    use CanBeConfigured;

    protected static ?string $configurationClass = ResourceConfiguration::class;

    public static function getEssentialsPlugin(): ?CmsPlugin
    {
        return CmsPlugin::get();
    }
}
```

Page 使用 `CanBeConfigured` + `PageConfiguration`：

```php
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Pages\PageConfiguration;

class CategoryPage extends Base
{
    use CanBeConfigured;

    protected static ?string $configurationClass = PageConfiguration::class;
}
```

### ScheduledTasks 资源

继承 `Wsmallnews\Support\Filament\Resources\ScheduledTasks\BaseResource` 快速实现定时任务管理：

```php
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\BaseResource;

class ScheduledTaskResource extends BaseResource
{
    // BaseResource 已提供：
    // - 列表页、查看页
    // - table() 配置（id、schedulable 多态列、action、status、scheduled_at、executed_at 等）
    // - infolist() 配置（Tabs: Overview/Payload/Raw Data）
    // - 图标、slug、导航排序、翻译标签
}
```

提供可配置的具体实现 `ScheduledTaskResource`，支持通过插件配置覆盖：

```php
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\ScheduledTaskResource;

// 在 PanelProvider 中直接注册
$panel->resources([ScheduledTaskResource::class]);
```

### 定时调度任务

#### Facade 注册

```php
use Wsmallnews\Support\Facades\ScheduledTask;

// 在 ServiceProvider 中注册可调度的动作
ScheduledTask::registers('sn_post', [
    'publish' => ['label' => '发布', 'handler' => PublishHandler::class],
    'unpublish' => ['label' => '下架', 'handler' => UnpublishHandler::class],
]);

// 在表单中嵌入调度器
ScheduledTask::scheduleRepeater('sn_post');
```

#### Resource & Widget

```php
// 查看页嵌入定时任务 Widget
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\Widgets\ScheduledTasks as ScheduledTasksWidget;

ScheduledTasksWidget::make()

// 表格行操作：查看关联的定时任务
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\Concerns\ViewScheduledTasksAction;

ViewScheduledTasksAction::make()
```

### 异常体系

所有扩展包的异常应继承 `SupportException`：

```php
use Wsmallnews\Support\Exceptions\SupportException;

class CmsException extends SupportException {}
class CommentException extends SupportException {}
class ProductException extends SupportException {}
```

`InvalidScopeException` 用于 Scopeable 配置错误时抛出。

### 枚举工具

`EnumHelper` trait 为枚举提供 `getLabel()`、`getColor()`、`getIcon()` 等默认实现：

```php
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum PostStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Draft = 'draft';
    case Published = 'published';
}
```

### Blade 组件

```blade

<x-sn-support::content :content-type="$contentType" :content="$content" />

<x-sn-support::collapse-content :content-type="$contentType" :content="$content" />

<x-sn-support::lightbox class="w-full" :galleries="$galleries" thumb-class="size-20" />
```

### Scopeable 系统

不使用 Laravel 原生多态，因为 `scope_id` 可以为 `0`（表示该 scope_type 下的全局作用域）。

**为什么不用 `morphs`：** Laravel 多态 `*_type='post'` + `*_id=0` 会尝试查找 ID 为 0 的 Post 记录（不存在）。而我们的 `scope_id = 0` 语义是"适用于该类型的所有记录"，不是指向某个具体 ID 的记录。

**使用场景：**
- Comment 包：评论同时给所有 post 和 article 提供支持 → `scope_type='post', scope_id=0` / `scope_type='article', scope_id=0`
- Comment 包：给某门店商品评论 → `scope_type='store', scope_id=<store_id>`

#### Model Trait

```php
use Wsmallnews\Support\Models\Concerns\Scopeable;

class Comment extends Model
{
    use Scopeable;

    // 自动获得：
    // $model->getScopeable();    // ['scope_type' => '...', 'scope_id' => 0]
    // $model->getScopeType();    // 'post'
    // $model->getScopeId();      // 0

    // 查询作用域：
    // Comment::scopeType('post')           ->where('scope_type', 'post')
    // Comment::scopeId(0)                  ->whereIn('scope_id', [0])
    // Comment::scopeable('post', 0)        ->where('scope_type', 'post')->whereIn('scope_id', [0])
}
```

#### ScopeableContext 值对象

```php
use Wsmallnews\Support\Data\ScopeableContext;

// 手动创建
$context = new ScopeableContext('post', 0);
$context->isGlobal(); // true (scopeId === 0)

// 从数组或配置创建
ScopeableContext::fromArray(['scope_type' => 'store', 'scope_id' => 5]);
ScopeableContext::fromConfig('sn-cms.scopeable');

// 辅助函数
scopeable_context(['scope_type' => 'post', 'scope_id' => 0]);
scopeable_query($query, ['scope_type' => 'post', 'scope_id' => 0]);
```

#### Filament 层面的 Scopeable

Filament Resources 使用 `HasScopeableProperties` concern：

- `Wsmallnews\Support\Filament\Concerns\HasScopeableProperties` — 提供静态的 `scopeType()` / `getScopeType()` 和 `scopeId()` / `getScopeId()`
- `Wsmallnews\Support\Filament\Resources\Concerns\Scopeable` — Resource 级别的 `applyScopeableToQuery()` 自动对 Eloquent 查询应用 scope 过滤
- `Wsmallnews\Support\Filament\Pages\Concerns\Scopeable` — Page 级别的 scope 支持

### SN 身份与实体接口

为 preference 等扩展包提供统一的"操作者"（谁）和"目标实体"（什么）数据抽象。Blade 组件通过这两个接口获取展示数据和跳转链接。

#### HasSnIdentifiable（身份/操作者接口）

`Wsmallnews\Support\Contracts\HasSnIdentifiable` — 操作者侧（用户）的标准接口，preferencer 模型必须实现：

```php
use Wsmallnews\Support\Contracts\HasSnIdentifiable;
use Illuminate\Support\HtmlString;

// 接口方法：
getSnId(): int;                                          // 操作者 ID
getSnName(): string | HtmlString | null;                 // 操作者名称
getSnAvatarUrl(): string | HtmlString | null;            // 头像 URL
getSnEmail(): string | HtmlString | null;                // 邮箱
getSnHrefUrl(): string | HtmlString | null;              // 详情页跳转链接
```

#### UserIdentifiable trait

`Wsmallnews\Support\Concerns\UserIdentifiable` 为 `HasSnIdentifiable` 提供基于 Eloquent 属性的默认实现，自动映射 `$this->id`、`$this->name`、`$this->avatar_url`、`$this->email`。`getSnHrefUrl()` 默认返回 `null`：

```php
use Wsmallnews\Support\Contracts\HasSnIdentifiable;
use Wsmallnews\Support\Concerns\UserIdentifiable;

class User extends Authenticatable implements HasSnIdentifiable
{
    use UserIdentifiable;
}
```

#### HasSnSubject（实体/目标接口）

`Wsmallnews\Support\Contracts\HasSnSubject` — 目标侧（内容实体）的标准接口，preferenceable 模型必须实现：

```php
use Wsmallnews\Support\Contracts\HasSnSubject;
use Illuminate\Support\HtmlString;

// 接口方法：
getSnSubjectId(): int;                                   // 实体 ID
getSnSubjectTitle(): string | HtmlString | null;          // 标题
getSnSubjectDescription(): string | HtmlString | null;    // 描述
getSnSubjectCoverUrl(): string | HtmlString | null;       // 封面图 URL
getSnSubjectHrefUrl(): string | HtmlString | null;        // 详情页跳转链接
```

`HasSnSubject` 没有默认 trait，每个实现类需自行实现所有方法：

```php
use Wsmallnews\Support\Contracts\HasSnSubject;

class Post extends SupportModel implements HasSnSubject
{
    public function getSnSubjectId(): int { return $this->id; }
    public function getSnSubjectTitle(): string | HtmlString | null { return $this->title; }
    public function getSnSubjectDescription(): string | HtmlString | null { return $this->description; }
    public function getSnSubjectCoverUrl(): string | HtmlString | null { return $this->getFirstMediaUrl('post_image'); }
    public function getSnSubjectHrefUrl(): string | HtmlString | null { return null; }
}
```

#### 接口对比

| 接口 | 用途 | 对应 trait | href 方法 |
|---|---|---|---|
| `HasSnIdentifiable` | 操作者/用户 | `UserIdentifiable` | `getSnHrefUrl()` |
| `HasSnSubject` | 目标/内容实体 | 无默认 trait | `getSnSubjectHrefUrl()` |

两个接口的 href 方法返回非空 URL 时，preference 等包的 Blade 组件会渲染为可点击的 `<a>` 标签；返回 `null` 时点击会分发 Livewire 事件。

### 自定义表单字段

<code-snippet name="BoxRepeater 用法" lang="php">
use Wsmallnews\Support\Filament\Forms\Fields\BoxRepeater;

BoxRepeater::make('items')
    ->columns(3)
    ->columnWidths(['name' => '200px', 'price' => '150px'])
    ->headers(['name' => '商品名称'])
    ->schema([
        TextInput::make('name'),
        TextInput::make('price')->numeric(),
    ])
    ->withoutHeader()      // 隐藏表头
    ->isFusionLayout()     // 融合布局（自动隐藏表头）
    ->hideLabels();        // 隐藏字段标签
</code-snippet>

<code-snippet name="Arrange 用法" lang="php">
use Wsmallnews\Support\Filament\Forms\Fields\Arrange;

Arrange::make('categories')
    ->relationships([
        'arranges' => 'categories',       // 一级排列关联
        'recursions' => 'children',       // 二级递归子关联
    ])
    ->tableFields([
        TextInput::make('name'),
        TextInput::make('sort')->numeric(),
    ]);
// 注意：必须同时设置 relationships() 和 tableFields()，否则无法保存和回显
</code-snippet>

<code-snippet name="DistrictSelect 用法" lang="php">
use Wsmallnews\Support\Filament\Forms\Fields\DistrictSelect;

DistrictSelect::make('district')
    // 自动关联 province_name/id, city_name/id, district_name/id 字段
    // 确保模型中存在这些字段，否则 state 回显会静默失败
</code-snippet>

### Activity Logs 资源

继承 `Wsmallnews\Support\Filament\Resources\ActivityLogs\BaseResource` 快速实现日志功能：

```php
use Wsmallnews\Support\Filament\Resources\ActivityLogs\BaseResource;

class ActivityLogResource extends BaseResource
{
    // BaseResource 已提供：
    // - 列表页、查看页、导出、时间线
    // - getEloquentQuery() 按 log_name 过滤
    // - 图标、slug、导航排序、翻译标签
}
```

模型实现接口可自定义日志展示：

- `Wsmallnews\Support\Contracts\HasModelLabel` — 自定义模型标签（`static getModelLabel(): string`），用于活动日志类型下拉选项的标签解析等

### Tags 资源

继承 `Wsmallnews\Support\Filament\Resources\Tags\BaseResource`：

```php
use Wsmallnews\Support\Filament\Resources\Tags\BaseResource;

class TagResource extends BaseResource
{
    public static function getTagType(): string
    {
        return 'article'; // 标签类型过滤
    }
}
```

### Livewire 基础设施

#### Base 组件

`Wsmallnews\Support\Livewire\Base` 继承 Livewire 的 `Component`，提供带 Halt 感知的数据库事务：

```php
$this->transaction(function () {
    // 数据库操作
});
$this->halt(); // 停止执行，回滚事务
$this->halt($shouldRollbackDatabaseTransaction: true);
```

#### 可用 Traits

| Trait | 提供 |
|---|---|
| `HasProperties` | `$properties` 数组 + `getProperty()`/`setProperty()` |
| `HasContentType` | `$contentType` 属性（Textarea/Richtext/Markdown 枚举） |
| `HasAuth` | `$user` 属性，`authUser()`/`hasAuthUser()` |
| `CanPagination` | **已包含 `WithPagination`**，三种分页模式（scroll/paginator/manual），`withPagination($builder)` |
| `CanBeContained` | `$contained` 布尔属性控制布局 |
| `Scopeable` | `#[Locked]` `$scopeType`/`$scopeId`，`getScopeable()` |

**注意：** `CanPagination` 已经 use 了 Livewire 的 `WithPagination`，**不要再单独 use `WithPagination`**。

#### HasColumns（响应式列配置）

`Wsmallnews\Support\Concerns\HasColumns` 提供响应式断点列配置：

```php
use Wsmallnews\Support\Concerns\HasColumns;

// 设置列数
$this->columns(2);                        // 所有断点默认 2 列
$this->columns(['default' => 1, 'lg' => 3]); // 按断点设置

// 获取列配置
$this->getColumns();       // 获取完整配置数组
$this->getColumns('lg');   // 获取指定断点的列数
$this->getColumnsConfig(); // 获取带默认值的完整配置
```

#### HasMediaFilter（媒体筛选）

`Wsmallnews\Support\Filament\Concerns\HasMediaFilter` 用于自定义媒体集合的筛选逻辑：

```php
use Wsmallnews\Support\Filament\Concerns\HasMediaFilter;

$this->filterMediaUsing(function (Collection $media): Collection {
    return $media->filter(fn ($item) => $item->getCustomProperty('featured'));
});

$this->filterMedia($media);  // 应用筛选
$this->hasMediaFilter();     // 检查是否设置了筛选回调
```

### 多租户

#### 中间件

`Wsmallnews\Support\Http\Middleware\IdentifyTenant` 已注册为全局 Livewire 持久中间件。从路由的 `tenant` 参数按 `slug` 字段解析租户模型，设置请求属性 `has_tenancy` 和 `current_tenant`。

#### 辅助函数

```php
has_tenancy();           // 是否有租户
current_tenant();        // 当前租户 Model（自动判断前端/后台）
frontend_has_tenancy();  // 前端是否有租户
frontend_current_tenant(); // 前端当前租户
is_in_panel();           // 是否在 Filament 后台
sn_route('page.show');   // 租户感知路由（自动添加 tenant 参数）
```

配置 `sn-support.tenant_model` 为租户 Model 类来启用多租户；设为 `null` 则禁用。

### Eloquent Casts

<code-snippet name="Eloquent Casts" lang="php">
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Casts\CounterCast;
use Wsmallnews\Support\Casts\ImplodeCast;

class Product extends Model
{
    protected $casts = [
        'price' => MoneyCast::class,                // cknow/money 金额转换
        'price' => MoneyCast::class . ':currency',  // 指定货币字段名
        'counters' => CounterCast::class,           // JSON 计数器，未设置的 key 默认返回 0
        'tags' => ImplodeCast::class,               // 逗号分隔字符串 ↔ 数组
    ];
}
</code-snippet>

### Rocket 数据管道

`Wsmallnews\Support\Rocket` 提供 params → radars → payloads 三层数据处理管道：

```php
$rocket = new Rocket();
$rocket->setParams(['user_id' => 1]);
$rocket->setRadar('key', 'value');
$rocket->setPayloads(['result' => $data]);

$rocket->getParam('user_id');
$rocket->getRadar('key');
$rocket->getPayloads(); // Collection
```

### Utils 工具类

`Wsmallnews\Support\Support\Utils` — 全部为静态方法：

| 方法 | 说明 |
|---|---|
| `getConfig('key', $default)` | 读取 `sn-support` 配置（dot notation） |
| `getModel('name', $shouldException)` | 获取配置中的模型类名，第二个参数 `false` 时不抛异常 |
| `getTenantModel()` | 获取租户模型类名 |
| `getContentModel()` | 获取 Content 模型类名 |
| `getScheduledTaskModel()` | 获取 ScheduledTask 模型类名 |
| `isTenancyEnabled()` | 判断多租户是否启用 |
| `getFilesystemDisk()` | 获取文件系统磁盘（回退到 Filament 默认盘） |
| `getScopeFromConfig('sn-cms.scopeable')` | 从配置创建 ScopeableContext |
| `getSchedulerConfig('key', $default)` | 读取定时调度配置 |

### 关键辅助函数

| 函数 | 说明 |
|---|---|
| `get_sn($id, $type)` | 生成唯一编号（时间戳 + 随机数 + ID） |
| `client_unique()` | 获取客户端唯一标识（基于 URL + IP + UserAgent 的 MD5） |
| `db_listen()` | 开启数据库查询监听（调试用，直接 echo SQL） |
| `sn_currency()` | 获取自定义 Currency 操作类实例 |
| `exception_log($exception, $name, $message)` | 格式化异常日志（含 Message、File、Trace） |
| `through_cache($key, $callback, $store, $is_force, $ttl)` | 缓存穿透模式，支持指定 store、强制刷新、TTL |
| `href_format($url, $newTab, $spaMode)` | 生成带 wire:navigate 的链接 HTML |
| `files_url($files, $disk)` | 解析文件 URL（自动判断 http/data 开头 vs 相对路径） |
| `filter_richeditor($content)` | 去除富文本中包裹图片的 anchor 标签 |
| `frontend_has_tenancy()` | 前端是否有租户（从 request attributes 读取） |
| `frontend_current_tenant()` | 前端当前租户 Model |
| `has_tenancy()` | 全局是否有租户（自动判断前端/后台） |
| `current_tenant()` | 全局当前租户 Model（自动判断前端/后台） |
| `get_tenancy_scope_name($panel)` | 获取租户作用域名称 |
| `is_in_panel()` | 当前是否在 Filament 后台面板 |
| `tree_to_flatten($tree)` | 递归将树结构扁平化为一维集合 |
| `sn_route($name, $params, $absolute)` | 租户感知路由，多租户启用时自动添加 tenant 参数 |
| `remove_query_param_from_url($url, $keys)` | 移除 URL 中的指定 query 参数 |
| `scopeable_context($input)` | 创建 ScopeableContext（支持数组、实例、配置 key） |
| `scopeable_query($query, $scope)` | 对查询应用 scope 过滤 |

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| FormComponents 工厂 | `Wsmallnews\Support\Filament\Forms\FormComponents` |
| FilterComponents 工厂 | `Wsmallnews\Support\Filament\Filters\FilterComponents` |
| ActionComponents 工厂 | `Wsmallnews\Support\Filament\Actions\ActionComponents` |
| ColumnComponents 工厂 | `Wsmallnews\Support\Filament\Tables\ColumnComponents` |
| 自定义表单字段 | `Wsmallnews\Support\Filament\Forms\Fields\` |
| Activity Logs 资源 | `Wsmallnews\Support\Filament\Resources\ActivityLogs\` |
| Tags 资源 | `Wsmallnews\Support\Filament\Resources\Tags\` |
| ScheduledTasks 资源 | `Wsmallnews\Support\Filament\Resources\ScheduledTasks\` |
| SupportModel 基类 | `Wsmallnews\Support\Models\SupportModel` |
| Livewire Base | `Wsmallnews\Support\Livewire\Base` |
| Livewire Traits | `Wsmallnews\Support\Livewire\Concerns\` |
| Model Traits | `Wsmallnews\Support\Models\Concerns\` |
| Models | `Wsmallnews\Support\Models\` |
| Casts | `Wsmallnews\Support\Casts\` |
| Enums | `Wsmallnews\Support\Enums\` |
| EnumHelper | `Wsmallnews\Support\Enums\Traits\EnumHelper` |
| Data 对象 | `Wsmallnews\Support\Data\` |
| Contracts（接口） | `Wsmallnews\Support\Contracts\` |
| Contracts - 活动日志 | `Wsmallnews\Support\Contracts\ActivityLogs\` |
| 通用 Traits | `Wsmallnews\Support\Concerns\` |
| UserIdentifiable | `Wsmallnews\Support\Concerns\UserIdentifiable` |
| HasColumns | `Wsmallnews\Support\Concerns\HasColumns` |
| Plugin 自定义属性 | `Wsmallnews\Support\Concerns\Plugin\` |
| Resource 自定义属性 | `Wsmallnews\Support\Concerns\Resource\` |
| 安装工具 | `Wsmallnews\Support\Concerns\Install\` |
| Filament 通用 | `Wsmallnews\Support\Filament\Concerns\` |
| FilamentModelHelper | `Wsmallnews\Support\Helpers\FilamentModelHelper` |
| Utils | `Wsmallnews\Support\Support\Utils` |
| Facade | `Wsmallnews\Support\Facades\Support` |
| ScheduledTask Facade | `Wsmallnews\Support\Facades\ScheduledTask` |
| 中间件 | `Wsmallnews\Support\Http\Middleware\` |

### 常见错误

- **使用 `FormComponents::` 工厂方法时不要再手动设置 disk/visibility 等**——工厂已从 config 读取默认值。
- **`BoxRepeater` 需要调用 `->columns()` 和 `->columnWidths()`** 才能正确显示表头宽度。
- **`Arrange` 必须同时设置 `relationships()` 和 `tableFields()`**，缺一不可。
- **`DistrictSelect` 要求模型中存在 `province_name/id`、`city_name/id`、`district_name/id` 字段**，否则 state 回显会静默失败。
- **`CanPagination` 已包含 `WithPagination`**，不要再单独 use `WithPagination`。
- **使用 `scope_id = 0` 时不要用 `where('scope_id', 0)` 直接查询**——用 Model trait 提供的 `scopeScopeId(0)`，它内部使用 `whereIn`。
- **`Utils` 所有方法都是静态的**——`Utils::getConfig()` 而非 `(new Utils)->getConfig()`。
- **`Utils::getModel()` 默认会抛异常**，传递 `false` 作为第二个参数以允许返回 `null`。
- **扩展 `ActivityLogs\BaseResource` 时不要覆盖 `getEloquentQuery()`**——除非你理解 causer 上移除租户全局作用域的逻辑。

</laravel-boost-guidelines>
