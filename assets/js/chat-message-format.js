(function (global) {
    'use strict';

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function normalizeAiReply(text) {
        return String(text || '')
            .trim()
            .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/gi, '$1: $2')
            .replace(/\*\*([^*]+)\*\*/g, '$1')
            .replace(/\*([^*]+)\*/g, '$1')
            .replace(/\n{3,}/g, '\n\n');
    }

    function toHtml(text) {
        var raw = String(text || '').trim();
        if (raw === '') {
            return '';
        }

        var linkTokens = {};
        var tokenIndex = 0;
        var withTokens = raw.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/gi, function (_match, label, url) {
            var token = '{{LBCHATLINK' + tokenIndex + '}}';
            linkTokens[token] = { label: label, url: url };
            tokenIndex += 1;
            return token;
        });

        var escaped = escapeHtml(withTokens);
        escaped = escaped.replace(/(https?:\/\/[^\s<>&"']+)/gi, function (match) {
            var url = match.replace(/[.,!?]+$/, '');
            var suffix = match.slice(url.length);
            return (
                '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer" class="chat-link">' +
                escapeHtml(url) +
                '</a>' +
                escapeHtml(suffix)
            );
        });

        Object.keys(linkTokens).forEach(function (token) {
            var link = linkTokens[token];
            var anchor =
                '<a href="' + escapeHtml(link.url) + '" target="_blank" rel="noopener noreferrer" class="chat-link">' +
                escapeHtml(link.label) +
                '</a>';
            escaped = escaped.replace(escapeHtml(token), anchor);
        });

        return escaped.replace(/\n/g, '<br>');
    }

    global.LeadBirdChatFormat = {
        normalize: normalizeAiReply,
        toHtml: toHtml,
    };
})(window);
