# Filament Resource Review — Progress Tracker

> **Source:** [Filament-Resource-review.md](./Filament-Resource-review.md)  
> **Started:** September 2, 2026  
> **Rule:** One conversion per PR. Run `IamAdminUiTest` / `MediaUploadTest` after each change.

**Status legend:** `⬜ Not started` · `🟡 In progress` · `✅ Done` · `⏸️ Blocked` · `➖ Skipped`

---

## Overall progress

| Phase | Focus | Status |
| :---- | :---- | :----- |
| Step 1 | `RoleResource` | ✅ Done |
| Step 2 | `FolderResource` | ✅ Done |
| Step 3 | Delete IAM hub pages | 🟡 In progress |
| Step 4 | Review Content hubs | ⬜ Not started |
| Later | Low-priority UX improvements | ⬜ Not started |

---

## Step 1 — Convert Roles & Permissions → `RoleResource`

**Priority:** 🔴 High  
**Model:** `Spatie\Permission\Models\Role`  
**Template:** `app/Filament/Resources/Users/`  
**Status:** ✅ Done  
**PR:** _link when opened_

### Target structure

```
app/Filament/Resources/Roles/
├── Pages/
│   ├── ListRoles.php
│   ├── CreateRole.php
│   ├── EditRole.php
│   └── ViewRole.php
├── Schemas/
│   ├── RoleForm.php
│   └── RoleInfolist.php
├── RelationManagers/
│   └── UsersRelationManager.php
├── Tables/
│   └── RolesTable.php
└── RoleResource.php
```

### Checklist

| # | Task | Status | Notes |
| :- | :--- | :----- | :---- |
| 1.1 | Scaffold `RoleResource` (Pages, Schemas, Tables, RelationManagers) | ✅ | Match `UserResource` layout; nav disabled until 1.7 |
| 1.2 | Port list UI → `RolesTable` (name, user count, permission count, actions) | ✅ | Role badge, description, coverage %, delete guards |
| 1.3 | Port create/edit form → `RoleForm` (name + grouped permission CheckboxList) | ✅ | No user email/password; syncPermissions on create/save |
| 1.4 | Port detail view → `RoleInfolist` | ✅ | Summary, coverage %, grouped permission IconEntry grid |
| 1.5 | Add `UsersRelationManager` | ✅ | List, assign via RoleAssignmentService, reassign, link to UserResource |
| 1.6 | **Separate** role CRUD from user create/update/password sync | ✅ | RoleResource + legacy EditRole are role-only; users via UserResource / relation manager |
| 1.7 | Wire navigation, policies, global search | ✅ | RoleResource owns nav + `iam/roles`; RolePolicy; global search details; no per-role nav query |
| 1.8 | Delete old IAM Pages + Blade templates (list below) | ✅ | Legacy role Pages + Blade removed; tests use RoleResource |
| 1.9 | Update routes / links that pointed at old pages | ✅ | No app/test refs remain; only RoleResource `iam/roles` routes; removed orphan `permission-status` partial |
| 1.10 | Run `IamAdminUiTest`; fix failures | ✅ | Full suite green |

### Files to delete when Step 1 is done

- [x] `app/Filament/Pages/Iam/RolesAndPermissions.php`
- [x] `app/Filament/Pages/Iam/CreateRole.php`
- [x] `app/Filament/Pages/Iam/EditRole.php`
- [x] `app/Filament/Pages/Iam/RoleDetailPage.php`
- [x] `resources/views/filament/pages/iam/roles-matrix.blade.php`
- [x] `resources/views/filament/pages/iam/create-role.blade.php`
- [x] `resources/views/filament/pages/iam/edit-role.blade.php`
- [x] `resources/views/filament/pages/iam/role-detail.blade.php`

### Done criteria

- [x] Role list/create/edit/view work via Resource
- [x] No manual `$isDeleteModalOpen` / `$deletingRoleId` state
- [x] Role edit does **not** create/update users
- [x] Old IAM role pages and Blade views removed
- [x] `IamAdminUiTest` passes

---

## Step 2 — Convert Folders → `FolderResource`

**Priority:** 🔴 High  
**Model:** `App\Models\Folder`  
**Depends on:** Step 1 preferred (not hard-blocked)  
**Status:** ✅ Done  
**PR:** _link when opened_

### Target structure

```
app/Filament/Resources/Folders/
├── Pages/
│   ├── ListFolders.php
│   ├── CreateFolder.php
│   ├── EditFolder.php
│   └── ViewFolder.php
├── Schemas/
│   ├── FolderForm.php
│   └── FolderInfolist.php
├── RelationManagers/
│   └── MediaAssetsRelationManager.php
├── Tables/
│   └── FoldersTable.php
└── FolderResource.php
```

### Checklist

| # | Task | Status | Notes |
| :- | :--- | :----- | :---- |
| 2.1 | Scaffold `FolderResource` | ✅ | Match Resource layout; nav off; interim slug `dam/folders-resource` |
| 2.2 | Port create/rename/delete/move into Resource actions or Create/Edit pages | ✅ | FolderService-backed create/edit; table Move + recursive Delete |
| 2.3 | Decide tree UX: custom `ListFolders` tree **or** hierarchical table | ✅ | **Decision: custom tree ListFolders** (drag-drop preserved); Edit/View via Resource pages |
| 2.4 | Add `MediaAssetsRelationManager` | ✅ | List/preview, add existing, move/bulk move, delete; links to MediaAssetResource |
| 2.5 | Form/infolist: name, parent select, stats | ✅ | Path preview on form; summary + contents stats on View |
| 2.6 | Delete old Folders Page + Blade (list below) | ✅ | Legacy page + Blade removed; FolderResource owns `dam/folders` + nav |
| 2.7 | Update DAM nav / links | ✅ | FolderResource registers Folders nav; tests use ListFolders/CreateFolder |
| 2.8 | Run `MediaUploadTest` (+ any folder tests); fix failures | ✅ | MediaFolderTest + MediaUploadTest green |

### Files to delete when Step 2 is done

- [x] `app/Filament/Pages/Dam/Folders.php`
- [x] `resources/views/filament/pages/dam/folders.blade.php`
- [x] `resources/views/filament/pages/dam/partials/folder-nodes.blade.php`

### Done criteria

- [x] Folder CRUD works via Resource
- [x] Authorization goes through policies / Resource, not ad-hoc action checks only
- [x] Media-in-folder visible via relation manager (or equivalent)
- [x] Old Folders page + Blade removed (or tree kept only as custom List page under Resource)
- [x] Relevant media/DAM tests pass

---

## Step 3 — Delete IAM navigation hub pages

**Priority:** 🟡 Medium  
**Depends on:** Step 1 (RoleResource in place); UserResource already owns user CRUD  
**Status:** ✅ Done  
**PR:** _link when opened_

### Checklist

| # | Hub page | Action | Status | Notes |
| :- | :------- | :----- | :----- | :---- |
| 3.1 | `AllUsers` | **Delete** — `UserResource` owns nav | ✅ | Hub deleted; UserResource registers All Users |
| 3.2 | `AddNewUser` | **Delete** — use ListUsers header action or `NavigationItem` | ✅ | Hub deleted; ListUsers header + UserResource nav item |
| 3.3 | Confirm no remaining links to hub routes | ✅ | No hub classes/routes; dashboard + tests use UserResource |
| 3.4 | Verify sidebar active states / badges | ✅ | All Users inactive on create; Add New User owns create active; no badge |
| 3.5 | Run `IamAdminUiTest` | ✅ | Full suite green |

### Done criteria

- [x] IAM hub redirect pages removed
- [x] Users reachable via Resource navigation only
- [x] No broken sidebar links; active state correct

---

## Step 4 — Review Content / taxonomy hubs

**Priority:** 🟡 Medium  
**Status:** 🟡 In progress  
**PR:** _link when opened_

### Checklist

| # | Hub page | Action from review | Decision | Status | Notes |
| :- | :------- | :----------------- | :------- | :----- | :---- |
| 4.1 | `TaxonomiesGroup` | **Delete** — resources already use `$navigationParentItem` | **Delete hub; register `TaxonomiesNavigation` parent** | ✅ | Parent nav item → Categories; no hub route |
| 4.2 | `PostsGroup` | **Evaluate** — move child links to `PostResource::getNavigationItems()` | **Delete hub; `PostsNavigation` + Add New Post on PostResource** | ✅ | Parent → All Posts; Add New Post nested under Posts |
| 4.3 | `PagesGroup` | **Evaluate** — same as Posts | _TBD_ | ⬜ | |
| 4.4 | `CustomPostTypes` | **Keep or move** dynamic CPT nav onto `PostTypeResource` | _TBD_ | ⬜ | Legitimate dynamic nav |
| 4.5 | Apply decisions; remove dead hub routes | ⬜ | |
| 4.6 | Smoke-test Content sidebar | ⬜ | |

### Done criteria

- [ ] Decision recorded for each hub above
- [ ] Implemented cleanup matches decision
- [ ] No orphan redirect-only hubs left (unless explicitly kept)

---

## Later — Consider (not blockers)

| # | Item | Priority | Status | Notes |
| :- | :--- | :------- | :----- | :---- |
| L1 | Merge `UploadMedia` into `MediaAssetResource` as `CreateMediaAsset` | 🟢 Low | ⬜ | Resource currently `canCreate(): false` |
| L2 | Consolidate 6 settings pages into one tabbed `SettingsPage` | 🟢 Low | ⬜ | UX only; not a Resource conversion |

### Keep as Pages (do not convert)

Do not treat these as open refactor work:

- [x] Settings: General, Reading, Permalink, Media, SEO, Email
- [x] `UploadMedia` (until L1)
- [x] `PageHierarchy`
- [x] `PageTemplates`

---

## Change log

| Date | Step | What changed | PR / commit |
| :--- | :--- | :----------- | :---------- |
| 2026-09-02 | — | Progress tracker created from review | — |
| 2026-09-02 | 1.1 | Scaffolded `RoleResource` (Pages, Schemas, Tables, UsersRelationManager); nav off | — |
| 2026-09-02 | 1.2 | Ported roles list UI into `RolesTable` (badge, description, coverage, delete guards) | — |
| 2026-09-02 | 1.3 | Ported role form (name + grouped permissions); wired Create/Edit syncPermissions | — |
| 2026-09-02 | 1.4 | Ported role detail into `RoleInfolist` (summary, coverage, capability matrix) | — |
| 2026-09-02 | 1.5 | Fleshed out `UsersRelationManager` (assign/reassign via RoleAssignmentService) | — |
| 2026-09-02 | 1.6 | Separated role CRUD from user accounts (stripped EditRole email/password sync; updated IAM test); interim RoleResource slug `iam/roles-resource` to avoid clash with legacy pages | — |
| 2026-09-02 | 1.7 | RoleResource owns nav/`iam/roles`; RolePolicy registered; global search details; legacy pages moved to `iam/roles-legacy*`; removed per-role nav query | — |
| 2026-09-02 | 1.8 | Deleted legacy IAM role Pages + Blade; IamAdminUiTest / nav tests pointed at RoleResource | — |
| 2026-09-02 | 1.9 | Link sweep clean; removed orphan `permission-status` partial; routes only RoleResource | — |
| 2026-09-02 | 1.10 | Full `IamAdminUiTest` passed | — |
| 2026-09-02 | 2.1 | Scaffolded `FolderResource` (Pages, Schemas, Tables, MediaAssetsRelationManager); nav off | — |
| 2026-09-02 | 2.2 | Ported create/rename/delete/move via FolderService + FolderActions (move/recursive delete) | — |
| 2026-09-02 | 2.3 | Chose custom tree ListFolders (drag-drop); Resource Create/Edit/View kept | — |
| 2026-09-02 | 2.4 | Fleshed out MediaAssetsRelationManager (list, add existing, move, delete) | — |
| 2026-09-02 | 2.5 | Polished FolderForm (path preview) + FolderInfolist (path, empty, counts) | — |
| 2026-09-02 | 2.6 | Deleted legacy Dam Folders page + Blade; FolderResource owns `dam/folders` + nav | — |
| 2026-09-02 | 2.7 | DAM nav/links pointed at FolderResource; MediaFolderTest updated | — |
| 2026-09-02 | 2.8 | MediaUploadTest + MediaFolderTest passed (no code fixes needed) | — |
| 2026-09-02 | 3.1 | Deleted AllUsers hub; UserResource owns All Users nav + dashboard users link | — |
| 2026-09-02 | 3.2 | Deleted AddNewUser hub; UserResource NavigationItem + ListUsers header | — |
| 2026-09-02 | 3.3–3.5 | Link sweep clean; create active state split; IamAdminUiTest + nav hub tests green | — |
| 2026-09-02 | 4.1 | Deleted TaxonomiesGroup hub; TaxonomiesNavigation parent → Categories | — |
| 2026-09-02 | 4.2 | Deleted PostsGroup hub; PostsNavigation + PostResource Add New Post | — |

---

## How to update this file

1. Flip task status (`⬜` → `🟡` → `✅`).
2. Fill **PR** and **Decision** fields when known.
3. Check off files-to-delete only after they are actually removed.
4. Add a row to **Change log** when a step ships.
5. Do not start the next high-priority step in the same PR as the previous one.
