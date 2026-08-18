// ======================================================
// GLOBAL HEADER SEARCH
// ======================================================
(function () {
    var $input = $('#globalSearchInput');
    var $dropdown = $('#globalSearchDropdown');

    if (!$input.length || !$dropdown.length) return;

    var searchUrl = $input.data('search-url');
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var searchTimer = null;
    var activeRequest = null;
    var activeIndex = -1;
    var requestToken = 0;

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function showDropdown() {
        $dropdown.removeClass('d-none');
    }

    function hideDropdown() {
        $dropdown.addClass('d-none').empty();
        activeIndex = -1;
        requestToken++; // invalidate any in-flight/pending response
        if (activeRequest) {
            activeRequest.abort();
        }
    }

    function renderLoading() {
        $dropdown.html('<div class="search-state-message"><i class="fa fa-spinner fa-spin me-1"></i> Searching...</div>');
        showDropdown();
    }

    function renderEmpty(term) {
        $dropdown.html('<div class="search-state-message">No results found for "' + escapeHtml(term) + '"</div>');
        showDropdown();
    }

    function renderGroups(groups) {
        if (!groups || !groups.length) {
            renderEmpty($input.val().trim());
            return;
        }

        var html = '';
        groups.forEach(function (group) {
            html += '<div class="search-group-label">' + escapeHtml(group.label) + ' (' + group.count + ')</div>';
            (group.results || []).forEach(function (result) {
                html += '<a href="' + result.url + '" class="search-result-item">' +
                    '<i class="fa ' + escapeHtml(group.icon) + '"></i>' +
                    '<div class="search-result-text">' +
                        '<div class="search-result-title">' + escapeHtml(result.title) + '</div>' +
                        (result.subtitle ? '<div class="search-result-subtitle">' + escapeHtml(result.subtitle) + '</div>' : '') +
                    '</div>' +
                '</a>';
            });
            if (group.more > 0) {
                html += '<div class="search-more-note">+' + group.more + ' more ' + escapeHtml(group.label).toLowerCase() + ' not shown - refine your search</div>';
            }
        });

        $dropdown.html(html);
        activeIndex = -1;
        showDropdown();
    }

    function performSearch(term) {
        if (activeRequest) {
            activeRequest.abort();
        }

        renderLoading();

        var thisToken = ++requestToken;

        activeRequest = $.ajax({
            url: searchUrl,
            type: 'GET',
            data: { q: term },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                if (thisToken !== requestToken) return; // superseded by a newer keystroke
                if (response && response.Success) {
                    renderGroups(response.Data.groups);
                } else {
                    renderEmpty(term);
                }
            },
            error: function (xhr) {
                if (thisToken !== requestToken || xhr.statusText === 'abort') return;
                $dropdown.html('<div class="search-state-message text-danger">Search failed. Please try again.</div>');
                showDropdown();
            },
            complete: function () {
                activeRequest = null;
            }
        });
    }

    $input.on('input', function () {
        var term = $(this).val().trim();
        clearTimeout(searchTimer);

        if (term.length < 2) {
            hideDropdown();
            return;
        }

        searchTimer = setTimeout(function () {
            performSearch(term);
        }, 300);
    });

    $input.on('focus', function () {
        var term = $(this).val().trim();
        if (term.length >= 2 && $dropdown.children().length) {
            showDropdown();
        }
    });

    $input.on('keydown', function (e) {
        var $items = $dropdown.find('.search-result-item');
        if (!$items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, $items.length - 1);
            $items.removeClass('active').eq(activeIndex).addClass('active')[0].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            $items.removeClass('active').eq(activeIndex).addClass('active')[0].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var $target = activeIndex >= 0 ? $items.eq(activeIndex) : $items.eq(0);
            var url = $target.attr('href');
            if (url) window.location.href = url;
        } else if (e.key === 'Escape') {
            hideDropdown();
            $input.blur();
        }
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#globalSearchWrapper').length) {
            hideDropdown();
        }
    });
})();
