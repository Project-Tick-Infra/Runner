# Project Tick

**Disciplined open-source engineering focused on long-term maintainability, architectural clarity, and reproducible infrastructure.**

Project Tick is not a loose federation of repositories.
It is a deliberately structured software ecosystem with clearly defined
boundaries, responsibilities, and governance.

## Mission

Project Tick exists to build software that:

* remains maintainable years later
* documents its architectural decisions
* treats infrastructure as part of the product
* prioritizes determinism and reproducibility
* enforces technical standards consistently

We reject velocity without discipline.

## Ecosystem Structure

Project Tick operates through a structured multi-organization model on GitHub.

Each organization has a defined role and trust boundary.

### 1. [Project Tick](https://gitlab.com/Project-Tick/core) (Core)

The primary organization.

Contains:

* ProjT Launcher
* Core repositories
* Actively developed software
* Entry point for contributors

This is the canonical development surface.

### 2. [Project Tick Infra](https://gitlab.com/Project-Tick/Infra)

Infrastructure and operational tooling.

Contains:

* CI/CD pipelines
* Automation systems
* Build orchestration
* Internal tooling
* Runner configuration

Infrastructure is versioned and reviewed like product code.

### 3. [Project Tick Libraries](https://gitlab.com/Project-Tick/Libraries)

Maintained libraries and controlled forks.

Contains:

* Long-term maintained forks
* Internal reusable libraries
* Stability-focused components

Libraries follow stricter compatibility guarantees.

### 4. [Project Tick Packages](https://gitlab.com/Project-Tick/Packages)

Packaging and distribution layer.

Contains:

* Packaging definitions
* Build manifests
* Reproducibility tooling
* Cross-distro support artifacts

Packaging is not an afterthought — it is part of design.

### 5. [Project Tick Vendored](https://gitlab.com/Project-Tick/Vendored)

Mirror-only organization.

Contains:

* Third-party code snapshots
* Controlled upstream mirrors
* Audit references

This organization is read-only and does not accept direct development
contributions.

It exists to ensure transparency and traceability of external dependencies.

### 6. [Project Tick Governance](https://gitlab.com/Project-Tick/Governance)

Policy and project-level documentation.

Contains:

* Contributor guidelines
* Review standards
* License documents
* Architectural decision records
* Long-term project charter

Governance is versioned, not implied.

## Flagship Project

### ProjT Launcher

A cross-platform launcher designed with:

* strict architectural boundaries
* explicit subsystem separation
* reproducible builds
* enforced CI validation
* long-term maintainability as a first-class requirement

Feature churn is not a goal. Structural integrity is.

## Core Engineering Principles

* Long-term maintenance over short-term momentum
* Architectural constraints are enforced, not optional
* CI/CD is mandatory for all active repositories
* Deterministic builds are required
* Decisions must be documented
* Dependencies must be auditable
* Upstream licenses and contributor intent are respected

If a change cannot justify its long-term cost, it does not land.

## Engineering Identity

Project Tick is defined by engineering discipline rather than feature velocity.

We operate under the assumption that:

* entropy is the default state of software
* architecture decays unless actively enforced
* infrastructure rots without ownership
* undocumented decisions become technical debt

Project Tick exists to resist that entropy.

We prefer:

* explicit constraints over implicit conventions
* slow, deliberate architectural evolution over reactive change
* deterministic systems over convenience abstractions
* documented trade-offs over silent compromises

Software is treated as infrastructure, not experimentation.

Changes are evaluated not only for correctness, but for their long-term
structural impact.

We do not optimize for hype cycles.
We optimize for longevity.

## Contribution Model

Project Tick is open, but disciplined.

* Merge requests required
* DCO / Signed-off-by mandatory
* Reviews evaluate architectural impact
* Backward compatibility is deliberate, not assumed
* Not all contributions will be accepted

The bar is technical, not social.

## Infrastructure Philosophy

Infrastructure is part of the product.

* CI failures block merges
* Multi-platform support is deliberate
* Packaging constraints influence design
* Reproducibility is verified continuously

Automation is not optional.

## Licensing

Each repository declares its license explicitly.

We prefer GPL-compatible licenses and expect contributors to respect:

* the legal framework
* the intent of upstream projects
* long-term compatibility obligations

## Scope

Project Tick builds fewer things — and maintains them properly.

We do not optimize for rapid feature expansion.
We optimize for stability, clarity, and sustainability.

## Canonical Source & Mirrors

Project Tick development occurs on GitHub.

Backup and archival strategies exist to ensure long-term independence from
any single hosting provider.

## Contact

Technical discussion occurs via issues and merge requests.

## TRADEMARK NOTICE

### Project Tick™ Trademark and Brand Policy

#### 1. Ownership of the Marks

Project Tick™, the Project Tick name, the Project Tick logo, and all related branding elements (collectively, the “Marks”) are trademarks of **Mehmet Samet Duman**.

All rights in the Marks are reserved.

This Trademark Policy governs use of the Marks independently of any open source license applicable to source code, documentation, or other materials.

---

#### 2. Relationship to Open Source Licenses

Each repository under the Project Tick namespace is licensed under its respective open source license (e.g., MIT, BSD, Apache-2.0, GPL, MS-PL, etc.).

These licenses govern the use, modification, and redistribution of source code only.

Open source licenses do **not** grant:

* Rights to use the Project Tick name
* Rights to use the Project Tick logo
* Rights to use Project Tick branding or trade dress
* Rights to imply affiliation, endorsement, sponsorship, or official status

Trademark rights are separate from copyright licenses.

---

#### 3. Permitted Uses

The following uses are generally permitted without prior written permission:

1. Factual references (e.g., “compatible with Project Tick”).
2. Accurate description of unmodified official releases.
3. Non-commercial commentary, research, educational, or journalistic references.

Permitted use must not:

* Create confusion regarding origin
* Suggest sponsorship, approval, or endorsement
* Present modified versions as official releases

---

#### 4. Modified and Redistributed Versions

Open source licenses permit modification and redistribution of source code.

However:

* Modified versions must not use the Project Tick name or logo in a way that implies official status.
* Forks must use distinct branding.
* Derivative works must clearly indicate that they are modified versions.
* Use of phrases such as “Official,” “Certified,” or similar terminology is prohibited without authorization.

Permissible example:

> “Based on Project Tick”

Impermissible example:

> “Official Project Tick Build”

Unless explicitly authorized.

---

#### 5. Commercial Use and SaaS Deployments

The Marks may not be used:

* As part of a product name
* As part of a company name
* As part of a commercial SaaS service name
* As part of a registered domain name
* In paid advertising or promotional materials

Without prior written permission.

Operating a commercial service using Project Tick source code does not grant the right to represent that service as an official Project Tick service.

Only services directly operated by Mehmet Samet Duman under the Project Tick identity may use the Marks in a commercial context.

---

#### 6. Official Releases

An “Official Project Tick Release” is a version that:

* Is built and distributed by the Project Tick maintainers
* Is published through official communication channels
* Is identified by official release tags or signatures

Modified builds, even if compliant with the applicable open source license, must not be presented as official releases.

---

#### 7. Logo Usage

The Project Tick logo is protected by copyright and trademark law.

If licensed under a Creative Commons license (e.g., CC BY-NC-ND), such license applies only within its stated scope and does not waive trademark protections.

The logo may not be:

* Modified
* Recolored
* Combined with other marks
* Used for commercial services
* Embedded in derivative branding

Without written authorization.

---

#### 8. Domain Names and Corporate Identifiers

The Marks may not be used:

* In domain names
* In social media handles
* In corporate names
* In registered business identifiers

Without explicit written permission.

---

#### 9. Prohibited Uses

The following uses are strictly prohibited:

* Implying endorsement or affiliation
* Misrepresenting unofficial builds as official
* Using the Marks in a misleading or deceptive manner
* Using the Marks in ways that damage reputation or goodwill
* Registering confusingly similar names

---

#### 10. Reservation of Rights

All rights not expressly granted in this policy are reserved.

Failure to enforce any provision of this policy shall not constitute a waiver of any rights.

Project Tick reserves the right to update this policy at any time.

---

#### 11. Contact

For permission requests or questions regarding trademark use:

[yongdohyun@projecttick.org](mailto:yongdohyun@projecttick.org)
