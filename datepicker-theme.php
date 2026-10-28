<!-- Shared date picker (flatpickr) styled to match the UAAGI look -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">

<style>
    /* Shared Flatpickr calendar — clean, rounded design */
    .flatpickr-calendar {
        z-index: 100000 !important;
        width: 288px !important;
        padding: 10px 10px 12px !important;
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 16px !important;
        box-shadow: 0 5px 20px rgba(15, 23, 42, 0.16) !important;
        font-family: inherit !important;
        overflow: hidden;
    }

    .flatpickr-calendar:before,
    .flatpickr-calendar:after {
        display: none !important;
    }

    /* Header and month title */
    .flatpickr-months {
        height: 34px !important;
        align-items: center;
    }

    .flatpickr-months .flatpickr-month {
        height: 34px !important;
    }

    .flatpickr-current-month {
        padding-top: 7px !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        color: #222222 !important;
    }

    .flatpickr-current-month .flatpickr-monthDropdown-months {
        background: transparent !important;
        color: #222222 !important;
        font-weight: 700 !important;
    }

    .flatpickr-current-month input.cur-year {
        color: #222222 !important;
        font-weight: 700 !important;
    }

    /* Simple navigation arrows */
    .flatpickr-months .flatpickr-prev-month,
    .flatpickr-months .flatpickr-next-month {
        top: 7px !important;
        padding: 5px !important;
        color: #333333 !important;
        fill: #333333 !important;
    }

    .flatpickr-months .flatpickr-prev-month svg,
    .flatpickr-months .flatpickr-next-month svg {
        width: 12px !important;
        height: 12px !important;
        fill: #333333 !important;
    }

    .flatpickr-months .flatpickr-prev-month:hover svg,
    .flatpickr-months .flatpickr-next-month:hover svg {
        fill: #234CA1 !important;
    }

    /* Weekday labels */
    .flatpickr-weekdays {
        height: 30px !important;
        background: #ffffff !important;
    }

    span.flatpickr-weekday {
        color: #6b7280 !important;
        font-size: 11px !important;
        font-weight: 500 !important;
        text-transform: none !important;
    }

    /* Calendar grid */
    .flatpickr-days {
        width: 266px !important;
        border: none !important;
    }

    .dayContainer {
        width: 266px !important;
        min-width: 266px !important;
        max-width: 266px !important;
        justify-content: space-around !important;
        padding: 0 !important;
    }

    /* Circular dates */
    .flatpickr-day {
        flex-basis: 14.2857% !important;
        max-width: 36px !important;
        height: 36px !important;
        line-height: 36px !important;
        margin: 1px 0 !important;
        border: 1px solid transparent !important;
        border-radius: 50% !important;
        color: #333333 !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        transition: background 0.15s ease, color 0.15s ease;
    }

    /* Hover */
    .flatpickr-day:hover,
    .flatpickr-day:focus {
        background: #edf3ff !important;
        border-color: transparent !important;
        color: #234CA1 !important;
    }

    /* Selected date */
    .flatpickr-day.selected,
    .flatpickr-day.selected:hover,
    .flatpickr-day.selected:focus,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange,
    .flatpickr-day.startRange:hover,
    .flatpickr-day.endRange:hover {
        background: #1877f2 !important;
        border-color: #1877f2 !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }

    /* Today's date: blue outline */
    .flatpickr-day.today {
        border: 1px solid #1877f2 !important;
    }

    .flatpickr-day.today:hover {
        background: #edf3ff !important;
        color: #234CA1 !important;
    }

    /* If today is selected, keep it filled */
    .flatpickr-day.today.selected {
        background: #1877f2 !important;
        color: #ffffff !important;
    }

    /* Dates outside this month */
    .flatpickr-day.prevMonthDay,
    .flatpickr-day.nextMonthDay,
    .flatpickr-day.prevMonthDay:hover,
    .flatpickr-day.nextMonthDay:hover {
        color: #c5cbd4 !important;
    }

    /* Disabled dates */
    .flatpickr-day.flatpickr-disabled,
    .flatpickr-day.flatpickr-disabled:hover {
        color: #d1d5db !important;
        background: transparent !important;
        border-color: transparent !important;
        cursor: not-allowed !important;
    }

    /* Keep the existing visible input design and calendar icon */
    input.flatpickr-alt {
        cursor: pointer;
        padding-right: 2.5rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23234CA1' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cpath d='M16 2v4M8 2v4M3 10h18'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1.1rem;
    }

    input.flatpickr-alt:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }

    /* Flatpickr visible text input — match .text-inputs */
    input.flatpickr-alt {
        width: 100% !important;
        height: 3rem !important;
        border: 1px solid #234CA1 !important;
        border-radius: 1rem !important;
        padding: 0 2.5rem 0 0.75rem !important;
        background-color: #ffffff !important;
        color: #222222 !important;
        font-family: inherit !important;
        font-size: inherit;
        box-sizing: border-box !important;
        cursor: pointer;
        outline: none;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;

        /* Calendar icon */
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23234CA1' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cpath d='M16 2v4M8 2v4M3 10h18'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1.1rem;
    }

    /* Focus state — match .text-inputs */
    input.flatpickr-alt:focus {
        outline: none !important;
        border-color: transparent !important;
        box-shadow: 0 0 0 1px #234CA1 !important;
    }

    /* Disabled state */
    input.flatpickr-alt:disabled {
        cursor: not-allowed;
        opacity: 0.6;
        background-color: #f3f4f6 !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
    /**
     * Upgrades every <input type="date"> inside `root` (default: the whole page).
     *   data-min / data-max   "today" or YYYY-MM-DD   (the normal min / max attributes work too)
     *   data-placeholder      text shown when empty
     *   data-native-date      leave this one as the browser's own picker
     * The real input keeps its id/name and its value stays YYYY-MM-DD.
     */
    function initDatePickers(root) {

        if (typeof flatpickr === "undefined") return;

        (root || document).querySelectorAll('input[type="date"]:not([data-dp-ready]):not([data-native-date])').forEach(input => {

            input.dataset.dpReady = "1";
            input.placeholder = input.dataset.placeholder || "Select a date";

            flatpickr(input, {
                altInput: true,
                altInputClass: "flatpickr-alt", // the visible box also inherits the input's own classes
                altFormat: "M j, Y", // what the user sees: Oct 19, 2026
                dateFormat: "Y-m-d", // what the form and server get: 2026-10-19
                minDate: input.dataset.min || input.getAttribute("min") || null,
                maxDate: input.dataset.max || input.getAttribute("max") || null,
                allowInvalidPreload: true, // an existing past date still shows (e.g. an ended schedule)
                disableMobile: true, // same styled picker on phones
                monthSelectorType: "dropdown",
                locale: {
                    firstDayOfWeek: 1
                }
            });

        });

    }

    document.addEventListener("DOMContentLoaded", () => initDatePickers());
</script>