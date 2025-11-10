<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

date_default_timezone_set('America/Los_Angeles');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Party Bookings Admin</title>
  <style>
    body { font-family: system-ui, Arial, sans-serif; margin: 20px; }
    .filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; font-size: 14px; }
    th { background: #f5f5f5; text-align: left; }
    .pagination { margin-top: 10px; display: flex; gap: 8px; align-items: center; }
    .stats { margin-bottom: 12px; }
  </style>
</head>
<body>
  <h1>Party Bookings</h1>

  <div class="stats" id="stats"></div>

  <div class="filters">
    <input type="date" id="date_from" />
    <input type="date" id="date_to" />
    <select id="status">
      <option value="">All Statuses</option>
      <option value="pending">Pending</option>
      <option value="confirmed">Confirmed</option>
      <option value="completed">Completed</option>
      <option value="cancelled">Cancelled</option>
    </select>
    <input type="text" id="q" placeholder="Search name, email, reference" />
    <button id="applyFilters">Apply</button>
    <a id="exportCsv" href="#">Export CSV</a>
  </div>

  <table>
    <thead>
      <tr>
        <th>Ref</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Date</th>
        <th>Time</th>
        <th>Guests</th>
        <th>Status</th>
        <th>Updated</th>
      </tr>
    </thead>
    <tbody id="rows"></tbody>
  </table>

  <div class="pagination">
    <button id="prev">Prev</button>
    <span id="page"></span>
    <button id="next">Next</button>
  </div>

<script>
let page = 1, perPage = 20;

function qs() {
  const p = new URLSearchParams();
  const s = document.getElementById('status').value;
  const df = document.getElementById('date_from').value;
  const dt = document.getElementById('date_to').value;
  const q = document.getElementById('q').value;
  if (s) p.set('status', s);
  if (df) p.set('date_from', df);
  if (dt) p.set('date_to', dt);
  if (q) p.set('q', q);
  p.set('page', page);
  p.set('per_page', perPage);
  return p.toString();
}

async function load() {
  const res = await fetch('api/bookings.php?' + qs());
  const json = await res.json();
  const tbody = document.getElementById('rows');
  tbody.innerHTML = '';
  json.data.forEach(b => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${b.booking_reference}</td>
      <td>${b.customer_name}</td>
      <td>${b.customer_email}</td>
      <td>${b.customer_phone}</td>
      <td>${b.party_date}</td>
      <td>${b.party_time}</td>
      <td>${b.guest_count ?? ''}</td>
      <td>
        <select data-id="${b.id}" class="status">
          ${['pending','confirmed','completed','cancelled'].map(x => `<option ${b.status===x?'selected':''}>${x}</option>`).join('')}
        </select>
      </td>
      <td>${b.created_at}</td>
    `;
    tbody.appendChild(tr);
  });
  document.getElementById('page').textContent = `Page ${json.pagination.page} of ${Math.ceil(json.pagination.total / json.pagination.per_page)}`;
}

async function loadStats() {
  const res = await fetch('api/bookings-stats.php');
  const s = await res.json();
  document.getElementById('stats').textContent = `Total: ${s.total} | Upcoming: ${s.upcoming} | Month to date: ${s.month_to_date}`;
}

addEventListener('change', async (e) => {
  if (e.target.classList.contains('status')) {
    const id = e.target.getAttribute('data-id');
    const status = e.target.value;
    await fetch('api/bookings.php', { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, status }) });
    load();
  }
});

document.getElementById('applyFilters').onclick = () => { page = 1; load(); };
document.getElementById('prev').onclick = () => { if (page>1) { page--; load(); } };
document.getElementById('next').onclick = () => { page++; load(); };
document.getElementById('exportCsv').onclick = (e) => { e.preventDefault(); window.location = 'api/bookings-export.php?' + qs(); };

load();
loadStats();
</script>
</body>
</html>