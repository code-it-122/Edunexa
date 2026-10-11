// assets/js/app.js - EduNexa Application Shell & CASPA AI Controller

$(document).ready(function () {
    // 1. CASPA AI Assistant Drawer Toggle
    $('#btnToggleCaspa, #btnBottomCaspa, .trigger-caspa').on('click', function (e) {
        e.preventDefault();
        $('#caspaDrawer').toggleClass('show');
        if ($('#caspaDrawer').hasClass('show')) {
            loadCaspaPrompts();
            $('#caspaQueryInput').focus();
        }
    });

    $('#btnCloseCaspa, #caspaBackdrop').on('click', function () {
        $('#caspaDrawer').removeClass('show');
    });

    // 2. Load Role-Specific CASPA Suggested Prompts
    function loadCaspaPrompts() {
        $.ajax({
            url: 'api/caspa.php?action=prompts',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.prompts && res.prompts.length > 0) {
                    let html = '';
                    res.prompts.slice(0, 3).forEach(function (p) {
                        html += `<button type="button" class="btn-prompt-chip">${p}</button>`;
                    });
                    $('#caspaSuggestedChips').html(html);
                }
            }
        });
    }

    // Click suggested prompt chip
    $(document).on('click', '.btn-prompt-chip', function () {
        const text = $(this).text();
        sendCaspaMessage(text);
    });

    // Submit CASPA Query
    $('#caspaChatForm').on('submit', function (e) {
        e.preventDefault();
        const text = $('#caspaQueryInput').val().trim();
        if (text) {
            sendCaspaMessage(text);
            $('#caspaQueryInput').val('');
        }
    });

    function sendCaspaMessage(query) {
        const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        // Append User Message
        const userHtml = `
            <div class="caspa-msg user">
                <div class="msg-bubble">${escapeHtml(query)}</div>
                <div class="msg-time">${now}</div>
            </div>
        `;
        $('#caspaMessageFeed').append(userHtml);
        scrollCaspaToBottom();

        // Show thinking indicator
        const thinkingHtml = `
            <div class="caspa-msg ai" id="caspaThinking">
                <div class="msg-bubble" style="color: var(--caspa-purple);">
                    <span class="spinner-border spinner-border-sm me-2"></span>CASPA is analyzing permissions & generating response...
                </div>
            </div>
        `;
        $('#caspaMessageFeed').append(thinkingHtml);
        scrollCaspaToBottom();

        $.ajax({
            url: 'api/caspa.php?action=chat',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ query: query }),
            dataType: 'json',
            success: function (res) {
                $('#caspaThinking').remove();
                const aiHtml = `
                    <div class="caspa-msg ai">
                        <div class="msg-bubble">${formatAiText(res.response)}</div>
                        <div class="msg-time">${res.timestamp} • <span class="text-purple fw-bold">AI Verified</span></div>
                    </div>
                `;
                $('#caspaMessageFeed').append(aiHtml);
                scrollCaspaToBottom();
            },
            error: function () {
                $('#caspaThinking').remove();
                $('#caspaMessageFeed').append(`
                    <div class="caspa-msg ai text-danger">
                        <div class="msg-bubble">Unable to reach CASPA AI server. Please try again.</div>
                    </div>
                `);
                scrollCaspaToBottom();
            }
        });
    }

    function scrollCaspaToBottom() {
        const feed = document.getElementById('caspaMessageFeed');
        if (feed) feed.scrollTop = feed.scrollHeight;
    }

    // 3. Command Palette (Ctrl+K)
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            $('#commandPaletteModal').modal('toggle');
            $('#cmdSearchInput').focus();
        }
    });

    $('#btnOpenCommandSearch').on('click', function () {
        $('#commandPaletteModal').modal('show');
        $('#cmdSearchInput').focus();
    });

    $('#cmdSearchInput').on('keyup', function () {
        const q = $(this).val().toLowerCase();
        $('.cmd-item').each(function () {
            const title = $(this).find('.cmd-title').text().toLowerCase();
            const tag = $(this).find('.cmd-tag').text().toLowerCase();
            if (title.includes(q) || tag.includes(q)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    function escapeHtml(text) {
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    function formatAiText(text) {
        // Simple markdown bold formatting
        return text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
    }
});
