import DOMPurify from 'dompurify';
import { marked } from 'marked';

// Create a configured marked instance
const markdownParser = marked;
markdownParser.setOptions({
    breaks: true,
    gfm: true,
    headerIds: false,
    mangle: false,
});

/**
 * Parse markdown to HTML with sanitization
 */
export function parseMarkdown(markdown: string): string {
    if (!markdown || !markdown.trim()) {
        return '';
    }

    const html = markdownParser.parse(markdown);

    return DOMPurify.sanitize(html, {
        ADD_ATTR: ['target', 'rel'],
        ADD_TAGS: ['details', 'summary'],
    });
}

/**
 * Parse markdown incrementally for streaming - closes unclosed elements
 */
export function parseMarkdownStreaming(markdown: string): string {
    if (!markdown || !markdown.trim()) {
        return '';
    }

    // Try to close unclosed code blocks
    const codeBlockCount = (markdown.match(/```/g) || []).length;
    let processed = markdown;

    if (codeBlockCount % 2 !== 0) {
        processed += '\n```';
    }

    // Try to close unclosed bold/italic
    const boldCount = (markdown.match(/\*\*/g) || []).length;

    if (boldCount % 2 !== 0) {
        processed += '**';
    }

    const italicCount = (markdown.match(/(?<!\*)\*(?!\*)/g) || []).length;

    if (italicCount % 2 !== 0) {
        processed += '*';
    }

    const html = markdownParser.parse(processed);

    return DOMPurify.sanitize(html, {
        ADD_ATTR: ['target', 'rel'],
        ADD_TAGS: ['details', 'summary'],
    });
}
