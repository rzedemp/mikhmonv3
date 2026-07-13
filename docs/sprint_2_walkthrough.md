# Sprint 2 Walkthrough — NOC Multi-Router Dashboard & Bandwidth Manager

In this sprint, we implemented a robust, asynchronous **NOC Multi-Router Dashboard** and a complete **Simple Queue QoS Manager** for Mikhmon v3, maintaining its zero-dependency, file-based architecture.

## 1. Key Accomplishments

### NOC Multi-Router Dashboard
*   **Asynchronous Processing:** Created `dashboard/noc_fetch.php` as a backend JSON provider that retrieves status metrics (CPU, Memory, Uptime, Active Users) for any router defined in `config.php` using the dual-mode API wrapper.
*   **Parallel Fetch UI:** Created `dashboard/noc.php` to display a responsive grid layout. The browser fetches router statuses asynchronously in parallel, ensuring that slow or unreachable routers do not block or lag the dashboard.
*   **Visual Status Indicators:** Built responsive cards with color-coded status pills (Online/Offline) and progress bars representing resource consumption.

### QoS / Simple Queue Manager
*   **Simple Queue CRUD Operations:**
    *   **Daftar Queue (`qos/queues.php`):** Displays simple queues in a searchable, responsive table with real-time text matching.
    *   **Tambah Queue (`qos/addqueue.php`):** Clean form with presets for speed limits (Upload/Download) and support for nesting under a parent queue. Includes strict CSRF token security.
    *   **Edit Queue (`qos/queuebyname.php`):** Form for editing simple queues, converting bytes limits back to readable units (e.g., `1000000` -> `1M`, `512000` -> `512k`).
    *   **Delete Queue (`process/removequeue.php`):** Secure deletion logic that redirects back to the queue list and writes to administrative audit logs.

### Routing & Integration
*   Integrated NOC routing in `admin.php` and QoS routing in `index.php`.
*   Added NOC Dashboard and Simple Queue items into `include/menu.php` sidebar.
*   Mapped live search inputs to the table filtering mechanism (`#filterTable` keyup handler) within `index.php`.

## 2. Technical Architecture & Security
*   **Zero-Dependency:** Standard PHP, JavaScript (jQuery), and HTML/CSS.
*   **Security Controls:**
    *   All POST data-modifying forms contain `csrf_field()` and are validated with `csrf_verify()`.
    *   Administrative actions log changes using `write_audit_log`.
    *   Parameters pass `.id` fields safely to prevent URL encoding or injection vulnerabilities.

---

## 3. Files Created & Modified

### Created Files
*   `dashboard/noc.php`
*   `dashboard/noc_fetch.php`
*   `qos/queues.php`
*   `qos/addqueue.php`
*   `qos/queuebyname.php`
*   `process/removequeue.php`

### Modified Files
*   `admin.php`
*   `index.php`
*   `include/menu.php`
*   `verson.txt`
*   `CHANGELOG.md`
*   `docs/ROADMAP.md`
*   `docs/devlog.md`
