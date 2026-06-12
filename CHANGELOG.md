# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Fixed
- Removed dead `get_feedback_annotation()` method that referenced a non-existent database field
- Replaced hardcoded hostname dev-mode check with `$CFG->debug >= DEBUG_DEVELOPER`
- Fixed `Exception::getMessage()` incorrect casing in WebApi (`GetMessage` → `getMessage`)

### Security
- AI API key admin setting now uses `configpasswordunmask` to prevent accidental exposure

---

## [1.4.0-beta] - 2026-04-16

### Added
- Privacy API implementation (`classes/privacy/provider.php`) for GDPR compliance: supports metadata declaration, data export, and data deletion per user

---

## [1.3.2-beta]

### Added
- Combobox opening direction set to automatic based on available screen space
- French translation improvements

---

## [1.3.1-beta]

### Changed
- Updated AI prompt handling and UI

---

## [1.3.0-beta]

### Added
- AI Model configuration field in admin settings

---

## [1.2.2-beta]

### Added
- Ability to abort pending AJAX web requests

---

## [1.2.1-beta]

### Added
- Documentation URL setting in admin panel
- Print comment list feature
- Quick annotation method
- Sort order control for criteria list
- AI integration: call Azure AI endpoint from annotation interface
- Show AI input/output in interface
- Reset annotation function
- Criterion badges on student submission view (click to toggle highlight)
- Import and export criteria list (XML)
- Delete all criteria at once
- Timeout on AJAX calls with loading indicator

### Changed
- Criteria display replaced by badge overlay on annotation view
- Improved modal for AI call interface
- Upgraded React rendering to avoid direct `innerHTML` manipulation
- Visual and layout adjustments

### Fixed
- Fixed sort order on comment list
- Fixed backup and restore
- Fixed React component unmounting bugs
- Fixed loading spinner under modal overlay
- Fixed top navigation bar display

---

## [1.0.7-beta]

### Fixed
- Fixed occurrence counters on student submission view
- Fixed attempt number handling on submission lookup

---

## [1.0.6-beta]

### Added
- Redo, undo, and clean HTML functions in annotation editor
- Bootstrap color palette for criterion color selection
- Backup and restore support for all plugin data

### Fixed
- Fixed teacher access check (`canUserAccess`)
- Fixed annotation display on student view
- Fixed `grade` field lookup in feedback record retrieval

---

## [1.0.0-beta] - Initial release

### Added
- Core annotation editor embedded in the assignment grading page (React app)
- Save and load teacher annotations per student submission
- Criteria management (create, edit, delete, reorder)
- Comments library per criterion
- Annotation summary displayed on student submission view
- AMD module integration for Moodle asset loading
- French and English language files
