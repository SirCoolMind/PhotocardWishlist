# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

Lightweight Vanilla PHP 8.3 + SQLite (`storage/database.sqlite` in WAL mode) + Alpine.js + Tailwind CSS (zero build step, runs natively on local Laragon and any standard PHP server).

## Users

- **Primary User (Collector / Admin):** Hafiz, a K-pop photocard collector with ADHD managing an active wishlist, pending group orders (GOs), collected cards, and a "no need" pass list across desktop and mobile. Needs immediate visual scanning and single-action status updates without spreadsheet friction.
- **Secondary Users (Public Read-Only Visitors):** Sellers, Group Order Managers (GOMs), and fellow traders viewing the hosted link to quickly verify which photocards Hafiz is still looking for (`Wishlist`), already has on the way (`Pending Order`), or has passed on (`No Need`), without any ability to modify data.

## Product Purpose

A fast, self-hostable photocard wishlist and order-status tracker that replaces a manual Google Sheet (`Uncollected Winteo Peace Hafiz`). Success means the collector can add a new photocard in under 5 seconds (via `Ctrl+V` screenshot paste, file upload, or image URL), move any card between statuses (`Wishlist`, `Pending Order`, `Collected`, `No Need`) in one click, share a clean read-only link with sellers/GOMs, and move or sync the entire database and image library between local and remote servers in a single `.zip` or `.sqlite` file.

## Positioning

Unlike generic cloud wishlist apps or heavy multi-table spreadsheets, this tracker pairs instant 1-click status transitions and `Ctrl+V` clipboard card capture with 100% single-bundle portability (`database.sqlite` + auto-compressed WebP card images exported/imported as one `.zip`), while serving as both a private admin workspace and a public read-only reference link for sellers and GOMs.

## Operating Context

- **Daily Collector Workflow:** Browsing X (Twitter), Mercari, Xianyu, Pocamarket, or Telegram group orders; taking screenshots of photocards or templates; pasting them directly (`Ctrl+V`) into the tracker; and flipping cards from `Wishlist` to `Pending Order` as claims are secured, or from `Pending Order` to `Collected` when mail arrives.
- **Sharing with Sellers / GOMs:** Sending the hosted server URL to sellers or GOMs so they can check current wishlist needs and avoid offering cards already on the `Pending Order` or `No Need` lists.
- **Local ↔ Server Portability:** Running locally via Laragon (`php-8.3.29`) or on a personal web server, using the built-in 1-click `.zip` / `.sqlite` export and import (Replace or Merge modes) to migrate or synchronize state across machines in seconds.

## Capabilities and Constraints

- **Four Core Statuses:** `uncollected` (Wishlist), `pending` (Pending Order / OTW), `collected` (Collected / Owned), and `no_need` (No Need / Pass List).
- **Card Data Model:**
  - Required: Photocard image (auto-converted to WebP in `storage/cards/`) and `era` (Album / Event / Merch category, with 1-click quick-pick chips).
  - Optional: `title` (POB store / card version label), `price` (in `RM`), and `notes` (seller, GOM batch, or tracking note).
  - **Future Expansion Constraint:** Data model and filtering must preserve room to expand beyond aespa Winter ("Winteo") to multiple idols and groups (`member` / `group` taxonomy layer) without breaking existing Era workflows.
- **Instant Status Switching & Undo:** Single-click status transition buttons on every card and batch multi-select mode, paired with an immediate Undo action.
- **Server Protection:** Public visitors see a clean read-only interface; creating, editing, moving, deleting, and syncing require unlocking Admin Mode via a configurable PIN (auto-unlocked on `localhost` with a 1-click Public Preview toggle).
- **Zero-Build Portability:** No Node/Composer build step required on the target server; SQLite snapshots use `VACUUM INTO` so exported `.sqlite` and `.zip` bundles are self-contained and free of WAL lock issues.

## Brand Commitments

- **Current Collection Identity:** Initialized as `Winteo Peace Photocard Wishlist` (tracking aespa Winter photocards), while remaining structurally adaptable to additional idols/groups.
- **Terminology:** Uses K-pop collecting terms naturally (`Era`, `POB`, `Wishlist`, `Pending Order`, `Collected`, `No Need`).

## Evidence on Hand

- **Seeded Collection Dataset (`storage/database.sqlite` & `storage/cards/`):** 43 real aespa Winter photocard images imported from the user's live Google Sheet (`Uncollected` tab, `gid=0`), spanning 19 eras (`Girls`, `Pink Christmas 2023`, `SMCU Palace`, `Better Things`, `MyWorld/Spicy`, `Drama`, `My First Page`, `Whiplash`, `Lemonade`, `Drama Unit`, `Everland`, `Savage`, `Japan 2026`, `Toreta`, `Dirty Work`, `Aespa Japan`, `Mamonde x Winter`, `Rich Man`, `D1 Concert Lemonade`) across `Wishlist` (17), `Pending Order` (20), and `No Need` (6).
- **Unimported Companion Sheet Tabs:** The source Google Spreadsheet also contains `Survey Price` (`gid=864752550`) and `Stats` (`gid=581598097`) tabs, which are not currently imported; no pricing or statistical history should be fabricated.

## Product Principles

1. **Photocard First, Chrome Second:** The photocard image and its current status/era must be recognizable in a split-second scan; UI chrome should never obscure the card face or caption.
2. **One-Tap State Transitions:** Moving a card between `Wishlist`, `Pending Order`, `Collected`, and `No Need` is the primary recurring action and must always be doable in a single click with an easy Undo.
3. **ADHD-Friendly Low Cognitive Load:** Keep filters flat, actions bounded, and forms minimal (only Image + Era required, with `Ctrl+V` paste and 1-click Era chips).
4. **Zero-Friction Portability:** Moving the tracker between local Laragon and a remote server must always work via a single `.zip` or `.sqlite` file without command-line database dumps.
5. **Safe to Share Publicly:** The default server view is a clean, trustworthy read-only reference for sellers and GOMs, while admin controls stay hidden behind the Admin PIN.

## Accessibility & Inclusion

- Designed explicitly for an ADHD collector workflow: high scannability, clear status differentiation, immediate visual feedback on state changes, and reversible actions (Undo toast on status moves).
