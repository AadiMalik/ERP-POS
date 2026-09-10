(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var instances = {};

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function optionHtml(value, label, selected) {
        return '<option value="' + escapeAttr(value) + '"' + (selected ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function escapeAttr(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function placeCustomizeToolbar($table, tableId, i18n) {
        var $wrapper = $table.closest('.dt-container, .dataTables_wrapper');
        if ($wrapper.find('.erp-dt-toolbar').length) {
            return;
        }
        var html =
            '<div class="erp-dt-toolbar" data-erp-attach="' + escapeAttr(tableId) + '">' +
            '<button type="button" class="btn btn-icon btn-outline-secondary erp-dt-customize-btn" title="' +
            escapeAttr((i18n && i18n.customize_table) || 'Customize Table') + '">' +
            '<i class="fa fa-sliders"></i></button></div>';
        var $searchEnd = $wrapper.find('.dt-search').closest('.dt-layout-end').first();
        if ($searchEnd.length) {
            $searchEnd.append(html);
            return;
        }
        var $host = $wrapper.length ? $wrapper : $table;
        $host.before(html);
    }

    function registerCustomizeFeature() {
        var feature = $.fn.dataTable && $.fn.dataTable.feature;
        if (!feature || typeof feature.register !== 'function') {
            return;
        }
        try {
            feature.register('erpCustomize', function (settings) {
                var i18n = window.erpDtI18n || {};
                var table = settings.nTable;
                var id = table && table.id ? table.id : '';
                var wrap = document.createElement('div');
                wrap.className = 'erp-dt-toolbar';
                if (!id || (table.closest && table.closest('.modal'))) {
                    wrap.style.display = 'none';
                    return wrap;
                }
                wrap.setAttribute('data-erp-attach', id);
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-icon btn-outline-secondary erp-dt-customize-btn';
                btn.title = i18n.customize_table || 'Customize Table';
                btn.setAttribute('aria-label', btn.title);
                btn.innerHTML = '<i class="fa fa-sliders"></i>';
                wrap.appendChild(btn);
                return wrap;
            });
        } catch (e) {
            // Already registered on a second script load.
        }
    }
    registerCustomizeFeature();

    function ErpTable(config, opts) {
        this.config = config;
        this.opts = opts || {};
        this.i18n = this.opts.i18n || {};
        this.$wrap = $('[data-erp-dt="' + config.key + '"]');
        this.tableId = this.opts.tableId;
        this.$table = $('#' + this.tableId);
        this.state = $.extend(true, {}, config.state || {});
        this.dt = null;
        this.$customize = $('#erpDtCustomizeModal-' + config.key);

        this.renderFilters();
        this.bind();
        this.initTable();
        this.bindRowActions();
    }

    ErpTable.prototype.columnsByKey = function () {
        var map = {};
        (this.config.columns || []).forEach(function (col) {
            map[col.key] = col;
        });
        return map;
    };

    ErpTable.prototype.orderedColumns = function () {
        var byKey = this.columnsByKey();
        var order = this.state.column_order || [];
        var list = [];
        order.forEach(function (key) {
            if (byKey[key]) {
                list.push(byKey[key]);
            }
        });
        Object.keys(byKey).forEach(function (key) {
            if (list.indexOf(byKey[key]) === -1) {
                list.push(byKey[key]);
            }
        });
        return list;
    };

    ErpTable.prototype.isVisible = function (key) {
        return (this.state.visible_columns || []).indexOf(key) !== -1;
    };

    ErpTable.prototype.isExportableVisible = function (col) {
        return !!col.exportable && (this.isVisible(col.key) || !!col.always);
    };

    ErpTable.prototype.visibleExportKeys = function () {
        var self = this;
        return this.orderedColumns().filter(function (col) {
            return self.isExportableVisible(col);
        }).map(function (col) {
            return col.key;
        });
    };

    ErpTable.prototype.buildDtColumns = function () {
        var self = this;
        var cols = [];
        this.orderedColumns().forEach(function (col) {
            cols.push({
                data: col.data,
                name: col.name,
                title: col.label,
                orderable: !!col.orderable,
                searchable: !!col.searchable,
                visible: self.isVisible(col.key) || !!col.always,
                defaultContent: ''
            });
        });
        return cols;
    };

    ErpTable.prototype.sortIndex = function (dtColumns) {
        var sortKey = this.state.sort_column;
        var byKey = this.columnsByKey();
        var data = byKey[sortKey] ? byKey[sortKey].data : sortKey;
        for (var i = 0; i < dtColumns.length; i++) {
            if (dtColumns[i].data === data) {
                return i;
            }
        }
        return 0;
    };

    ErpTable.prototype.collectFilters = function () {
        var filters = {};
        var self = this;
        (this.config.filters || []).forEach(function (filter) {
            var $el = self.$wrap.find('[data-erp-filter="' + filter.key + '"]');
            if (!$el.length) {
                return;
            }
            if (filter.type === 'numeric_range') {
                var min = $el.find('.erp-dt-min').val();
                var max = $el.find('.erp-dt-max').val();
                if (min || max) {
                    filters[filter.key] = { min: min, max: max };
                }
                return;
            }
            if (filter.type === 'date_range') {
                if (typeof filterStartDate !== 'undefined' && (filterStartDate || filterEndDate)) {
                    filters[filter.key] = { start: filterStartDate, end: filterEndDate };
                }
                return;
            }
            if (filter.type === 'multi_select') {
                var vals = $el.val();
                if (vals && vals.length) {
                    filters[filter.key] = vals;
                }
                return;
            }
            var value = $el.val();
            if (value !== null && value !== '') {
                filters[filter.key] = value;
            }
        });
        return filters;
    };

    ErpTable.prototype.applyFiltersToDom = function (filters) {
        var self = this;
        filters = filters || {};
        (this.config.filters || []).forEach(function (filter) {
            var value = filters[filter.key];
            var $el = self.$wrap.find('[data-erp-filter="' + filter.key + '"]');
            if (filter.type === 'numeric_range') {
                $el.find('.erp-dt-min').val(value && value.min != null ? value.min : '');
                $el.find('.erp-dt-max').val(value && value.max != null ? value.max : '');
                return;
            }
            if (filter.type === 'date_range') {
                if (value && (value.start || value.end)) {
                    filterStartDate = value.start || '';
                    filterEndDate = value.end || '';
                    if (typeof renderDateRange === 'function') {
                        renderDateRange();
                    }
                }
                return;
            }
            if (filter.type === 'multi_select') {
                $el.val(value || []).trigger('change');
                return;
            }
            $el.val(value || '').trigger('change');
        });
    };

    ErpTable.prototype.renderFilters = function () {
        var $box = this.$wrap.find('#filterSection');
        if (!$box.length) {
            return;
        }

        $box.find('select').not('#date_filter').each(function () {
            if ($.fn.select2) {
                $(this).select2();
            }
        });

        this.applyFiltersToDom(this.state.filters || {});
    };

    ErpTable.prototype.initTable = function () {
        var self = this;
        var dtColumns = this.buildDtColumns();
        var sortIdx = this.sortIndex(dtColumns);
        var sortDir = this.state.sort_dir === 'asc' ? 'asc' : 'desc';
        var dtConfig = {
            processing: true,
            serverSide: true,
            destroy: true,
            pageLength: parseInt(this.state.page_length, 10) || 10,
            order: [[sortIdx, sortDir]],
            erpEngine: true,
            layout: (typeof window.erpDtLayout === 'function' ? window.erpDtLayout() : undefined),
            columns: dtColumns,
            ajax: {
                url: this.config.urls.data,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf() },
                data: function (d) {
                    d._token = csrf();
                    d.filters = self.collectFilters();
                    d.visible_columns = self.state.visible_columns;
                    d.column_order = self.state.column_order;
                    d.sort_column = self.state.sort_column;
                    d.sort_dir = self.state.sort_dir;
                    d.page_length = self.state.page_length;
                    d.export_columns = self.state.export_columns;
                    if (typeof filterStartDate !== 'undefined') {
                        d.start_date = filterStartDate;
                        d.end_date = filterEndDate;
                    }
                }
            }
        };

        if (this.config.locale) {
            dtConfig.language = {
                url: '//cdn.datatables.net/plug-ins/2.3.8/i18n/' + this.config.locale + '.json'
            };
        }

        if (this.dt) {
            this.dt.destroy();
            this.$table.find('thead, tbody').empty();
        }

        this.dt = this.$table.DataTable(dtConfig);
        placeCustomizeToolbar(this.$table, this.tableId, this.i18n);
    };

    ErpTable.prototype.reload = function () {
        if (this.dt) {
            this.dt.ajax.reload(null, false);
        } else {
            this.initTable();
        }
    };

    ErpTable.prototype.payload = function (extra) {
        var data = {
            _token: csrf(),
            filters: this.collectFilters(),
            visible_columns: this.state.visible_columns,
            column_order: this.state.column_order,
            sort_column: this.state.sort_column,
            sort_dir: this.state.sort_dir,
            page_length: this.state.page_length,
            export_columns: this.state.export_columns
        };
        return $.extend(data, extra || {});
    };

    ErpTable.prototype.savePreferences = function (extra) {
        return $.post(this.config.urls.preferences, this.payload(extra)).done(function (res) {
            if (res && res.Data) {
                this.state = $.extend(this.state, res.Data);
            }
        }.bind(this));
    };

    ErpTable.prototype.openCustomize = function () {
        var self = this;
        var $list = this.$customize.find('.erp-dt-col-list').empty();
        var canExport = !!this.config.can_export;
        var exportSet = this.state.export_columns || [];
        this.orderedColumns().forEach(function (col) {
            var visibleChecked = self.isVisible(col.key) || col.always ? ' checked' : '';
            var visDisabled = col.always ? ' disabled' : '';
            var exportBox = '';
            if (canExport && col.exportable) {
                var exportChecked = exportSet.indexOf(col.key) !== -1 ? ' checked' : '';
                exportBox =
                    '<div class="form-check mb-0">' +
                    '<input class="form-check-input erp-dt-col-export" type="checkbox"' + exportChecked + '>' +
                    '<label class="form-check-label">' + escapeHtml(self.i18n.export_this || 'Export') + '</label>' +
                    '</div>';
            }
            $list.append(
                '<div class="erp-dt-col-item" data-key="' + escapeAttr(col.key) + '">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary erp-dt-move-up" title="' + escapeAttr(self.i18n.move_up) + '"><i class="fa fa-arrow-up"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary erp-dt-move-down" title="' + escapeAttr(self.i18n.move_down) + '"><i class="fa fa-arrow-down"></i></button>' +
                '<span class="erp-dt-col-label">' + escapeHtml(col.label) + '</span>' +
                '<div class="form-check mb-0">' +
                '<input class="form-check-input erp-dt-col-visible" type="checkbox"' + visibleChecked + visDisabled + '>' +
                '<label class="form-check-label">' + escapeHtml(self.i18n.show_column) + '</label>' +
                '</div>' +
                exportBox +
                '</div>'
            );
        });

        var $sort = this.$customize.find('.erp-dt-sort-column').empty();
        this.orderedColumns().forEach(function (col) {
            if (!col.orderable) {
                return;
            }
            $sort.append(optionHtml(col.key, col.label, col.key === self.state.sort_column));
        });
        this.$customize.find('.erp-dt-sort-dir').val(this.state.sort_dir || 'desc');
        var $len = this.$customize.find('.erp-dt-page-length').empty();
        (this.config.page_lengths || [10, 25, 50, 100]).forEach(function (n) {
            $len.append(optionHtml(n, String(n), parseInt(self.state.page_length, 10) === parseInt(n, 10)));
        });

        this.$customize.modal('show');
    };

    ErpTable.prototype.readCustomize = function () {
        var visible = [];
        var order = [];
        var exportCols = [];
        var canExport = !!this.config.can_export;
        this.$customize.find('.erp-dt-col-item').each(function () {
            var key = $(this).data('key');
            order.push(key);
            if ($(this).find('.erp-dt-col-visible').is(':checked')) {
                visible.push(key);
            }
            if (canExport && $(this).find('.erp-dt-col-export').is(':checked')) {
                exportCols.push(key);
            }
        });
        this.state.visible_columns = visible;
        this.state.column_order = order;
        this.state.export_columns = canExport ? exportCols : this.visibleExportKeys();
        this.state.sort_column = this.$customize.find('.erp-dt-sort-column').val();
        this.state.sort_dir = this.$customize.find('.erp-dt-sort-dir').val();
        this.state.page_length = parseInt(this.$customize.find('.erp-dt-page-length').val(), 10);
    };

    ErpTable.prototype.bindRowActions = function () {
        var self = this;
        var page = this.config.page || {};
        if (page.status && typeof updateStatus === 'function') {
            updateStatus({
                buttonClass: page.status.button_class,
                url: page.status.url,
                tableCallback: function () {
                    self.reload();
                }
            });
        }
        if (page.delete && typeof deleteRecord === 'function') {
            deleteRecord({
                buttonClass: page.delete.button_class,
                url: page.delete.url,
                tableCallback: function () {
                    self.reload();
                }
            });
        }
    };

    ErpTable.prototype.bind = function () {
        var self = this;

        this.$wrap.on('click', '#search_btn', function () {
            self.state.filters = self.collectFilters();
            self.savePreferences();
            self.reload();
        });
        this.$wrap.on('click', '#reset_filter', function () {
            self.state.filters = {};
            filterStartDate = '';
            filterEndDate = '';
            self.$wrap.find('#date_filter').val('');
            self.applyFiltersToDom({});
            self.savePreferences();
            self.reload();
        });

        this.$customize.on('click', '.erp-dt-move-up', function () {
            var $item = $(this).closest('.erp-dt-col-item');
            $item.prev('.erp-dt-col-item').before($item);
        });
        this.$customize.on('click', '.erp-dt-move-down', function () {
            var $item = $(this).closest('.erp-dt-col-item');
            $item.next('.erp-dt-col-item').after($item);
        });
        this.$customize.on('click', '.erp-dt-apply-btn', function () {
            self.readCustomize();
            self.$customize.modal('hide');
            self.savePreferences();
            self.initTable();
        });
        this.$customize.on('click', '.erp-dt-reset-btn', function () {
            $.post(self.config.urls.preferences, { _token: csrf(), reset: 1 }).done(function (res) {
                if (res && res.Data) {
                    self.state = res.Data;
                }
                self.$customize.modal('hide');
                self.applyFiltersToDom(self.state.filters || {});
                self.initTable();
            });
        });
    };

    ErpTable.prototype.exportQuery = function () {
        var exportCols = this.config.can_export
            ? (this.state.export_columns || [])
            : this.visibleExportKeys();
        var params = {
            export_format: 'xlsx',
            export_columns: JSON.stringify(exportCols),
            visible_columns: JSON.stringify(this.state.visible_columns || []),
            filters: JSON.stringify(this.collectFilters())
        };
        if (this.dt && this.dt.search) {
            params.search = this.dt.search();
        }
        if (typeof filterStartDate !== 'undefined') {
            params.start_date = filterStartDate;
            params.end_date = filterEndDate;
        }
        return params;
    };

    function dtI18n() {
        return window.erpDtI18n || {};
    }

    function dtPageLengths() {
        return window.erpDtPageLengths || [10, 25, 50, 100];
    }

    function preferencesUrl(key) {
        var root = (typeof url_local !== 'undefined' ? url_local : '') || '';
        return root + '/admin/datatable/' + encodeURIComponent(key) + '/preferences';
    }

    function cardHasExport($table) {
        var $card = $table.closest('.card');
        var $scope = $card.length ? $card : $table.parent();
        return $scope.find('.import-export-export-btn').length > 0;
    }

    function ensureCustomizeModal(key) {
        var id = 'erpDtCustomizeModal-' + key;
        var $modal = $('#' + id);
        if ($modal.length) {
            return $modal;
        }
        var i18n = dtI18n();
        $('body').append(
            '<div class="modal fade" id="' + id + '" tabindex="-1">' +
            '<div class="modal-dialog modal-lg modal-dialog-scrollable">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<h5 class="modal-title">' + escapeHtml(i18n.customize_table || 'Customize Table') + '</h5>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
            '</div>' +
            '<div class="modal-body">' +
            '<p class="text-muted small">' + escapeHtml(i18n.column_order || '') + '</p>' +
            '<div class="erp-dt-col-list"></div>' +
            '<div class="row g-3 mt-2">' +
            '<div class="col-md-4"><label class="form-label">' + escapeHtml(i18n.sort_column || 'Sort by') + '</label>' +
            '<select class="form-select erp-dt-sort-column"></select></div>' +
            '<div class="col-md-4"><label class="form-label">' + escapeHtml(i18n.sort_direction || 'Direction') + '</label>' +
            '<select class="form-select erp-dt-sort-dir">' +
            '<option value="asc">' + escapeHtml(i18n.ascending || 'Ascending') + '</option>' +
            '<option value="desc">' + escapeHtml(i18n.descending || 'Descending') + '</option>' +
            '</select></div>' +
            '<div class="col-md-4"><label class="form-label">' + escapeHtml(i18n.page_length || 'Rows per page') + '</label>' +
            '<select class="form-select erp-dt-page-length"></select></div>' +
            '</div></div>' +
            '<div class="modal-footer justify-content-between">' +
            '<button type="button" class="btn btn-outline-secondary erp-dt-reset-btn">' + escapeHtml(i18n.reset_layout || 'Reset') + '</button>' +
            '<div>' +
            '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">' + escapeHtml((window.i18n && window.i18n.cancel) || 'Cancel') + '</button> ' +
            '<button type="button" class="btn btn-primary erp-dt-apply-btn">' + escapeHtml(i18n.apply || 'Apply') + '</button>' +
            '</div></div></div></div></div>'
        );
        return $('#' + id);
    }

    function shouldSkipAttach(settings) {
        var table = settings.nTable;
        if (!table || !table.id) {
            return true;
        }
        if (!/^[A-Za-z0-9_-]{1,80}$/.test(table.id)) {
            return true;
        }
        var $table = $(table);
        if ($table.is('[data-erp-skip-customize]')) {
            return true;
        }
        if ($table.closest('.modal').length) {
            return true;
        }
        if (settings.oInit && settings.oInit.erpEngine) {
            return true;
        }
        if ($table.hasClass('erp-datatable') && $table.closest('[data-erp-dt]').length) {
            return true;
        }
        var hasAjax = !!(settings.oInit && settings.oInit.ajax);
        var serverSide = !!(settings.oFeatures && settings.oFeatures.bServerSide);
        if (!hasAjax && !serverSide) {
            var api = new $.fn.dataTable.Api(settings);
            if (api.rows().count() === 0) {
                return true;
            }
        }
        return false;
    }

    function AttachTable(api) {
        this.dt = api;
        this.$table = $(api.table().node());
        this.tableId = this.$table.attr('id');
        this.i18n = dtI18n();
        this.canExport = cardHasExport(this.$table);
        this.columns = this.readColumns();
        this.defaults = this.captureDefaults();
        this.state = $.extend(true, {}, this.defaults);
        this.$customize = ensureCustomizeModal(this.tableId);
        this.injectToolbar();
        this.bind();
        this.wireExportButton();
        this.loadAndApply();
    }

    AttachTable.prototype.readColumns = function () {
        var cols = [];
        var self = this;
        this.dt.columns().every(function () {
            var idx = this.index();
            var $th = $(this.header());
            var settingsCol = self.dt.settings()[0].aoColumns[idx] || {};
            var data = settingsCol.mData;
            if (data == null || data === '') {
                data = settingsCol.data;
            }
            if (typeof data === 'function' || data == null) {
                data = settingsCol.sName || ('col_' + idx);
            }
            var name = settingsCol.sName || String(data);
            var key = String(name || data);
            var label = $.trim($th.text());
            var always = false;
            var exportable = true;
            if ($th.hasClass('dt-control') || key.toLowerCase() === 'action' || /action/i.test(label)) {
                always = true;
                exportable = false;
            }
            cols.push({
                key: key,
                index: idx,
                label: label || key,
                orderable: settingsCol.bSortable !== false,
                exportable: exportable,
                always: always
            });
        });
        return cols;
    };

    AttachTable.prototype.captureDefaults = function () {
        var visible = [];
        var order = [];
        var exportCols = [];
        var self = this;
        this.columns.forEach(function (col) {
            order.push(col.key);
            if (self.dt.column(col.index).visible() || col.always) {
                visible.push(col.key);
            }
            if (col.exportable) {
                exportCols.push(col.key);
            }
        });
        var orderInfo = this.dt.order();
        var sortIdx = orderInfo && orderInfo[0] ? orderInfo[0][0] : 0;
        var sortCol = this.columns[sortIdx] ? this.columns[sortIdx].key : (this.columns[0] ? this.columns[0].key : '');
        var sortDir = orderInfo && orderInfo[0] ? orderInfo[0][1] : 'desc';
        return {
            visible_columns: visible,
            column_order: order,
            sort_column: sortCol,
            sort_dir: sortDir === 'asc' ? 'asc' : 'desc',
            page_length: parseInt(this.dt.page.len(), 10) || 10,
            export_columns: exportCols
        };
    };

    AttachTable.prototype.injectToolbar = function () {
        placeCustomizeToolbar(this.$table, this.tableId, this.i18n);
    };

    AttachTable.prototype.wireExportButton = function () {
        if (!this.canExport) {
            return;
        }
        var tableId = this.tableId;
        var $card = this.$table.closest('.card');
        $card.find('.import-export-export-btn').each(function () {
            if (!$(this).attr('data-datatable-key')) {
                $(this).attr('data-datatable-key', tableId);
            }
        });
    };

    AttachTable.prototype.columnByKey = function (key) {
        for (var i = 0; i < this.columns.length; i++) {
            if (this.columns[i].key === key) {
                return this.columns[i];
            }
        }
        return null;
    };

    AttachTable.prototype.orderedColumns = function () {
        var self = this;
        var list = [];
        (this.state.column_order || []).forEach(function (key) {
            var col = self.columnByKey(key);
            if (col) {
                list.push(col);
            }
        });
        this.columns.forEach(function (col) {
            if (list.indexOf(col) === -1) {
                list.push(col);
            }
        });
        return list;
    };

    AttachTable.prototype.isVisible = function (key) {
        return (this.state.visible_columns || []).indexOf(key) !== -1;
    };

    AttachTable.prototype.loadAndApply = function () {
        var self = this;
        $.get(preferencesUrl(this.tableId)).done(function (res) {
            if (res && res.Data && (res.Data.visible_columns || res.Data.column_order)) {
                self.state = $.extend(true, {}, self.defaults, res.Data);
            }
            self.applyToDt();
        }).fail(function () {
            self.applyToDt();
        });
    };

    AttachTable.prototype.applyToDt = function () {
        var self = this;
        this.columns.forEach(function (col) {
            var show = self.isVisible(col.key) || !!col.always;
            self.dt.column(col.index).visible(show, false);
        });
        this.dt.columns.adjust();
        var pageLength = parseInt(this.state.page_length, 10);
        if (pageLength) {
            this.dt.page.len(pageLength);
        }
        var sortCol = this.columnByKey(this.state.sort_column);
        if (sortCol && sortCol.orderable) {
            this.dt.order([[sortCol.index, this.state.sort_dir === 'asc' ? 'asc' : 'desc']]);
        }
        this.dt.draw(false);
    };

    AttachTable.prototype.openCustomize = function () {
        var self = this;
        var i18n = this.i18n;
        var $list = this.$customize.find('.erp-dt-col-list').empty();
        var exportSet = this.state.export_columns || [];
        this.orderedColumns().forEach(function (col) {
            var visibleChecked = self.isVisible(col.key) || col.always ? ' checked' : '';
            var visDisabled = col.always ? ' disabled' : '';
            var exportBox = '';
            if (self.canExport && col.exportable) {
                var exportChecked = exportSet.indexOf(col.key) !== -1 ? ' checked' : '';
                exportBox =
                    '<div class="form-check mb-0">' +
                    '<input class="form-check-input erp-dt-col-export" type="checkbox"' + exportChecked + '>' +
                    '<label class="form-check-label">' + escapeHtml(i18n.export_this || 'Export') + '</label>' +
                    '</div>';
            }
            $list.append(
                '<div class="erp-dt-col-item" data-key="' + escapeAttr(col.key) + '">' +
                '<span class="erp-dt-col-label">' + escapeHtml(col.label) + '</span>' +
                '<div class="form-check mb-0">' +
                '<input class="form-check-input erp-dt-col-visible" type="checkbox"' + visibleChecked + visDisabled + '>' +
                '<label class="form-check-label">' + escapeHtml(i18n.show_column || 'Show') + '</label>' +
                '</div>' +
                exportBox +
                '</div>'
            );
        });
        var $sort = this.$customize.find('.erp-dt-sort-column').empty();
        this.orderedColumns().forEach(function (col) {
            if (!col.orderable) {
                return;
            }
            $sort.append(optionHtml(col.key, col.label, col.key === self.state.sort_column));
        });
        this.$customize.find('.erp-dt-sort-dir').val(this.state.sort_dir || 'desc');
        var $len = this.$customize.find('.erp-dt-page-length').empty();
        dtPageLengths().forEach(function (n) {
            $len.append(optionHtml(n, String(n), parseInt(self.state.page_length, 10) === parseInt(n, 10)));
        });
        this.$customize.modal('show');
    };

    AttachTable.prototype.readCustomize = function () {
        var visible = [];
        var order = [];
        var exportCols = [];
        this.$customize.find('.erp-dt-col-item').each(function () {
            var key = $(this).data('key');
            order.push(key);
            if ($(this).find('.erp-dt-col-visible').is(':checked')) {
                visible.push(key);
            }
            if ($(this).find('.erp-dt-col-export').is(':checked')) {
                exportCols.push(key);
            }
        });
        this.state.visible_columns = visible;
        this.state.column_order = order;
        if (this.canExport) {
            this.state.export_columns = exportCols;
        }
        this.state.sort_column = this.$customize.find('.erp-dt-sort-column').val();
        this.state.sort_dir = this.$customize.find('.erp-dt-sort-dir').val();
        this.state.page_length = parseInt(this.$customize.find('.erp-dt-page-length').val(), 10);
    };

    AttachTable.prototype.savePreferences = function (extra) {
        var data = {
            _token: csrf(),
            visible_columns: this.state.visible_columns,
            column_order: this.state.column_order,
            sort_column: this.state.sort_column,
            sort_dir: this.state.sort_dir,
            page_length: this.state.page_length,
            export_columns: this.state.export_columns
        };
        return $.post(preferencesUrl(this.tableId), $.extend(data, extra || {})).done(function (res) {
            if (res && res.Data && (res.Data.visible_columns || extra && extra.reset)) {
                this.state = $.extend(this.state, res.Data);
            }
        }.bind(this));
    };

    AttachTable.prototype.exportQuery = function () {
        return {
            export_format: 'xlsx',
            export_columns: JSON.stringify(this.state.export_columns || []),
            visible_columns: JSON.stringify(this.state.visible_columns || [])
        };
    };

    AttachTable.prototype.bind = function () {
        var self = this;
        this.$customize.off('click.erpdt').on('click.erpdt', '.erp-dt-apply-btn', function () {
            self.readCustomize();
            self.$customize.modal('hide');
            self.savePreferences();
            self.applyToDt();
        });
        this.$customize.on('click.erpdt', '.erp-dt-reset-btn', function () {
            self.savePreferences({ reset: 1 }).done(function () {
                self.state = $.extend(true, {}, self.defaults);
                self.$customize.modal('hide');
                self.applyToDt();
            });
        });
    };

    function attachFromSettings(settings) {
        if (shouldSkipAttach(settings)) {
            return;
        }
        var api = new $.fn.dataTable.Api(settings);
        var id = $(api.table().node()).attr('id');
        if (instances[id] && typeof instances[id].applyToDt === 'function') {
            instances[id].dt = api;
            instances[id].columns = instances[id].readColumns();
            instances[id].injectToolbar();
            window.setTimeout(function () {
                if (instances[id] && typeof instances[id].applyToDt === 'function') {
                    instances[id].applyToDt();
                }
            }, 0);
            return;
        }
        instances[id] = new AttachTable(api);
    }

    $(document).on('init.dt', function (e, settings) {
        if (e.namespace !== 'dt') {
            return;
        }
        attachFromSettings(settings);
    });

    $(document).on('click', '.erp-dt-customize-btn', function () {
        var $btn = $(this);
        var id = $btn.closest('[data-erp-attach]').attr('data-erp-attach');
        if (!id) {
            id = $btn.closest('[data-erp-dt]').attr('data-erp-dt');
        }
        if (!id) {
            id = $btn.closest('.dt-container, .dataTables_wrapper').find('table[id]').first().attr('id');
        }
        if (instances[id] && typeof instances[id].openCustomize === 'function') {
            instances[id].openCustomize();
        }
    });

    window.ErpDataTable = {
        init: function (config, opts) {
            if (!config || !config.key) {
                return null;
            }
            var table = new ErpTable(config, opts);
            instances[config.key] = table;
            if (opts && opts.tableId) {
                instances[opts.tableId] = table;
            }
            return table;
        },
        reload: function (key) {
            if (instances[key] && instances[key].reload) {
                instances[key].reload();
            }
        },
        instance: function (key) {
            return instances[key] || null;
        }
    };
})(window, window.jQuery);
