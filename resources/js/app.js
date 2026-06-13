import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import 'flatpickr/dist/flatpickr.min.css';

const init = () => {
    document.querySelectorAll('[data-datepicker]:not([data-fp-inited])').forEach((el) => {
        const fp = flatpickr(el, {
            locale: Indonesian,
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j F Y',
            allowInput: true,
            disableMobile: true,
        });
        el.dataset.fpInited = '1';
        // expose destroy hook for Livewire re-init safety
        el._flatpickr = fp;
    });

    document.querySelectorAll('[data-monthpicker]:not([data-fp-inited])').forEach((el) => {
        const fp = flatpickr(el, {
            locale: Indonesian,
            dateFormat: 'Y-m',
            altInput: true,
            altFormat: 'F Y',
            disableMobile: true,
        });
        el.dataset.fpInited = '1';
        el._flatpickr = fp;
    });
};

const destroyAll = () => {
    document.querySelectorAll('[data-fp-inited]').forEach((el) => {
        if (el._flatpickr && typeof el._flatpickr.destroy === 'function') {
            el._flatpickr.destroy();
        }
        delete el.dataset.fpInited;
        delete el._flatpickr;
    });
};

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('livewire:navigated', init);
document.addEventListener('livewire:init', init);
window.addEventListener('beforeunload', destroyAll);
