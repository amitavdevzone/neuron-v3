# Blog Writing Workflow with Human-in-the-Loop Interrupts

## Context

Build a multi-step AI blog writing workflow using Neuron AI v3.2 that demonstrates workflow interrupts and human-in-the-loop patterns. The workflow pauses at two points: (1) to gather clarifying questions from the user before writing, and (2) to collect feedback in a review loop (max 5 iterations). All notifications are logged via the existing `ai` log channel (log mail driver).

---

## Workflow Overview

```
[Screen 1: Create Form]
    idea + reference file upload
         ↓
  StartBlogWorkflowEvent
         ↓
  GatherQuestionsNode
    - AI generates 3-5 clarifying questions
    - Logs "email" with questions
    - INTERRUPT #1 (QuestionsInterruptRequest)
         ↓
[Screen 2: Questions Form]
    blog_explanation textarea (shows questions, user answers)
         ↓ (resume workflow)
  WriteBlogNode
    - AI writes full blog post
         ↓
  NotifyBlogWrittenNode
    - Logs "blog written" email
    - Returns FeedbackReceivedEvent
         ↓
  ReviewBlogNode
    - Check attempt cap (≥5 → publish → StopEvent)
    - INTERRUPT #2 (FeedbackInterruptRequest)
         ↓
[Screen 3: Review Form]
    blog content display + feedback textarea
         ↓ (resume workflow)
  ReviewBlogNode (resumed)
    - Empty feedback → publish → StopEvent
    - Feedback present → increment attempts, AI rewrite,
      log "rewrite" email → FeedbackReceivedEvent (loops back)
         ↓
[Screen 4: Published confirmation]
```

---

## Step 1 — Database Migrations

### `database/migrations/..._create_blog_posts_table.php`
```
id, timestamps
idea (text)
reference_content (text, nullable)  — parsed markdown file content
questions (text, nullable)          — JSON-encoded array of AI-generated questions
blog_explanation (text, nullable)   — user's free-text answers
content (longText, nullable)        — AI-generated blog post
status (string, default 'draft')    — draft | published
workflow_id (string, nullable, index)
feedback_attempts (integer, default 0)
```

### `database/migrations/..._create_workflow_states_table.php`
```
id, timestamps
workflow_id (string, unique, index)
interrupt (longText)                — PHP-serialized WorkflowInterrupt object (needs longText)
```

---

## Step 2 — Models

### `app/Models/BlogPost.php`
Extends `Model`. Fillable: all columns above. Cast `questions → array`, `feedback_attempts → integer`.

### `app/Models/WorkflowState.php`
Extends `Model`. Fillable: `workflow_id`, `interrupt`. Table: `workflow_states`.
Passed directly to `EloquentPersistence(WorkflowState::class)`.

---

## Step 3 — Events

All in `app/Neuron/Blog/Events/`, each implements `NeuronAI\Workflow\Events\Event` (empty interface).

| File | Purpose |
|------|---------|
| `StartBlogWorkflowEvent.php` | Kicks off workflow; triggers `GatherQuestionsNode` |
| `QuestionsAnsweredEvent.php` | After interrupt #1 resolved; triggers `WriteBlogNode` |
| `BlogWrittenEvent.php` | After writing done; triggers `NotifyBlogWrittenNode` |
| `FeedbackReceivedEvent.php` | Triggers `ReviewBlogNode`; also returned by it to loop |

---

## Step 4 — Custom Interrupt Requests

### `app/Neuron/Blog/Interrupts/QuestionsInterruptRequest.php`
Extends `InterruptRequest`. Extra properties: `questions (array)`, `blogPostId (int)`, `blogExplanation (string)`.
The controller populates `blogExplanation` when building the resume request.

### `app/Neuron/Blog/Interrupts/FeedbackInterruptRequest.php`
Extends `InterruptRequest`. Extra properties: `blogPostId (int)`, `feedback (string)`.
The controller populates `feedback` when building the resume request.

---

## Step 5 — Workflow Nodes (`app/Neuron/Blog/Nodes/`)

### `GatherQuestionsNode.php`
`__invoke(StartBlogWorkflowEvent, WorkflowState): QuestionsAnsweredEvent`
1. Load `BlogPost::find($state->get('blog_post_id'))`.
2. `$this->checkpoint('generate_questions', fn() => ...)` → call `QuestionsAgent` to produce array of questions.
3. `$blogPost->update(['questions' => $questions])`.
4. `Log::channel('ai')->info('Questions email', ['questions' => $questions])`.
5. `$resumeRequest = $this->interrupt(new QuestionsInterruptRequest(..., questions: $questions))`.
6. On resume: `$blogPost->update(['blog_explanation' => $resumeRequest->blogExplanation])`.
7. Return `new QuestionsAnsweredEvent()`.

### `WriteBlogNode.php`
`__invoke(QuestionsAnsweredEvent, WorkflowState): BlogWrittenEvent`
1. Load `BlogPost`.
2. `$this->checkpoint('write_blog', fn() => ...)` → call `BlogWriterAgent` with idea + reference + explanation.
3. `$blogPost->update(['content' => $content])`.
4. Return `new BlogWrittenEvent()`.

### `NotifyBlogWrittenNode.php`
`__invoke(BlogWrittenEvent, WorkflowState): FeedbackReceivedEvent`
1. `Log::channel('ai')->info('Blog written email sent', ['blog_post_id' => ...])`.
2. Return `new FeedbackReceivedEvent()`.

### `ReviewBlogNode.php`
`__invoke(FeedbackReceivedEvent, WorkflowState): FeedbackReceivedEvent|StopEvent`
1. Load `BlogPost`.
2. If `feedback_attempts >= 5` → `update(['status' => 'published'])` → `return new StopEvent()`.
3. `$resumeRequest = $this->interrupt(new FeedbackInterruptRequest(...))`.
4. On resume: extract `$feedback = $resumeRequest->feedback`.
5. If empty → `update(['status' => 'published'])` → `return new StopEvent()`.
6. Else: `increment('feedback_attempts')` → call `BlogWriterAgent` to rewrite with feedback → `update(['content' => $rewritten])` → log rewrite email → `return new FeedbackReceivedEvent()`.

The self-loop: `FeedbackReceivedEvent` → `ReviewBlogNode` creates the iteration.

---

## Step 6 — Supporting Agents (`app/Neuron/Blog/`)

### `QuestionsAgent.php`
Extends `Agent`. Provider: `AIProviderFactory::make()`.
Instructions: generate 3-5 clarifying questions about tone, target audience, style, key points to cover — return as JSON array.

### `BlogWriterAgent.php`
Extends `Agent`. Provider: `AIProviderFactory::make()`.
Instructions: expert blog writer; write well-structured markdown blog post based on provided context (idea, reference, explanation, optional rewrite feedback).

---

## Step 7 — The Workflow Class

### `app/Neuron/Blog/BlogWorkflow.php`
Extends `NeuronAI\Workflow\Workflow`.
- `nodes()` returns all 4 node instances.
- `startEvent()` returns `new StartBlogWorkflowEvent()`.

Fresh run:
```php
BlogWorkflow::make(persistence: new EloquentPersistence(WorkflowState::class))
```
Resume:
```php
BlogWorkflow::make(
    persistence: new EloquentPersistence(WorkflowState::class),
    resumeToken: $blogPost->workflow_id,
)
```

---

## Step 8 — Form Requests

| File | Rules |
|------|-------|
| `app/Http/Requests/StoreBlogPostRequest.php` | `idea`: required string min:10; `reference`: nullable file mimes:md,txt max:2048 |
| `app/Http/Requests/AnswerQuestionsRequest.php` | `blog_explanation`: required string min:10 |
| `app/Http/Requests/SubmitFeedbackRequest.php` | `feedback`: nullable string |

---

## Step 9 — Controller

### `app/Http/Controllers/BlogController.php`

**`create()`** `GET /blog/create` → render `blog/create`

**`store()`** `POST /blog`
- Validate, read reference file content.
- Create `BlogPost`.
- Start fresh workflow, set state `blog_post_id`.
- `try { $handler->run() } catch (WorkflowInterrupt $i) { $blogPost->update(['workflow_id' => $i->getResumeToken()]); redirect to questions }`

**`questions()`** `GET /blog/{blogPost}/questions`
- Render `blog/questions` with `blogPost->questions` and `id`.

**`answerQuestions()`** `POST /blog/{blogPost}/questions`
- Build `QuestionsInterruptRequest` with `blog_explanation`.
- Resume workflow using `$blogPost->workflow_id` as resumeToken.
- `try { $handler->run() } catch (WorkflowInterrupt $i) { $blogPost->update(['workflow_id' => $i->getResumeToken()]); redirect to review }`

**`review()`** `GET /blog/{blogPost}/review`
- Render `blog/review` with `content`, `id`, `feedback_attempts`.

**`submitFeedback()`** `POST /blog/{blogPost}/review`
- Build `FeedbackInterruptRequest` with `feedback` (may be empty string).
- Resume workflow.
- `try { $handler->run(); redirect to published } catch (WorkflowInterrupt $i) { update token; redirect back to review }`

**`published()`** `GET /blog/{blogPost}/published`
- Render `blog/published` confirmation page.

---

## Step 10 — Routes

New file `routes/blog.php`, included from `routes/web.php`.
All routes under `auth` + `verified` middleware.

```
GET  /blog/create                   → blog.create
POST /blog                          → blog.store
GET  /blog/{blogPost}/questions     → blog.questions
POST /blog/{blogPost}/questions     → blog.answerQuestions
GET  /blog/{blogPost}/review        → blog.review
POST /blog/{blogPost}/review        → blog.submitFeedback
GET  /blog/{blogPost}/published     → blog.published
```

After adding routes run: `php artisan wayfinder:generate`

---

## Step 11 — React Pages (`resources/js/pages/blog/`)

All pages use `AppLayout`. Follow the pattern in `resources/js/pages/auth/register.tsx` for forms (`<Form>`, Wayfinder actions, `Button`, `Label`, `Input`, `InputError`, `Spinner`).

| File | Key elements |
|------|-------------|
| `create.tsx` | `idea` textarea, file input for reference, submit button |
| `questions.tsx` | Read-only numbered list of `blogPost.questions`, `blog_explanation` textarea |
| `review.tsx` | `<article>` showing blog content, feedback textarea, dynamic button ("Publish" when empty / "Request Rewrite" when has text) |
| `published.tsx` | Success message, link back to dashboard |

---

## Implementation Order

1. Migrations (`blog_posts`, `workflow_states`) → `php artisan migrate`
2. Models: `BlogPost`, `WorkflowState`
3. Events (4 files)
4. Interrupt Requests (2 files)
5. Agents: `QuestionsAgent`, `BlogWriterAgent`
6. Nodes (4 files)
7. `BlogWorkflow`
8. Form Requests (3 files)
9. `BlogController`
10. `routes/blog.php` + include in `web.php`
11. `php artisan wayfinder:generate`
12. React pages (4 files)

---

## Verification

1. Start dev server: `npm run dev` + `php artisan serve`
2. Visit `/blog/create`, submit → redirects to `/blog/{id}/questions`
3. Check `storage/logs/ai-logs.log` for questions email entry
4. Submit `blog_explanation` → redirects to `/blog/{id}/review`
5. Check logs for "blog written" email
6. Submit empty feedback → status becomes `published`, redirect to `/blog/{id}/published`
7. Submit with feedback → logs rewrite email, redirects back to review with updated content
8. Repeat feedback 5× → 6th entry auto-publishes without interrupt
9. Run `php artisan test` to confirm no regressions
