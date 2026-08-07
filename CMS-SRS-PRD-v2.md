# Laravel 13 + Filament 5 CMS — Software Requirements Specification (SRS) & Product Requirements Document (PRD)

**Document Version:** 2.0  
**Status:** Ready for Development Handoff  
**Author:** Mohammed Jemal  
**Date:** 2026-08-02  
**Classification:** Internal — Engineering Use Only

---

## Part I — Governance

### 1. Document Control

#### 1.1 Version

| Attribute | Value |
|-----------|-------|
| Document Version | 2.0 |
| Status | Ready for Development Handoff |
| Author | Mohammed Jemal |
| Date | 2026-08-02 |
| Classification | Internal — Engineering Use Only |

#### 1.2 Approval

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Product Owner | [TBD] | — | — |
| Lead Architect | [TBD] | — | — |
| Engineering Lead | [TBD] | — | — |
| QA Lead | [TBD] | — | — |

---

### 2. Executive Summary

This document defines the Software Requirements Specification (SRS) and Product Requirements Document (PRD) for a content management system (CMS) backend built on Laravel 13 and Filament 5. The system is designed as an editorial-first, SEO-centric platform for managing structured web content including posts, pages, taxonomies, media, users, and system settings.

The CMS targets organizations requiring a robust, scalable, and intuitive backend for content publishing without the overhead of full-stack theming, e-commerce, or headless distribution. The product prioritizes editorial productivity, structured content modeling, and search engine optimization embedded directly within the content creation workflow.

This specification serves as the single source of truth for engineering, quality assurance, product management, and stakeholder review prior to development commencement.

---

### 3. Product Vision & Goals

#### 3.1 Vision Statement

To deliver a lean, enterprise-grade CMS backend that empowers editorial teams to create, manage, and optimize content efficiently while providing administrators with granular control over users, structure, and system behavior.

#### 3.2 Goals

1. **Simplicity** — Reduce cognitive load for content editors through intuitive navigation, consistent UI patterns, and contextual actions. Every feature must justify its presence in the MVP.
2. **Editorial Workflow** — Support a clear content lifecycle from draft to publication with visible status indicators, quick actions, and bulk operations that accelerate editorial throughput.
3. **SEO-First Publishing** — Integrate search engine optimization directly into the post and page editing experience. Editors must configure meta data, Open Graph properties, and structured schema without leaving the content form.
4. **Structured Content Management** — Enable hierarchical page trees, categorized posts, tagged content, and custom post types that adapt to diverse content strategies without code changes.
5. **Scalability for Enterprise Expansion** — Architect the system with modular boundaries so that future capabilities—such as revisions, workflows, multilingual support, and headless APIs—can be introduced without structural refactoring.

---

### 4. Project Scope

#### 4.1 MVP Inclusions

The following capabilities are explicitly within scope for the initial release:

- Dashboard with content overview, recent activity, drafts summary, and quick actions
- Post management with custom post type support
- Page management with parent/child hierarchy and template selection
- Taxonomy management: hierarchical categories, flat tags, and custom taxonomies
- Centralized media library with folder organization and metadata
- User management with role-based access control
- System settings: General, Reading, Permalinks, Media, SEO Defaults, and Email
- Embedded SEO panel within Post and Page editors
- Content lifecycle: draft, publish, update, unpublish, archive, delete, restore
- URL generation with slug uniqueness and permalink structure
- Bulk actions, filtering, sorting, and search across content listings
- Responsive admin interface conforming to Filament 5 design patterns

#### 4.2 MVP Exclusions

The following capabilities are explicitly out of scope for the initial release:

- Theme management and frontend rendering
- Widgets and sidebar components
- Comment systems
- Plugin architecture and marketplace
- Backup and restore automation
- Import/export functionality
- Multilingual content and localization beyond structural readiness
- Form builders
- Content revisions, versioning, and comparison
- Editorial workflows and approval chains
- Scheduled publishing beyond basic publish/unpublish dates
- Headless API and REST/GraphQL endpoints
- Analytics dashboards and reporting
- E-commerce capabilities
- Advanced SEO modules (sitemap generation, redirect management, link checking)

---

### 5. Assumptions & Constraints

#### 5.1 Assumptions

- Single organization deployment
- Single database architecture
- Internet-connected environment
- Authenticated admin users only; no public-facing admin registration
- Modern browser usage (latest two versions of Chrome, Firefox, Safari, Edge)
- Standard LAMP/LEMP stack compatibility

#### 5.2 Constraints

- Laravel 13.x locked for the MVP lifecycle
- Filament 5.x locked for the MVP lifecycle
- MySQL 8.0.x as the sole persistent data store
- No Redis dependency in MVP (database cache, session, and queue drivers)
- No frontend rendering engine in MVP
- No third-party authentication providers in MVP
- File storage abstracted via Laravel Storage; local filesystem in MVP

---

### 6. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Post creation time | < 2 minutes | Time from "Add New" to saved draft |
| Dashboard load time | < 2 seconds | Time to interactive under normal load |
| Content listing query | < 500 ms | 100,000 record dataset |
| Search query response | < 1 second | Full-text search across content |
| Permission bypass incidents | Zero | Security audit and penetration testing |
| SEO completeness rate | > 90% | Percentage of published content with meta title and description |
| Admin interface responsiveness | 320 px – 4K | Verified across breakpoints |

---

### 7. Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Scope creep from stakeholder requests | High | Strict adherence to MVP exclusions (Section 19); change control process |
| Taxonomy hierarchy complexity | Medium | Unlimited nesting supported; UI collapse controls for deep trees |
| SEO feature expansion pressure | Medium | Modular SEO specification; clear inheritance model prevents ad-hoc additions |
| Media storage growth | Medium | Abstracted storage layer; cloud migration-ready architecture |
| Future multilingual migration | Medium | Externalized strings; RTL structural readiness; no hardcoded locale logic |
| Filament 5 breaking changes | Low | Version lock policy; major upgrades require architecture review |
| Performance degradation at scale | Low | Indexed queries; pagination; database cache driver replaceable with Redis |

---

## Part II — Product Architecture

### 8. Technology Stack

#### 8.1 Core Platform

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| Language | PHP | 8.3.x | Server-side scripting; typed properties, readonly classes |
| Framework | Laravel | 13.x | Routing, ORM, validation, queueing, mail |
| Admin Panel | Filament | 5.x | Tables, forms, notifications, actions, widgets |
| Frontend Components | Livewire | 4.x | Reactive UI components without custom JavaScript |
| Database | MySQL | 8.0.x | Primary relational data store |
| Cache | Database | — | Native Laravel database cache driver |
| Session | Database | — | Native Laravel database session driver |
| Queue | Database | — | Native Laravel database queue driver |

#### 8.2 Authentication & Authorization

| Package | Vendor | Version | Purpose |
|---------|--------|---------|---------|
| Breezy | Filament | 5.x | Two-factor authentication, password confirmation, session management |
| Laravel Permission | Spatie | 6.x | Role-based access control (RBAC) |

#### 8.3 Media & File Handling

| Package | Vendor | Version | Purpose |
|---------|--------|---------|---------|
| Laravel Media Library | Spatie | 11.x | File uploads, model associations, conversions, collections |

#### 8.4 SEO & Structured Data

| Package | Vendor | Version | Purpose |
|---------|--------|---------|---------|
| Laravel SEO | Spatie | 3.x | Meta tags, Open Graph, JSON-LD schema output |

#### 8.5 Development & Tooling

| Tool | Version | Purpose |
|------|---------|---------|
| Composer | 2.x | PHP dependency management |
| NPM / Node.js | 20.x LTS | Frontend asset compilation |
| Vite | 5.x | Frontend build tool |
| PHPUnit | 11.x | Unit and feature testing |
| Pint | 1.x | PHP code style enforcement (Laravel preset) |

#### 8.6 Version Lock Policy

- Major versions are locked at specification time (e.g., Laravel 13.x, Filament 5.x).
- Minor and patch versions may advance within the locked major version during development.
- Any major version upgrade during the project lifecycle requires architecture review and regression testing.
- Package updates are managed through Composer lock files; `composer update` is gated by CI pipeline success.

#### 8.7 Infrastructure Assumptions

| Component | Assumption |
|-----------|------------|
| Web Server | Nginx or Apache with PHP-FPM |
| SSL/TLS | Required for all environments |
| File Storage | Local filesystem (Laravel Storage abstraction; cloud migration-ready) |
| Backups | Manual or external cron-based (not built into application) |
| Queue Worker | Database-driven; runs via `schedule:run` or dedicated worker process |
| Mail Transport | SMTP via Settings > Email configuration |

---

### 9. System Architecture Overview

#### 9.1 Architecture Overview

The CMS operates as a **modular monolith** within the Laravel 13 framework. The admin interface is rendered through Filament 5, providing a consistent, component-driven UI layer. Business logic is separated from presentation logic. Configuration is externalized; no hardcoded values exist in application logic.

#### 9.2 Domain Boundaries

The system separates concerns across six primary domains:

| Domain | Responsibility |
|--------|----------------|
| Content | Posts, pages, custom post types, content lifecycle |
| Taxonomy | Categories, tags, custom taxonomies, hierarchical classification |
| Digital Asset Management | Media library, folders, uploads, metadata, conversions |
| Identity & Access Management | Users, roles, permissions, authentication, audit logging |
| System Configuration | General, reading, permalinks, media, SEO defaults, email settings |
| SEO & Metadata | Embedded SEO panels, structured data, Open Graph, meta tag generation |

#### 9.3 Design Principles

- **Separation of Concerns** — Domain logic is isolated within bounded contexts.
- **Single Source of Truth** — Each data concept is defined once and referenced elsewhere.
- **Content-First Architecture** — Editorial workflow drives UI and data model decisions.
- **Configuration over Customization** — System behavior is controlled via settings, not code changes.
- **Abstraction over Implementation** — Storage, cache, and queue layers are abstracted to support future infrastructure changes without refactoring.

---

### 10. Information Architecture

#### 10.1 Navigation Hierarchy

The left-hand navigation menu is organized into five primary sections. This hierarchy is fixed and must be preserved exactly as specified.

```
CMS Dashboard
├── Dashboard
│   ├── Overview
│   ├── Recent Content
│   ├── Draft Summary
│   └── Quick Actions
├── Content
│   ├── Posts
│   │   ├── All Posts
│   │   ├── Add New Post
│   │   └── Custom Post Types
│   │       ├── Articles
│   │       ├── News
│   │       ├── Case Studies
│   │       └── Custom Types (dynamic)
│   ├── Pages
│   │   ├── All Pages
│   │   ├── Add New Page
│   │   ├── Page Hierarchy
│   │   └── Page Templates
│   └── Taxonomies
│       ├── Categories (hierarchical)
│       ├── Tags (flat)
│       └── Custom Taxonomies
├── Digital Asset Management
│   ├── Library
│   ├── Upload Media
│   └── Folders
├── Identity & Access Management
│   ├── All Users
│   ├── Add New User
│   └── Roles & Permissions
│       ├── Administrator
│       ├── Editor
│       ├── Author
│       └── Contributor
└── System Configuration
    ├── General
    ├── Reading
    ├── Permalinks
    ├── Media
    ├── SEO Defaults
    └── Email
```

#### 10.2 Navigation Rationale

- **Content as Primary Domain** — All editorial activity is grouped under Content to minimize navigation depth. Posts and Pages are separated because they serve different structural purposes: posts are taxonomy-driven and time-ordered; pages are hierarchy-driven and navigation-oriented.
- **SEO Embedded, Not Modular** — SEO configuration is a property of content, not an independent system. Editors configure SEO while editing content, ensuring optimization is part of the creation habit.
- **Digital Asset Management as Independent Module** — The media library is elevated to a primary navigation item because media is reused across posts, pages, and settings. Centralized access prevents duplication.
- **System Configuration at Root Level** — Administrative concerns are separated from editorial workflows, reducing accidental misconfiguration by non-administrative users.

#### 10.3 Editorial Productivity Support

- **Quick Actions** on the Dashboard provide one-click entry to "Add New Post," "Add New Page," and "Upload Media."
- **Recent Content** surfaces the last 10 edited items with direct edit links.
- **Draft Summary** displays the current user's drafts and, for Editors and Administrators, drafts awaiting review.

---

### 11. User Roles & Authorization Matrix

#### 11.1 Role Definitions

| Role | Description |
|------|-------------|
| **Administrator** | Full system access including all modules, user management, role assignment, and system configuration. |
| **Editor** | Full content and taxonomy management; can publish, edit others' content, and manage media. Restricted from user role assignment, system settings, and SEO Defaults. |
| **Author** | Can create and edit own posts and pages, assign taxonomies, upload media, and configure SEO on own content. Cannot publish; submits content for review. |
| **Contributor** | Most restricted role. Can create own draft posts only. Cannot upload media directly; may select from existing library. Cannot manage pages. |

#### 11.2 Permission Inheritance

- Permissions are defined at the role level.
- Users inherit all permissions associated with their single assigned role.
- No per-user permission overrides are supported in the MVP.
- Role changes take effect immediately upon save.
- Only Administrators may assign or change roles.

#### 11.3 Ownership Model

- Ownership checks ensure users without cross-content privileges can only modify their own records.
- Users cannot modify their own role (privilege escalation prevention).
- Content authorship is preserved when a user is suspended or soft-deleted.

#### 11.4 Authorization Matrix

| Capability | Administrator | Editor | Author | Contributor |
|------------|:-------------:|:------:|:------:|:-----------:|
| **Dashboard** |
| View Dashboard | Yes | Yes | Yes | Yes |
| View All Drafts | Yes | Yes | No | No |
| View Recent Content (all) | Yes | Yes | No | No |
| **Posts** |
| View All Posts | Yes | Yes | Own only | Own only |
| Create Post | Yes | Yes | Yes | Yes |
| Edit Own Post | Yes | Yes | Yes | Yes |
| Edit Others' Posts | Yes | Yes | No | No |
| Publish Post | Yes | Yes | No | No |
| Delete Own Post | Yes | Yes | Yes | No |
| Delete Others' Posts | Yes | Yes | No | No |
| Restore Post | Yes | Yes | No | No |
| Hard Delete Post | Yes | No | No | No |
| Duplicate Post | Yes | Yes | Yes | No |
| **Pages** |
| View All Pages | Yes | Yes | Own only | No |
| Create Page | Yes | Yes | Yes | No |
| Edit Own Page | Yes | Yes | Yes | No |
| Edit Others' Pages | Yes | Yes | No | No |
| Publish Page | Yes | Yes | No | No |
| Delete Own Page | Yes | Yes | Yes | No |
| Delete Others' Pages | Yes | Yes | No | No |
| Restore Page | Yes | Yes | No | No |
| Hard Delete Page | Yes | No | No | No |
| **Custom Post Types** |
| All capabilities | Yes | Yes | Same as Posts | Same as Posts |
| **Taxonomies** |
| View Taxonomies | Yes | Yes | Yes | Yes |
| Create Taxonomy Term | Yes | Yes | No | No |
| Edit Taxonomy Term | Yes | Yes | No | No |
| Delete Taxonomy Term | Yes | Yes | No | No |
| **Digital Asset Management** |
| View Library | Yes | Yes | Yes | Yes |
| Upload Media | Yes | Yes | Yes | No |
| Edit Own Media | Yes | Yes | Yes | No |
| Edit Others' Media | Yes | Yes | No | No |
| Delete Media | Yes | Yes | No | No |
| Force Delete Media | Yes | No | No | No |
| **Identity & Access Management** |
| View All Users | Yes | Yes | No | No |
| Create User | Yes | No | No | No |
| Edit User | Yes | Own only | Own only | Own only |
| Edit User Role | Yes | No | No | No |
| Delete User | Yes | No | No | No |
| Suspend User | Yes | No | No | No |
| **System Configuration** |
| View Settings | Yes | No | No | No |
| Edit Settings | Yes | No | No | No |
| **SEO & Metadata** |
| Configure SEO on Content | Yes | Yes | Yes | No |
| View SEO Defaults | Yes | Yes | No | No |
| Edit SEO Defaults | Yes | No | No | No |

---

## Part III — Functional Requirements

### 12. Content Management Domain

#### 12.1 Overview

The Content Management Domain governs all editorial content: Posts, Pages, and Custom Post Types. It defines shared behaviors—including slug management, content lifecycle, publishing rules, SEO integration, and version readiness—that apply uniformly across all content types.

---

#### 12.2 Posts

##### 12.2.1 Purpose

Posts are the primary editorial content type. They support categorization, tagging, authorship, scheduling, and featured imagery. Posts are displayed in reverse chronological order by default and are filtered by status, taxonomy, author, and date range.

##### 12.2.2 Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Title | Text | Yes | Primary headline; max 255 characters |
| Slug | Text | Auto-generated | URL-friendly identifier; unique across all posts. See Section 12.5.1. |
| Content | Rich Text / HTML | No | Main body content with formatting support |
| Excerpt | Text | No | Manual or auto-generated summary; max 500 characters |
| Featured Image | Media Reference | No | Primary image for post representation |
| Author | User Reference | Auto-assigned | Defaults to current user; editable by Editors and Administrators |
| Post Type | Enum / Reference | Yes | Determines taxonomy availability and labeling |
| Categories | Taxonomy Reference (many) | No | Hierarchical classification |
| Tags | Taxonomy Reference (many) | No | Flat keyword classification |
| Custom Taxonomies | Taxonomy Reference (many) | No | Post-type-specific classifications |
| Publish Date | DateTime | Auto-set | Date and time of publication |
| Status | Enum | Yes | Draft, Pending Review, Published, Archived. See Section 12.5.2. |
| Visibility | Enum | Yes | Public, Password Protected, Private |
| Password | Text | Conditional | Required if visibility is Password Protected |
| Created At | DateTime | Auto | Record creation timestamp |
| Updated At | DateTime | Auto | Last modification timestamp |

##### 12.2.3 Featured Image

- A single featured image may be assigned per post.
- The image is selected from the media library.
- If the featured image is deleted from the media library, the post retains a broken reference until reassigned.
- The featured image may be used as the Open Graph image if no custom OG image is specified in the SEO panel.

##### 12.2.4 Excerpt

- Editors may provide a manual excerpt.
- If no manual excerpt is provided, the system may generate one from the first 160 characters of the content body.
- Excerpts are used in listing views, RSS feeds, and meta descriptions as fallback.

##### 12.2.5 Categories

- Posts may be assigned to one or more categories.
- Categories are hierarchical; a post may belong to a parent category, a child category, or both.
- Category assignment is optional unless enforced by post type configuration.

##### 12.2.6 Tags

- Posts may be assigned to one or more tags.
- Tags are flat and have no parent/child relationship.
- Tags support auto-creation: typing a non-existent tag name and confirming creates the tag immediately.
- Duplicate tag names are prevented at creation; case-insensitive matching is applied.

##### 12.2.7 Custom Taxonomies

- Custom taxonomies may be defined and associated with specific post types.
- Assignment behavior mirrors categories or tags depending on the taxonomy's structural definition.

##### 12.2.8 Author Assignment

- The author defaults to the user creating the post.
- Editors and Administrators may reassign authorship to any active user.
- Authors may not change the author field on their own posts.

##### 12.2.9 Publish Date

- The publish date defaults to the current date and time upon creation.
- Editors may backdate or future-date posts.
- Future-dated posts with status "Published" remain unpublished until the specified date/time is reached.
- The publish date is independent of the "Created At" timestamp.

##### 12.2.10 Visibility

- **Public** — Visible to all visitors.
- **Password Protected** — Requires a visitor-supplied password to view content.
- **Private** — Visible only to authenticated users with content viewing privileges.

##### 12.2.11 Filters

The post listing supports the following filters:

- Status (single or multiple)
- Post Type
- Category
- Tag
- Author
- Date Range (created or published)
- Visibility

##### 12.2.12 Search

- Full-text search across title, slug, excerpt, and content body.
- Search results are ordered by relevance then publish date descending.
- Search is scoped to the current post type when viewing a custom post type listing.

##### 12.2.13 Sorting

- Default sort: Publish date descending.
- Available sort columns: Title, Author, Status, Publish Date, Created At, Updated At.
- Sort direction toggles between ascending and descending.

##### 12.2.14 Bulk Actions

- Change Status (Draft, Pending, Published, Archived)
- Change Author (Editors and Administrators only)
- Assign Categories
- Assign Tags
- Delete (soft delete with confirmation)
- Restore (from archived/trashed state)

##### 12.2.15 Duplicate

- Any post may be duplicated.
- Duplication creates a new post with "(Copy)" appended to the title.
- The slug is regenerated per Section 12.5.1.
- Status is set to Draft.
- Taxonomies and featured image are copied.
- SEO fields are copied.
- Publish date is reset to current time.

##### 12.2.16 Restore

- Archived or soft-deleted posts may be restored to Draft status.
- Restoration preserves the original slug unless a conflict exists, in which case a numeric suffix is appended per Section 12.5.1.

##### 12.2.17 Delete Behavior

- Posts are soft-deleted by default.
- Soft-deleted posts are excluded from listings unless the "Trashed" filter is applied.
- Hard deletion is available only to Administrators and requires explicit confirmation.
- Hard deletion of a post removes its SEO metadata and taxonomy associations.
- Hard deletion does not delete associated media from the library.

##### 12.2.18 URL Generation

- Post URLs follow the permalink structure defined in System Configuration > Permalinks.
- Default structure: `/{post-type-slug}/{slug}/`
- If the post type slug is omitted from the structure, URLs use `/{slug}/`.
- URL generation must account for slug uniqueness across the entire URL namespace.

##### 12.2.19 SEO Integration

- Every post editor includes an embedded SEO panel as a collapsible section or tab.
- SEO fields and behavior are defined in Section 12.5.3.
- SEO values are saved atomically with the post.
- If SEO fields are left empty, values inherit from SEO Defaults (Section 16.5).

---

#### 12.3 Pages

##### 12.3.1 Purpose

Pages represent static, non-chronological content. They support hierarchical organization, template selection, and navigation readiness. Pages are ideal for "About," "Contact," and structural site content.

##### 12.3.2 Fields

Pages inherit all shared content behaviors and include the following specific fields:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Title | Text | Yes | Primary headline; max 255 characters |
| Slug | Text | Auto-generated | URL-friendly identifier. See Section 12.5.1. |
| Content | Rich Text / HTML | No | Main body content |
| Parent | Page Reference | No | Hierarchical parent page |
| Template | Dropdown | No | Frontend presentation variant |
| Order | Integer | Auto | Manual sort order among siblings |
| Show in Navigation | Boolean | No | Signals inclusion intent for frontend menus |
| Status | Enum | Yes | Draft, Pending Review, Published, Archived. See Section 12.5.2. |
| Created At | DateTime | Auto | Record creation timestamp |
| Updated At | DateTime | Auto | Last modification timestamp |

##### 12.3.3 Parent/Child Hierarchy

- Pages may be organized in an unlimited parent/child tree.
- A page may have zero or one parent.
- A page may have zero or more children.
- Circular references are prohibited (a page cannot be its own ancestor).
- Child page URLs may inherit the parent slug prefix based on permalink settings.

##### 12.3.4 Tree View

- The "Page Hierarchy" navigation item displays a collapsible tree view of all pages.
- Tree view supports drag-and-drop reordering of sibling pages.
- Tree view displays page status indicators (color-coded) and template icons.
- Clicking a page in the tree navigates to the edit form.

##### 12.3.5 Templates

- Pages may optionally select from available page templates.
- Templates are registered at the system level and define frontend presentation variants.
- The template field is a dropdown populated from the template registry.
- If no template is selected, a "Default" template is assumed.

##### 12.3.6 Ordering

- Pages within the same parent are ordered manually via the tree view.
- Order values are integers; lower values appear first.
- Reordering updates sibling order values automatically.

##### 12.3.7 Slug Uniqueness

- Page slugs must be unique across the entire page namespace.
- If a child page shares a slug with an unrelated parent page, the full path (parent/child) distinguishes them.
- Slug generation and sanitization rules match post slug behavior (Section 12.5.1).

##### 12.3.8 Navigation Readiness

- Pages include a "Show in Navigation" boolean flag.
- When enabled, the page is flagged as available for frontend navigation menus.
- Navigation order respects the page hierarchy order.
- This flag does not affect URL accessibility; it only signals navigation inclusion intent.

##### 12.3.9 SEO Integration

- Page editors include the same embedded SEO panel as posts.
- SEO behavior, inheritance, and validation are identical to posts (Section 12.5.3).
- Schema type defaults to "WebPage" unless overridden.

---

#### 12.4 Custom Post Types

##### 12.4.1 Dynamic Registration Concept

Administrators may define custom post types through the UI without code deployment. Each custom post type generates a dedicated submenu under Content > Posts > Custom Post Types.

##### 12.4.2 Labels

- **Plural Name** — Displayed in navigation and listing headers (e.g., "Case Studies").
- **Singular Name** — Displayed in form headers and action buttons (e.g., "Case Study").
- **Menu Icon** — Optional icon identifier for navigation display.

##### 12.4.3 Slug

- Each custom post type defines a URL slug prefix.
- The slug is used in permalink generation for posts of this type.
- Slug must be unique and cannot conflict with reserved system routes.

##### 12.4.4 Taxonomy Assignment

- Custom post types may be associated with categories, tags, and custom taxonomies.
- Taxonomy associations are configured at the post type definition level.
- Only associated taxonomies appear in the post editor for that type.

##### 12.4.5 Supported Fields

Custom post types inherit all standard post fields (Section 12.2.2) unless explicitly disabled in configuration.

##### 12.4.6 Publishing Behavior

- Publishing behavior matches standard posts: draft, pending, published, archived (Section 12.5.2).
- Visibility options are identical.
- Author assignment rules are identical.

##### 12.4.7 SEO Behavior

- SEO panel is available for all custom post types.
- Default schema type may be configured per post type (e.g., "NewsArticle" for News type).
- Inheritance from SEO Defaults applies when fields are left empty (Section 12.5.3).

---

#### 12.5 Shared Content Behaviors

##### 12.5.1 Slug & URL Management

- Slugs are auto-generated from the title upon initial save.
- Slugs are sanitized to lowercase alphanumeric characters with hyphens as word separators.
- Slugs must be unique across all posts regardless of post type, and across all pages regardless of hierarchy.
- Manual slug editing is permitted before first publication. After publication, slug changes require explicit confirmation to prevent broken external links.
- Slug conflict resolution appends a numeric suffix (e.g., `sample-post-2`).
- URL generation follows the permalink structure defined in System Configuration > Permalinks.

##### 12.5.2 Content Lifecycle Workflow

All content types (Posts, Pages, Custom Post Types) share the following status model and editorial transitions:

**Statuses:**

- **Draft** — Content is saved but not visible on the frontend. Only the author and privileged roles can view.
- **Pending Review** — Content is submitted by an Author or Contributor and awaits Editor or Administrator approval.
- **Published** — Content is live and publicly accessible (subject to visibility settings and publish date).
- **Archived** — Content is withdrawn from public view but retained in the system. Can be restored.

**Editorial Flow Summary:**

| Action | Draft | Pending | Published | Archived | Trashed |
|--------|-------|---------|-----------|----------|---------|
| Edit | Yes | Yes | Yes | Yes | No |
| Publish | Yes | Yes | — | Yes | No |
| Unpublish | — | — | Yes | — | — |
| Archive | Yes | Yes | Yes | — | — |
| Soft Delete | Yes | Yes | Yes | Yes | — |
| Restore | — | — | — | — | Yes |
| Hard Delete | No | No | No | No | Yes |

**Workflow Rules:**

1. **Create** — User navigates to Content > Add New. System presents a blank form with all fields and the embedded SEO panel. User saves as Draft or submits for review (depending on role permissions).
2. **Draft** — Drafts are saved incrementally without validation of required fields beyond title. Auto-save is triggered at 60-second intervals during active editing. Drafts are visible only to the author and roles with "Edit Others" permission. Drafts appear in the Dashboard "Draft Summary" widget.
3. **Edit** — Any editable content item may be opened for modification. Editors with cross-content permissions may edit any item. Authors may edit only their own items. Contributors may edit only their own draft posts. Changes are tracked via Updated At timestamp.
4. **Publish** — Publishing requires status change to "Published." Users without publish permission (Authors, Contributors) cannot publish; status remains "Pending Review." Published content is immediately accessible subject to visibility and date constraints.
5. **Update** — Published content may be edited and saved without changing status. Updates to published content are effective immediately upon save. Slug changes on published content require explicit confirmation.
6. **Unpublish** — Published content may be reverted to Draft or Archived. Unpublishing removes the content from public access. Unpublishing does not delete the content or its SEO data.
7. **Archive** — Archiving sets status to "Archived" and removes content from public view. Archived content is excluded from default listings but retains its URL history. Archived content may be restored to Draft or Published.
8. **Delete** — Soft delete moves content to "Trashed" status. Trashed content is excluded from all listings except the Trashed filter view. Hard delete permanently removes the record and its associated SEO metadata and taxonomy links. Hard delete requires Administrator role and explicit confirmation.
9. **Restore** — Trashed or Archived content may be restored. Restoration defaults to Draft status. If the original slug is now occupied, a numeric suffix is appended per Section 12.5.1.

##### 12.5.3 Embedded SEO & Metadata Model

The SEO panel is embedded within the Post Editor, Page Editor, and Custom Post Type Editor as a dedicated tab or collapsible section.

**Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Meta Title | Text | No | HTML `<title>` content; max 60 characters recommended |
| Meta Description | Textarea | No | HTML `<meta name="description">` content; max 160 characters recommended |
| Focus Keyword | Text | No | Primary target keyword for editorial guidance; not rendered in HTML |
| Canonical URL | URL | No | Overrides default canonical URL if specified |
| Robots Meta | Multi-select | No | Options: index/noindex, follow/nofollow, noarchive, nosnippet |
| Open Graph Title | Text | No | og:title; falls back to Meta Title then Content Title |
| Open Graph Description | Textarea | No | og:description; falls back to Meta Description then Excerpt |
| Open Graph Image | Media Reference | No | og:image; falls back to Featured Image then SEO Defaults OG Image |
| Schema Type | Dropdown | No | Structured data type; see Supported Schema Types below |

**Supported Schema Types:**

- Article
- BlogPosting
- NewsArticle
- WebPage
- AboutPage
- ContactPage
- FAQPage
- Custom (free-text entry for advanced use)

**Validation Expectations:**

- Meta Title: maximum 255 characters; recommended 60 characters. Visual indicator when exceeding 60.
- Meta Description: maximum 500 characters; recommended 160 characters. Visual indicator when exceeding 160.
- Canonical URL: must be a valid absolute URL if provided.
- Open Graph Image: must reference an existing media item with a valid image file.
- Focus Keyword: informational only; no validation beyond max 100 characters.

**Inheritance from SEO Defaults:**

When an SEO field is left empty at the content level, the system resolves the value using the following priority:

1. Content-level value (if explicitly set)
2. SEO Defaults configuration (Section 16.5)
3. Dynamic fallback (e.g., Content Title for Meta Title, Excerpt for Meta Description)

**Preview Behavior:**

- The SEO panel includes a live preview rendering a simulated search engine result snippet.
- Preview displays: title, URL, and description.
- Preview updates in real-time as SEO fields are modified.
- Open Graph preview is optional and may be displayed as a secondary preview card.

**Content-Specific Overrides:**

- SEO values are stored per content item and override defaults completely when set.
- Clearing an SEO field restores inheritance behavior; the field is not stored as an empty string.
- Robots Meta defaults to the SEO Defaults robots configuration if not explicitly set.

---

### 13. Taxonomies

#### 13.1 Categories

##### 13.1.1 Hierarchical Structure

Categories are organized in a tree structure supporting unlimited nesting depth. Each category may have zero or one parent and zero or more children.

##### 13.1.2 Parent Categories

- When creating or editing a category, a parent category may be selected from existing categories.
- A category cannot be its own parent or descendant (circular reference prevention).
- Changing a category's parent updates the hierarchy for all descendant categories.

##### 13.1.3 Unlimited Nesting

- The system does not impose an artificial depth limit on category nesting.
- UI representation may collapse deep hierarchies with expand/collapse controls.

##### 13.1.4 Slug Uniqueness

- Category slugs must be unique across the entire category namespace.
- Slug generation follows the same sanitization rules as content slugs (Section 12.5.1).
- Slug conflicts are resolved with numeric suffixes.

##### 13.1.5 Content Assignment

- Categories may be assigned to posts and custom post types.
- A category with assigned content may not be deleted until the content is reassigned or the category is empty.
- Deleting a parent category does not cascade to children; children become root-level categories.

#### 13.2 Tags

##### 13.2.1 Flat Structure

Tags have no hierarchy. All tags exist at the same level.

##### 13.2.2 Auto-Creation Behavior

- In the post editor, typing a tag name that does not exist presents an option to create the tag.
- Tag creation requires no additional form; it is created inline upon user confirmation.
- Auto-created tags use the typed name as both name and slug (sanitized).

##### 13.2.3 Duplicate Handling

- Tag names are compared case-insensitively for duplicates.
- If a tag "Laravel" exists, typing "laravel" or "LARAVEL" matches the existing tag.
- Slug duplicates are resolved with numeric suffixes.

#### 13.3 Custom Taxonomies

##### 13.3.1 Hierarchical vs Flat

- Custom taxonomies may be defined as either hierarchical (category-like) or flat (tag-like).
- The structural type is immutable after creation to prevent data integrity issues.

##### 13.3.2 Association with Post Types

- Custom taxonomies must be associated with one or more post types.
- Only associated post types display the taxonomy in their editor.
- A taxonomy may be associated with standard posts and multiple custom post types simultaneously.

##### 13.3.3 Slug Management

- Custom taxonomy slugs must be unique across all taxonomies.
- Slug conflicts with reserved system terms are prohibited.

##### 13.3.4 Future Extensibility

- The taxonomy system is designed to support additional metadata fields per term in future releases (e.g., description, image, custom fields).
- The current MVP stores only name, slug, parent (if hierarchical), and association metadata.

---

### 14. Digital Asset Management

#### 14.1 Upload Workflow

- Users may upload files via drag-and-drop or file picker.
- Uploads are processed through the "Upload Media" navigation item or inline within content editors.
- Supported upload methods: single file, multiple files, and bulk upload.
- Upload progress is displayed visually.
- Upon completion, files appear in the library with generated thumbnails (for images).

#### 14.2 Supported File Types

| Category | Extensions | Notes |
|----------|-----------|-------|
| Images | jpg, jpeg, png, gif, webp, svg | Thumbnails generated for raster images |
| Documents | pdf, doc, docx, txt | Stored as-is; no preview generation in MVP |
| Archives | zip | Stored as-is |

The system administrator may restrict allowed types via System Configuration > Media.

#### 14.3 Image Handling

- Uploaded images may be resized to predefined dimensions (thumbnail, medium, large) based on System Configuration > Media configuration.
- Original files are always preserved.
- Image dimensions and file size are stored as metadata.

#### 14.4 Metadata

Each media item stores the following metadata:

| Field | Description |
|-------|-------------|
| File Name | Original upload name |
| File Size | In bytes |
| MIME Type | Detected MIME type |
| Dimensions | Width x Height (images only) |
| Upload Date | Timestamp |
| Uploaded By | User reference |
| URL | Publicly accessible path |

#### 14.5 Alt Text

- Alt text is mandatory for images when used in content (enforced at insertion time, not upload time).
- Alt text max length: 255 characters.
- Alt text is stored per media item and may be updated globally.

#### 14.6 Title

- Title defaults to the file name without extension.
- Title is editable and used for internal identification and search.

#### 14.7 Caption

- Optional caption for display alongside the media item in content.
- Max length: 500 characters.

#### 14.8 Description

- Optional long-form description for internal reference.
- Not rendered on the frontend by default.
- Max length: 2000 characters.

#### 14.9 Folder Organization

- Media items may be organized into folders.
- Folders support nesting (unlimited depth).
- Folder names must be unique within their parent folder.
- Moving items between folders is supported via bulk action or drag-and-drop.

#### 14.10 Grid/List View

- The library supports Grid view (thumbnail-centric) and List view (detail-centric).
- View preference is persisted per user session.

#### 14.11 Search

- Search across media title, file name, alt text, caption, and description.
- Search is scoped to the current folder unless "Search All Folders" is selected.

#### 14.12 Filtering

- Filter by file type (image, document, archive)
- Filter by upload date range
- Filter by uploader

#### 14.13 Reuse Across Content

- Any media item may be referenced by unlimited posts, pages, settings, and SEO panels.
- Media items are referenced by ID, not by URL, to support path changes.
- Changing a media item's folder does not break existing references.

#### 14.14 Delete Rules

- Media items may be deleted only if not referenced by any content.
- If a media item is in use, deletion is blocked and a reference list is displayed.
- Administrators may force-delete media items, breaking references and leaving empty placeholders in content.
- Deleting a folder requires it to be empty or the user must confirm recursive deletion of all contents.

#### 14.15 Media Collections (Future-Ready)

The following collections are structurally prepared for future scalability:

- Images
- Documents
- SEO Assets
- User Avatars

---

### 15. Identity & Access Management

#### 15.1 User Lifecycle

| Stage | Description |
|-------|-------------|
| Invitation | Administrator creates user account with role assignment |
| Activation | User sets password via secure invitation link |
| Active | User may log in and perform role-authorized actions |
| Suspended | User cannot log in; existing content remains attributed |
| Deleted | Soft-deleted; user record retained for audit; content authorship preserved |

#### 15.2 User Profile Fields

| Field | Required | Description |
|-------|----------|-------------|
| Full Name | Yes | Display name across the CMS |
| Email | Yes | Unique; used for authentication |
| Username | Yes | Unique; used for URL generation and attribution |
| Password | Yes | Minimum 12 characters; complexity enforced |
| Avatar | No | Media reference; optional profile image |
| Biography | No | Short bio; max 1000 characters |
| Role | Yes | Assigned at creation; editable by Administrators |
| Account Status | Auto | Active, Suspended, Pending Activation |

#### 15.3 Password Management

- Passwords must be minimum 12 characters with at least one uppercase, one lowercase, one number, and one special character.
- Password reset is available via email token.
- Password history and forced password rotation are recognized as enterprise security enhancements and are reserved for a future security roadmap.

#### 15.4 Account Status

- **Active** — Full role-based access.
- **Suspended** — Login blocked; no data loss.
- **Pending Activation** — Account created but password not yet set.

#### 15.5 Role Assignment

- Only Administrators may assign or change roles.
- A user must have exactly one role.
- Role changes take effect immediately upon save.

#### 15.6 Permission Inheritance

- Permissions are defined at the role level.
- Users inherit all permissions associated with their role.
- No per-user permission overrides are supported in the MVP.

#### 15.7 Access Restrictions

- Suspended users are denied authentication.
- Deleted users cannot authenticate; their content remains in the system with attribution.
- IP-based restrictions are out of scope for MVP.

#### 15.8 Audit Expectations

- User creation, modification, role changes, and status changes are logged with timestamp and actor.
- Login attempts (successful and failed) are logged.
- Content changes are attributed to the acting user.
- Full audit trails (revisions and diff tracking) are excluded from MVP but the system must log basic change events.

---

### 16. System Configuration

#### 16.1 Configuration Ownership

| Setting Group | Editable By |
| ------------- | ----------- |
| General | Administrator |
| Reading | Administrator |
| Permalinks | Administrator |
| Media | Administrator |
| SEO Defaults | Administrator |
| Email | Administrator |

#### 16.2 General

| Setting | Type | Description |
|---------|------|-------------|
| Site Title | Text | Primary site name; max 255 characters |
| Tagline | Text | Short site description; max 255 characters |
| Timezone | Dropdown | System-wide timezone for date display |
| Date Format | Dropdown | Preferred date display format |
| Time Format | Dropdown | Preferred time display format (12h / 24h) |

#### 16.3 Reading

| Setting | Type | Description |
|---------|------|-------------|
| Homepage | Page Reference | Select which page serves as the site homepage |
| Posts Page | Page Reference | Select which page displays the post listing |
| Posts Per Page | Number | Default number of posts per listing page; min 1, max 100 |

#### 16.4 Permalinks

| Setting | Type | Description |
|---------|------|-------------|
| URL Structure | Pattern | Defines URL pattern for posts (e.g., `/{post-type}/{slug}/`, `/{year}/{month}/{slug}/`) |
| Page URL Structure | Pattern | Defines URL pattern for pages (e.g., `/{parent-slug}/{slug}/`, `/{slug}/`) |
| Slug Generation | Toggle | Auto-generate slugs from title on save |
| Conflict Resolution | Enum | Behavior when slug conflicts: append number, block save, or prompt user |

#### 16.5 Media

| Setting | Type | Description |
|---------|------|-------------|
| Thumbnail Width | Number | Max width for thumbnail size |
| Thumbnail Height | Number | Max height for thumbnail size |
| Medium Width | Number | Max width for medium size |
| Medium Height | Number | Max height for medium size |
| Large Width | Number | Max width for large size |
| Large Height | Number | Max height for large size |
| Upload Max File Size | Number | Maximum upload size in MB |
| Default Upload Folder | Folder Reference | Folder where uploads are placed by default |
| Allowed File Types | Multi-select | Permitted MIME types or extensions |

#### 16.6 SEO Defaults

| Setting | Type | Description |
|---------|------|-------------|
| Default Meta Title Pattern | Text | Pattern using variables (e.g., `{title} | {site_title}`) |
| Default Meta Description | Textarea | Fallback meta description when content-level is empty |
| Default OG Image | Media Reference | Fallback Open Graph image |
| Default Schema Type | Dropdown | Fallback schema type (WebPage recommended) |
| Default Robots | Multi-select | Default robots directive (e.g., index, follow) |

#### 16.7 Email

| Setting | Type | Description |
|---------|------|-------------|
| SMTP Host | Text | Mail server hostname |
| SMTP Port | Number | Mail server port |
| SMTP Encryption | Dropdown | TLS, SSL, or None |
| SMTP Username | Text | Authentication username |
| SMTP Password | Password | Authentication password (masked) |
| Sender Name | Text | Default "From" name |
| Sender Address | Email | Default "From" address |
| Test Email Recipient | Email | Address for test email delivery |
| Test Email Button | Action | Sends a test email to verify configuration |

**Test Email Behavior:**
- Clicking "Send Test Email" dispatches a test message to the configured recipient.
- Success or failure is reported inline with the specific error if applicable.
- Test emails include a timestamp and server identification header for debugging.

---

## Part IV — System Design

### 17. Domain Data Model

#### 17.1 Entities

**Users**
- Represents authenticated system users.
- Attributes: identifier, full name, email, username, password hash, avatar reference, biography, role, status, timestamps.

**Posts**
- Represents editorial content entries.
- Attributes: identifier, title, slug, content body, excerpt, featured image reference, author reference, post type reference, publish date, status, visibility, password, timestamps.

**Pages**
- Represents static hierarchical content.
- Attributes: identifier, title, slug, content body, parent reference, template, order, navigation flag, status, timestamps.

**Post Types**
- Represents content type definitions.
- Attributes: identifier, singular name, plural name, slug, icon, supported fields, taxonomy associations, default schema type.

**Categories**
- Represents hierarchical taxonomy terms.
- Attributes: identifier, name, slug, parent reference, description, timestamps.

**Tags**
- Represents flat taxonomy terms.
- Attributes: identifier, name, slug, description, timestamps.

**Custom Taxonomies**
- Represents user-defined taxonomy structures.
- Attributes: identifier, name, slug, type (hierarchical/flat), post type associations, timestamps.

**Media**
- Represents uploaded files and their metadata.
- Attributes: identifier, file name, file path, file size, MIME type, dimensions, folder reference, uploader reference, title, alt text, caption, description, timestamps.

**SEO Metadata**
- Represents search optimization data attached to content.
- Attributes: identifier, content reference (polymorphic), meta title, meta description, focus keyword, canonical URL, robots meta, OG title, OG description, OG image reference, schema type, timestamps.

**Settings**
- Represents system configuration values.
- Attributes: group, key, value, type, timestamps.

#### 17.2 Relationships

- **User to Posts** — One-to-Many. A user may author many posts. A post has one author.
- **User to Pages** — One-to-Many. A user may author many pages. A page has one author.
- **User to Media** — One-to-Many. A user may upload many media items. A media item has one uploader.
- **Post to Categories** — Many-to-Many. A post may belong to many categories; a category may contain many posts.
- **Post to Tags** — Many-to-Many. A post may have many tags; a tag may belong to many posts.
- **Post to Custom Taxonomies** — Many-to-Many through the custom taxonomy term entity.
- **Post to Post Type** — Many-to-One. Many posts belong to one post type.
- **Page to Page (Parent/Child)** — One-to-Many (self-referencing). A page may have one parent and many children.
- **Post to SEO Metadata** — One-to-One (polymorphic). Each post has one SEO metadata record.
- **Page to SEO Metadata** — One-to-One (polymorphic). Each page has one SEO metadata record.
- **Media to Folders** — Many-to-One. Many media items may reside in one folder.
- **Folder to Folder (Parent/Child)** — One-to-Many (self-referencing). A folder may have one parent and many children.
- **Category to Category (Parent/Child)** — One-to-Many (self-referencing). A category may have one parent and many children.
- **Custom Taxonomy Term to Custom Taxonomy** — Many-to-One. Many terms belong to one taxonomy definition.

---

### 18. Administrative Interface Standards

#### 18.1 Filament-Inspired Layout

- The admin interface adopts Filament 5's sidebar navigation, top bar, and content area layout.
- The sidebar is collapsible on desktop and hidden by default on mobile (hamburger toggle).
- The top bar contains global search, user menu, and notification area.

#### 18.2 Form Tabs

- Content editing forms are organized into logical tabs:
  - **Content** — Primary fields (title, slug, body, excerpt, featured image)
  - **Taxonomies** — Category, tag, and custom taxonomy assignment
  - **SEO** — Embedded SEO panel (Section 12.5.3)
  - **Settings** — Status, visibility, publish date, author, template (pages)
- Tabs are persistent across saves; the last active tab is remembered per user.

#### 18.3 Section Grouping

- Related fields are grouped into card-like sections with clear headings.
- Section headings use sentence case.
- Helper text is provided for complex fields (e.g., slug behavior, canonical URL purpose).

#### 18.4 Table Behavior

- Listings use data tables with sortable columns.
- Default pagination: 25 items per page; options for 10, 25, 50, 100.
- Row actions: Edit, View (if applicable), Duplicate, Delete.
- Bulk action bar appears when one or more rows are selected.
- Empty states display contextual illustrations and primary action buttons.

#### 18.5 Filters

- Filters are presented as a collapsible panel above the table.
- Active filters are displayed as removable chips.
- Filter state persists per user per listing.
- "Clear All" resets all active filters.

#### 18.6 Bulk Actions

- Bulk actions are available via a dropdown in the table header when rows are selected.
- Destructive actions (delete, archive) require confirmation.
- Bulk action results are reported via toast notification.

#### 18.7 Search

- Global search is accessible from the top bar and searches across posts, pages, users, and media.
- Listing search is scoped to the current resource and searches title, slug, and relevant metadata.
- Search debounces at 300ms to prevent excessive queries.

#### 18.8 Responsive Behavior

- The interface is fully responsive from 320px to 4K displays.
- Sidebar collapses to overlay on viewports below 1024px.
- Tables convert to card-based layouts on viewports below 768px.
- Forms stack vertically on mobile; tabs may convert to an accordion pattern.

#### 18.9 Accessibility

- All interactive elements are keyboard-navigable.
- Form fields have associated labels and ARIA attributes.
- Color contrast meets WCAG 2.1 AA standards.
- Focus indicators are visible on all interactive elements.
- Screen reader announcements for dynamic content updates (toasts, modals).

#### 18.10 Consistency Rules

- Primary actions use the brand color (solid button).
- Secondary actions use outline or ghost buttons.
- Destructive actions use red accent color.
- Icons are used consistently; no icon is used without a text label unless universally understood (e.g., search magnifying glass).
- Date formats respect the user's configured preference.
- All forms display validation errors inline with the offending field.
- Success and error messages use consistent toast positioning (top-right) and duration (5 seconds).

---

### 19. Non-Functional Requirements

#### 19.1 Performance

- Page load time for the Dashboard must not exceed 2 seconds under normal load.
- Content listing queries must return results within 500ms for datasets up to 100,000 records.
- Media library thumbnail generation must not block the upload response; processing occurs asynchronously.
- Search queries must return results within 1 second.

#### 19.2 Security

- All passwords are hashed using bcrypt with a minimum cost factor of 12.
- All form inputs are sanitized to prevent XSS and injection attacks.
- CSRF tokens are required for all state-changing requests.
- Session timeout occurs after 30 minutes of inactivity.
- Failed login attempts are rate-limited: 5 attempts per 15 minutes per IP.
- File uploads are validated against allowed MIME types and scanned for malicious content where feasible.
- Direct file URLs must not expose server directory structure.

#### 19.3 Authorization

- All routes and actions enforce role-based access control.
- Ownership checks are performed before edit and delete operations.
- Privilege escalation is prevented; users cannot modify their own role.
- API endpoints (if any future addition) must independently validate authorization tokens.

#### 19.4 Validation

- All user inputs are validated server-side; client-side validation is supplementary.
- Validation errors are specific and actionable.
- Unique constraints are enforced at the database level with user-friendly error messages.
- Slug uniqueness is checked across the entire applicable namespace.

#### 19.5 Scalability

- The system architecture supports horizontal scaling of the application layer.
- Database queries must be optimized with appropriate indexing strategies.
- Media storage is abstracted to support cloud object storage migration without code changes.
- Taxonomy hierarchies must perform efficiently at 10+ levels of nesting.

#### 19.6 Maintainability

- Code must follow Laravel and Filament conventions and best practices.
- Business logic is separated from presentation logic.
- Configuration is externalized; no hardcoded values in application logic.
- Feature modules are decoupled to enable independent testing and future extension.

#### 19.7 Backup Readiness

- The system must be compatible with standard MySQL backup tools (mysqldump, Percona XtraBackup).
- Media files must be stored in a location separable from application code for independent backup.
- Database and media backups should be restorable independently.

#### 19.8 Logging

- Application errors are logged with stack traces, request context, and user identification.
- Security events (login failures, permission violations, user status changes) are logged at warning level or higher.
- Logs are rotatable and must not contain sensitive data (passwords, tokens).

#### 19.9 Localization Readiness

- All user-facing strings are externalized to translation files.
- Date, time, and number formatting use locale-aware functions.
- RTL layout support is structurally prepared though not fully implemented in MVP.
- Language switching is not required in MVP but must not require structural refactoring to add.

#### 19.10 SEO Performance

- SEO metadata retrieval must not add more than 50ms to page response time.
- Schema output must be valid JSON-LD.
- Meta tag generation must support arbitrary tag addition without core modification.

#### 19.11 Browser Compatibility

- The admin interface supports the latest two versions of Chrome, Firefox, Safari, and Edge.
- Internet Explorer is not supported.
- Mobile browsers (iOS Safari, Chrome Mobile) must support core editing functions.

---

## Part V — Validation & Governance

### 20. Acceptance Criteria

#### 20.1 Dashboard

- [ ] Dashboard loads within 2 seconds.
- [ ] Overview widget displays current post count, page count, media count, and user count.
- [ ] Recent Content widget lists the last 10 edited items with accurate timestamps.
- [ ] Draft Summary displays drafts for the current user; Editors and Administrators see all pending drafts.
- [ ] Quick Actions buttons navigate to the correct creation forms.

#### 20.2 Posts

- [ ] Post can be created with title, content, and status.
- [ ] Slug auto-generation, uniqueness enforcement, and conflict resolution function per Section 12.5.1.
- [ ] Categories and tags can be assigned during creation and editing.
- [ ] Featured image can be selected from the media library.
- [ ] Excerpt can be entered manually or auto-populated.
- [ ] Author can be reassigned by Editors and Administrators.
- [ ] Publish date can be set to future or past dates.
- [ ] Status transitions follow the workflow defined in Section 12.5.2.
- [ ] SEO fields persist and inherit from SEO Defaults per Section 12.5.3.
- [ ] SEO preview renders accurately based on current field values.
- [ ] Post appears in listing with correct filters, sorting, and pagination.
- [ ] Bulk actions apply correctly to selected posts.
- [ ] Duplicate creates a new draft post with copied content.
- [ ] Soft-deleted post can be restored or permanently deleted by Administrators.

#### 20.3 Pages

- [ ] Page can be created with title, content, and parent selection.
- [ ] Parent/child hierarchy is displayed correctly in tree view.
- [ ] Circular parent references are prevented.
- [ ] Template selection is available and persists.
- [ ] Page order can be changed via drag-and-drop in tree view.
- [ ] Slug uniqueness is enforced across all pages per Section 12.5.1.
- [ ] Navigation readiness flag can be toggled.
- [ ] SEO panel functions identically to posts per Section 12.5.3.
- [ ] Page URL respects permalink settings and parent slug prefix.

#### 20.4 Custom Post Types

- [ ] Custom post type can be defined with name, slug, and taxonomy associations.
- [ ] Custom post type appears as a submenu under Content > Posts.
- [ ] Posts of custom type function identically to standard posts within their type constraints.
- [ ] SEO defaults can be configured per post type.

#### 20.5 Taxonomies

- [ ] Category can be created with name, slug, and parent.
- [ ] Category hierarchy supports unlimited nesting.
- [ ] Tag can be created inline during post editing.
- [ ] Tag duplicate detection is case-insensitive.
- [ ] Custom taxonomy can be defined as hierarchical or flat.
- [ ] Custom taxonomy appears only for associated post types.
- [ ] Taxonomy term deletion is blocked if content is assigned.

#### 20.6 Digital Asset Management

- [ ] File can be uploaded via drag-and-drop and file picker.
- [ ] Upload progress is displayed.
- [ ] Thumbnails are generated for supported image types.
- [ ] Metadata (title, alt, caption, description) can be edited.
- [ ] Folders can be created, nested, and renamed.
- [ ] Media can be moved between folders.
- [ ] Search returns accurate results across metadata fields.
- [ ] Delete is blocked for media in use; force delete available to Administrators.

#### 20.7 Identity & Access Management

- [ ] User can be created by Administrator with role assignment.
- [ ] Invitation email is sent (if email is configured).
- [ ] User can set password via secure link.
- [ ] Password complexity is enforced.
- [ ] Role assignment restricts access per the Authorization Matrix (Section 11.4).
- [ ] User status changes (suspend, activate) take effect immediately.
- [ ] User profile can be edited by the user and Administrators.

#### 20.8 System Configuration

- [ ] General settings save and reflect across the system.
- [ ] Reading settings affect homepage and posts page behavior.
- [ ] Permalink structure changes are reflected in URL generation.
- [ ] Media settings constrain upload size and generate resized images.
- [ ] SEO Defaults populate empty SEO fields on content.
- [ ] Email settings save and test email sends successfully.

#### 20.9 SEO & Metadata

- [ ] SEO fields save independently per content item.
- [ ] Empty SEO fields inherit from SEO Defaults per Section 12.5.3.
- [ ] Meta title and description show character count indicators.
- [ ] Canonical URL validates as proper URL format.
- [ ] OG image can be selected from media library.
- [ ] Schema type dropdown contains all specified options.
- [ ] SEO preview updates in real-time.

#### 20.10 Permissions

- [ ] Each role can only access permitted modules and actions per Section 11.4.
- [ ] Ownership checks prevent unauthorized edits.
- [ ] Bulk actions respect role permissions.
- [ ] Settings access is restricted to Administrators.

---

### 21. Future Roadmap

The following capabilities are identified for post-MVP development. They are listed in approximate priority order and are subject to product strategy review.

#### 21.1 Revisions and Versioning

- Automatic snapshot capture on each save.
- Side-by-side diff comparison.
- One-click restoration to any previous version.
- Revision attribution with user and timestamp.

#### 21.2 Comments System

- Frontend comment submission with moderation queue.
- Spam detection integration.
- Threaded replies and user mention notifications.

#### 21.3 Theme Management

- Theme installation and activation.
- Template hierarchy visualization.
- Live preview of content within active theme.

#### 21.4 Plugin Architecture

- Hook and filter system for extending core behavior.
- Plugin marketplace integration.
- Isolated plugin namespaces to prevent conflicts.

#### 21.5 Editorial Workflows

- Custom approval stages (e.g., Draft → Review → Legal → Publish).
- Role-based notification routing.
- Deadline tracking and escalation.

#### 21.6 Multilingual Support

- Content translation management.
- Language-specific URL prefixes.
- Translation assignment and progress tracking.

#### 21.7 Headless API

- RESTful API for content retrieval and management.
- GraphQL endpoint for flexible querying.
- API key management and rate limiting.

#### 21.8 Scheduled Publishing

- Calendar interface for scheduling posts.
- Recurring content scheduling.
- Timezone-aware publication.

#### 21.9 Analytics Dashboard

- Content performance metrics (views, engagement).
- Author productivity reports.
- SEO score tracking over time.

#### 21.10 Advanced Media

- In-browser image editing (crop, resize, filters).
- Video and audio file support with playback.
- Automatic WebP conversion and responsive image generation.

---

### 22. Appendices

#### Appendix A: Glossary

| Term | Definition |
|------|------------|
| CMS | Content Management System |
| MVP | Minimum Viable Product |
| SEO | Search Engine Optimization |
| OG | Open Graph |
| Slug | URL-friendly string identifier |
| Taxonomy | Classification system for content |
| Soft Delete | Logical deletion retaining data |
| Hard Delete | Permanent data removal |
| Schema | Structured data markup type |
| Permalink | Permanent URL structure |

#### Appendix B: Document References

| Reference | Description |
|-----------|-------------|
| Laravel 13 Documentation | Framework reference for architectural decisions |
| Filament 5 Documentation | Admin panel component and pattern reference |
| WCAG 2.1 | Web Content Accessibility Guidelines |
| MySQL 8.0 | Database platform specification |

---

*End of Document*

**Document Version:** 2.0  
**Status:** Ready for Development Handoff  
**Author:** Mohammed Jemal  
**Last Updated:** 2026-08-02
