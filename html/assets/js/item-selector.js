(function () {
    const DEFAULT_PAGE_SIZE = 12;

    function getPageSize(modalEl) {
        const pageSizeSelect = modalEl.querySelector('[data-item-selector-page-size]');
        const rawValue = pageSizeSelect?.value || modalEl.dataset.itemSelectorPageSize || DEFAULT_PAGE_SIZE;
        const parsed = parseInt(rawValue, 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : DEFAULT_PAGE_SIZE;
    }

    function getFilterValues(modalEl) {
        const searchInput = modalEl.querySelector('[data-item-selector-search]');
        const typeFilter = modalEl.querySelector('[data-item-selector-filter="type"]');
        const categoryFilter = modalEl.querySelector('[data-item-selector-filter="category"]');
        const equipmentFilter = modalEl.querySelector('[data-item-selector-filter="equipment"]');

        return {
            search: (searchInput?.value || '').trim().toLowerCase(),
            type: (typeFilter?.value || '').toLowerCase(),
            category: (categoryFilter?.value || '').toLowerCase(),
            equipment: (equipmentFilter?.value || '').toLowerCase(),
        };
    }

    function updateSummary(modalEl, start, end, total, page, totalPages) {
        const summaryEl = modalEl.querySelector('[data-item-selector-pagination-summary]');
        const labelEl = modalEl.querySelector('[data-item-selector-pagination-label]');
        if (summaryEl) {
            const safeStart = total === 0 ? 0 : start;
            const safeEnd = total === 0 ? 0 : end;
            summaryEl.textContent = `Showing ${safeStart}-${safeEnd} of ${total} items`;
        }
        if (labelEl) {
            labelEl.textContent = `Page ${page}${totalPages ? ` of ${totalPages}` : ''}`;
        }
    }

    function applyPagination(modalEl) {
        const cards = Array.from(modalEl.querySelectorAll('[data-item-selector-item]'));
        const emptyState = modalEl.querySelector('[data-item-selector-empty]');
        const { search, type, category, equipment } = getFilterValues(modalEl);
        const pageSize = getPageSize(modalEl);

        const matchingCards = [];

        cards.forEach(card => {
            const name = (card.dataset.itemName || '').toLowerCase();
            const id = (card.dataset.itemId || '').toLowerCase();
            const cardType = (card.dataset.itemType || '').toLowerCase();
            const cardCategory = (card.dataset.itemCategory || '').toLowerCase();
            const cardEquipment = (card.dataset.itemEquipment || '').toLowerCase();

            const matchesSearch = !search || name.includes(search) || id.includes(search);
            const matchesType = !type || cardType === type;
            const matchesCategory = !category || cardCategory === category;
            const matchesEquipment = !equipment || cardEquipment === equipment;

            const isMatch = matchesSearch && matchesType && matchesCategory && matchesEquipment;
            card.classList.add('d-none');
            if (isMatch) {
                matchingCards.push(card);
            }
        });

        const totalPages = matchingCards.length === 0 ? 1 : Math.ceil(matchingCards.length / pageSize);
        let currentPage = parseInt(modalEl.dataset.itemSelectorPage || '1', 10);
        if (!Number.isFinite(currentPage) || currentPage < 1) {
            currentPage = 1;
        }
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        modalEl.dataset.itemSelectorPage = currentPage;

        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, matchingCards.length);

        matchingCards.forEach((card, index) => {
            const inPage = index >= startIndex && index < endIndex;
            card.classList.toggle('d-none', !inPage);
        });

        if (emptyState) {
            emptyState.classList.toggle('d-none', matchingCards.length !== 0);
        }

        const prevBtn = modalEl.querySelector('[data-item-selector-prev]');
        const nextBtn = modalEl.querySelector('[data-item-selector-next]');

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
            crand: card.dataset.itemId,
            name: card.dataset.itemName,
            rarity: card.dataset.itemRarity,
            icon: card.dataset.itemIcon,
            type: card.dataset.itemTypeLabel,
            category: card.dataset.itemCategoryLabel,
            equipment: card.dataset.itemEquipmentLabel,
        };

        const event = new CustomEvent('item-selector:selected', {
            detail
        });

        document.dispatchEvent(event);
    }

    function setup(modalEl) {
        const selectorId = modalEl.id;
        const searchInput = modalEl.querySelector('[data-item-selector-search]');
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        const filterInputs = modalEl.querySelectorAll('[data-item-selector-filter]');
        const paginationButtons = modalEl.querySelectorAll('[data-item-selector-prev], [data-item-selector-next]');
        const pageSizeSelect = modalEl.querySelector('[data-item-selector-page-size]');

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                modalEl.dataset.itemSelectorPage = '1';
                applyPagination(modalEl);
            });
        }

        filterInputs.forEach(select => {
            select.addEventListener('change', () => {
                modalEl.dataset.itemSelectorPage = '1';
                applyPagination(modalEl);
            });
        });

        if (pageSizeSelect) {
            modalEl.dataset.itemSelectorPageSize = pageSizeSelect.value;
            pageSizeSelect.addEventListener('change', () => {
                modalEl.dataset.itemSelectorPageSize = pageSizeSelect.value;
                modalEl.dataset.itemSelectorPage = '1';
                applyPagination(modalEl);
            });
        }

        paginationButtons.forEach(button => {
            button.addEventListener('click', () => {
                const direction = button.hasAttribute('data-item-selector-prev') ? -1 : 1;
                const currentPage = parseInt(modalEl.dataset.itemSelectorPage || '1', 10);
                const nextPage = Math.max(1, currentPage + direction);
                modalEl.dataset.itemSelectorPage = nextPage;
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
            const trigger = event.target.closest('[data-item-selector-choose]');
            if (!trigger) {
                return;
            }

            const card = trigger.closest('[data-item-selector-item]');
            if (!card) {
                return;
            }

            dispatchSelection(card, selectorId);
            modalInstance.hide();
        });

        applyPagination(modalEl);
    }

    window.ItemSelector = {
        init(selectorId) {
            const modalEl = document.getElementById(selectorId);
            if (!modalEl || modalEl.dataset.itemSelectorInitialized === 'true') {
                return null;
            }

            setup(modalEl);
            modalEl.dataset.itemSelectorInitialized = 'true';
            return modalEl;
        }
    };
})();
