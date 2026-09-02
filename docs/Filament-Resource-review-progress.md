# Filament Resource Review — Progress Tracker

> **Source:** [Filament-Resource-review.md](./Filament-Resource-review.md)  
> **Started:** September 2, 2026  
> **Rule:** One conversion per PR. Run `IamAdminUiTest` / `MediaUploadTest` after each change.

**Status legend:** `⬜ Not started` · `🟡 In progress` · `✅ Done` · `⏸️ Blocked` · `➖ Skipped`

---

## Overall progress

| Phase | Focus | Status |
| :---- | :---- | :----- |
| Step 1 | `RoleResource` | 🟡 In progress |
| Step 2 | `FolderResource` | ⬜ Not started |
| Step 3 | Delete IAM hub pages | ⬜ Not started |
| Step 4 | Review Content hubs | ⬜ Not started |
| Later | Low-priority UX improvements | ⬜ Not started |

---

## Step 1 — Convert Roles & Permissions → `RoleResource`

**Priority:** 🔴 High  
**Model:** `Spatie\Permission\Models\Role`  
**Template:** `app/Filament/Resources/Users/`  
**Status:** 🟡 In progress  
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
| 1.3 | Port create/edit form → `RoleForm` (name + grouped permission CheckboxList) | ⬜ | Use Filament Create/Edit pages |
| 1.4 | Port detail view → `RoleInfolist` | ⬜ | Coverage %, permission grid |
| 1.5 | Add `UsersRelationManager` | ⬜ | Users assigned to role |
| 1.6 | **Separate** role CRUD from user create/update/password sync | ⬜ | User work stays in `UserResource` only |
| 1.7 | Wire navigation, policies, global search | ⬜ | Remove per-role `getNavigationItems()` DB query |
| 1.8 | Delete old IAM Pages + Blade templates (list below) | ⬜ | After feature parity |
| 1.9 | Update routes / links that pointed at old pages | ⬜ | |
| 1.10 | Run `IamAdminUiTest`; fix failures | ⬜ | |

### Files to delete when Step 1 is done

- [ ] `app/Filament/Pages/Iam/RolesAndPermissions.php`
- [ ] `app/Filament/Pages/Iam/CreateRole.php`
- [ ] `app/Filament/Pages/Iam/EditRole.php`
- [ ] `app/Filament/Pages/Iam/RoleDetailPage.php`
- [ ] `resources/views/filament/pages/iam/roles-matrix.blade.php`
- [ ] `resources/views/filament/pages/iam/create-role.blade.php`
- [ ] `resources/views/filament/pages/iam/edit-role.blade.php`
- [ ] `resources/views/filament/pages/iam/role-detail.blade.php`

### Done criteria

- [ ] Role list/create/edit/view work via Resource
- [ ] No manual `$isDeleteModalOpen` / `$deletingRoleId` state
- [ ] Role edit does **not** create/update users
- [ ] Old IAM role pages and Blade views removed
- [ ] `IamAdminUiTest` passes

---

## Step 2 — Convert Folders → `FolderResource`

**Priority:** 🔴 High  
**Model:** `App\Models\Folder`  
**Depends on:** Step 1 preferred (not hard-blocked)  
**Status:** ⬜ Not started  
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
| 2.1 | Scaffold `FolderResource` | ⬜ | |
| 2.2 | Port create/rename/delete/move into Resource actions or Create/Edit pages | ⬜ | Policy-backed auth |
| 2.3 | Decide tree UX: custom `ListFolders` tree **or** hierarchical table | ⬜ | Review allows custom list page |
| 2.4 | Add `MediaAssetsRelationManager` | ⬜ | Files in folder |
| 2.5 | Form/infolist: name, parent select, stats | ⬜ | |
| 2.6 | Delete old Folders Page + Blade (list below) | ⬜ | |
| 2.7 | Update DAM nav / links | ⬜ | |
| 2.8 | Run `MediaUploadTest` (+ any folder tests); fix failures | ⬜ | |

### Files to delete when Step 2 is done

- [ ] `app/Filament/Pages/Dam/Folders.php`
- [ ] `resources/views/filament/pages/dam/folders.blade.php`
- [ ] `resources/views/filament/pages/dam/partials/folder-nodes.blade.php`

### Done criteria

- [ ] Folder CRUD works via Resource
- [ ] Authorization goes through policies / Resource, not ad-hoc action checks only
- [ ] Media-in-folder visible via relation manager (or equivalent)
- [ ] Old Folders page + Blade removed (or tree kept only as custom List page under Resource)
- [ ] Relevant media/DAM tests pass

---

## Step 3 — Delete IAM navigation hub pages

**Priority:** 🟡 Medium  
**Depends on:** Step 1 (RoleResource in place); UserResource already owns user CRUD  
**Status:** ⬜ Not started  
**PR:** _link when opened_

### Checklist

| # | Hub page | Action | Status | Notes |
| :- | :------- | :----- | :----- | :---- |
| 3.1 | `AllUsers` | **Delete** — `UserResource` owns nav | ⬜ | |
| 3.2 | `AddNewUser` | **Delete** — use ListUsers header action or `NavigationItem` | ⬜ | |
| 3.3 | Confirm no remaining links to hub routes | ⬜ | |
| 3.4 | Verify sidebar active states / badges | ⬜ | |
| 3.5 | Run `IamAdminUiTest` | ⬜ | |

### Done criteria

- [ ] IAM hub redirect pages removed
- [ ] Users reachable via Resource navigation only
- [ ] No broken sidebar links; active state correct

---

## Step 4 — Review Content / taxonomy hubs

**Priority:** 🟡 Medium  
**Status:** ⬜ Not started  
**PR:** _link when opened_

### Checklist

| # | Hub page | Action from review | Decision | Status | Notes |
| :- | :------- | :----------------- | :------- | :----- | :---- |
| 4.1 | `TaxonomiesGroup` | **Delete** — resources already use `$navigationParentItem` | _TBD_ | ⬜ | |
| 4.2 | `PostsGroup` | **Evaluate** — move child links to `PostResource::getNavigationItems()` | _TBD_ | ⬜ | |
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

---

## How to update this file

1. Flip task status (`⬜` → `🟡` → `✅`).
2. Fill **PR** and **Decision** fields when known.
3. Check off files-to-delete only after they are actually removed.
4. Add a row to **Change log** when a step ships.
5. Do not start the next high-priority step in the same PR as the previous one.
