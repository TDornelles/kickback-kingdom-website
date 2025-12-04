(function () {
    const DEFAULT_PAGE_SIZE = 12;

    function normalize(value) {
        return (value ?? '').toString().trim().toLowerCase();
    }

    function getPageSize(modalEl) {
        const pageSizeSelect = modalEl.querySelector('[data-product-selector-page-size]');
        const raw = pageSizeSelect?.value || modalEl.dataset.productSelectorPageSize || DEFAULT_PAGE_SIZE;
        const parsed = parseInt(raw, 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : DEFAULT_PAGE_SIZE;
    }

    function getFilters(modalEl) {
        const searchInput = modalEl.querySelector('[data-product-selector-search]');
        const storeFilter = modalEl.querySelector('[data-product-selector-filter="store"]');

        return {
            search: normalize(searchInput?.value),
            store: normalize(storeFilter?.value),
        };
    }

    function updateSummary(modalEl, start, end, total, page, totalPages) {
        const summaryEl = modalEl.querySelector('[data-product-selector-pagination-summary]');
        const labelEl = modalEl.querySelector('[data-product-selector-pagination-label]');

        if (summaryEl) {
            const safeStart = total === 0 ? 0 : start;
            const safeEnd = total === 0 ? 0 : end;
            summaryEl.textContent = `Showing ${safeStart}-${safeEnd} of ${total} products`;
        }

        if (labelEl) {
            labelEl.textContent = `Page ${page}${totalPages ? ` of ${totalPages}` : ''}`;
        }
    }

    function applyPagination(modalEl) {
        const cards = Array.from(modalEl.querySelectorAll('[data-product-selector-item]'));
        const emptyState = modalEl.querySelector('[data-product-selector-empty]');
        const { search, store } = getFilters(modalEl);
        const pageSize = getPageSize(modalEl);

        const matchingCards = [];
        cards.forEach(card => {
            const name = normalize(card.dataset.productName);
            const id = normalize(card.dataset.productId);
            const storeKey = normalize(card.dataset.productStore);

            const matchesSearch = !search || name.includes(search) || id.includes(search);
            const matchesStore = !store || storeKey === store;

            card.classList.add('d-none');
            if (matchesSearch && matchesStore) {
                matchingCards.push(card);
            }
        });

        const totalPages = matchingCards.length === 0 ? 1 : Math.ceil(matchingCards.length / pageSize);
        let currentPage = parseInt(modalEl.dataset.productSelectorPage || '1', 10);
        if (!Number.isFinite(currentPage) || currentPage < 1) {
            currentPage = 1;
        }
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        modalEl.dataset.productSelectorPage = currentPage;

        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, matchingCards.length);

        matchingCards.forEach((card, index) => {
            const inPage = index >= startIndex && index < endIndex;
            card.classList.toggle('d-none', !inPage);
        });

        if (emptyState) {
            emptyState.classList.toggle('d-none', matchingCards.length !== 0);
        }

        const prevBtn = modalEl.querySelector('[data-product-selector-prev]');
        const nextBtn = modalEl.querySelector('[data-product-selector-next]');

        if (prevBtn) {
            prevBtn.disabled = matchingCards.length === 0 || currentPage === 1;
        }

        if (nextBtn) {
            nextBtn.disabled = matchingCards.length === 0 || currentPage >= totalPages;
        }

        updateSummary(modalEl, startIndex + 1, endIndex, matchingCards.length, currentPage, totalPages);
    }

    function dispatchSelection(card, selectorId) {
        const detail = {
            selectorId,
            ctime: card.dataset.productCtime,
            crand: card.dataset.productId,
            name: card.dataset.productName,
            store: card.dataset.productStoreLabel,
            icon: card.querySelector('img')?.src,
        };

        const event = new CustomEvent('product-selector:selected', { detail });
        document.dispatchEvent(event);
    }

    function setup(modalEl) {
        const selectorId = modalEl.id;
        const searchInput = modalEl.querySelector('[data-product-selector-search]');
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        const filterInputs = modalEl.querySelectorAll('[data-product-selector-filter]');
        const paginationButtons = modalEl.querySelectorAll('[data-product-selector-prev], [data-product-selector-next]');
        const pageSizeSelect = modalEl.querySelector('[data-product-selector-page-size]');

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                modalEl.dataset.productSelectorPage = '1';
                applyPagination(modalEl);
            });
        }

        filterInputs.forEach(select => {
            select.addEventListener('change', () => {
                modalEl.dataset.productSelectorPage = '1';
                applyPagination(modalEl);
            });
        });

        if (pageSizeSelect) {
            modalEl.dataset.productSelectorPageSize = pageSizeSelect.value;
            pageSizeSelect.addEventListener('change', () => {
                modalEl.dataset.productSelectorPageSize = pageSizeSelect.value;
                modalEl.dataset.productSelectorPage = '1';
                applyPagination(modalEl);
            });
        }

        paginationButtons.forEach(button => {
            button.addEventListener('click', () => {
                const direction = button.hasAttribute('data-product-selector-prev') ? -1 : 1;
                const currentPage = parseInt(modalEl.dataset.productSelectorPage || '1', 10);
                const nextPage = Math.max(1, currentPage + direction);
                modalEl.dataset.productSelectorPage = nextPage;
                applyPagination(modalEl);
            });
        });

        modalEl.addEventListener('shown.bs.modal', () => {
            if (searchInput) {
                searchInput.focus();
            }
            applyPagination(modalEl);
        });

        modalEl.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-product-selector-choose]');
            if (!trigger) {
                return;
            }

            const card = trigger.closest('[data-product-selector-item]');
            if (!card) {
                return;
            }

            dispatchSelection(card, selectorId);
            modalInstance.hide();
        });

        applyPagination(modalEl);
    }

    window.ProductSelector = {
        init(selectorId) {
            const modalEl = document.getElementById(selectorId);
            if (!modalEl || modalEl.dataset.productSelectorInitialized === 'true') {
                return null;
            }

            modalEl.dataset.productSelectorInitialized = 'true';
            setup(modalEl);
            return modalEl;
        }
    };
})();
