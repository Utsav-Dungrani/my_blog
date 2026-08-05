document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('myblog-list-container');
    if (!container) return;

    function attachAjaxEvents() {
        const links = container.querySelectorAll('a[href]');
        links.forEach(link => {
            if (link.closest('.pagination') || link.closest('[aria-label="Filter blog posts by category"]')) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    loadAjaxContent(this.href);
                });
            }
        });

        const forms = container.querySelectorAll('form');
        forms.forEach(form => {
            const sortSelect = form.querySelector('select[name$="[sortBy]"]') || form.querySelector('select[name="sortBy"]');
            if (sortSelect) {
                sortSelect.removeAttribute('onchange');
                sortSelect.addEventListener('change', function (e) {
                    const formData = new FormData(form);
                    loadAjaxContent(form.action || window.location.href, {
                        method: form.method ? form.method.toUpperCase() : 'POST',
                        body: formData
                    });
                });

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    loadAjaxContent(this.action || window.location.href, {
                        method: this.method ? this.method.toUpperCase() : 'POST',
                        body: formData
                    });
                });
            }
        });
    }

    function loadAjaxContent(url, options = {}) {
        container.style.opacity = '0.5';
        container.style.pointerEvents = 'none';
        
        options.headers = options.headers || {};
        options.headers['X-Requested-With'] = 'XMLHttpRequest';

        let fetchUrl;
        try {
            const urlObj = new URL(url, window.location.origin);
            urlObj.searchParams.set('type', '99');
            fetchUrl = urlObj.href;
        } catch (e) {
            fetchUrl = url;
        }

        fetch(fetchUrl, options)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.getElementById('myblog-list-container');
                
                if (newContainer) {
                    container.innerHTML = newContainer.innerHTML;
                    
                    if (!options.method || options.method === 'GET') {
                        window.history.pushState(null, '', url);
                    }
                    
                    attachAjaxEvents();
                    
                    const rect = container.getBoundingClientRect();
                    if (rect.top < 0) {
                        window.scrollTo({
                            top: window.pageYOffset + rect.top - 50,
                            behavior: 'smooth'
                        });
                    }
                } else {
                    console.error('AJAX container not found in response');
                    window.location.href = url;
                }
            })
            .catch(error => {
                console.error('AJAX error:', error);
                window.location.href = url;
            })
            .finally(() => {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            });
    }

    window.addEventListener('popstate', function () {
        if (document.getElementById('myblog-list-container')) {
            loadAjaxContent(window.location.href);
        }
    });

    attachAjaxEvents();
});
