import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['form', 'results', 'summary', 'pagination', 'searchInput'];
  static values = {
    endpoint: String,
  };

  connect() {
    this.debounceTimer = null;
    if (this.hasSearchInputTarget) {
      this.searchInputTarget.addEventListener('input', this.debouncedSearch.bind(this));
    }
  }

  debouncedSearch() {
    clearTimeout(this.debounceTimer);
    this.debounceTimer = setTimeout(() => {
      this.loadResults();
    }, 300);
  }

  filter(event) {
    event.preventDefault();
    this.loadResults();
  }

  paginate(event) {
    event.preventDefault();
    const url = new URL(event.currentTarget.href);
    this.loadResults(url.searchParams);
  }

  async loadResults(params = null) {
    if (!this.hasEndpointValue || !this.hasFormTarget || !this.hasResultsTarget) {
      return;
    }

    const formData = new FormData(this.formTarget);
    const searchParams = params || new URLSearchParams(formData);

    try {
      const response = await fetch(this.endpointValue + '?' + searchParams.toString(), {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      const data = await response.json();

      if (data.success && typeof data.html === 'string') {
        this.resultsTarget.innerHTML = data.html;
        if (this.hasSummaryTarget && typeof data.summaryHtml === 'string') {
          this.summaryTarget.innerHTML = data.summaryHtml;
        }
        this.updatePagination(data.pagination || null);
        this.updateUrl(searchParams);
      }
    } catch (error) {
      console.error('Error loading results:', error);
    }
  }

  updatePagination(pagination) {
    if (!this.hasPaginationTarget) {
      return;
    }

    if (!pagination || Number(pagination.totalPages || 0) <= 1) {
      this.paginationTarget.innerHTML = '';
      return;
    }

    const currentPage = Number(pagination.page);
    const totalPages = Number(pagination.totalPages);
    const prevPage = currentPage - 1;
    const nextPage = currentPage + 1;

    let html = '';

    if (currentPage > 1) {
      html += `<a class="btn btn-mini btn-ghost" href="?page=${prevPage}" data-action="click->tournaments#paginate">&larr; Prev</a> `;
    }

    html += `<span class="tour-page-info">Page ${currentPage} / ${totalPages}</span> `;

    if (currentPage < totalPages) {
      html += `<a class="btn btn-mini btn-ghost" href="?page=${nextPage}" data-action="click->tournaments#paginate">Next &rarr;</a>`;
    }

    this.paginationTarget.innerHTML = html;
  }

  updateUrl(params) {
    if (!params) {
      return;
    }

    const url = new URL(window.location.href);
    url.search = params.toString();
    window.history.replaceState({}, '', url.toString());
  }
}
