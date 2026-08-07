document.addEventListener('click', function (e) {
    const target = e.target.closest('button');
    if (!target) return;

    if (target.id === 'blog-search-btn' || target.id === 'blog-date-apply-btn') {
        const searchInput = document.getElementById('searchTitle');
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');

        if (searchInput) searchInput.setCustomValidity('');
        if (startDate) startDate.setCustomValidity('');
        if (endDate) endDate.setCustomValidity('');

        if (target.id === 'blog-search-btn') {
            if (searchInput && !searchInput.value.trim()) {
                searchInput.setCustomValidity('Please enter a search title.');
                searchInput.reportValidity();
                e.preventDefault();
            }
        }

        if (target.id === 'blog-date-apply-btn') {
            if (startDate && endDate) {
                if (!startDate.value || !endDate.value) {
                    const message = 'Please select both a start and end date.';
                    if (!startDate.value) {
                        startDate.setCustomValidity(message);
                        startDate.reportValidity();
                    } else {
                        endDate.setCustomValidity(message);
                        endDate.reportValidity();
                    }
                    e.preventDefault();
                }
            }
        }
    }
});
