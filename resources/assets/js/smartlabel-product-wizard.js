/**
 * Add / edit product wizard: bs-stepper navigation, spec fields loaded per
 * category, light required-field checks per step and a review summary.
 */
document.addEventListener('DOMContentLoaded', () => {
  const wizardEl = document.getElementById('productWizard');
  if (!wizardEl || typeof Stepper === 'undefined') return;

  const form = document.getElementById('productForm');
  const stepper = new Stepper(wizardEl, { linear: false });
  const i18n = document.getElementById('i18n').dataset;
  const specContainer = document.getElementById('specFields');
  const categorySelect = document.getElementById('category_id');
  const values = JSON.parse(wizardEl.dataset.specValues || '{}');
  const specErrors = JSON.parse(wizardEl.dataset.specErrors || '{}');
  let fields = [];

  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

  const fieldHtml = f => {
    const name = `specs[${f.key}]`;
    const id = `spec_${f.key}`;
    const value = values[f.key] ?? '';
    const invalid = specErrors[f.key] ? ' is-invalid' : '';
    const req = f.required ? ' required' : '';
    const label = `<label class="form-label" for="${id}">${esc(f.label)}${f.unit ? ` (${esc(f.unit)})` : ''}${f.required ? ' *' : ''}</label>`;
    let input;

    if (f.type === 'select') {
      input = `<select id="${id}" name="${name}" class="form-select${invalid}"${req}><option value="">${esc(i18n.choose)}</option>${f.options
        .map(o => `<option value="${esc(o)}"${o === value ? ' selected' : ''}>${esc(o)}</option>`)
        .join('')}</select>`;
    } else if (f.type === 'boolean') {
      const v = String(value).toLowerCase();
      const isYes = ['1', 'true', 'yes', i18n.yes.toLowerCase()].includes(v);
      const isNo = ['0', 'false', 'no', i18n.no.toLowerCase()].includes(v);
      input = `<select id="${id}" name="${name}" class="form-select${invalid}"${req}><option value="">${esc(i18n.choose)}</option><option value="1"${isYes ? ' selected' : ''}>${esc(i18n.yes)}</option><option value="0"${isNo ? ' selected' : ''}>${esc(i18n.no)}</option></select>`;
    } else {
      const type = f.type === 'number' ? 'number" step="any" min="0' : 'text';
      input = `<input type="${type}" id="${id}" name="${name}" value="${esc(value)}" class="form-control${invalid}"${req}>`;
    }

    const error = specErrors[f.key] ? `<div class="invalid-feedback">${esc(specErrors[f.key])}</div>` : '';
    return `<div class="col-md-6 col-lg-4">${label}${input}${error}</div>`;
  };

  const loadFields = async () => {
    // Keep what the user already typed when switching categories.
    specContainer.querySelectorAll('[name^="specs["]').forEach(el => {
      values[el.name.slice(6, -1)] = el.value;
    });

    if (!categorySelect.value) {
      fields = [];
      return;
    }

    specContainer.innerHTML = `<div class="col-12 text-body-secondary">${esc(i18n.loading)}</div>`;
    const res = await fetch(wizardEl.dataset.fieldsUrl.replace('__ID__', categorySelect.value), {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    fields = res.ok ? (await res.json()).fields : [];
    specContainer.innerHTML = fields.length
      ? fields.map(fieldHtml).join('')
      : `<div class="col-12 text-body-secondary">${esc(i18n.empty)}</div>`;
  };

  const validateStep = stepEl => {
    let ok = true;
    stepEl.querySelectorAll('[required]').forEach(el => {
      const empty = !String(el.value).trim();
      el.classList.toggle('is-invalid', empty);
      if (empty) {
        ok = false;
        if (!el.nextElementSibling?.classList.contains('invalid-feedback')) {
          el.insertAdjacentHTML('afterend', `<div class="invalid-feedback">${esc(i18n.required)}</div>`);
        }
      }
    });
    return ok;
  };

  const buildReview = () => {
    const rows = [];
    const text = id => {
      const el = document.getElementById(id);
      return el?.tagName === 'SELECT' ? el.options[el.selectedIndex]?.text : el?.value;
    };
    const add = (label, value) => value && rows.push(`<dt class="col-sm-5 fw-normal text-body-secondary">${esc(label)}</dt><dd class="col-sm-7">${esc(value)}</dd>`);
    const labelFor = id => document.querySelector(`label[for="${id}"]`)?.textContent.replace(/\s*\*$/, '').trim();

    ['category_id', 'branch_id', 'brand', 'model', 'price', 'condition', 'status', 'quantity', 'warranty_months', 'serial_number'].forEach(id => {
      let v = text(id);
      if (id === 'price' && v) v = 'TZS ' + Number(String(v).replace(/\D/g, '')).toLocaleString('en-US');
      add(labelFor(id), v);
    });
    fields.forEach(f => add(f.label, text(`spec_${f.key}`) && text(`spec_${f.key}`) !== i18n.choose ? `${text(`spec_${f.key}`)}${f.unit ? ' ' + f.unit : ''}` : ''));

    document.getElementById('reviewList').innerHTML = rows.join('');
  };

  wizardEl.addEventListener('show.bs-stepper', e => {
    if (e.detail.indexStep === 2) buildReview();
  });

  wizardEl.querySelectorAll('[data-wizard-next]').forEach(btn =>
    btn.addEventListener('click', () => {
      if (validateStep(btn.closest('.content'))) stepper.next();
    })
  );
  wizardEl.querySelectorAll('[data-wizard-prev]').forEach(btn => btn.addEventListener('click', () => stepper.previous()));

  form.addEventListener('input', e => {
    if (e.target.classList.contains('is-invalid') && String(e.target.value).trim()) e.target.classList.remove('is-invalid');
  });

  categorySelect.addEventListener('change', loadFields);
  loadFields().then(() => {
    if (wizardEl.dataset.errorStep === '1') stepper.to(2);
  });
});
