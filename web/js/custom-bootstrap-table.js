(function($) {
    'use strict';

    var BootstrapTable = $.fn.bootstrapTable.Constructor,
        _initBody = BootstrapTable.prototype.initBody,
        _init = BootstrapTable.prototype.init,
        _horizontalScroll = BootstrapTable.prototype.horizontalScroll,
        _onColumnSearch = BootstrapTable.prototype.onColumnSearch
    ;

    BootstrapTable.prototype.init = function () {
        _init.apply(this, Array.prototype.slice.apply(arguments));

        var that = this;

        this.$el.on('load-success.bs.table', function () {
            if (localStorage.filterColumnsDefaults) {
                var filterColumnsDefaults = JSON.parse(localStorage.filterColumnsDefaults);
                that.$fixedHeader.find('input, select').each(function () {
                    var field = $(this).closest('[data-field]').data('field');

                    if (filterColumnsDefaults.hasOwnProperty(field)) {
                        $(this).val(filterColumnsDefaults[field]);
                    }
                });
                that.initColumnSearch(filterColumnsDefaults);
                that.initSearch();
                that.updatePagination();
            }
        });

    };

    $.fn.bootstrapTable.methods.push('updatePlanData');
    BootstrapTable.prototype.updatePlanData = function () {
        var timetableId = $('#timetable').data('id');

        $.ajax({
            url: Routing.generate('timetable_plan_data', {timetable: timetableId}),
            success: function (planData) {
                var $wrapper = $('.plan-wrapper');

                if (planData.plan_amount) {
                    $('.plan-amount', $wrapper).html(planData.plan_amount);
                    $('.plan-amount', $wrapper).parent('.label').removeClass('hide');
                } else {
                    $('.plan-amount', $wrapper).parent('.label').addClass('hide');
                }

                if (planData.plan_completed) {
                    $('.plan-completed', $wrapper).html(planData.plan_completed);
                    $('.plan-completed-percent', $wrapper).html(planData.plan_completed_percent);
                    $('.plan-completed', $wrapper).parent('.label').removeClass('hide');
                } else {
                    $('.plan-completed', $wrapper).parent('.label').addClass('hide');
                }

                if (planData.left_amount) {
                    $('.plan-left', $wrapper).html(planData.left_amount);
                    $('.plan-left-percent', $wrapper).html(planData.left_amount_percent);
                    $('.plan-left', $wrapper).parent('.label').removeClass('hide');
                } else {
                    $('.plan-left', $wrapper).parent('.label').addClass('hide');
                }
            }
        });
    };

    BootstrapTable.prototype.initBody = function () {
        _initBody.apply(this, Array.prototype.slice.apply(arguments));
        this.updatePlanData();

        $('.tt-tooltip').tooltip({
            trigger: 'click',
            container: 'body'
        });

        this.$tableBody.find('.times-comment').editable({
            container: 'body',
            placeholder: `ТОЛЬКО ДЛЯ УКАЗАНИЯ ДОКУМЕНТОВ ОТ ПОСТАВЩИКОВ
Для указания любых других  комментариев используйте соответствующее поле в свойствах записи.`,
            display: function() {
                return '';
            },
            success : function (response, newValue) {
                if (newValue == '') {
                    $(this).addClass('text-muted');
                } else {
                    $(this).removeClass('text-muted');
                }
            }
        });

        this.$tableBody.selectable({
            filter: 'div.times-item'
        });

        $('.timetable td').hover(function() {
            var index = $(this).index() + 1;
            $('.timetable th:nth-child(' + index + ')').addClass('hover');
        }, function() {
            var index = $(this).index() + 1;
            $('.timetable th:nth-child(' + index + ')').removeClass('hover');
        });

        $('.row-act-toggle').click(function() {
            var id = $(this).parents('tr').data('row');
            var $td = $(this).parents('td');
            $.ajax({
                url: Routing.generate('timetable_row_toggle_act', {timetableRow: id}),
                success: function (response) {
                    if (response.has_act) {
                        $td.addClass('has-act');
                    } else {
                        $td.removeClass('has-act');
                    }
                }
            });
        });
    };

    BootstrapTable.prototype.horizontalScroll = function () {
        _horizontalScroll.apply(this, Array.prototype.slice.apply(arguments));

        this.$tableBody.on('scroll', function () {
            localStorage.scrollTo = $(this).scrollTop();
        });

        if (localStorage.scrollTo) {
            this.scrollTo(parseInt(localStorage.scrollTo));
        }
    };

    BootstrapTable.prototype.onColumnSearch = function (event) {
        _onColumnSearch.apply(this, Array.prototype.slice.apply(arguments));

        var filterColumnsDefaults = {};
        this.$fixedHeader.find('.form-control').each(function() {
            var text = $.trim($(this).val());
            var $field = $(this).closest('[data-field]').data('field');


            if (text) {
                filterColumnsDefaults[$field] = text;
            }
        });

        if (filterColumnsDefaults) {
            localStorage.filterColumnsDefaults = JSON.stringify(filterColumnsDefaults);
        } else {
            localStorage.removeItem('filterColumnsDefaults');
        }
    };

    $.fn.bootstrapTable.methods.push('showIncomplete');
    BootstrapTable.prototype.showIncomplete = function (enable) {
        if (enable) {
            this.searchText = 'showIncomplete';
            this.options.customSearch = 'showIncomplete';
        }

        this.initSearch();
        this.updatePagination();

        this.searchText = null;
        this.options.customSearch = $.noop;
    }
})(jQuery);

function showIncomplete () {
    this.data = $.grep(this.options.data, function (item) {
        if (item.sum_times.value === '0.0') {
            return false;
        }

        for (let i = 1; i <= 31; i++) {
            const cell = item[`times_${i}`];

            const hasColor = cell.withColor || false;
            const withTime = cell.time !== '' && cell.time !== 0;
            const withComment = cell.comment !== '';
            let hasIncomplete = false;

            if (hasColor || withTime || withComment) {
                if (!hasColor || !withTime || !withComment) {
                    hasIncomplete = true;
                }
            }

            if (hasIncomplete) {
                return true;
            }
        }

        return false;
    });
}
