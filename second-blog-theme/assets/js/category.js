/*
 * Category archive demo interactions.
 * در نسخه نمایشی مستقل، فیلتر موضوع، جست‌وجو و مرتب‌سازی کارت‌ها را کنترل می‌کند.
 * در قالب وردپرس، این اسکریپت فقط زمانی کاری انجام می‌دهد که عناصر مربوط وجود داشته باشند.
 */
(() => {
  'use strict';

  const menuToggle = document.querySelector('.menu-toggle');
  const navigation = document.querySelector('.site-nav');

  if (menuToggle && navigation) {
    menuToggle.addEventListener('click', () => {
      const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
      menuToggle.setAttribute('aria-expanded', String(!isExpanded));
      navigation.classList.toggle('is-open', !isExpanded);
    });

    navigation.addEventListener('click', (event) => {
      if (event.target.closest('a')) {
        menuToggle.setAttribute('aria-expanded', 'false');
        navigation.classList.remove('is-open');
      }
    });
  }

  const grid = document.querySelector('#article-grid');
  const searchInput = document.querySelector('#article-search');
  const sortSelect = document.querySelector('#article-sort');
  const resultCount = document.querySelector('#results-count');
  const emptyState = document.querySelector('#empty-state');
  const resetButton = document.querySelector('#reset-filters');
  const filterButtons = Array.from(document.querySelectorAll('[data-filter]'));

  if (!grid) return;

  const originalCards = Array.from(grid.querySelectorAll('.post-card'));
  let activeCategory = 'all';

  const normalizeText = (value) => String(value || '')
    .toLocaleLowerCase('fa')
    .replace(/[ي]/g, 'ی')
    .replace(/[ك]/g, 'ک')
    .replace(/\s+/g, ' ')
    .trim();

  function applyFilters() {
    const query = normalizeText(searchInput ? searchInput.value : '');
    let visibleCount = 0;

    originalCards.forEach((card) => {
      const cardCategory = card.dataset.category || '';
      const cardText = normalizeText(card.textContent);
      const categoryMatches = activeCategory === 'all' || cardCategory === activeCategory;
      const queryMatches = !query || cardText.includes(query);
      const isVisible = categoryMatches && queryMatches;

      card.hidden = !isVisible;
      if (isVisible) visibleCount += 1;
    });

    if (resultCount) {
      resultCount.textContent = `${new Intl.NumberFormat('fa-IR').format(visibleCount)} مقاله نمایش داده می‌شود`;
    }
    if (emptyState) emptyState.hidden = visibleCount !== 0;
  }

  function sortCards() {
    const mode = sortSelect ? sortSelect.value : 'newest';
    const cards = [...originalCards];

    cards.sort((a, b) => {
      if (mode === 'oldest') {
        return (a.dataset.date || '').localeCompare(b.dataset.date || '');
      }
      if (mode === 'reading') {
        return Number(a.dataset.minutes || 0) - Number(b.dataset.minutes || 0);
      }
      return (b.dataset.date || '').localeCompare(a.dataset.date || '');
    });

    cards.forEach((card) => grid.appendChild(card));
  }

  filterButtons.forEach((button) => {
    button.addEventListener('click', () => {
      activeCategory = button.dataset.filter || 'all';
      filterButtons.forEach((item) => {
        const selected = item === button;
        item.classList.toggle('is-active', selected);
        item.setAttribute('aria-pressed', String(selected));
      });
      applyFilters();
    });
  });

  if (searchInput) searchInput.addEventListener('input', applyFilters);
  if (sortSelect) sortSelect.addEventListener('change', () => {
    sortCards();
    applyFilters();
  });

  if (resetButton) {
    resetButton.addEventListener('click', () => {
      activeCategory = 'all';
      if (searchInput) searchInput.value = '';
      if (sortSelect) sortSelect.value = 'newest';
      filterButtons.forEach((button) => {
        const selected = button.dataset.filter === 'all';
        button.classList.toggle('is-active', selected);
        button.setAttribute('aria-pressed', String(selected));
      });
      sortCards();
      applyFilters();
      if (searchInput) searchInput.focus();
    });
  }

  // میان‌بر ساده جست‌وجو: کلید /، مگر زمانی که کاربر داخل ورودی تایپ می‌کند.
  document.addEventListener('keydown', (event) => {
    const target = event.target;
    const typing = target instanceof HTMLElement && (
      target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
    );
    if (event.key === '/' && !typing && searchInput) {
      event.preventDefault();
      searchInput.focus();
    }
    if (event.key === 'Escape' && searchInput && document.activeElement === searchInput) {
      searchInput.value = '';
      applyFilters();
      searchInput.blur();
    }
  });

  sortCards();
  applyFilters();
})();
