/**
 * Swap & Review module — client-side, event-driven scripts (Barry).
 * Vanilla JavaScript only, no libraries. Loaded on public/swaps.php and
 * public/details.php (see the <script> tag added near the end of those
 * pages' markup / includes/footer.php).
 *
 * Responsibilities:
 *   1. Confirm dialogs before irreversible actions (decline, withdraw,
 *      complete, delete) — anything with a [data-confirm] attribute.
 *   2. Client-side validation on the review form (a star rating must be
 *      picked) and the swap request / comment forms (no blank/whitespace-only
 *      submissions), so the user gets instant feedback instead of a
 *      round-trip to the server.
 *   3. A live remaining-character counter on textareas with maxlength, so
 *      people can see the 500/1000 char cap as they type.
 */

document.addEventListener('DOMContentLoaded', function () {
    attachConfirmGuards();
    attachReviewFormGuards();
    attachTextRequiredGuards();
    attachCharCounters();
});

/**
 * Any form whose submit button carries [data-confirm="..."] gets a
 * window.confirm() gate before it's allowed to submit. Cancelling the
 * dialog stops the form from posting at all.
 */
function attachConfirmGuards() {
    var confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            var message = button.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
}

/**
 * The star-rating widget is a set of radio inputs, and `required` on a
 * radio group already stops empty submission in every modern browser —
 * but the rating fieldset styles the inputs invisibly (see style.css),
 * which can make the native validation bubble land in an odd spot. This
 * adds a clearer inline message instead of relying on that bubble alone.
 */
function attachReviewFormGuards() {
    var reviewForms = document.querySelectorAll('.review-form');
    reviewForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var picked = form.querySelector('input[name="rating"]:checked');
            if (!picked) {
                event.preventDefault();
                showInlineError(form, 'Please select a star rating before submitting.');
            }
        });
    });
}

/**
 * Stops the swap-request message and comment forms from submitting
 * whitespace-only text where the field is meant to carry content (the
 * comment box is `required` in HTML, but a user can still type only
 * spaces — this catches that case client-side before the server does).
 */
function attachTextRequiredGuards() {
    var commentForms = document.querySelectorAll('.comment-form');
    commentForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var textarea = form.querySelector('textarea[name="comment_text"]');
            if (textarea && textarea.value.trim() === '') {
                event.preventDefault();
                showInlineError(form, 'Comment cannot be empty.');
                textarea.focus();
            }
        });
    });
}

/**
 * Adds a small "X characters left" hint under every textarea that
 * declares a maxlength, updating on every keystroke (the "input" event).
 */
function attachCharCounters() {
    var textareasWithLimit = document.querySelectorAll('textarea[maxlength]');
    textareasWithLimit.forEach(function (textarea) {
        if (textarea.dataset.charCounterAttached === 'true') {
            return;
        }

        var max = parseInt(textarea.getAttribute('maxlength'), 10);
        if (!max) {
            return;
        }

        var counter = document.createElement('p');
        counter.className = 'char-counter';
        textarea.insertAdjacentElement('afterend', counter);
        textarea.dataset.charCounterAttached = 'true';

        var updateCounter = function () {
            var remaining = max - textarea.value.length;
            counter.textContent = remaining + ' characters left';
            counter.classList.toggle('char-counter-low', remaining <= 20);
        };

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
}

/**
 * Renders a one-line error message directly above the offending form,
 * removing any previous message from a prior failed attempt first.
 */
function showInlineError(form, message) {
    var existing = form.querySelector('.js-inline-error');
    if (existing) {
        existing.remove();
    }
    var errorEl = document.createElement('p');
    errorEl.className = 'js-inline-error';
    errorEl.textContent = message;
    form.insertAdjacentElement('afterbegin', errorEl);
}
