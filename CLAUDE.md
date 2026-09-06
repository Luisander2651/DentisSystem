PRIORITY: This workflow OVERRIDES all other built-in workflows
When user requests software development, ALWAYS follow this workflow FIRST
Adaptive Workflow Principle
The workflow adapts to the work, not the other way around.
The AI model intelligently assesses what stages are needed based on:
User's stated intent and clarity
Existing codebase state (if any)
Complexity and scope of change
Risk and impact assessment
MANDATORY: Rule Details Loading
CRITICAL: When performing any phase, you MUST read and use relevant content from rule detail files. Check these paths in order and use the first one that exists, regardless of which IDE or setup method was used:
`.aidlc/aidlc-rules/aws-aidlc-rule-details/` (typical with AI-assisted setup)
`.aidlc-rule-details/` (typical with Cursor, Cline, Claude Code, GitHub Copilot, OpenAI Codex)
`.kiro/aws-aidlc-rule-details/` (typical with Kiro IDE and CLI)
`.amazonq/aws-aidlc-rule-details/` (typical with Amazon Q Developer)
All subsequent rule detail file references (e.g., `common/process-overview.md`, `inception/workspace-detection.md`) are relative to whichever rule details directory was resolved above.
Common Rules: ALWAYS load common rules at workflow start:
Load `common/process-overview.md` for workflow overview
Load `common/session-continuity.md` for session resumption guidance
Load `common/content-validation.md` for content validation requirements
Load `common/question-format-guide.md` for question formatting rules
Reference these throughout the workflow execution
MANDATORY: Extensions Loading (Context-Optimized)
CRITICAL: At workflow start, scan the `extensions/` directory recursively but load ONLY lightweight opt-in files — NOT full rule files. Full rule files are loaded on-demand after the user opts in.
Loading process:
List all subdirectories under `extensions/` (e.g., `extensions/security/`, `extensions/compliance/`)
In each subdirectory, load ONLY `*.opt-in.md` files — these contain the extension's opt-in prompt. The corresponding rules file is derived by convention: strip the `.opt-in.md` suffix and append `.md` (e.g., `security-baseline.opt-in.md` → `security-baseline.md`)
Do NOT load full rule files (e.g., `security-baseline.md`) at this stage
Deferred Rule Loading:
During Requirements Analysis, opt-in prompts from the loaded `*.opt-in.md` files are presented to the user
When the user opts IN for an extension, load the corresponding rules file (derived by naming convention) at that point
When the user opts OUT, the full rules file is never loaded — saving context
Extensions without a matching `*.opt-in.md` file are always enforced — load their rule files immediately at workflow start
Enforcement (applies only to loaded/enabled extensions):
Extension rules are hard constraints, not optional guidance
At each stage, the model intelligently evaluates which extension rules are applicable based on the stage's purpose, the artifacts being produced, and the context of the work — enforce only those rules that are relevant
Rules that are not applicable to the current stage should be marked as N/A in the compliance summary (this is not a blocking finding)
Non-compliance with any applicable enabled extension rule is a blocking finding — do NOT present stage completion until resolved
When presenting stage completion, include a summary of extension rule compliance (compliant/non-compliant/N/A per rule, with brief rationale for N/A determinations)
Conditional Enforcement: Extensions may be conditionally enabled/disabled. See `inception/requirements-analysis.md` for the opt-in mechanism. Before enforcing any extension at ANY stage, check its `Enabled` status in `aidlc-docs/aidlc-state.md` under `## Extension Configuration`. Skip disabled extensions and log the skip in audit.md. Default to enforced if no configuration exists.
MANDATORY: Content Validation
CRITICAL: Before creating ANY file, you MUST validate content according to `common/content-validation.md` rules:
Validate Mermaid diagram syntax
Validate ASCII art diagrams (see `common/ascii-diagram-standards.md`)
Escape special characters properly
Provide text alternatives for complex visual content
Test content parsing compatibility
MANDATORY: Question File Format
CRITICAL: When asking questions at any phase, you MUST follow question format guidelines.
See `common/question-format-guide.md` for complete question formatting rules including:
Multiple choice format (A, B, C, D, E options)
[Answer]: tag usage
Answer validation and ambiguity resolution
MANDATORY: Custom Welcome Message
CRITICAL: When starting ANY software development request, you MUST display the welcome message.
How to Display Welcome Message:
Load the welcome message from `common/welcome-message.md` (in the resolved rule details directory)
Display the complete message to the user
This should only be done ONCE at the start of a new workflow
Do NOT load this file in subsequent interactions to save context space
Adaptive Software Development Workflow
---
INCEPTION PHASE
Purpose: Planning, requirements gathering, and architectural decisions
Focus: Determine WHAT to build and WHY
Stages in INCEPTION PHASE:
Workspace Detection (ALWAYS)
Reverse Engineering (CONDITIONAL - Brownfield only)
Requirements Analysis (ALWAYS - Adaptive depth)
User Stories (CONDITIONAL)
Workflow Planning (ALWAYS)
Application Design (CONDITIONAL)
Units Generation (CONDITIONAL)
---
Workspace Detection (ALWAYS EXECUTE)
MANDATORY: Log initial user request in audit.md with complete raw input
Load all steps from `inception/workspace-detection.md`
Execute workspace detection:
Check for existing aidlc-state.md (resume if found)
Scan workspace for existing code
Determine if brownfield or greenfield
Check for existing reverse engineering artifacts
Determine next phase: Reverse Engineering (if brownfield and no artifacts) OR Requirements Analysis
MANDATORY: Log findings in audit.md
Present completion message to user (see workspace-detection.md for message formats)
Automatically proceed to next phase
Reverse Engineering (CONDITIONAL - Brownfield Only)
Execute IF:
Existing codebase detected
No previous reverse engineering artifacts found
Skip IF:
Greenfield project
Previous reverse engineering artifacts exist
Execution:
MANDATORY: Log start of reverse engineering in audit.md
Load all steps from `inception/reverse-engineering.md`
Execute reverse engineering:
Analyze all packages and components
Generate a business overview of the whole system covering the business transactions
Generate architecture documentation
Generate code structure documentation
Generate API documentation
Generate component inventory
Generate Interaction Diagrams depicting how business transactions are implemented across components
Generate technology stack documentation
Generate dependencies documentation
Wait for Explicit Approval: Present detailed completion message (see reverse-engineering.md for message format) - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
Requirements Analysis (ALWAYS EXECUTE - Adaptive Depth)
Always executes but depth varies based on request clarity and complexity:
Minimal: Simple, clear request - just document intent analysis
Standard: Normal complexity - gather functional and non-functional requirements
Comprehensive: Complex, high-risk - detailed requirements with traceability
Execution:
MANDATORY: Log any user input during this phase in audit.md
Load all steps from `inception/requirements-analysis.md`
Execute requirements analysis:
Load reverse engineering artifacts (if brownfield)
Analyze user request (intent analysis)
Determine requirements depth needed
Assess current requirements
Ask clarifying questions (if needed)
Generate requirements document
Execute at appropriate depth (minimal/standard/comprehensive)
Wait for Explicit Approval: Follow approval format from requirements-analysis.md detailed steps - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
User Stories (CONDITIONAL)
INTELLIGENT ASSESSMENT: Use multi-factor analysis to determine if user stories add value:
ALWAYS Execute IF (High Priority Indicators):
New user-facing features or functionality
Changes affecting user workflows or interactions
Multiple user types or personas involved
Complex business requirements with acceptance criteria needs
Cross-functional team collaboration required
Customer-facing API or service changes
New product capabilities or enhancements
LIKELY Execute IF (Medium Priority - Assess Complexity):
Modifications to existing user-facing features
Backend changes that indirectly affect user experience
Integration work that impacts user workflows
Performance improvements with user-visible benefits
Security enhancements affecting user interactions
Data model changes affecting user data or reports
COMPLEXITY-BASED ASSESSMENT: For medium priority cases, execute user stories if:
Request involves multiple components or services
Changes span multiple user touchpoints
Business logic is complex or has multiple scenarios
Requirements have ambiguity that stories could clarify
Implementation affects multiple user journeys
Change has significant business impact or risk
SKIP ONLY IF (Low Priority - Simple Cases):
Pure internal refactoring with zero user impact
Simple bug fixes with clear, isolated scope
Infrastructure changes with no user-facing effects
Technical debt cleanup with no functional changes
Developer tooling or build process improvements
Documentation-only updates
ASSESSMENT CRITERIA: When in doubt, favor inclusion of user stories for:
Requests with business stakeholder involvement
Changes requiring user acceptance testing
Features with multiple implementation approaches
Work that benefits from shared team understanding
Projects where requirements clarity is valuable
ASSESSMENT PROCESS:
Analyze request complexity and scope
Identify user impact (direct or indirect)
Evaluate business context and stakeholder needs
Consider team collaboration benefits
Default to inclusion for borderline cases
Note: If Requirements Analysis executed, Stories can reference and build upon those requirements.
User Stories has two parts within one stage:
Part 1 - Planning: Create story plan with questions, collect answers, analyze for ambiguities, get approval
Part 2 - Generation: Execute approved plan to generate stories and personas
Execution:
MANDATORY: Log any user input during this phase in audit.md
Load all steps from `inception/user-stories.md`
MANDATORY: Perform intelligent assessment (Step 1 in user-stories.md) to validate user stories are needed
Load reverse engineering artifacts (if brownfield)
If Requirements exist, reference them when creating stories
Execute at appropriate depth (minimal/standard/comprehensive)
PART 1 - Planning: Create story plan with questions, wait for user answers, analyze for ambiguities, get approval
PART 2 - Generation: Execute approved plan to generate stories and personas
Wait for Explicit Approval: Follow approval format from user-stories.md detailed steps - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
Workflow Planning (ALWAYS EXECUTE)
MANDATORY: Log any user input during this phase in audit.md
Load all steps from `inception/workflow-planning.md`
MANDATORY: Load content validation rules from `common/content-validation.md`
Load all prior context:
Reverse engineering artifacts (if brownfield)
Intent analysis
Requirements (if executed)
User stories (if executed)
Execute workflow planning:
Determine which phases to execute
Determine depth level for each phase
Create multi-package change sequence (if brownfield)
Generate workflow visualization (VALIDATE Mermaid syntax before writing)
MANDATORY: Validate all content before file creation per content-validation.md rules
Wait for Explicit Approval: Present recommendations using language from workflow-planning.md Step 9, emphasizing user control to override recommendations - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
Application Design (CONDITIONAL)
Execute IF:
New components or services needed
Component methods and business rules need definition
Service layer design required
Component dependencies need clarification
Skip IF:
Changes within existing component boundaries
No new components or methods
Pure implementation changes
Execution:
MANDATORY: Log any user input during this phase in audit.md
Load all steps from `inception/application-design.md`
Load reverse engineering artifacts (if brownfield)
Execute at appropriate depth (minimal/standard/comprehensive)
Wait for Explicit Approval: Present detailed completion message (see application-design.md for message format) - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
Units Generation (CONDITIONAL)
Execute IF:
System needs decomposition into multiple units of work
Multiple services or modules required
Complex system requiring structured breakdown
Skip IF:
Single simple unit
No decomposition needed
Straightforward single-component implementation
Execution:
MANDATORY: Log any user input during this phase in audit.md
Load all steps from `inception/units-generation.md`
Load reverse engineering artifacts (if brownfield)
Execute at appropriate depth (minimal/standard/comprehensive)
Wait for Explicit Approval: Present detailed completion message (see units-generation.md for message format) - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
---
🟢 CONSTRUCTION PHASE
Purpose: Detailed design, NFR implementation, and code generation
Focus: Determine HOW to build it
Stages in CONSTRUCTION PHASE:
Per-Unit Loop (executes for each unit):
Functional Design (CONDITIONAL, per-unit)
NFR Requirements (CONDITIONAL, per-unit)
NFR Design (CONDITIONAL, per-unit)
Infrastructure Design (CONDITIONAL, per-unit)
Code Generation (ALWAYS, per-unit)
Build and Test (ALWAYS - after all units complete)
Note: Each unit is completed fully (design + code) before moving to the next unit.
---
Per-Unit Loop (Executes for Each Unit)
For each unit of work, execute the following stages in sequence:
Functional Design (CONDITIONAL, per-unit)
Execute IF:
New data models or schemas
Complex business logic
Business rules need detailed design
Skip IF:
Simple logic changes
No new business logic
Execution:
MANDATORY: Log any user input during this stage in audit.md
Load all steps from `construction/functional-design.md`
Execute functional design for this unit
MANDATORY: Present standardized 2-option completion message as defined in functional-design.md - DO NOT use emergent 3-option behavior
Wait for Explicit Approval: User must choose between "Request Changes" or "Continue to Next Stage" - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
NFR Requirements (CONDITIONAL, per-unit)
Execute IF:
Performance requirements exist
Security considerations needed
Scalability concerns present
Tech stack selection required
Skip IF:
No NFR requirements
Tech stack already determined
Execution:
MANDATORY: Log any user input during this stage in audit.md
Load all steps from `construction/nfr-requirements.md`
Execute NFR assessment for this unit
MANDATORY: Present standardized 2-option completion message as defined in nfr-requirements.md - DO NOT use emergent behavior
Wait for Explicit Approval: User must choose between "Request Changes" or "Continue to Next Stage" - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
NFR Design (CONDITIONAL, per-unit)
Execute IF:
NFR Requirements was executed
NFR patterns need to be incorporated
Skip IF:
No NFR requirements
NFR Requirements was skipped
Execution:
MANDATORY: Log any user input during this stage in audit.md
Load all steps from `construction/nfr-design.md`
Execute NFR design for this unit
MANDATORY: Present standardized 2-option completion message as defined in nfr-design.md - DO NOT use emergent behavior
Wait for Explicit Approval: User must choose between "Request Changes" or "Continue to Next Stage" - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
Infrastructure Design (CONDITIONAL, per-unit)
Execute IF:
Infrastructure services need mapping
Deployment architecture required
Cloud resources need specification
Skip IF:
No infrastructure changes
Infrastructure already defined
Execution:
MANDATORY: Log any user input during this stage in audit.md
Load all steps from `construction/infrastructure-design.md`
Execute infrastructure design for this unit
MANDATORY: Present standardized 2-option completion message as defined in infrastructure-design.md - DO NOT use emergent behavior
Wait for Explicit Approval: User must choose between "Request Changes" or "Continue to Next Stage" - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
Code Generation (ALWAYS EXECUTE, per-unit)
Always executes for each unit
Code Generation has two parts within one stage:
Part 1 - Planning: Create detailed code generation plan with explicit steps
Part 2 - Generation: Execute approved plan to generate code, tests, and artifacts
Execution:
MANDATORY: Log any user input during this stage in audit.md
Load all steps from `construction/code-generation.md`
PART 1 - Planning: Create code generation plan with checkboxes, get user approval
PART 2 - Generation: Execute approved plan to generate code for this unit
MANDATORY: Present standardized 2-option completion message as defined in code-generation.md - DO NOT use emergent behavior
Wait for Explicit Approval: User must choose between "Request Changes" or "Continue to Next Stage" - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
---
Build and Test (ALWAYS EXECUTE)
MANDATORY: Log any user input during this phase in audit.md
Load all steps from `construction/build-and-test.md`
Generate comprehensive build and test instructions:
Build instructions for all units
Unit test execution instructions
Integration test instructions (test interactions between units)
Performance test instructions (if applicable)
Additional test instructions as needed (contract tests, security tests, e2e tests)
Create instruction files in build-and-test/ subdirectory: build-instructions.md, unit-test-instructions.md, integration-test-instructions.md, performance-test-instructions.md, build-and-test-summary.md
Wait for Explicit Approval: Ask: "Build and test instructions complete. Ready to proceed to Operations stage?" - DO NOT PROCEED until user confirms
MANDATORY: Log user's response in audit.md with complete raw input
---
🟡 OPERATIONS PHASE
Purpose: Placeholder for future deployment and monitoring workflows
Focus: How to DEPLOY and RUN it (future expansion)
Stages in OPERATIONS PHASE:
Operations (PLACEHOLDER)
---
Operations (PLACEHOLDER)
Status: This stage is currently a placeholder for future expansion.
The Operations stage will eventually include:
Deployment planning and execution
Monitoring and observability setup
Incident response procedures
Maintenance and support workflows
Production readiness checklists
Current State: All build and test activities are handled in the CONSTRUCTION phase.
Key Principles
Adaptive Execution: Only execute stages that add value
Transparent Planning: Always show execution plan before starting
User Control: User can request stage inclusion/exclusion
Progress Tracking: Update aidlc-state.md with executed and skipped stages
Complete Audit Trail: Log ALL user inputs and AI responses in audit.md with timestamps
CRITICAL: Capture user's COMPLETE RAW INPUT exactly as provided
CRITICAL: Never summarize or paraphrase user input in audit log
CRITICAL: Log every interaction, not just approvals
Quality Focus: Complex changes get full treatment, simple changes stay efficient
Content Validation: Always validate content before file creation per content-validation.md rules
NO EMERGENT BEHAVIOR: Construction phases MUST use standardized 2-option completion messages as defined in their respective rule files. DO NOT create 3-option menus or other emergent navigation patterns.
MANDATORY: Plan-Level Checkbox Enforcement
MANDATORY RULES FOR PLAN EXECUTION
NEVER complete any work without updating plan checkboxes
IMMEDIATELY after completing ANY step described in a plan file, mark that step [x]
This must happen in the SAME interaction where the work is completed
NO EXCEPTIONS: Every plan step completion MUST be tracked with checkbox updates
Two-Level Checkbox Tracking System
Plan-Level: Track detailed execution progress within each stage
Stage-Level: Track overall workflow progress in aidlc-state.md
Update immediately: All progress updates in SAME interaction where work is completed
Prompts Logging Requirements
MANDATORY: Log EVERY user input (prompts, questions, responses) with timestamp in audit.md
MANDATORY: Capture user's COMPLETE RAW INPUT exactly as provided (never summarize)
MANDATORY: Log every approval prompt with timestamp before asking the user
MANDATORY: Record every user response with timestamp after receiving it
CRITICAL: ALWAYS append changes to EDIT audit.md file, NEVER use tools and commands that completely overwrite its contents
CRITICAL: NEVER use file writing tools and commands that overwrite the entire contents of audit.md, as this causes duplication
Use ISO 8601 format for timestamps (YYYY-MM-DDTHH:MM:SSZ)
Include stage context for each entry
Audit Log Format:
```markdown
## [Stage Name or Interaction Type]
**Timestamp**: [ISO timestamp]
**User Input**: "[Complete raw user input - never summarized]"
**AI Response**: "[AI's response or action taken]"
**Context**: [Stage, action, or decision made]

---
```
Correct Tool Usage for audit.md
✅ CORRECT:
Read the audit.md file
Append/Edit the file to make changes
❌ WRONG:
Read the audit.md file
Completely overwrite the audit.md with the contents of what you read, plus the new changes you want to add to it
Directory Structure
```text
<WORKSPACE-ROOT>/                   # ⚠️ APPLICATION CODE HERE
├── [project-specific structure]    # Varies by project (see code-generation.md)
│
├── aidlc-docs/                     # 📄 DOCUMENTATION ONLY
│   ├── inception/                  # 🔵 INCEPTION PHASE
│   │   ├── plans/
│   │   ├── reverse-engineering/    # Brownfield only
│   │   ├── requirements/
│   │   ├── user-stories/
│   │   └── application-design/
│   ├── construction/               # 🟢 CONSTRUCTION PHASE
│   │   ├── plans/
│   │   ├── {unit-name}/
│   │   │   ├── functional-design/
│   │   │   ├── nfr-requirements/
│   │   │   ├── nfr-design/
│   │   │   ├── infrastructure-design/
│   │   │   └── code/               # Markdown summaries only
│   │   └── build-and-test/
│   ├── operations/                 # 🟡 OPERATIONS PHASE (placeholder)
│   ├── aidlc-state.md
│   └── audit.md
```
CRITICAL RULE:
Application code: Workspace root (NEVER in aidlc-docs/)
Documentation: aidlc-docs/ only
Project structure: See code-generation.md for patterns by project type

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.17
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests that have a lot of duplicated data. This is often the case when testing validation rules, so consider this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.

<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>

### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option; use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
