# RÉCIT Annotation Feedback — Moodle Assignment Feedback Plugin

An assignment feedback plugin that lets teachers annotate student text submissions directly inside the Moodle grading interface. Teachers can highlight text, apply colour-coded criteria, attach reusable comments, and optionally consult an AI assistant — all without leaving the grading page.

## Features

- **Inline text annotation** — highlight any part of a student's submitted text and attach a note
- **Colour-coded criteria** — define a rubric of criteria, each with a colour; highlighted spans are tagged to the matching criterion
- **Occurrence counters** — each criterion badge shows how many times it was applied in the submission
- **Comment library** — maintain a reusable bank of comments per criterion; search and insert with one click
- **Undo / redo / clean** — full edit history and an HTML clean-up tool to strip unwanted markup from student text
- **Quick annotation mode** — select text and apply the active criterion in a single click
- **AI assistant (optional)** — send selected text to a configurable AI endpoint (Azure OpenAI or compatible) and get a suggested response based on per-criterion instructions
- **Import / export criteria** — share rubric configurations between assignments via XML file
- **Print comment list** — generate a printable list of all comments for a given assignment
- **Student summary view** — students see the annotated text and criterion badges after grading
- **Backup & restore** — all annotation data is included in Moodle course backup/restore
- **Privacy API** — fully GDPR-compliant; supports data export and deletion per user

## Requirements

| Requirement | Version |
|---|---|
| Moodle | 4.3 or higher (tested up to 4.5) |
| PHP | 7.4 or higher |
| Database | Any Moodle-supported database |

## Installation

1. Download or clone this repository.
2. Copy the `src/` folder into your Moodle installation at:
   ```
   <moodleroot>/mod/assign/feedback/recitannotation/
   ```
3. Log in as a site administrator and navigate to **Site administration → Notifications**.
4. Moodle will detect the new plugin and run the database installation automatically.
5. The plugin is now available as a feedback method on any assignment.

### Enable on an assignment

1. Open an assignment (or create a new one).
2. Go to **Edit settings → Feedback types**.
3. Check **Text Annotation** to enable it for that assignment.

To enable it by default for all new assignments, go to **Site administration → Plugins → Activity modules → Assignment → Feedback plugins → RÉCIT Annotation feedback** and check **Enabled by default**.

## Configuration

Go to **Site administration → Plugins → Activity modules → Assignment → Feedback plugins → RÉCIT Annotation feedback**.

| Setting | Description |
|---|---|
| Enabled by default | Enable this feedback method automatically on all new assignments |
| AI API Endpoint | Full URL of the AI API (e.g. Azure OpenAI deployment URL) |
| AI Model | Model name sent in API requests (e.g. `gpt-4o`) |
| AI API Key | Secret key for the AI service (stored masked) |
| Documentation URL | Optional link shown in the annotation interface |

The AI assistant features are hidden in the interface when the endpoint or key are not configured.

### AI API compatibility

The plugin sends POST requests with a JSON payload and an `api-key` header, which is compatible with **Azure OpenAI** deployments. Any API that accepts the same request format will work.

## Usage

### For teachers

1. Open a student submission from the assignment grading page.
2. The annotation panel appears below the student's text.
3. **Annotate** — select text in the student's submission and click *Annotate* to highlight it. Choose a criterion from the list to colour-code it.
4. **Criteria** — open the criteria panel to add, edit, reorder, or delete criteria. Each criterion has a name, description, colour, and optional AI instruction.
5. **Comments** — open the comments panel to build a reusable comment bank. Search and insert comments directly from the annotation view.
6. **Ask AI** — select text, open the AI panel, choose a criterion, and click *Ask* to send the text to the configured AI endpoint. The response appears in the output field and can be applied as an annotation.
7. **Export / Import** — use the toolbar buttons to export the current criteria and comments to an XML file, or import from a previously exported file.
8. Annotations are saved automatically.

### For students

After a teacher has graded and annotated a submission, students see:
- The full annotated text with highlighted spans
- Colour-coded criterion badges showing how many times each criterion was applied

## Languages

- English (`en`)
- French (`fr`)

## Privacy

This plugin stores the following personal data:

| Data | Purpose |
|---|---|
| Submission reference | Links the annotation to the student's work |
| Teacher ID (`ownerid`) | Records who wrote the annotation |
| Annotated HTML content | The annotation itself |
| Last update timestamp | Audit trail |

All data can be exported and deleted through Moodle's standard privacy tools (**Site administration → Privacy and policies → Data requests**).

## License

This plugin is distributed under the [GNU General Public License v3.0](LICENSE) or later.

## Credits

Developed by [RÉCIT](https://recit.qc.ca), a Quebec educational technology support service.
