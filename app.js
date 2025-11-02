const baseApi = '/animal-tracker/api/animals.php';


// Utility: safe fetch with JSON + error handling
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


async function fetchAreas() {
return safeFetch(baseApi + '?action=areas');
}


async function fetchAnimals(area_id) {
return safeFetch(baseApi + '?action=animals&area_id=' + encodeURIComponent(area_id));
}


async function fetchAreaSummary(area_id) {
return safeFetch(baseApi + '?action=areasummary&area_id=' + encodeURIComponent(area_id));
}


function renderAreas(areas) {
const list = document.getElementById('areasList');
list.innerHTML = '';
if (!Array.isArray(areas) || areas.length === 0) {
const li = document.createElement('li');
li.className = 'list-group-item';
li.textContent = 'No areas available';
list.appendChild(li);
return;
}


areas.forEach(a => {
const li = document.createElement('li');
li.className = 'list-group-item';
li.textContent = `${a.name} — ${a.total_animals} animals`;
li.dataset.areaId = a.id; // store id for easy access
li.addEventListener('click', () => loadArea(a.id, a.name, li));
list.appendChild(li);
});
}