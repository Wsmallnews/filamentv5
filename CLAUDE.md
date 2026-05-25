<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

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

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

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

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== filament/filament rules ===

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

=== wsmallnews/support rules ===

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

// 本地图片/文件上传
FormComponents::localImageUpload('cover');
FormComponents::localFileUpload('report');

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

ActionComponents::deleteAction();
ActionComponents::editAction();
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
    // - getEloquentQuery() 按 log_name 过滤，预加载 causer（移除租户全局作用域）和 subject
    // - 图标、slug、导航排序、翻译标签
}
```

模型实现接口可自定义日志展示：

- `Wsmallnews\Support\Contracts\ActivityLogs\HasActivityLogTitle` — 自定义日志标题
- `Wsmallnews\Support\Contracts\ActivityLogs\HasActivityLogUrl` — 自定义查看链接

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
| `isTenancyEnabled()` | 判断多租户是否启用 |
| `getFilesystemDisk()` | 获取文件系统磁盘（回退到 Filament 默认盘） |
| `getScopeFromConfig('sn-cms.scopeable')` | 从配置创建 ScopeableContext |

### 关键辅助函数

| 函数 | 说明 |
|---|---|
| `get_sn($id, $type)` | 生成唯一编号 |
| `sn_route($name, $params)` | 租户感知路由，多租户启用时自动添加 tenant 参数 |
| `files_url($files, $disk)` | 解析文件 URL |
| `href_format($url, $newTab, $spaMode)` | 生成带 wire:navigate 的链接 |
| `through_cache($key, $callback)` | 缓存穿透模式 |
| `filter_richeditor($content)` | 去除富文本中包裹图片的 anchor 标签 |
| `tree_to_flatten($tree)` | 递归将树结构扁平化 |
| `scopeable_context($input)` | 创建 ScopeableContext |
| `scopeable_query($query, $scope)` | 对查询应用 scope 过滤 |
| `exception_log($exception, $name, $message)` | 结构化异常日志 |

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| FormComponents 工厂 | `Wsmallnews\Support\Filament\Forms\FormComponents` |
| FilterComponents 工厂 | `Wsmallnews\Support\Filament\Filters\FilterComponents` |
| ActionComponents 工厂 | `Wsmallnews\Support\Filament\Actions\ActionComponents` |
| 自定义表单字段 | `Wsmallnews\Support\Filament\Forms\Fields\` |
| Activity Logs 资源 | `Wsmallnews\Support\Filament\Resources\ActivityLogs\` |
| Tags 资源 | `Wsmallnews\Support\Filament\Resources\Tags\` |
| Livewire Base | `Wsmallnews\Support\Livewire\Base` |
| Livewire Traits | `Wsmallnews\Support\Livewire\Concerns\` |
| Model Traits | `Wsmallnews\Support\Models\Concerns\` |
| Models | `Wsmallnews\Support\Models\` |
| Casts | `Wsmallnews\Support\Casts\` |
| Enums | `Wsmallnews\Support\Enums\` |
| Data 对象 | `Wsmallnews\Support\Data\` |
| Contracts | `Wsmallnews\Support\Contracts\` |
| Utils | `Wsmallnews\Support\Support\Utils` |
| Facade | `Wsmallnews\Support\Facades\Support` |
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
