(function (window) {
    'use strict';

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function getSelectedRange(textarea) {
        const start = textarea.selectionStart ?? textarea.value.length;
        const end = textarea.selectionEnd ?? start;
        return { start, end };
    }

    function ensureTextarea(textarea) {
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return null;
        }
        return textarea;
    }

    class MarkdownEditorInstance {
        constructor(config) {
            this.textareaId = config.textareaId;
            this.previewId = config.previewId;
            this.writeToggleId = config.writeToggleId;
            this.previewToggleId = config.previewToggleId;
            this.mode = 'write';
            this.previewRenderer = typeof config.previewRenderer === 'function'
                ? config.previewRenderer
                : (markdownText) => {
                    if (typeof window.renderMarkdownToHtml === 'function') {
                        return window.renderMarkdownToHtml(markdownText || '');
                    }
                    return markdownText || '';
                };

            this.registerViewToggle();
        }

        get textarea() {
            return ensureTextarea(document.getElementById(this.textareaId));
        }

        get preview() {
            return document.getElementById(this.previewId);
        }

        registerViewToggle() {
            const writeToggle = this.writeToggleId ? document.getElementById(this.writeToggleId) : null;
            const previewToggle = this.previewToggleId ? document.getElementById(this.previewToggleId) : null;

            if (writeToggle) {
                writeToggle.addEventListener('click', () => this.setMode('write'));
            }

            if (previewToggle) {
                previewToggle.addEventListener('click', () => this.setMode('preview'));
            }
        }

        setMode(mode) {
            const normalized = mode === 'preview' ? 'preview' : 'write';
            this.mode = normalized;

            const textarea = this.textarea;
            const preview = this.preview;
            const writeToggle = this.writeToggleId ? document.getElementById(this.writeToggleId) : null;
            const previewToggle = this.previewToggleId ? document.getElementById(this.previewToggleId) : null;
            const isPreview = normalized === 'preview';

            if (writeToggle && previewToggle) {
                writeToggle.classList.toggle('active', !isPreview);
                previewToggle.classList.toggle('active', isPreview);
            }

            if (textarea && preview) {
                textarea.classList.toggle('d-none', isPreview);
                preview.classList.toggle('d-none', !isPreview);
                if (isPreview) {
                    this.updatePreview();
                } else {
                    textarea.focus();
                }
            }
        }

        updatePreview() {
            const textarea = this.textarea;
            const preview = this.preview;
            if (!textarea || !preview) {
                return;
            }

            preview.innerHTML = this.previewRenderer(textarea.value || '');
        }

        handleInput() {
            if (this.mode === 'preview') {
                this.updatePreview();
            }
        }

        applyWrap(prefix, suffix, placeholder = '') {
            const textarea = this.textarea;
            if (!textarea) {
                return;
            }

            const { start, end } = getSelectedRange(textarea);
            let selectedText = textarea.value.substring(start, end);
            if (!selectedText) {
                selectedText = placeholder;
            }

            const replacement = `${prefix}${selectedText}${suffix}`;
            textarea.setRangeText(replacement, start, end, 'end');
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.focus();
        }

        applyBlock(prefix, suffix, placeholder = '') {
            const textarea = this.textarea;
            if (!textarea) {
                return;
            }

            const { start, end } = getSelectedRange(textarea);
            let selectedText = textarea.value.substring(start, end);
            if (!selectedText) {
                selectedText = placeholder;
            }

            const needsLeadingNewline = start > 0 && textarea.value[start - 1] !== '\n';
            const needsTrailingNewline = end < textarea.value.length && textarea.value[end] !== '\n';

            const leading = needsLeadingNewline ? '\n' : '';
            const trailing = needsTrailingNewline ? '\n' : '';

            const replacement = `${leading}${prefix}${selectedText}${suffix}${trailing}`;
            textarea.setRangeText(replacement, start, end, 'end');
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.focus();
        }

        applyHeading(level) {
            const textarea = this.textarea;
            if (!textarea) {
                return;
            }

            const safeLevel = clamp(level, 1, 6);
            const { start, end } = getSelectedRange(textarea);
            const value = textarea.value;

            const lineStart = value.lastIndexOf('\n', start - 1) + 1;
            let lineEndIndex = value.indexOf('\n', end);
            if (lineEndIndex === -1) {
                lineEndIndex = value.length;
            }

            const selectedText = value.substring(lineStart, lineEndIndex);
            const lines = selectedText.split(/\r?\n/);
            const prefix = '#'.repeat(safeLevel) + ' ';

            const transformed = lines.map((line) => {
                if (!line.trim()) {
                    return line;
                }

                const trimmed = line.trimStart().replace(/^#{1,6}\s+/, '');
                const leadingWhitespace = line.substring(0, line.length - trimmed.length);
                return leadingWhitespace + prefix + trimmed;
            }).join('\n');

            textarea.setRangeText(transformed, lineStart, lineEndIndex, 'select');
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.focus();
        }

        applyPrefix(prefix) {
            const textarea = this.textarea;
            if (!textarea) {
                return;
            }

            const { start, end } = getSelectedRange(textarea);
            const value = textarea.value;

            const selectionStart = value.lastIndexOf('\n', start - 1) + 1;
            let selectionEnd = value.indexOf('\n', end);
            if (selectionEnd === -1) {
                selectionEnd = value.length;
            }

            const selectedText = value.substring(selectionStart, selectionEnd);
            const lines = selectedText.split(/\r?\n/);

            const transformed = lines.map((line) => {
                if (!line.trim()) {
                    return line;
                }

                const trimmed = line.trimStart();
                const leadingWhitespace = line.substring(0, line.length - trimmed.length);
                if (trimmed.startsWith(prefix)) {
                    return line;
                }

                return leadingWhitespace + prefix + trimmed;
            }).join('\n');

            textarea.setRangeText(transformed, selectionStart, selectionEnd, 'select');
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.focus();
        }

        applyList(type) {
            const textarea = this.textarea;
            if (!textarea) {
                return;
            }

            const { start, end } = getSelectedRange(textarea);
            const value = textarea.value;

            const selectionStart = value.lastIndexOf('\n', start - 1) + 1;
            let selectionEnd = value.indexOf('\n', end);
            if (selectionEnd === -1) {
                selectionEnd = value.length;
            }

            const selectedText = value.substring(selectionStart, selectionEnd);
            const lines = selectedText.split(/\r?\n/);

            const transformed = lines.map((line, index) => {
                if (!line.trim()) {
                    return line;
                }

                let trimmed = line.trimStart();
                const leadingWhitespace = line.substring(0, line.length - trimmed.length);

                if (type === 'ordered') {
                    trimmed = trimmed.replace(/^\d+\.\s+/, '');
                    return `${leadingWhitespace}${index + 1}. ${trimmed}`;
                }

                if (type === 'task') {
                    trimmed = trimmed.replace(/^[-*+]\s+\[[ xX]?\]\s+/, '');
                    return `${leadingWhitespace}- [ ] ${trimmed}`;
                }

                trimmed = trimmed.replace(/^[-*+]\s+/, '');
                return `${leadingWhitespace}- ${trimmed}`;
            }).join('\n');

            textarea.setRangeText(transformed, selectionStart, selectionEnd, 'select');
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.focus();
        }

        insertLink() {
            const textarea = this.textarea;
            if (!textarea) {
                return;
            }

            const { start, end } = getSelectedRange(textarea);
            const selectedText = textarea.value.substring(start, end) || 'link text';
            const urlPlaceholder = 'https://example.com';
            const replacement = `[${selectedText}](${urlPlaceholder})`;

            textarea.setRangeText(replacement, start, end, 'end');
            const cursorPosition = start + replacement.length - (urlPlaceholder.length + 1);
            textarea.setSelectionRange(cursorPosition, cursorPosition + urlPlaceholder.length);
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.focus();
        }

        insertImage() {
            const textarea = this.textarea;
            if (!textarea) {
                return;
            }

            const { start, end } = getSelectedRange(textarea);
            const selectedText = textarea.value.substring(start, end) || 'alt text';
            const urlPlaceholder = 'https://example.com/image.png';
            const replacement = `![${selectedText}](${urlPlaceholder})`;

            textarea.setRangeText(replacement, start, end, 'end');
            const cursorPosition = start + replacement.length - (urlPlaceholder.length + 1);
            textarea.setSelectionRange(cursorPosition, cursorPosition + urlPlaceholder.length);
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.focus();
        }

        insertHorizontalRule() {
            const textarea = this.textarea;
            if (!textarea) {
                return;
            }

            const { start, end } = getSelectedRange(textarea);
            textarea.setRangeText('\n\n---\n\n', start, end, 'end');
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.focus();
        }

        insertTable() {
            const textarea = this.textarea;
            if (!textarea) {
                return;
            }

            const { start, end } = getSelectedRange(textarea);
            const tableTemplate = '\n\n| Column 1 | Column 2 | Column 3 |\n| --- | --- | --- |\n| Row 1 | Data | Data |\n\n';
            textarea.setRangeText(tableTemplate, start, end, 'end');
            const cursorPosition = start + tableTemplate.indexOf('Row 1');
            textarea.setSelectionRange(cursorPosition, cursorPosition + 'Row 1'.length);
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.focus();
        }
    }

    window.MarkdownEditor = {
        create: function create(config) {
            return new MarkdownEditorInstance(config);
        },
    };
})(window);
