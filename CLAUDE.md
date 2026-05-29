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

=== wsmallnews/filament-nestedset rules ===

## Nestedset 包（wsmallnews/filament-nestedset）

`wsmallnews/filament-nestedset` 是基于 [kalnoy/nestedset](https://github.com/lazychaser/laravel-nestedset) 的 Filament 嵌套集树形管理插件，支持 Filament v4/v5、多语言、多租户和 Tabs 筛选。命名空间根为 `Wsmallnews\FilamentNestedset`，Blade 视图前缀为 `sn-filament-nestedset`，配置文件为 `config/sn-filament-nestedset.php`。

### 核心架构

依赖 `kalnoy/nestedset` 的 `NodeTrait` 实现嵌套集模型，通过 `scoped` 特性支持多租户和 Tabs 筛选。提供两种使用方式：

- **NestedsetPage**：继承 Filament `Page` 的完整嵌套集管理页面（CRUD、拖拽排序、修复树）
- **Nestedset Livewire 组件**：可嵌入任意页面的只读树形展示组件

### NestedsetPage（管理页面基类）

`Wsmallnews\FilamentNestedset\Pages\NestedsetPage` 继承 `Filament\Pages\Page`，使用 traits：`CanUseDatabaseTransactions`、`HasTabs`、`HasUnsavedDataChangesAlert`、`InteractsWithFormActions`。

#### 创建页面

```bash
php artisan make:filament-nestedset-page
```

生成的页面类继承 `NestedsetPage`，需设置 `$model` 和 `$recordTitleAttribute`。

#### 静态属性（类级别配置，通过子类覆盖）

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$model` | `?string` | `null` | 嵌套集模型类名，**必须设置** |
| `$modelLabel` | `?string` | `null` | 模型标签，为空时自动从 model 推断 |
| `$recordTitleAttribute` | `string` | `'name'` | 节点标题字段名 |
| `$level` | `?int` | `null` | 嵌套集层级限制，`null` = 不限制 |
| `$emptyLabel` | `?string` | `''` | 树为空时的提示文本 |
| `$tabFieldName` | `?string` | `null` | Tabs 筛选的字段名 |
| `$infolistAlignment` | `Alignment` | `Alignment::Right` | Infolist 对齐方式 |
| `$infolistHiddenEndpoint` | `string` | `'md'` | Infolist 显示的最小断点 |
| `$isScopedToTenant` | `bool` | `true` | 是否关联多租户 |
| `$navigationIcon` | `string\|BackedEnum\|null` | `'heroicon-o-bars-3-bottom-right'` | 导航图标（继承自 Page） |

#### 非静态属性（实例属性）

| 属性 | 类型 | 说明 |
|---|---|---|
| `$activeTab` | `?string` | 当前选中的 Tab（`#[Url]` 绑定） |
| `$view` | `string` | 页面 Blade 视图路径 |

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
public function getRecordLabel(Model $item): HtmlString | string { ... }

// 自定义嵌套集查询条件
public function getEloquentQuery($query) { return $query->where('status', 'normal'); }

// 额外的 scope 参数（kalnoy/nestedset scoped）
public function nestedScoped() { return ['category_id' => 5]; }

// 动态层级限制
public function getLevel(): ?int { return static::$level; }

// Tabs 配置
public function getTabs(): array
{
    return [
        'web' => Tab::make()->label('Website Navigation'),
        'shop' => Tab::make()->label('Shop Navigation'),
    ];
}
```

#### 操作 Actions

页面提供以下内置 Actions：

| Action | 说明 |
|---|---|
| `createAction()` | 创建节点（header action） |
| `createChildAction()` | 创建子节点（行内） |
| `editAction()` | 编辑节点 |
| `deleteAction()` | 删除节点（受 `allow_delete_parent` / `allow_delete_root` 配置控制） |
| `moveNodeAction()` | 拖拽排序确认 |
| `fixTreeAction()` | 修复树结构（header action） |

#### 模型要求

模型必须 use `Kalnoy\Nestedset\NodeTrait`，否则 `mount()` 会抛出 `NestedsetException`。

```php
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait;

    // 多租户 / Tabs 支持：定义 scope attributes
    public function getScopeAttributes(): array
    {
        return ['team_id', 'type'];
    }
}
```

### Nestedset Livewire 组件（树形展示）

`Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset` 继承 `Livewire\Component`，提供可嵌入的树形展示。

#### 静态属性（子类覆盖）

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$model` | `?string` | `null` | 嵌套集模型类名 |
| `$recordTitleAttribute` | `string` | `'name'` | 节点标题字段名 |

#### 实例属性（可通过 Blade 属性传入）

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
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
public function getEloquentQuery($query) { return $query; }

// 额外 scope 参数
public function nestedScoped() { return []; }
```

#### 事件

| 事件 | 触发时机 |
|---|---|
| `sn-filament-nestedset-leaf-click` | 点击叶子节点 |
| `sn-filament-nestedset-node-click` | 点击非叶子节点 |

#### 使用示例

```php
use Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset;

class Categories extends Nestedset
{
    protected static ?string $model = Category::class;

    protected static string $recordTitleAttribute = 'name_label';

    #[On('sn-filament-nestedset-leaf-click')]
    public function clickCategory($recordId)
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

### 配置

`config/sn-filament-nestedset.php`：

```php
return [
    'allow_delete_parent' => false,                // 是否允许删除有子节点的节点
    'allow_delete_root' => false,                   // 是否允许删除根节点
    'create_action_modal_show_parent_select' => true,  // 创建弹窗是否显示父级选择
    'show_create_child_node_action_in_row' => true,    // 行内是否显示"创建子节点"按钮
    'autoload_assets' => true,                      // 是否自动加载 CSS（自定义主题时关闭）
];
```

### 多租户支持

基于 `kalnoy/nestedset` 的 `scoped` 特性。模型需定义 `getScopeAttributes()` 返回 scope 字段数组。页面默认 `$isScopedToTenant = true`，自动将 `team_id` 加入 scope。

如果面板支持多租户但当前页面不需要，设置 `$isScopedToTenant = false`。

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| Page 基类 | `Wsmallnews\FilamentNestedset\Pages\NestedsetPage` |
| Livewire 组件 | `Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset` |
| 表单字段 | `Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree` |
| Artisan 命令 | `Wsmallnews\FilamentNestedset\Commands\MakeNestedsetPageCommand` |
| 异常 | `Wsmallnews\FilamentNestedset\Exceptions\NestedsetException` |
| ServiceProvider | `Wsmallnews\FilamentNestedset\FilamentNestedsetServiceProvider` |

### 常见错误

- **模型必须 use `NodeTrait`**，否则 `mount()` 抛出 `NestedsetException`。
- **`$level` 设置为 `1` 时只能有根节点**，至少 `2` 才能选择父级（`createAction` 中 `getLevel() >= 2` 才显示父级选择字段）。
- **`$recordTitleAttribute` 是 `protected static`**，在子类中用 `protected static string $recordTitleAttribute = 'title'` 覆盖，不要用实例属性。
- **Livewire 组件必须覆盖 `getNestedset()` 或设置 `$model`**，否则抛出异常。
- **`autoload_assets` 关闭后需在自定义主题 CSS 中手动引入**：`@import '../../../../vendor/wsmallnews/filament-nestedset/resources/css/index.css'`。
- **多租户 scope 需要模型定义 `getScopeAttributes()`**，返回的字段必须包含 `team_id`。
- **拖拽移动节点受 `$level` 限制**，超过层级限制时操作会被取消并提示。

=== wsmallnews/preference rules ===

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
    // - getEloquentQuery() 按 log_name 过滤，预加载 causer（移除租户全局作用域）和 subject
    // - 图标、slug、导航排序、翻译标签
}
```

模型实现接口可自定义日志展示：

- `Wsmallnews\Support\Contracts\ActivityLogs\HasActivityLogTitle` — 自定义日志标题
- `Wsmallnews\Support\Contracts\ActivityLogs\HasActivityLogUrl` — 自定义查看链接
- `Wsmallnews\Support\Contracts\HasModelLabel` — 自定义模型标签（`static getModelLabel(): string`），用于活动日志类型下拉选项的标签解析

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

#### 自定义属性（HasCustomProperties）

**Plugin 层** — `Wsmallnews\Support\Concerns\Plugin\HasCustomProperties`：

```php
// 在插件中设置自定义属性
$plugin->customProperties([
    'table' => fn (Table $table, string $resource) => $table,
    'form' => fn (Schema $schema, string $resource) => $schema,
    'scopeable' => ['scopeType' => 'post', 'scopeId' => 0],
]);

// 读取
$plugin->getCustomProperties($resourceClass);
```

**Resource 层** — `Wsmallnews\Support\Concerns\Resource\HasCustomProperties`：委托到插件层，提供快捷方法：

```php
// 快捷获取自定义的 table/form/infolist
static::getCustomTable($table);           // 返回 ?Table
static::getCustomForm($schema);           // 返回 ?Schema
static::getCustomFormArray($arguments);   // 返回 ?array
static::getCustomInfolist($schema);       // 返回 ?Schema
static::getCustomInfolistArray();         // 返回 ?array

// scopeable 相关
static::getCustomScopeable();             // 返回 ?array
static::getCustomScopeType();             // 返回 ?string
static::getCustomScopeId();               // 返回 ?int
static::getCustomProperty('key');         // 获取单个属性
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
| Contracts（接口） | `Wsmallnews\Support\Contracts\` |
| Contracts - 活动日志 | `Wsmallnews\Support\Contracts\ActivityLogs\` |
| 通用 Traits | `Wsmallnews\Support\Concerns\` |
| Plugin 自定义属性 | `Wsmallnews\Support\Concerns\Plugin\` |
| Resource 自定义属性 | `Wsmallnews\Support\Concerns\Resource\` |
| 安装工具 | `Wsmallnews\Support\Concerns\Install\` |
| Filament 通用 | `Wsmallnews\Support\Filament\Concerns\` |
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
