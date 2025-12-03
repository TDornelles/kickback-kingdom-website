(function () {
    function filterItems(modalEl, searchTerm) {
        const cards = modalEl.querySelectorAll('[data-item-selector-item]');
        const emptyState = modalEl.querySelector('[data-item-selector-empty]');
        const term = (searchTerm || '').trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach(card => {
            const name = (card.dataset.itemName || '').toLowerCase();
            const id = (card.dataset.itemId || '').toLowerCase();
            const matches = !term || name.includes(term) || id.includes(term);
            card.classList.toggle('d-none', !matches);
            if (matches) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            emptyState.classList.toggle('d-none', visibleCount !== 0);
        }
    }

    function dispatchSelection(card, selectorId) {
        const detail = {
            selectorId,
            crand: card.dataset.itemId,
            name: card.dataset.itemName,
            rarity: card.dataset.itemRarity,
            icon: card.dataset.itemIcon
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

        if (searchInput) {
            searchInput.addEventListener('input', () => filterItems(modalEl, searchInput.value));
        }

        modalEl.addEventListener('shown.bs.modal', () => {
            if (searchInput) {
                searchInput.focus();
            }
            filterItems(modalEl, searchInput ? searchInput.value : '');
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

        filterItems(modalEl, searchInput ? searchInput.value : '');
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
