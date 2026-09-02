# CMS System — Filament 5.x Standard Resource Architecture Review

> **Reviewer:** Mohammed Jemal
> **Date:** September 1, 2026
> **Project:** CMS System (`filament/filament ^5.6.6`, Laravel 13)
> **Purpose:** Identify components that should be refactored into standard Filament 5.x Resources for consistency, maintainability, and framework alignment.

---

## 1. What Is a Filament 5.x Standard Resource?

A **Filament Resource** is the framework's first-class pattern for managing any **Eloquent model** that has CRUD (Create, Read, Update, Delete) operations. When you follow the standard Resource structure, Filament automatically provides:

| Feature                       | What You Get for Free                                                                           |
| :---------------------------- | :---------------------------------------------------------------------------------------------- |
| **Table Listing**       | Searchable, sortable, filterable, paginated table with bulk actions                             |
| **Create / Edit Forms** | Schema-driven forms with validation, relationships, and lifecycle hooks                         |
| **View (Infolist)**     | Read-only detail view with formatted fields                                                     |
| **Authorization**       | Automatic policy integration (`canView`, `canCreate`, `canEdit`, `canDelete`)           |
| **Global Search**       | Model records appear in the universal search bar                                                |
| **Navigation**          | Auto-registered sidebar items with icons, badges, groups, and parent-child nesting              |
| **URL Routing**         | Consistent, RESTful URL patterns (`/resource`, `/resource/create`, `/resource/{id}/edit`) |
| **Relation Managers**   | Sub-tables on View/Edit pages showing related models (e.g., Users assigned to a Role)           |
| **Notifications**       | Built-in success/error notifications on all CRUD operations                                     |
| **Soft Deletes**        | Trash, restore, and force-delete actions with zero custom code                                  |

### The Standard Filament 5.x Resource Directory Structure

```
app/Filament/Resources/[ResourceName]/
├── Pages/
│   ├── List[ResourceNamePlural].php      ← Table listing page
│   ├── Create[ResourceName].php          ← Create form page
│   ├── Edit[ResourceName].php            ← Edit form page
│   └── View[ResourceName].php            ← Read-only detail page
├── Schemas/
│   ├── [ResourceName]Form.php            ← Form schema (shared by Create & Edit)
│   └── [ResourceName]Infolist.php        ← Infolist schema (used by View)
├── RelationManagers/
│   └── [RelationName]RelationManager.php ← Sub-table for related models
├── Tables/
│   └── [ResourceName]Table.php           ← Table columns, filters, actions
└── [ResourceName]Resource.php            ← Root orchestrator (model, nav, pages, search)
```

### Why Standardize?

1. **Consistency** — Every model-backed UI follows the same pattern. New developers find things instantly.
2. **Less Code** — Filament handles routing, authorization, notifications, pagination, and URL generation. You delete hundreds of lines of manual Livewire state, validation, and redirect logic.
3. **Fewer Bugs** — Manual Livewire properties like `$isDeleteModalOpen` and `$deletingRoleId` are replaced by battle-tested Filament action modals.
4. **Better UX** — Navigation active states, breadcrumbs, global search, and badge counts work correctly out-of-the-box. Redirect hubs break these features.
5. **Testability** — Filament Resources have a well-documented testing API (`livewire(ListRoles::class)->assertCanSeeTableRecords(...)`) that custom Pages lack.

---

## 2. Current Architecture Audit

### 2.1 What Already Follows the Standard ✅

These Resources are well-structured and follow the Filament 5.x convention:

| Resource                   | Location                                     | Structure                                   |
| :------------------------- | :------------------------------------------- | :------------------------------------------ |
| `PostResource`           | `app/Filament/Resources/Posts/`            | ✅ Pages, Schemas, Tables                   |
| `PageResource`           | `app/Filament/Resources/Pages/`            | ✅ Pages, Schemas, Tables                   |
| `CategoryResource`       | `app/Filament/Resources/Categories/`       | ✅ Pages, Schemas, Tables                   |
| `TagResource`            | `app/Filament/Resources/Tags/`             | ✅ Pages, Schemas, Tables                   |
| `CustomTaxonomyResource` | `app/Filament/Resources/CustomTaxonomies/` | ✅ Pages, Schemas, Tables, RelationManagers |
| `PostTypeResource`       | `app/Filament/Resources/PostTypes/`        | ✅ Pages, Schemas, Tables                   |
| `UserResource`           | `app/Filament/Resources/Users/`            | ✅ Pages, Schemas, Tables                   |
| `MediaAssetResource`     | `app/Filament/Resources/MediaAssets/`      | ✅ Pages, Schemas, Tables                   |

**These are good. No changes needed to their Resource structure.**

---

### 2.2 What Must Be Refactored Into Standard Resources 🔴

The following components manage **Eloquent models** but are built as standalone custom Livewire Pages instead of standard Filament Resources. This creates inconsistency, duplicated boilerplate, and missed framework features.

---

## 3. Refactoring Recommendations

---

### 3.1 🔴 PRIORITY 1 — Roles & Permissions → `RoleResource`

**Model:** `Spatie\Permission\Models\Role` (Eloquent model)

**Current State:** 4 standalone custom Pages + 4 Blade templates

| Current File                                                  | What It Does                                                  | Lines of Code |
| :------------------------------------------------------------ | :------------------------------------------------------------ | :------------ |
| `app/Filament/Pages/Iam/RolesAndPermissions.php`            | Lists roles as custom cards with manual Livewire delete modal | 166 lines     |
| `app/Filament/Pages/Iam/CreateRole.php`                     | Create form with manual validation, save, and redirect        | 99 lines      |
| `app/Filament/Pages/Iam/EditRole.php`                       | Edit form with manual validation, user-sync logic, redirect   | 176 lines     |
| `app/Filament/Pages/Iam/RoleDetailPage.php`                 | View page with manually-built permission capabilities         | 169 lines     |
| `resources/views/filament/pages/iam/roles-matrix.blade.php` | Custom card grid template                                     | 13,752 bytes  |
| `resources/views/filament/pages/iam/create-role.blade.php`  | Custom create form template                                   | 6,429 bytes   |
| `resources/views/filament/pages/iam/edit-role.blade.php`    | Custom edit form template                                     | 8,640 bytes   |
| `resources/views/filament/pages/iam/role-detail.blade.php`  | Custom detail view template                                   | 9,693 bytes   |

**Total: ~610 lines of PHP + ~38 KB of Blade templates**

#### Why This Must Be a Resource

1. **`Role` is an Eloquent model** — It has a database table (`roles`), attributes (`name`, `guard_name`), timestamps, and relationships (`permissions`, `users`). This is exactly what Resources are designed for.
2. **Manual Livewire state is fragile** — The current `RolesAndPermissions.php` manages `$isDeleteModalOpen` and `$deletingRoleId` as public Livewire properties. A standard Resource replaces this with a single `DeleteAction::make()->requiresConfirmation()` call — zero state management needed.
3. **Manual validation is error-prone** — Both `CreateRole.php` and `EditRole.php` implement custom `$this->validate()` calls, manual `Role::create()`, and manual `$this->redirect()`. A Resource handles all of this through `CreateRecord` and `EditRecord` page classes automatically.
4. **No relation managers** — There is no way to see which users are assigned to a role from the role detail screen. A `UsersRelationManager` on the Resource would provide this instantly.
5. **Navigation is broken** — `RoleDetailPage` manually builds `NavigationItem` objects for every role in the database via `getNavigationItems()`. This adds a database query on every page load for every user, and breaks standard active-state highlighting.

#### Target Structure

```
app/Filament/Resources/Roles/
├── Pages/
│   ├── ListRoles.php
│   ├── CreateRole.php
│   ├── EditRole.php
│   └── ViewRole.php
├── Schemas/
│   ├── RoleForm.php            ← Role name + grouped permission CheckboxList
│   └── RoleInfolist.php        ← Role summary, coverage %, permission grid
├── RelationManagers/
│   └── UsersRelationManager.php ← Users assigned to this role
├── Tables/
│   └── RolesTable.php          ← Name, user count, permission count, badge, actions
└── RoleResource.php
```

#### Files to Delete After Refactoring

- `app/Filament/Pages/Iam/RolesAndPermissions.php`
- `app/Filament/Pages/Iam/CreateRole.php`
- `app/Filament/Pages/Iam/EditRole.php`
- `app/Filament/Pages/Iam/RoleDetailPage.php`
- `resources/views/filament/pages/iam/roles-matrix.blade.php`
- `resources/views/filament/pages/iam/create-role.blade.php`
- `resources/views/filament/pages/iam/edit-role.blade.php`
- `resources/views/filament/pages/iam/role-detail.blade.php`

> [!IMPORTANT]
> The `EditRole.php` currently mixes Role editing with User account management (creating/updating a user tied to the role, syncing email/password). This coupling must be separated during refactoring. Role CRUD should only manage the role's name and permissions. User management belongs exclusively in `UserResource`.

---

### 3.2 🔴 PRIORITY 2 — Folders → `FolderResource`

**Model:** `App\Models\Folder` (Eloquent model with parent-child hierarchy)

**Current State:** 1 standalone custom Page + 1 Blade template

| Current File                                             | What It Does                                            | Lines of Code  |
| :------------------------------------------------------- | :------------------------------------------------------ | :------------- |
| `app/Filament/Pages/Dam/Folders.php`                   | Tree view with manual create/rename/delete/move actions | 265 lines      |
| `resources/views/filament/pages/dam/folders.blade.php` | Custom tree UI with drag-drop                           | Large template |

#### Why This Should Be a Resource

1. **`Folder` is a full Eloquent model** — It has `name`, `parent_id`, timestamps, relationships (`parent()`, `children()`, `mediaAssets()`), and a factory. This is a textbook case for a Resource.
2. **All CRUD operations exist but are manually wired** — The current Page manually implements `createFolder`, `renameFolder`, `deleteFolder`, and `moveFolder` as separate Filament Action methods with inline forms. A Resource provides these through standard Create/Edit/Delete pages and actions.
3. **No relation manager for media** — You cannot see which media assets are inside a folder from the folder management screen. A `MediaAssetsRelationManager` would provide this.
4. **Authorization is manually checked everywhere** — Each action method manually calls `$this->authorize('update', $folder)` or checks permissions inline. Resource pages handle this automatically through model policies.

#### Target Structure

```
app/Filament/Resources/Folders/
├── Pages/
│   ├── ListFolders.php
│   ├── CreateFolder.php
│   ├── EditFolder.php
│   └── ViewFolder.php
├── Schemas/
│   ├── FolderForm.php             ← Name, parent folder select
│   └── FolderInfolist.php         ← Folder stats, child count, media count
├── RelationManagers/
│   └── MediaAssetsRelationManager.php  ← Files inside this folder
├── Tables/
│   └── FoldersTable.php           ← Hierarchical listing with depth indicator
└── FolderResource.php
```

#### Files to Delete After Refactoring

- `app/Filament/Pages/Dam/Folders.php`
- `resources/views/filament/pages/dam/folders.blade.php`
- `resources/views/filament/pages/dam/partials/folder-nodes.blade.php`

> [!NOTE]
> The current tree view with drag-drop is a custom visual feature. If you want to preserve this specific UX, you can implement it as a custom `ListFolders` page that overrides the default table with a tree component while still benefiting from the Resource structure for Create, Edit, View, and authorization.

---

## 4. Navigation Hub Redirect Pages — What to Clean Up

### 4.1 The `NavigationHubPage` Anti-Pattern

The project uses an abstract `NavigationHubPage` class — a Page that exists solely to appear in the sidebar and immediately redirect to a real Resource URL. There are currently **6 hub pages** using this pattern:

| Hub Page            | Redirects To                          | Why It Exists                  |
| :------------------ | :------------------------------------ | :----------------------------- |
| `PostsGroup`      | `PostResource::getUrl('index')`     | Sidebar parent for Posts       |
| `PagesGroup`      | `PageResource::getUrl('index')`     | Sidebar parent for Pages       |
| `TaxonomiesGroup` | `CategoryResource::getUrl('index')` | Sidebar parent for Taxonomies  |
| `CustomPostTypes` | `PostTypeResource::getUrl('index')` | Sidebar parent for CPT         |
| `AllUsers`        | `UserResource::getUrl('index')`     | Sidebar entry for All Users    |
| `AddNewUser`      | `UserResource::getUrl('create')`    | Sidebar entry for Add New User |

#### Problems With This Pattern

1. **Unnecessary HTTP redirects** — Every click triggers a full page load to the hub URL, then an immediate 302 redirect to the real URL. This doubles the network round-trips.
2. **Broken navigation active states** — When the user lands on `PostResource`'s list page, Filament highlights `PostResource`'s nav item, not the `PostsGroup` hub. The hub never appears "active" because the user is never actually on it.
3. **Navigation badges don't work** — If you add a `getNavigationBadge()` to a hub page, it renders on a page the user never sees.
4. **Extra registered routes** — Each hub page registers a route (`/content/posts-hub`, `/iam/users-hub`, `/iam/users/create-hub`) that serves no purpose except to redirect.
5. **Duplicate permission checks** — Both the hub page and the target Resource implement `canAccess()` with the same permission checks.

#### Recommended Approach

Instead of hub pages, use Filament 5.x's native `$navigationParentItem` property on each Resource to create sidebar hierarchy:

```php
// In PostResource.php — register its own navigation
public static function shouldRegisterNavigation(): bool
{
    return true; // Let the Resource own its nav item
}

protected static ?string $navigationGroup = 'Content';
protected static ?string $navigationLabel = 'Posts';
```

For child items like "Add New Post", inject them as `NavigationItem` objects directly from the Resource's `getNavigationItems()` method, or simply rely on the header action button on the List page.

### 4.2 Recommendation Per Hub Page

| Hub Page            | Action                                                                                                                                                                                                                                                                                                         |
| :------------------ | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `AllUsers`        | **Delete.** Let `UserResource` register its own navigation directly.                                                                                                                                                                                                                                   |
| `AddNewUser`      | **Delete.** Use header action on `ListUsers` page, or a `NavigationItem` from `UserResource`.                                                                                                                                                                                                      |
| `TaxonomiesGroup` | **Delete.** Categories, Tags, and CustomTaxonomies already use `$navigationParentItem = 'Taxonomies'`. Register "Taxonomies" as a static navigation group label instead.                                                                                                                               |
| `PostsGroup`      | **Evaluate.** If the only purpose is sidebar nesting + "Add New Post" child link, move the child link to `PostResource::getNavigationItems()` and let the Resource own its nav.                                                                                                                        |
| `PagesGroup`      | **Evaluate.** Same reasoning as PostsGroup.                                                                                                                                                                                                                                                              |
| `CustomPostTypes` | **Keep (conditionally).** This hub has real logic — it dynamically generates nav items for each registered custom post type. This is legitimate Page behavior, not a simple redirect. However, review whether this can be handled by a custom `getNavigationItems()` on `PostTypeResource` instead. |

---

## 5. What Should NOT Be a Resource

Not everything belongs in a Resource. The following are correctly implemented as standalone Pages:

### 5.1 System Configuration Settings ✅ (Keep as Pages)

| Page                      | Why It's Correct as a Page                                              |
| :------------------------ | :---------------------------------------------------------------------- |
| `GeneralSettingsPage`   | Manages singleton key-value settings, not a multi-record Eloquent model |
| `ReadingSettingsPage`   | Same — single configuration form, not a table of records               |
| `PermalinkSettingsPage` | Same                                                                    |
| `MediaSettingsPage`     | Same                                                                    |
| `SeoDefaultsPage`       | Same                                                                    |
| `EmailSettingsPage`     | Same                                                                    |

**Resources are for collections of records.** Settings are a single configuration state. These are correctly built as form-based Pages.

> [!TIP]
> **Future improvement:** Consider consolidating the 6 settings pages into a single tabbed `SettingsPage` to reduce sidebar clutter, but this is a UX improvement — not a Resource refactoring issue.

### 5.2 Upload Media ✅ (Keep as Page)

`UploadMedia` is a **workflow page** — a multi-file upload form that creates `MediaAsset` records in bulk. It is not a standard CRUD screen for a single model. The current implementation correctly uses Filament's `Schema`, `FileUpload`, database transactions, and redirects to the library after upload.

> [!TIP]
> **Future improvement:** Consider making this the `CreateMediaAsset` page inside `MediaAssetResource` (the Resource currently has `canCreate(): false`). This would eliminate the standalone Page while preserving the upload workflow.

### 5.3 Page Hierarchy & Page Templates ✅ (Keep as Pages)

| Page              | Why It's Correct as a Page                                                                      |
| :---------------- | :---------------------------------------------------------------------------------------------- |
| `PageHierarchy` | Custom tree/drag-drop UI for reordering pages — a specialized visualization, not standard CRUD |
| `PageTemplates` | Read-only catalog of registered templates — no Eloquent model backing it                       |

These are legitimate custom Pages that provide specialized UIs beyond standard table/form patterns.

---

## 6. Summary: Refactoring Action Items

### Must Do (Standard Resource Conversions)

| # | Item                                           | Priority | Estimated Impact                                                                  |
| :- | :--------------------------------------------- | :------- | :-------------------------------------------------------------------------------- |
| 1 | Convert Roles & Permissions to`RoleResource` | 🔴 High  | Eliminates ~610 lines PHP + ~38 KB Blade; gains table, search, relation managers  |
| 2 | Convert Folders to`FolderResource`           | 🔴 High  | Eliminates ~265 lines PHP + Blade templates; gains standard CRUD + media relation |

### Should Do (Navigation Cleanup)

| # | Item                                              | Priority  | Estimated Impact                                        |
| :- | :------------------------------------------------ | :-------- | :------------------------------------------------------ |
| 3 | Delete`AllUsers` hub page                       | 🟡 Medium | Removes unnecessary redirect; fixes nav active state    |
| 4 | Delete`AddNewUser` hub page                     | 🟡 Medium | Removes unnecessary redirect; use header action instead |
| 5 | Delete`TaxonomiesGroup` hub page                | 🟡 Medium | Resources already use`$navigationParentItem`          |
| 6 | Review`PostsGroup` and `PagesGroup` hub pages | 🟡 Medium | May be replaceable with Resource nav configuration      |

### Consider (UX Improvements, Not Blockers)

| # | Item                                                                     | Priority | Estimated Impact                    |
| :- | :----------------------------------------------------------------------- | :------- | :---------------------------------- |
| 7 | Merge`UploadMedia` into `MediaAssetResource` as `CreateMediaAsset` | 🟢 Low   | Consolidates DAM into one Resource  |
| 8 | Consolidate 6 settings pages into tabbed`SettingsPage`                 | 🟢 Low   | Cleaner sidebar, same functionality |

---

## 7. Recommended Implementation Order

```
Step 1: RoleResource         ← Highest value, most code eliminated
Step 2: FolderResource       ← Second highest, with MediaAssetsRelationManager  
Step 3: Delete IAM hub pages ← Quick cleanup after RoleResource exists
Step 4: Review Content hubs  ← Decide PostsGroup/PagesGroup fate
```

> [!CAUTION]
> **Do not refactor multiple items simultaneously.** Each conversion should be a single PR with its own test coverage. Run the existing `IamAdminUiTest` and `MediaUploadTest` suites after each change to verify nothing breaks.

---

## 8. Reference: Existing Resources as Examples

When building the new Resources, use the existing codebase as your template. The best example of a fully-structured Filament 5.x Resource in this project is:

**`UserResource`** — `app/Filament/Resources/Users/`

```
Users/
├── Pages/
│   ├── ListUsers.php
│   ├── CreateUser.php
│   ├── EditUser.php
│   └── ViewUser.php
├── Schemas/
│   ├── UserForm.php
│   └── UserInfolist.php
├── Tables/
│   └── UsersTable.php
└── UserResource.php
```

Follow this exact pattern for `RoleResource` and `FolderResource`.
