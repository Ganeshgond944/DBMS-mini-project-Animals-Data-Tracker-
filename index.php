<?php
// admin/index.php
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin - Animal Data Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f7f9fc; }
    .card { box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06); }
    .form-label { font-weight: 600; }
  </style>
</head>
<body class="p-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="m-0">Admin — Animal Data Tracker</h3>
      <a class="btn btn-outline-secondary" href="../index.html" target="_blank">Open Public UI</a>
    </div>

    <div class="row g-4">
      <!-- Animal Form -->
      <div class="col-lg-5">
        <div class="card p-3">
          <h5 class="mb-3">Add / Edit Animal</h5>

          <form id="animalForm" novalidate>
            <input type="hidden" id="id" name="id">

            <div class="mb-3">
              <label for="area_id" class="form-label">Area</label>
              <select id="area_id" name="area_id" class="form-select" required>
                <option value="">Loading areas...</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="common_name" class="form-label">Common Name</label>
              <input id="common_name" name="common_name" class="form-control" required>
            </div>

            <div class="mb-3">
              <label for="species" class="form-label">Species (scientific)</label>
              <input id="species" name="species" class="form-control">
            </div>

            <div class="row g-2">
              <div class="col-6 mb-3">
                <label for="count_est" class="form-label">Count Estimate</label>
                <input id="count_est" name="count_est" type="number" min="0" class="form-control">
              </div>
              <div class="col-6 mb-3">
                <label for="average_age_years" class="form-label">Avg Age (years)</label>
                <input id="average_age_years" name="average_age_years" step="0.1" class="form-control">
              </div>
            </div>

            <div class="mb-3">
              <label for="last_seen" class="form-label">Last Seen</label>
              <input id="last_seen" name="last_seen" type="date" class="form-control">
            </div>

            <div class="mb-3">
              <label for="notes" class="form-label">Notes</label>
              <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">Save</button>
              <button type="button" class="btn btn-outline-secondary" id="resetBtn">Reset</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Records Table -->
      <div class="col-lg-7">
        <div class="card p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0">Existing Records</h5>
            <button class="btn btn-sm btn-outline-primary" id="refreshBtn">Refresh</button>
          </div>

          <div class="table-responsive">
            <table id="recordsTable" class="table table-sm table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Common Name</th>
                  <th>Area</th>
                  <th>Count</th>
                  <th style="width:180px">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr><td colspan="4" class="text-muted">Loading…</td></tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>

    <footer class="mt-4 text-muted small">
      Tip: Add authentication before using this admin on a public server.
    </footer>
  </div>

  <script src="app-admin.js"></script>
  <script>
    // simple client-side event delegation for Edit/Delete buttons
    document.getElementById('recordsTable').addEventListener('click', async (e) => {
      if (e.target.classList.contains('btn-secondary')) {
        const id = e.target.dataset.id;
        window.edit(id);
      } else if (e.target.classList.contains('btn-danger')) {
        const id = e.target.dataset.id;
        window.del(id);
      }
    });

    document.getElementById('resetBtn').addEventListener('click', () => {
      document.getElementById('animalForm').reset();
    });

    document.getElementById('refreshBtn').addEventListener('click', async () => {
      await loadAreasSelect();
      await loadRecords();
    });
  </script>
</body>
</html>
