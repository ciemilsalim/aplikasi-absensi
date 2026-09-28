---
name: app-tutorial-video
description: Creates end-to-end tutorial videos for the application in the current Antigravity workspace. Use when the user asks to document, demonstrate, teach, record, or produce a video tutorial for a feature, module, workflow, or release of a web application. The skill inspects the real application, creates a verified tutorial plan and narration, automates the browser demo, generates optional AI/local voice-over and subtitles, renders a final MP4 with FFmpeg, and performs a final quality check.
---

# App Tutorial Video Skill

## Purpose

Turn a real, working application feature into a user-facing tutorial video. Treat the running application—not assumptions about the code—as the source of truth.

## Core principles

1. **Truth from the product**: inspect the current workspace and verify the actual UI, routes, permissions, labels, states, and outcomes before writing narration.
2. **Demo-safe by default**: use seeded/demo accounts and non-destructive data. Never expose real credentials, tokens, API keys, personal data, private URLs, or unrelated browser tabs.
3. **Test before recording**: the exact tutorial flow must successfully execute before a recording is accepted.
4. **One skill, modular tools**: use the bundled scripts as black boxes. Run `--help` first when available; do not read an entire helper script unless debugging it.
5. **No false claims**: narration must describe only behavior verified in the current build.
6. **Human-audible teaching**: each important interaction gets a short spoken explanation and, when useful, an on-screen highlight or subtitle.
7. **Professional output**: prefer 1080p, readable text, sensible pacing, clear voice, and no dead time or accidental UI exposure.

## Invocation modes

### `/app-tutorial-video`
Create a tutorial for the user's stated feature/topic.

### `/app-tutorial-video feature <feature>`
Create a focused tutorial for one feature.

### `/app-tutorial-video module <module>`
Create a tutorial covering a connected group of features.

### `/app-tutorial-video release`
Inspect the latest verified release/change set and create tutorial coverage for the most relevant user-facing changes. Never infer changes only from commit names; inspect the product.

## Output contract

Create a working folder `.tutorial-video/` in the project root and keep intermediate artifacts there. Final deliverables go to:

```text
.tutorial-video/
├── discovery.md
├── tutorial-plan.json
├── storyboard.md
├── narration.md
├── narration.txt
├── recording/
├── audio/
├── subtitles/
├── rendered/
└── qa.md
```

The final video should be:

```text
.tutorial-video/rendered/tutorial-final.mp4
```

Also create, when possible:

```text
.tutorial-video/rendered/tutorial-final.srt
.tutorial-video/rendered/tutorial-thumbnail.png
```

## Phase 0 — Understand the request

Extract:

- target audience
- feature/module
- learning objective
- desired language
- approximate duration
- desired tone
- whether the user wants voice-over
- whether subtitles are required
- whether branding/logo is required

Defaults:

```text
Language: Indonesian
Audience: end users of the application
Tone: clear, professional, friendly
Target duration: 3–7 minutes for a feature tutorial
Voice-over: yes when TTS is available
Subtitles: yes
Resolution: 1920x1080 when practical
```

If the user did not specify duration, prioritize clarity over hitting an exact duration.

## Phase 1 — Inspect the real application

Use the available workspace, terminal, file, and browser tools.

Inspect enough to determine:

- framework and run method
- application entry URL/port
- authentication flow
- relevant roles/permissions
- relevant route/page/component names
- primary buttons, forms, tables, dialogs, menus
- success/error states
- data prerequisites
- destructive actions that must not occur during recording

For Laravel projects, check `composer.json`, `.env.example`, routes, migrations, seeders/factories, controllers/actions, Blade/Inertia/React/Vue UI, and README before guessing.

For other stacks, adapt to the detected framework. Do not force Laravel assumptions on non-Laravel projects.

## Phase 2 — Create a safe demo environment

Prefer, in this order:

1. existing demo/test account
2. seeded local data
3. test database/database reset mechanism
4. isolated local environment

Never use a user's production account if a safe alternative exists.

Mask or remove:

- passwords
- API tokens
- email addresses not intended for the demo
- phone numbers
- private student/employee/person data
- internal hostnames or secrets

Do not record `.env`, terminal secrets, browser password managers, or unrelated notifications.

## Phase 3 — Build and verify the tutorial plan

Create `.tutorial-video/tutorial-plan.json` following the schema in `resources/tutorial-plan.schema.json`.

Each scene should contain:

- objective
- narration
- exact visual actions
- expected result
- fallback/retry behavior
- approximate duration

Use the smallest number of scenes that teaches the workflow clearly.

Preferred scene structure:

```text
1. Intro / goal
2. Login or starting state
3. Main workflow
4. Verification/result
5. Useful secondary action
6. Summary / call to action
```

For a very small feature, omit unnecessary scenes.

## Phase 4 — Validate the flow before recording

Execute the planned actions against the real application.

Do not accept a scene until:

- the page loaded correctly
- the intended control was found
- the action succeeded
- the expected state was observed
- no unexpected modal/error appeared
- the next scene can start from a deterministic state

If the test fails, fix the tutorial plan or the application before recording. Do not hide failures with narration.

## Phase 5 — Record

For web applications, prefer a deterministic Playwright recording using the bundled `scripts/record.js` when a reproducible file is required. Antigravity's browser agent can also generate its own browser recording artifact; that is useful for review, but the final pipeline should have a local video file that can be rendered and archived. Antigravity documents that browser recordings are saved as reviewable recording artifacts. citeturn833484search3turn833484search8

Before recording:

- close/ignore unrelated tabs
- set browser viewport consistently
- use a stable zoom level
- clear transient notifications
- ensure demo data is ready
- navigate to the starting page

During recording:

- move the cursor deliberately
- pause briefly before important clicks
- avoid unnecessary pointer movement
- keep the app centered and readable
- avoid rapid clicks
- never reveal credentials

Use scene-level recordings whenever practical. This makes failed scenes replaceable without re-recording the entire tutorial.

## Phase 6 — Narration

Write narration in natural Indonesian unless another language was requested.

Rules:

- short sentences
- explain why a step matters, not just what was clicked
- avoid reading every label on screen
- never claim a result that was not verified
- target roughly 120–160 spoken words/minute
- keep each scene's narration aligned with its actions

Generate voice-over with the best available configured provider:

1. configured local TTS adapter
2. configured API TTS adapter
3. Windows/system TTS fallback

Do not hard-code API keys into the project or scripts. Read secrets from environment variables or the configured secret store.

The bundled reference adapter uses Edge TTS when installed and can be replaced later with another provider without changing the Skill instructions.

## Phase 7 — Subtitles

Create timed subtitles from the narration and final scene timings.

Requirements:

- Indonesian UTF-8 text
- readable duration per caption
- no giant paragraphs
- avoid covering important buttons/forms when possible
- synchronize captions to speech

Prefer SRT for portability.

## Phase 8 — Render final video

Use FFmpeg through `scripts/render.py` or another available renderer.

Recommended composition:

```text
video screen capture
    + voice-over
    + optional subtle background music
    + optional intro/outro
    + subtitles
        ↓
     MP4/H.264
```

Do not add background music by default when it reduces voice clarity.

Recommended defaults:

```text
Container: MP4
Video: H.264
Audio: AAC
Resolution: 1920x1080 when source supports it
Frame rate: keep source frame rate or use 30 fps
Audio: clear speech, normalized to a comfortable level
```

## Phase 9 — Thumbnail

Create a simple thumbnail with:

- feature/tutorial title
- one useful UI screenshot or representative visual
- high readability
- no secret/private data

If image-generation or design tooling is available, it may be used. Otherwise create a clean frame-based thumbnail.

## Phase 10 — Final QA gate

Before declaring success, verify:

### Functional
- tutorial follows a real working path
- final stated outcome is visible
- no broken scene remains

### Visual
- no accidental secrets/private information
- cursor is visible when needed
- important controls are readable
- no black/frozen frames
- no abrupt scene truncation

### Audio
- narration exists when requested/available
- narration is intelligible
- no severe clipping
- background music does not overpower narration

### Subtitle
- subtitle file exists when requested
- timing roughly matches speech
- no corrupted characters

### File
- final MP4 opens successfully
- duration is plausible
- audio stream exists
- video stream exists

Create `.tutorial-video/qa.md` with PASS/FAIL for each gate and list any known limitations.

## Failure policy

If TTS is unavailable:

- still create the video with subtitles if possible
- report exactly which TTS adapter is unavailable
- do not pretend the voice-over was generated

If recording is unavailable:

- create the verified storyboard, narration, and action plan
- do not fabricate a completed video

If application verification fails:

- stop before final render
- report the blocking app behavior
- do not produce a misleading tutorial

## Reuse and incremental updates

When the user asks to update a tutorial after an application change:

1. compare the current UI and prior `tutorial-plan.json`
2. identify scenes affected by the change
3. re-test only the affected scenes first
4. re-record affected scenes
5. re-render the final video
6. update `qa.md`

Do not discard a valid previous recording unless it is affected.

## Completion message

When successful, summarize:

- tutorial title
- verified workflow
- final video path
- subtitle path
- thumbnail path
- any limitations

Do not claim that a deliverable exists unless the corresponding file was actually created and verified.
