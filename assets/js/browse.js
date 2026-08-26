/* ==========================================
   SkillExpert - Browse Skills JavaScript
   Client-side search and category filtering
   ========================================== */

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('browse-search');
    const filterPills = document.querySelectorAll('.filter-pill');
    const skillCards = document.querySelectorAll('.browse-card');
    const visibleCountEl = document.getElementById('visible-count');
    const noResultsEl = document.getElementById('browse-no-results');

    let activeCategory = 'All';
    let searchQuery = '';

    function filterSkills() {
        let visibleCount = 0;

        skillCards.forEach(card => {
            const cardCategory = card.getAttribute('data-category') || '';
            const cardTitle = (card.getAttribute('data-title') || '').toLowerCase();
            const cardDesc = (card.getAttribute('data-desc') || '').toLowerCase();
            const cardTeacher = (card.getAttribute('data-teacher') || '').toLowerCase();

            const matchesCategory = (activeCategory === 'All' || cardCategory.toLowerCase() === activeCategory.toLowerCase());
            const matchesSearch = searchQuery === '' || 
                                  cardTitle.includes(searchQuery) || 
                                  cardDesc.includes(searchQuery) || 
                                  cardTeacher.includes(searchQuery);

            if (matchesCategory && matchesSearch) {
                card.classList.remove('is-hidden');
                visibleCount++;
            } else {
                card.classList.add('is-hidden');
            }
        });

        if (visibleCountEl) {
            visibleCountEl.textContent = visibleCount;
        }

        if (noResultsEl) {
            noResultsEl.classList.toggle('is-hidden', visibleCount !== 0);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchQuery = this.value.trim().toLowerCase();
            filterSkills();
        });
    }

    filterPills.forEach(pill => {
        pill.addEventListener('click', function () {
            filterPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            activeCategory = this.getAttribute('data-filter') || 'All';
            filterSkills();
        });
    });
});
