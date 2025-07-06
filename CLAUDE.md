x ## Core Development Principles

Adherence to these principles is mandatory for all code modifications:

*   **Simplicity, Clarity & Conciseness:** Write only necessary code.
*   **Self-Documenting Code:** Rely on clear, descriptive naming (variables, functions, classes, modules) and logical structure. The purpose should be evident without comments.
*   **Minimal Comments:** Avoid comments. If you see them, remove them. The code itself must be the single source of truth.
*   **Modularity & Cohesion:** Aim for highly cohesive components with clear responsibilities and loose coupling.
*   **DRY (Don't Repeat Yourself):** Extract and reuse common logic patterns.
*   **Dependency Management:** Always prefer constructor injection. Avoid direct creation of complex services within consumers.
*   **Maximum 400 lines per file:** Keep files modular and focused. If a file exceeds 400 lines during your work, refactor it by breaking down the logic according to the principles above. Never append to a file over 400 lines without the user's express permission.
*   **Verify Line Counts:** After completing your tasks, use a command like `find . -name "*.py" -type f -print0 | xargs -0 wc -l` to check the line counts of files you have modified. If any exceed 400 lines, you must refactor them.
*   **Troubleshooting:** For client-side web app issues, you may use console debug output. Ask the user to fetch the console messages from their browser's developer tools; they are familiar with this.

## Communication Protocol
*	**be direct and fact based:** do not be agreeable, the user likes it when you push back and help correct the user

## Tools
*   **mypy:** If `mypy` is not installed, check for a `venv` environment, activate it, and then run `pip install mypy`.
*   **context7 - mcp:** Use this for up-to-date documentation if you need a reference.
*   **playwright - mcp:** whenever you need to use a browser for tests etc, use this

## When You Get Stuck: Using the Gemini CLI for Targeted Analysis

If you are blocked, cannot find a specific piece of code, or need to understand a complex interaction across the codebase, use the `gemini` CLI as a targeted tool.

**CRITICAL: `gemini -p` is STATELESS.** Each command is a new, isolated query. It does not remember past interactions. You cannot ask follow-up questions. You must provide all necessary context in a single command.

### How to Use the Gemini CLI:

1.  **Formulate a Specific Question:** Determine the *exact* information you need to unblock yourself. Avoid general questions like "summarize the code."
2.  **Identify Relevant Source Directories:** Scope your query to the most relevant top-level directories (e.g., `@src`, `@api`, `@lib`). Do **not** use `@./` unless absolutely necessary.
3.  **Construct and Run the Command:** Combine the directories and your specific question into a single `gemini -p` command.

#### Usage Examples:

*   **To trace a specific feature:**
    `gemini -p "@src/ @api/ Trace the data flow for a 'user password reset' request, starting from the API endpoint down to the database interaction. What services are involved?"`

*   **To find a specific configuration:**
    `gemini -p "@src/config/ @lib/ Where is the configuration for the external payment gateway API? Show me the file and the relevant lines."`

*   **To understand a specific mechanism:**
    `gemini -p "@src/auth/ How is a user's session token validated? Show me the middleware or function responsible for this check."`

Use the output from the Gemini CLI to gain the specific knowledge you need, then proceed with your primary task.

====-
## Project Specific Instructions Below

read readme.txt