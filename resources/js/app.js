import Alpine from 'alpinejs';
import jQuery from 'jquery';
import select2 from 'select2';

window.Alpine = Alpine;
window.$ = window.jQuery = jQuery;
select2($);

Alpine.start();

// Initialize Select2 on all .js-select2 elements after DOM load
document.addEventListener('DOMContentLoaded', function() {
    function initSelect2() {
        $('.js-select2').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) return;
            var $this = $(this);
            // If inside a modal, attach dropdown to the modal so it positions correctly
            var $modal = $this.closest('.fixed.inset-0');
            $this.select2({
                width: '100%',
                placeholder: $this.data('placeholder') || $this.find('option:first').text(),
                allowClear: !$this.prop('required'),
                minimumResultsForSearch: 0,
                dropdownAutoWidth: false,
                dropdownParent: $modal.length ? $modal : $(document.body),
            });
        });
    }
    initSelect2();

    // Bridge Select2 change events back to Alpine x-model
    let bridging = false;
    $(document).on('change.select2', '.js-select2', function(e) {
        if (bridging) return;
        bridging = true;
        this.dispatchEvent(new Event('change', { bubbles: true }));
        bridging = false;
    });

    // Re-init Select2 for Alpine dynamically added elements
    const observer = new MutationObserver(function(mutations) {
        let needsInit = false;
        mutations.forEach(function(m) {
            m.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && (node.querySelector?.('.js-select2') || node.classList?.contains('js-select2'))) {
                    needsInit = true;
                }
            });
        });
        if (needsInit) setTimeout(initSelect2, 50);
    });
    observer.observe(document.body, { childList: true, subtree: true });
});
