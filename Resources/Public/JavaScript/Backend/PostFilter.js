class PostFilter {
    constructor() {
        this.currentPage = 1;
        this.itemsPerPage = 10;
        
        // UI Elements
        this.titleInput = document.getElementById('filter-title');
        this.categorySelect = document.getElementById('filter-category');
        this.dateStartInput = document.getElementById('filter-date-start');
        this.dateEndInput = document.getElementById('filter-date-end');
        
        this.btnFilter = document.getElementById('filter-btn-apply');
        this.btnClear = document.getElementById('filter-btn-clear');
        
        this.tableBody = document.querySelector('.myblog-post-table tbody');
        this.rows = Array.from(this.tableBody ? this.tableBody.querySelectorAll('tr') : []);
        
        this.btnPrev = document.getElementById('pagination-prev');
        this.btnNext = document.getElementById('pagination-next');
        this.pageIndicator = document.getElementById('pagination-info');

        this.init();
    }

    init() {
        if (!this.tableBody || this.rows.length === 0) return;

        // Prevent selecting future dates
        const today = new Date().toISOString().split('T')[0];
        if (this.dateStartInput) this.dateStartInput.max = today;
        if (this.dateEndInput) this.dateEndInput.max = today;

        // Ensure end date cannot be before start date
        if (this.dateStartInput) {
            this.dateStartInput.addEventListener('input', () => {
                if (this.dateEndInput) {
                    this.dateEndInput.min = this.dateStartInput.value;
                    if (this.dateEndInput.value && this.dateEndInput.value < this.dateStartInput.value) {
                        this.dateEndInput.value = this.dateStartInput.value;
                    }
                }
            });
        }
        
        // Bind Filter Button
        if (this.btnFilter) {
            this.btnFilter.addEventListener('click', (e) => {
                e.preventDefault();
                this.onFilterChange();
            });
        }
        
        // Bind Clear Button
        if (this.btnClear) {
            this.btnClear.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.titleInput) this.titleInput.value = '';
                if (this.categorySelect) this.categorySelect.value = '';
                if (this.dateStartInput) this.dateStartInput.value = '';
                if (this.dateEndInput) {
                    this.dateEndInput.value = '';
                    this.dateEndInput.min = ''; // Reset min validation
                }
                this.onFilterChange();
            });
        }

        // Pagination Prev
        if (this.btnPrev) {
            this.btnPrev.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.applyState();
                }
            });
        }

        // Pagination Next
        if (this.btnNext) {
            this.btnNext.addEventListener('click', (e) => {
                e.preventDefault();
                const totalPages = Math.ceil(this.getMatchedRows().length / this.itemsPerPage);
                if (this.currentPage < totalPages) {
                    this.currentPage++;
                    this.applyState();
                }
            });
        }

        // Initial render
        this.applyState();
    }

    onFilterChange() {
        // Reset to page 1 when filtering changes
        this.currentPage = 1;
        this.applyState();
    }

    getMatchedRows() {
        const searchTitle = this.titleInput ? this.titleInput.value.toLowerCase().trim() : '';
        const searchCategory = this.categorySelect ? this.categorySelect.value : '';
        const startDate = this.dateStartInput ? this.dateStartInput.value : '';
        const endDate = this.dateEndInput ? this.dateEndInput.value : '';

        return this.rows.filter(row => {
            const rowTitle = (row.dataset.title || '').toLowerCase();
            const rowCategories = (row.dataset.categories || '').split(',');
            const rowDate = row.dataset.date || '';

            // Title match
            if (searchTitle && !rowTitle.includes(searchTitle)) return false;
            
            // Category match
            if (searchCategory && !rowCategories.includes(searchCategory)) return false;

            // Date Range match
            if (startDate && rowDate < startDate) return false;
            if (endDate && rowDate > endDate) return false;

            return true;
        });
    }

    applyState() {
        const matchedRows = this.getMatchedRows();
        const totalItems = matchedRows.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / this.itemsPerPage));
        
        if (this.currentPage > totalPages) {
            this.currentPage = totalPages;
        }

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;

        // Hide all rows first
        this.rows.forEach(row => {
            row.classList.add('d-none');
        });

        // Update row numbers and show only the matched rows for the current page slice
        matchedRows.forEach((row, index) => {
            // Update the counter number (first cell)
            const numberCell = row.querySelector('td:first-child');
            if (numberCell) {
                numberCell.textContent = index + 1;
            }
            
            // Show row if it falls within the current page
            if (index >= startIndex && index < endIndex) {
                row.classList.remove('d-none');
            }
        });

        // Update pagination UI
        if (this.pageIndicator) {
            this.pageIndicator.textContent = `Page ${this.currentPage} of ${totalPages} (${totalItems} items)`;
        }

        if (this.btnPrev) {
            this.btnPrev.disabled = this.currentPage === 1;
        }

        if (this.btnNext) {
            this.btnNext.disabled = this.currentPage === totalPages;
        }
    }
}

// Initialize when the DOM is ready (or script is loaded)
new PostFilter();
