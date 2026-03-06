# AJAX Implementation Plan

## Phase 1: Create AJAX Endpoints (JSON APIs)

### 1.1 Frenoffice TournamentController AJAX
- [ ] Add `/tournaments/ajax-list` route that returns JSON with filtered tournaments
- [ ] Add `/tournaments/ajax-prize-pool/{id}` route for dynamic prize pool updates

### 1.2 Admin TournamentController AJAX  
- [ ] Add `/admin/tournament/ajax-list-json` route returning JSON data
- [ ] Ensure filtering parameters work with JSON response

### 1.3 Admin VoucherController AJAX
- [ ] Add `/admin/vouchers/ajax-list` route returning JSON data
- [ ] Add filtering by tournament

## Phase 2: JavaScript Controllers

### 2.1 Frenoffice Tournaments
- [ ] Create `assets/controllers/tournaments_controller.js`
- [ ] Implement debounced search (300ms)
- [ ] Handle filter form submission via AJAX
- [ ] Handle pagination via AJAX
- [ ] Update tournament grid dynamically

### 2.2 Admin Tournaments
- [ ] Create `assets/controllers/admin_tournaments_controller.js`
- [ ] Implement debounced search
- [ ] Handle filter/pagination via AJAX

### 2.3 Admin Vouchers  
- [ ] Create `assets/controllers/admin_vouchers_controller.js`
- [ ] Handle tournament filter via AJAX

## Phase 3: Template Updates

### 3.1 Frenoffice Tournaments Template
- [ ] Add data attributes for AJAX
- [ ] Add container IDs for dynamic content updates
- [ ] Add CSRF token for AJAX requests

### 3.2 Admin Tournament Template
- [ ] Add data attributes for AJAX
- [ ] Add container IDs

### 3.3 Admin Voucher Template
- [ ] Add data attributes for AJAX
- [ ] Add container IDs

## Phase 4: Prize Pool & Progress Bar (Frenoffice)

- [ ] Add AJAX call to update prize pool on tournament cards
- [ ] Add progress bar showing vouchers sold vs max players
- [ ] Style the progress bar
