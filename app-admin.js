const baseApi = '/animal-tracker/api/animals.php';

// safeFetch reused from public app for consistent error handling
async function safeFetch(url, options = {}) {
  try {
    const res = await fetch(url, options);
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`HTTP ${res.status}: ${text}`);
    }
    return await res.json();
  } catch (err) {
    console.error('Fetch error:', err);
    throw err;
  }
}

let areasCache = null; // cached areas for name lookup

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function areaNameById(id) {
  if (!areasCache) return id; // fallback to ID
  const a = areasCache.find(x => Number(x.id) === Number(id));
  return a ? a.name : id;
}

document.addEventListener('DOMContentLoaded', () => {
  // DOM elements
  const animalForm = document.getElementById('animalForm');
  const recordsTbody = document.querySelector('#recordsTable tbody');
  const areaSelect = document.getElementById('area_id');
  const submitBtn = animalForm.querySelector('button[type="submit"]');

  async function loadAreasSelect() {
    try {
      const areas = await safeFetch(baseApi + '?action=areas');
      areasCache = Array.isArray(areas) ? areas : [];
      areaSelect.innerHTML = '';
      if (areasCache.length === 0) {
        areaSelect.innerHTML = '<option value="">(no areas)</option>';
        return;
      }
      areasCache.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id;
        opt.textContent = a.name;
        areaSelect.appendChild(opt);
      });
    } catch (err) {
      areaSelect.innerHTML = '<option value="">(failed to load areas)</option>';
    }
  }

  async function loadRecords() {
    try {
      if (!areasCache) await loadAreasSelect();
      const res = await safeFetch(baseApi + '?action=animals');
      const arr = Array.isArray(res) ? res : [];
      recordsTbody.innerHTML = '';
      if (arr.length === 0) {
        recordsTbody.innerHTML = '<tr><td colspan="4" class="text-muted">No records found.</td></tr>';
        return;
      }
      for (const r of arr) {
        const tr = document.createElement('tr');
        const areaName = escapeHtml(areaNameById(r.area_id));
        tr.innerHTML = `<td>${escapeHtml(r.common_name)}</td>
                        <td>${areaName}</td>
                        <td>${Number(r.count_est) || 0}</td>
                        <td>
                          <button class="btn btn-sm btn-secondary me-1" data-edit-id="${r.id}">Edit</button>
                          <button class="btn btn-sm btn-danger" data-del-id="${r.id}">Delete</button>
                        </td>`;
        recordsTbody.appendChild(tr);
      }
    } catch (err) {
      recordsTbody.innerHTML = '<tr><td colspan="4" class="text-danger">Failed to load records. See console.</td></tr>';
    }
  }

  // Use event delegation instead of inline onclick handlers
  recordsTbody.addEventListener('click', (ev) => {
    const editBtn = ev.target.closest('[data-edit-id]');
    const delBtn = ev.target.closest('[data-del-id]');
    if (editBtn) {
      const id = editBtn.getAttribute('data-edit-id');
      editRecord(Number(id));
    } else if (delBtn) {
      const id = delBtn.getAttribute('data-del-id');
      deleteRecord(Number(id));
    }
  });

  async function submitForm(e) {
    e.preventDefault();
    const id = document.getElementById('id').value;
    const payload = {
      id: id || undefined,
      area_id: areaSelect.value,
      common_name: document.getElementById('common_name').value,
      species: document.getElementById('species').value,
      count_est: Number(document.getElementById('count_est').value) || 0,
      average_age_years: document.getElementById('average_age_years').value || null,
      last_seen: document.getElementById('last_seen').value || null,
      notes: document.getElementById('notes').value || null
    };

    // Basic client-side validation
    if (!payload.area_id) {
      alert('Please select an area.');
      return;
    }
    if (!payload.common_name || payload.common_name.trim() === '') {
      alert('Please enter a common name.');
      return;
    }

    try {
      submitBtn.disabled = true;
      submitBtn.textContent = id ? 'Saving...' : 'Creating...';

      if (id) {
        await safeFetch(baseApi + '?action=animal', {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        alert('Record updated successfully.');
      } else {
        await safeFetch(baseApi + '?action=animal', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        alert('Record created successfully.');
      }

      animalForm.reset();
      await loadAreasSelect();
      await loadRecords();
    } catch (err) {
      alert('Failed to save record. See console for details.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Save';
    }
  }

  async function editRecord(id) {
    try {
      // For now reuse the animals list (ok for small data). If needed add a single-record API.
      const res = await safeFetch(baseApi + '?action=animals');
      const arr = Array.isArray(res) ? res : [];
      const item = arr.find(x => Number(x.id) === Number(id));
      if (!item) { alert('Record not found'); return; }

      document.getElementById('id').value = item.id;
      areaSelect.value = item.area_id;
      document.getElementById('common_name').value = item.common_name;
      document.getElementById('species').value = item.species || '';
      document.getElementById('count_est').value = item.count_est || '';
      document.getElementById('average_age_years').value = item.average_age_years || '';
      document.getElementById('last_seen').value = item.last_seen || '';
      document.getElementById('notes').value = item.notes || '';

      // move focus to the name field
      document.getElementById('common_name').focus();
    } catch (err) {
      alert('Failed to load record for editing. See console.');
    }
  }

  async function deleteRecord(id) {
    if (!confirm('Delete this record?')) return;
    try {
      await safeFetch(baseApi + '?action=animal&id=' + encodeURIComponent(id), { method: 'DELETE' });
      await loadRecords();
      alert('Record deleted.');
    } catch (err) {
      alert('Failed to delete record. See console for details.');
    }
  }

  // Expose for compatibility (not strictly needed because we use delegation)
  window.edit = editRecord;
  window.del = deleteRecord;

  // Attach form handler and load initial data
  animalForm.addEventListener('submit', submitForm);
  loadAreasSelect();
  loadRecords();
});
