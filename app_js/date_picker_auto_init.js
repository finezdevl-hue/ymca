/**
 * Auto-initialize all date input pickers with the current local date (YYYY-MM-DD)
 */
(function() {
    function getTodayDateString() {
        var now = new Date();
        var yyyy = now.getFullYear();
        var mm = String(now.getMonth() + 1).padStart(2, '0');
        var dd = String(now.getDate()).padStart(2, '0');
        return yyyy + '-' + mm + '-' + dd;
    }

    function initCurrentDatePickers() {
        var todayVal = getTodayDateString();
        if (typeof $ !== 'undefined') {
            $('input[type="date"]').each(function() {
                var currentVal = $(this).val();
                if (!currentVal || currentVal === '0000-00-00') {
                    $(this).val(todayVal);
                }
            });
        }
    }

    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            initCurrentDatePickers();
        });

        $(document).on('show.bs.modal shown.bs.modal', function() {
            initCurrentDatePickers();
        });

        $(document).on('click focus', 'input[type="date"]', function() {
            var currentVal = $(this).val();
            if (!currentVal || currentVal === '0000-00-00') {
                $(this).val(getTodayDateString());
            }
        });

        $(document).on('click', 'input[type="month"]', function() {
            if (this.showPicker && typeof this.showPicker === 'function') {
                try { this.showPicker(); } catch(e) {}
            }
        });
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            var todayVal = getTodayDateString();
            var inputs = document.querySelectorAll('input[type="date"]');
            inputs.forEach(function(input) {
                if (!input.value || input.value === '0000-00-00') {
                    input.value = todayVal;
                }
            });
        });
    }
})();
