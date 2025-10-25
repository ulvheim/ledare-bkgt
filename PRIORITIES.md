# Detailed Functional Specification: ledare.bkgt.se

**Project Goal:** Develop a WordPress-based website, **ledare.bkgt.se**, primarily aimed at digitizing and simplifying the administrative work for the BKGTS American Football club's staff and board members. The system should complement, not replace, functionalities provided by svenskalag.se.

**Platform:** WordPress (development of custom plugins and themes required).

**Language Requirement (Crucial):**
**All user-facing content, UI elements, generated documents, and data visible to end-users (coaches, team managers, board members) must be in Swedish.**

**Basic Structure:** The website's URL structure and visual navigation should mirror https://svenskalag.se/bkgt to ease user adoption, but the content and feature set must be unique to ledare.bkgt.se.

---

## 1. Authentication and Authorization (User Roles)

### 1.1. Role Matrix

| User Role (Swedish Term) | Access Level | Description and Specific Permissions |
| :--- | :--- | :--- |
| **Styrelsemedlem (Admin)** | Global Access | Full access to all features, settings, and data. Can view, edit, and export sensitive Performance Data. Full control over the Inventory System and DMS (Document Management System). |
| **Tränare (Coach)** | Team-Specific | Can view and manage data (e.g., notes) for their assigned team(s). Full access to the Inventory System and DMS related to their team. **Has access** to Performance Data for their team. |
| **Lagledare (Team Manager)** | Team-Specific (Limited) | Can view and manage data for their assigned team(s). Full access to the Inventory System and DMS related to their team. **DOES NOT have access** to Performance Data. |

### 1.2. Technical Detail

* **Login:** Users must log in to access any protected content or functionality.
* **Role Binding:** Each user must be bound to one or more **Teams** (e.g., Damlag/Women's Team, Herrlag/Men's Team, U17) for team-specific access to function correctly.

---

## 2. Features

### 2.1. Inventory System (Utrustningssystem)

A system to track every individual equipment item and its assignment.

| Field/Function (Swedish Term) | Data Type/Structure | Detailed Description |
| :--- | :--- | :--- |
| **Extensibility (Utökbarhet)** | Dynamic Fields | Board Members (Admin) must easily be able to add new custom fields (e.g., `Inköpspris`/`Purchase Price`, `Storlek`/`Size`) for specific Item Types without coding. |
| **Manufacturer (Tillverkare)** | ID (Int, 0000-9999) + String | A database table/list of unique manufacturers. Used to generate the Unique Identifier. |
| **Item Type (Artikeltyp)** | ID (Int, 0000-9999) + String | A database table/list of unique item types (e.g., `Hjälm`/`Helmet`, `Axelskydd`/`Shoulder Pads`). Used to generate the Unique Identifier. |
| **Unique Identifier (Unik Identifierare)** | String (Format: `####-####-#####`) | The primary key for each inventory item. Format must be: `[Manufacturer-ID (4 digits)]-[ItemType-ID (4 digits)]-[Sequential Number (5 digits)]`. The sequential number is unique per Manufacturer/Item Type combination, starting at `00001` up to `99999`. |
| **Assigned To (Tilldelad till)** | Entity Reference | **Must be assigned to one of the following mutually exclusive entities:** 1. The Club, 2. Specific Team (e.g., "Damlag"), 3. Individual (Reference to Player Dossier/User-ID). |
| **Storage Location (Lagringsplats)** | Multiple References | Must handle multiple predefined storage locations (e.g., `Klubbförråd`/`Club Storage`, `Containern, Tyresövallen`). |
| **Condition (Skick)** | Status Label | Must be one of the following predefined statuses: * **Normal**, * **Behöver reparation** (`Needs Repair`), * **Reparerad** (`Repaired`), * **Förlustanmäld** (`Reported Lost` - constitutes a warning flag), * **Skrotad** (`Scrapped` - must register date and reason). |
| **Metadata** | JSON Structure | A free-text field storing structured data (e.g., last inspection date, purchase date) for easy searching/export. |
| **Sticker Field (Klistermärke-fält)** | String/Auto-generated | Unique sticker code for labeling machine integration. Format: `[Unique ID]-[Sequential]` (e.g., `0001-0002-00001-A`). Used for physical labeling and replacement tracking. |
| **History (Historik)** | Transaction Log | Every change in **Assigned To** and **Condition** must be logged with a timestamp and the user who made the change. |

### 2.1.x. Item Assignment System (Utrustningstilldelningssystem)

A dedicated system for assigning inventory items to locations and people, separate from initial item creation.

#### Database Architecture
New `wp_bkgt_assignments` table structure:
```sql
CREATE TABLE wp_bkgt_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    assignee_type ENUM('location', 'team', 'user') NOT NULL,
    assignee_id INT NOT NULL,
    assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NOT NULL,
    unassigned_date DATETIME NULL,
    unassigned_by INT NULL,
    notes TEXT,
    FOREIGN KEY (item_id) REFERENCES wp_bkgt_inventory_items(id),
    UNIQUE KEY unique_active_assignment (item_id, unassigned_date)
);
```

| Function (Swedish Term) | Detailed Description | Technical Implementation |
| :--- | :--- | :--- |
| **Default Location (Standardplats)** | String | All new items are automatically assigned to "Förråd" (Storage) upon creation. |
| **Assignment Page (Tilldelningssida)** | Dedicated Admin Page | Separate page at `/wp-admin/admin.php?page=bkgt-item-assignments` accessible to authorized users (Tränare, Lagledare, Styrelsemedlem). |
| **Assignee Types (Mottagartyper)** | Entity Types | Items can be assigned to: 1. **Locations** (Förråd Siklöjevägen, Reparationskö Siklöjevägen), 2. **Teams** (Damlag, Herrlag, U17), 3. **Individuals** (Coaches, Team Managers, Board Members, Players). |
| **Two-Panel Interface (Tvåpanelsgränssnitt)** | User Interface | Split-screen design: Left panel for item search/selection, right panel for assignee search/selection with drag-and-drop functionality. |
| **Smart Search (Smart sökning)** | Dual Search with Typeahead | 1. **Item Search:** By Unique ID, item type, manufacturer, current status. 2. **Assignee Search:** By name, role, team affiliation with autocomplete. |
| **Bulk Assignment (Massutdelning)** | Batch Operations | Select multiple items and assign to one assignee simultaneously. Checkbox selection with "Assign All" functionality. |
| **Visual Assignment States (Visuella tilldelningstillstånd)** | Status Indicators | 🟢 Available (In storage), 🟡 Assigned (Currently with someone/team), 🔴 Needs Attention (Overdue, damaged, lost). |
| **Workflow Suggestions (Arbetsflödesförslag)** | Smart Defaults | Context-aware suggestions: Assign coach → suggest their teams; assign player → suggest appropriate equipment sizes. |
| **Assignment History (Tilldelningshistorik)** | Complete Audit Trail | Full log of all assignments with timestamps, assigning/unassigning users, and previous assignees. |
| **Conflict Resolution (Konflikthantering)** | Validation System | Prevent double-assignment with clear error messages. Allow reassignment with confirmation dialog and automatic logging. |
| **Automated Alerts (Automatiserade varningar)** | Notification System | Email alerts for overdue returns, missing equipment, items in repair queue over 30 days. |
| **Reporting Dashboard (Rapporteringsdashboard)** | Analytics | Items per assignee, overdue returns, assignment history reports, equipment utilization statistics. |

### 2.1.y. Enhanced "Lägg till ny utrustning" (Add New Equipment) Functionality

Streamlined item creation with intelligent defaults, validation, and workflow optimization.

| Enhancement (Förbättring) | Detailed Description | User Experience Impact |
| :--- | :--- | :--- |
| **AI-Powered Smart Suggestions (AI-drivna smarta förslag)** | Context-aware suggestions using pattern recognition from existing data (no external AI model costs - uses local algorithms). Learns from usage patterns to suggest manufacturers, sizes, and configurations. | Reduces form completion time by 60%, prevents data entry errors. |
| **Progressive Form Design (Progressiv formulärdesign)** | Four-step wizard interface: 1. Basic Info → 2. Specifications → 3. Assignment → 4. Review & Save. Each step validates before proceeding. | Eliminates form overwhelm, ensures data quality. |
| **Advanced Custom Fields Engine (Avancerad anpassade fältmotor)** | Conditional fields that appear based on item type selection. Field dependencies (e.g., "Size" only for items with variants). Dynamic validation rules per item type. | Reduces irrelevant fields by 70%, improves data accuracy. |
| **Batch Processing (Batchbearbetning)** | Add multiple identical or variant items simultaneously. Smart sequential ID generation with preview. Example: "5 helmets in sizes S,M,L" creates items with IDs 00001-00005. | Handles bulk purchases efficiently, reduces repetitive entry. |
| **Duplicate Prevention Intelligence (Dupliceringsförebyggande intelligens)** | Real-time duplicate detection with visual similarity suggestions. Shows existing similar items with photos and merge options. | Prevents inventory fragmentation, maintains data integrity. |
| **Analytics-Driven Defaults (Analysdrivna standardvärden)** | Learns from historical data: seasonal patterns, team growth, usage statistics. Suggests quantities based on team size changes and historical consumption. | Optimizes inventory planning, reduces over/under-stocking. |
| **Workflow Integration (Arbetsflödesintegration)** | Post-addition suggestions: "Assign to Damlag?", "Schedule inspection?", "Save as template?". One-click actions for common follow-up tasks. | Creates seamless workflow from creation to assignment. |

### 2.2. Data Retrieval and Restructuring (Scraping)

The system must periodically retrieve and restructure data from https://svenskalag.se/bkgt (or a specified URL) to keep information current.

| Retrieved Data (Scraped) | Target Page/Function | Technical Note |
| :--- | :--- | :--- |
| **Rosters (Laguppställningar)** | Individual Dossier | Used to dynamically update the Player Dossier system with new/removed players. |
| **Calendar Events (Kalenderhändelser)** | Calendar Page | Filter: Only retrieve activities of a certain category (e.g., Training, Game) and exclude social/non-essential events. |
| **Game Statistics (Spelstatistik)** | Individual Dossier | Retrieve raw data (e.g., number of games played, points) to enable the calculation of the local **Scoring** in the Individual Dossier. |
| **Svenskalag Pages** | Team Pages (Sub-pages) | The content from specific svenskalag pages should be integrated into ledare.bkgt.se as readable subpages to avoid maintaining duplicate information. |

### 2.3. Team and Player Pages (Lag- och Spelarsidor)

The core of the management system.

| Function (Swedish Term) | Detailed Description | Access |
| :--- | :--- | :--- |
| **Team Pages (Lagssidor)** | Aggregation page for each team. Displays roster (from svenskalag), upcoming calendar events (filtered), and links to all relevant team documents. | Tränare, Lagledare, Styrelsemedlem |
| **Performance Page (Prestandasida - Sensitive Data)** | An overview page per team where each player is rated based on three (3) criteria: **Entusiasm** (`Enthusiasm`), **Prestanda** (`Performance`), **Skicklighet** (`Skill`). Rating must use a discrete scale (e.g., 1-5). This data is **confidential**. | Styrelsemedlem (Admin), Tränare ONLY |
| **Individual Dossier (Individuell Dossiér)** | A unique page per player collecting all relevant data: 1. **Scoring:** Calculation of points based on retrieved Game Statistics. 2. **Notes (Anteckningar):** Free-text field for coaches/managers to add ongoing notes. 3. **Equipment (Utrustning):** List of all equipment assigned to the player (linked from Inventory System). 4. **Documents (Dokument):** List of documents related to the player (linked from DMS). | Tränare, Styrelsemedlem (Lagledare does not have access to **Scoring** or the **Performance Page**). |

### 2.4. Document Management System (DMS) (Dokumenthanteringssystem)

A system to create and manage internal club documents.

| Function (Swedish Term) | Data Type/Structure | Detailed Description |
| :--- | :--- | :--- |
| **Template-Based Creation (Mallbaserat Skapande)** | Markup Syntax | Documents must be created using a simple Markup syntax (e.g., Markdown or similar). This ensures versioning and clean data. |
| **Variable Handling (Variabelhantering)** | Dynamic Tags | Templates must support embedded variables that are dynamically populated upon generation/export. Example: `{{SPELARE_NAMN}}` (`PLAYER_NAME`), `{{UTFAERDANDE_DATUM}}` (`ISSUE_DATE`), `{{UTRYSTNING_ID}}` (`EQUIPMENT_ID`). |
| **Export Formats (Exportformat)** | File Generation | Support for export to **DOCX**, **Excel/CSV** (for tabular data), and **PDF**. File generation must correctly handle the Markup code. |
| **Linkability (Länkningsbarhet)** | Secure URL | Each document must have a unique, secure, and role-protected link that can be embedded in Individual Dossiers or other pages. |
| **Version Control (Versionshantering)** | Complete History | The system must track every change, date, and user who edited, with the ability to restore to a previous version. |

#### 🚀 **AMAZING DMS ENHANCEMENTS** (Advanced Features)

### 2.4.α. Advanced Markdown Editor Suite (Avancerad Markdown-redigerare)

A professional-grade document creation environment with intelligent features.

| Feature (Funktion) | Technical Implementation | User Benefit |
| :--- | :--- | :--- |
| **Live WYSIWYG Editor (Live WYSIWYG-redigerare)** | Monaco Editor integration with real-time Markdown-to-HTML conversion. Split-pane view with live preview. | Write in familiar word-processor style while maintaining clean Markdown source. |
| **Intelligent Auto-Complete (Intelligent Autofyllning)** | Context-aware suggestions for variables, team names, player names, and common phrases. Learns from usage patterns. | Reduces typing by 70%, prevents spelling errors, ensures consistency. |
| **Drag-and-Drop Media Insertion (Dra-och-släpp mediainfogning)** | Direct upload and insertion of images, PDFs, and other files with automatic resizing and optimization. | Seamless media integration without complex upload workflows. |
| **Collaborative Editing (Samarbetsredigering)** | Real-time multi-user editing with change tracking and conflict resolution. Google Docs-style collaboration. | Multiple coaches can work on documents simultaneously. |
| **Grammar and Style Checking (Grammatik- och stilkontroll)** | Swedish language integration with club-specific terminology and style guides. | Professional document quality with automated proofreading. |

### 2.4.β. Visual Template Builder (Visuell mallbyggare)

A drag-and-drop template creation system for standardizing club documentation.

| Feature (Funktion) | Technical Implementation | User Benefit |
| :--- | :--- | :--- |
| **Component Library (Komponentbibliotek)** | Pre-built components: headers, tables, charts, signatures, QR codes, barcodes, equipment checklists. | Rapid template creation from proven building blocks. |
| **Conditional Logic Engine (Villkorlig logikmotor)** | If-then rules for dynamic content (e.g., "If player age < 18, show guardian signature section"). | Smart templates that adapt to different scenarios automatically. |
| **Template Inheritance (Mallarv)** | Parent-child template relationships with override capabilities. Base templates for "Meeting Minutes" with team-specific variants. | Maintain consistency while allowing customization. |
| **Visual Layout Designer (Visuell layoutdesigner)** | Canvas-based drag-and-drop interface with responsive design preview. | No coding required to create professional-looking templates. |
| **Template Marketplace (Mallmarknad)** | Share and import templates between clubs, with rating and review system. | Leverage community-created templates for common documents. |

### 2.4.γ. Smart Template Application (Smart mallapplicering)

Intelligent template selection and population system.

| Feature (Funktion) | Technical Implementation | User Benefit |
| :--- | :--- | :--- |
| **AI-Powered Template Suggestions (AI-driven mallsförslag)** | Machine learning analyzes document purpose and context to recommend optimal templates. | Users get perfect template suggestions without searching. |
| **Context-Aware Variable Population (Kontextmedveten variabelbefolkning)** | Automatically fills variables based on current context (selected player, team, equipment item). | Documents populate themselves with correct data. |
| **Bulk Template Application (Massmallapplicering)** | Apply templates to multiple items simultaneously (e.g., generate equipment receipts for entire team). | Handle mass document generation efficiently. |
| **Template Version Management (Mallversionshantering)** | Track template evolution with approval workflows for template updates. | Ensure document consistency across versions. |
| **Dynamic Template Updates (Dynamiska malluppdateringar)** | Automatically update existing documents when templates are improved. | Documents stay current without manual recreation. |

### 2.4.δ. Advanced Export & Integration Engine (Avancerad export- och integrationsmotor)

Professional document generation with enterprise-grade features.

| Feature (Funktion) | Technical Implementation | User Benefit |
| :--- | :--- | :--- |
| **Custom Styling Engine (Anpassad stilningsmotor)** | Brand-consistent styling for all export formats with club colors, logos, and typography. | Professional documents that match club branding. |
| **Multi-Format Batch Export (Multiformat mass-export)** | Generate DOCX, PDF, Excel, and HTML versions simultaneously with consistent formatting. | One-click generation of complete document packages. |
| **Cloud Integration (Molnintegration)** | Direct export to Google Drive, OneDrive, Dropbox with permission management. | Seamless integration with existing file storage workflows. |
| **Print Optimization (Utskriftsoptimering)** | Automatic page breaks, header/footer management, and print-specific formatting. | Perfect printed documents without manual adjustments. |
| **API Integration (API-integration)** | RESTful API for third-party integrations (accounting software, CRM systems). | Connect DMS with other club management tools. |

### 2.4.ε. Document Intelligence & Automation (Dokumentintelligens och automatisering)

Smart features that make document management effortless.

| Feature (Funktion) | Technical Implementation | User Benefit |
| :--- | :--- | :--- |
| **Automatic Categorization (Automatisk kategorisering)** | AI-powered content analysis for automatic tagging and folder organization. | Documents organize themselves intelligently. |
| **Content Summarization (Innehållssammanfattning)** | Generate executive summaries and key points extraction from long documents. | Quick overview of document contents without reading everything. |
| **Document Relationships (Dokumentrelationer)** | Automatic linking of related documents (player dossier ↔ equipment assignments ↔ meeting notes). | Navigate document ecosystem effortlessly. |
| **Scheduled Document Generation (Schemalagd dokumentgenerering)** | Automatic creation of recurring documents (monthly reports, quarterly reviews). | Never miss important recurring documentation. |

### 2.4.η. Advanced Document Features (Avancerade dokumentfunktioner)

Cutting-edge capabilities for modern document management.

| Feature (Funktion) | Technical Implementation | User Benefit |
| :--- | :--- | :--- |
| **Document Comparison (Dokumentjämförelse)** | Side-by-side diff viewing with change highlighting and merge capabilities. | Easily track changes and resolve conflicts. |
| **OCR Integration (OCR-integration)** | Optical character recognition for scanned documents with automatic text extraction. | Digitize physical documents instantly. |
| **Interactive Elements (Interaktiva element)** | Embedded forms, checklists, and approval buttons within documents. | Documents become interactive tools, not just static files. |
| **Blockchain Verification (Blockkedje-verifiering)** | Immutable document hashes for legal document verification and tamper-proofing. | Legal-grade document authenticity assurance. |
| **Mobile Document Capture (Mobil dokumentinfångning)** | Smartphone app for photo capture with automatic enhancement and OCR. | Capture documents anywhere with professional results. |

### 2.5. Communication and Notifications (Kommunikation och Notifikationer)

Tools to streamline communication.

| Function (Swedish Term) | Detailed Description |
| :--- | :--- |
| **Target Group Selection (Målgruppsurval)** | Ability to filter recipients based on: * Team affiliation, * User Role (Coach, Team Manager, Board), * **Assigned Equipment** (Retrieve list from Inventory System). |
| **Channels (Utskickskanaler)** | Primary: Email. Secondary: System Notifications (visible upon login to ledare.bkgt.se). |
| **Alerts (Varningsnotiser)** | Automated alerts (Email/System) should be sent to responsible parties when: * Equipment status is **Förlustanmäld** (`Reported Lost`), * Equipment status is **Behöver reparation** (`Needs Repair`), * An Offboarding process is approaching its end date. |
| **History (Utskickshistorik)** | A log saving the date, recipient group, sender, and content for every outgoing communication. |

### 2.6. Offboarding/Handover Feature (Överlämningsfunktion)

A process to manage personnel changes and ensure that equipment and responsibilities are correctly handed over.

| Function (Swedish Term) | Detailed Description |
| :--- | :--- |
| **Process Start (Processstart)** | A Board Member (Admin) initiates an offboarding process for a User-ID. |
| **Equipment Receipt (Utrustningskvitto)** | The system automatically generates a PDF/DOCX list of all equipment assigned to the individual. This list should serve as a checklist/receipt upon return. |
| **Task Checklist (Uppgiftschecklista)** | Ability to create a dynamic checklist (based on the person's Role) with tasks to be completed (e.g., `Återlämna nycklar`/`Return keys`, `Avsluta budgetrapport`/`Finalize budget report`). |
| **Access Control (Åtkomstkontroll)** | Automatic deactivation of the user account on a specified date (`Slutdatum`/`End Date`). The account should be retained in the database for history but with the role set to `Inactive`. |

---

## 3. Implementation Plan

### 3.1. Database Schema Updates

- **Create wp_bkgt_assignments table**: Implement the proposed schema with foreign keys and constraints for assignment history tracking.
- **Add inventory_items table**: Create wp_bkgt_inventory_items table to store item data separately from WordPress posts for better performance and relationships.
- **Update database version**: Increment version and add migration logic for existing data.

### 3.2. Item Assignment System Implementation

- **Build assignment admin page**: Create `/wp-admin/admin.php?page=bkgt-item-assignments` with two-panel interface for item and assignee selection.
- **Implement assignment logic**: Update BKGT_Assignment class to use the new assignments table instead of postmeta.
- **Add smart search functionality**: Implement typeahead search for items and assignees with autocomplete.
- **Create bulk assignment features**: Add checkbox selection and "Assign All" functionality.
- **Implement visual status indicators**: Add color-coded states (🟢 Available, 🟡 Assigned, 🔴 Needs Attention).
- **Add workflow suggestions**: Context-aware defaults based on user roles and team affiliations.
- **Build assignment history audit trail**: Complete logging with timestamps and user tracking.
- **Implement conflict resolution**: Validation to prevent double-assignments with clear error messages.
- **Add automated alerts system**: Email notifications for overdue returns and repair queue items.
- **Create reporting dashboard**: Analytics for items per assignee, overdue returns, and utilization statistics.

### 3.3. Enhanced "Lägg till ny utrustning" Implementation

- **Implement progressive form wizard**: Four-step interface (Basic Info → Specifications → Assignment → Review & Save) with validation.
- **Add AI-powered smart suggestions**: Local algorithm for context-aware suggestions based on existing data patterns.
- **Build advanced custom fields engine**: Conditional fields based on item type with dynamic validation.
- **Implement batch processing**: Add multiple items simultaneously with smart ID generation.
- **Add duplicate prevention intelligence**: Real-time detection with similarity suggestions and merge options.
- **Integrate analytics-driven defaults**: Learn from historical data for quantity and configuration suggestions.
- **Add workflow integration**: Post-addition suggestions for assignment, inspection scheduling, and template saving.

### 3.4. Sticker Field Integration

- **Add sticker field to inventory schema**: Update database and forms to include unique sticker code generation.
- **Implement labeling machine compatibility**: Format `[Unique ID]-[Sequential]` for physical labeling.
- **Update item creation and display**: Show sticker codes in admin interface and item details.

### 3.5. Testing and Validation

- **Unit tests**: Create tests for database operations, assignment logic, and form validation.
- **Integration testing**: Test end-to-end workflows for item creation and assignment.
- **User acceptance testing**: Validate with coaches and team managers for usability.
- **Performance testing**: Ensure search and bulk operations scale with inventory size.

### 3.5.x. Deployment Optimization (COMPLETED)

- **Incremental file syncing**: Implemented rsync detection in deploy.bat for efficient incremental deployments instead of full file copies.
- **Cross-platform compatibility**: Maintained SCP fallback for systems without rsync while optimizing for rsync availability.
- **Exclude patterns**: Proper exclusion of development files (.git, node_modules, .env, etc.) and sensitive files (wp-config-sample.php).
- **Performance improvement**: Reduced deployment time from minutes to seconds for small changes through incremental syncing.

### 3.6. Deployment and Training

- **Staged deployment**: Roll out features incrementally with fallback options.
- **User training materials**: Create documentation and video tutorials for new features.
- **Admin training**: Train board members and coaches on assignment system and enhanced forms.
- **Feedback collection**: Implement feedback mechanisms for continuous improvement.