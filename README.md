# Project Tick™ Project Repository

> Sovereign, modular and release-oriented open source ecosystem.

Project Tick Project is the super-repository that coordinates all core components of the Project Tick ecosystem.

This repository does not contain primary source code.
It acts as:

* Version coordinator
* Release snapshot anchor
* Ecosystem integration layer
* Governance boundary

# Architecture Overview

The ecosystem is organized into domain-specific modules:

```
project-tick-project
│
├── bot/
│   └── refraction
│
├── forgewrapper/
│
├── frameworks/
│   └── symfony
│
├── gamemode/
│
├── infra/
│   ├── cgit
│   ├── images
│   └── renovate
│
├── javacheck/
│
├── libraries/
│   ├── bzip2
│   ├── cmark
│   ├── extra-cmake-modules
│   ├── json
│   ├── libqrencode
│   └── ptlibzippy
│
├── projt-launcher/
│
├── tomlplusplus/
│
└── website/
    └── portal
```

Each directory is a **Git submodule** pinned to a specific commit.

The super repository guarantees deterministic builds by fixing exact module revisions.

# 🧩 Domain Segmentation

## Core Application Layer

* `projt-launcher`
* `gamemode`
* `javacheck`
* `forgewrapper`

These represent runtime-facing components.

## Library Layer

Located under `libraries/`.

Includes:

* Third-party upstream mirrors
* Patched forks
* Internal maintained libraries
* `ptlibzippy` (Project Tick maintained fork)

All libraries are version-pinned and independently releasable.

## Infrastructure Layer

Located under `infra/`.

Includes:

* CI automation
* cgit instance configuration
* Container images
* Renovate automation rules

This layer exists to maintain ecosystem sovereignty.

## Framework & Integration Layer

* `frameworks/symfony`
* `bot/refraction`

Used for orchestration, automation, and service integrations.

## Web Layer

* `website/portal`

Public interface and user-facing services.

# Submodule Management

Clone with:

```bash
git clone --recurse-submodules <repo-url>
```

Initialize after clone:

```bash
git submodule update --init --recursive
```

Update workflow (release-controlled):

```bash
git submodule update --remote --merge
git commit -am "Update submodules"
```

All updates must pass module-level CI before super-repo pointer updates.

# 🏷 Versioning Model

* Modules version independently
* Super-repo tags represent ecosystem snapshots
* Super-repo does not override module semantic versioning

Example:

```
v0.0.5-1  → Ecosystem snapshot
```

# Design Principles

* Deterministic builds
* Infrastructure independence
* Modular governance
* Cross-platform support
* CI cost awareness
* Release discipline

# Contribution Model

Direct changes should be made inside the corresponding module repository.

This repository only tracks module revisions.

Do not submit feature PRs here unless they affect:

* Submodule updates
* Release orchestration
* Ecosystem coordination

---

# Why Submodules?

This repository intentionally uses submodules instead of a monorepo:

* Enables independent development cycles
* Allows strict version pinning
* Reduces cross-domain merge conflicts
* Maintains repository sovereignty boundaries

# Governance

Project Tick Project acts as the official integration authority.

Only reviewed module commits are elevated into ecosystem snapshots.
