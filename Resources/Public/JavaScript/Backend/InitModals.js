(function() {
    // Inject CSS to visually hide the scrollbar in modals without breaking functionality
    const style = document.createElement('style');
    style.innerHTML = `
        .modal::-webkit-scrollbar { display: none !important; }
        .modal { -ms-overflow-style: none !important; scrollbar-width: none !important; }
    `;
    document.head.appendChild(style);

    const fallbackModals = new Map();
    const originalPositions = new Map();
    let activeFallbackCount = 0;
    // Store original body scroll styles so we can restore them when modals close
    let originalBodyOverflow = null;
    let originalBodyPaddingRight = null;
    // Variables for TYPO3 internal scroll containers
    let typo3ScrollContainer = null;
    let originalContainerOverflow = null;

    function createBackdrop() {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade';
        // Apply safe fallback styles so backdrop is semi-transparent if Bootstrap CSS isn't present
        backdrop.style.position = 'fixed';
        backdrop.style.top = '0';
        backdrop.style.left = '0';
        backdrop.style.width = '100%';
        backdrop.style.height = '100%';
        backdrop.style.backgroundColor = 'rgba(0, 0, 0, 0.1)';
        backdrop.style.zIndex = '1050';
        backdrop.style.opacity = '0';
        document.body.appendChild(backdrop);
        // force reflow then show
        // eslint-disable-next-line no-unused-expressions
        backdrop.offsetHeight;
        backdrop.style.opacity = '';
        backdrop.classList.add('show');
        return backdrop;
    }

    function removeBackdrop(backdrop) {
        if (!backdrop) return;
        backdrop.classList.remove('show');
        setTimeout(() => {
            if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
        }, 200);
    }

    function showFallbackModal(targetEl) {
        if (!targetEl) return;
        if (fallbackModals.has(targetEl)) return; // already shown

        // If modal wasn't already moved to body during init, move it now
        if (!originalPositions.has(targetEl) && targetEl.parentNode && targetEl.parentNode !== document.body) {
            originalPositions.set(targetEl, {parent: targetEl.parentNode, nextSibling: targetEl.nextSibling});
            document.body.appendChild(targetEl);
        }

        const backdrop = createBackdrop();
        activeFallbackCount++;

        targetEl.classList.add('modal', 'fade');
        // Ensure modal sits above backdrop
        targetEl.style.zIndex = '1060';
        targetEl.style.display = 'block';
        // Fallback positioning: center the modal in the viewport when
        // Bootstrap CSS is not available. This prevents the modal from
        // appearing at the end of the document (bottom of the page).
        targetEl.style.position = 'fixed';
        targetEl.style.top = '50%';
        targetEl.style.left = '50%';
        targetEl.style.transform = 'translate(-50%, -50%)';
        targetEl.style.margin = '0';
        targetEl.style.maxWidth = '90%';
        targetEl.style.maxHeight = '90%';
        targetEl.style.overflow = 'auto';
        // force reflow then show
        // eslint-disable-next-line no-unused-expressions
        targetEl.offsetHeight;
        targetEl.classList.add('show');
        targetEl.setAttribute('aria-modal', 'true');
        targetEl.removeAttribute('aria-hidden');

        function hide() {
            targetEl.classList.remove('show');
            targetEl.style.display = 'none';
            targetEl.setAttribute('aria-hidden', 'true');
            targetEl.removeAttribute('aria-modal');
            removeBackdrop(backdrop);
            fallbackModals.delete(targetEl);
            activeFallbackCount = Math.max(0, activeFallbackCount - 1);
            if (activeFallbackCount === 0) {
                document.removeEventListener('keydown', escHandler);
                document.body.classList.remove('modal-open');
                // Restore original body scroll styles
                if (originalBodyOverflow !== null) document.body.style.overflow = originalBodyOverflow;
                if (originalBodyPaddingRight !== null) document.body.style.paddingRight = originalBodyPaddingRight;
                originalBodyOverflow = null;
                originalBodyPaddingRight = null;

                // Restore TYPO3 internal scroll containers
                if (typo3ScrollContainer) {
                    typo3ScrollContainer.style.overflow = originalContainerOverflow;
                    typo3ScrollContainer = null;
                    originalContainerOverflow = null;
                }
            }
            // Note: we intentionally leave the modal in `document.body` to avoid
            // re-inserting it into table rows which can cause layout jumps.
        }

        // Close buttons inside modal
        targetEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                hide();
            });
        });

        // Clicking backdrop closes
        backdrop.addEventListener('click', () => hide());

        function escHandler(e) {
            if (e.key === 'Escape') hide();
        }

        document.addEventListener('keydown', escHandler);
        // Prevent body scroll when modal is open. For the fallback (no
        // Bootstrap CSS), explicitly lock body scrolling and compensate for
        // the scrollbar width to avoid layout shift.
        if (activeFallbackCount === 1) {
            originalBodyOverflow = document.body.style.overflow;
            originalBodyPaddingRight = document.body.style.paddingRight;
            const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            if (scrollbarWidth > 0) document.body.style.paddingRight = `${scrollbarWidth}px`;
            document.body.style.overflow = 'hidden';

            // Lock TYPO3 specific scroll containers
            typo3ScrollContainer = document.querySelector('.t3js-module-body, .typo3-module-body, .scaffold-content, .t3-module-body');
            if (typo3ScrollContainer) {
                originalContainerOverflow = typo3ScrollContainer.style.overflow;
                typo3ScrollContainer.style.overflow = 'hidden';
            }
        }
        document.body.classList.add('modal-open');

        fallbackModals.set(targetEl, {hide, backdrop});
    }

    // Listen for Bootstrap modal events to lock TYPO3 specific scroll containers
    document.addEventListener('show.bs.modal', function () {
        typo3ScrollContainer = document.querySelector('.t3js-module-body, .typo3-module-body, .scaffold-content, .t3-module-body');
        if (typo3ScrollContainer) {
            originalContainerOverflow = typo3ScrollContainer.style.overflow;
            typo3ScrollContainer.style.overflow = 'hidden';
        }
    });

    document.addEventListener('hidden.bs.modal', function () {
        if (typo3ScrollContainer) {
            typo3ScrollContainer.style.overflow = originalContainerOverflow;
            typo3ScrollContainer = null;
            originalContainerOverflow = null;
        }
    });

    function initModals() {
        document.querySelectorAll('a[data-bs-toggle="modal"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetSelector = this.getAttribute('data-bs-target') || this.getAttribute('href');
                if (!targetSelector) return;
                const targetEl = document.querySelector(targetSelector);
                if (!targetEl) return;

                // If Bootstrap's Modal API is available, use it
                if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
                    // Move modal to body to avoid table layout shifts
                    if (!originalPositions.has(targetEl) && targetEl.parentNode && targetEl.parentNode !== document.body) {
                        originalPositions.set(targetEl, {parent: targetEl.parentNode, nextSibling: targetEl.nextSibling});
                        document.body.appendChild(targetEl);
                    }
                    const modal = window.bootstrap.Modal.getOrCreateInstance(targetEl);
                    modal.show();
                    return;
                }

                // Fallback modal implementation (also moves to body)
                showFallbackModal(targetEl);
            });
        });

        // Ensure dismiss attributes work even if modal was opened by other means
        document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                // find closest modal parent and hide via fallback map if present
                const modalEl = btn.closest('.modal');
                if (modalEl && fallbackModals.has(modalEl)) {
                    e.preventDefault();
                    const obj = fallbackModals.get(modalEl);
                    if (obj && typeof obj.hide === 'function') obj.hide();
                }
            });
        });
    }

    // Move any inline modal elements (e.g. rendered inside table cells) to document.body
    // on init so they don't occupy space inside table layout and cause gaps in v14.
    function relocateInlineModals() {
        document.querySelectorAll('.modal').forEach(modalEl => {
            if (modalEl.parentNode && modalEl.parentNode !== document.body) {
                // store original position in case it's needed later
                originalPositions.set(modalEl, {parent: modalEl.parentNode, nextSibling: modalEl.nextSibling});
                // ensure hidden by default to avoid layout issues
                modalEl.style.display = 'none';
                modalEl.setAttribute('aria-hidden', 'true');
                document.body.appendChild(modalEl);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            relocateInlineModals();
            initModals();
        });
    } else {
        relocateInlineModals();
        initModals();
    }
})();
