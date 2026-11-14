/**
 * QR & Labels page: live HTML preview in an iframe (same view as the PDF),
 * plus Download PDF and Print actions built from the same query string.
 */
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('labelForm');
  if (!form) return;

  const frame = document.getElementById('previewFrame');
  const empty = document.getElementById('previewEmpty');
  const select = document.getElementById('products');

  const query = () => new URLSearchParams(new FormData(form)).toString();
  const hasProducts = () => Array.from(select.selectedOptions).length > 0;

  let timer;
  const refresh = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const show = hasProducts();
      frame.classList.toggle('d-none', !show);
      empty.classList.toggle('d-none', show);
      if (show) frame.src = `${form.dataset.previewUrl}?${query()}`;
    }, 250);
  };

  form.addEventListener('change', refresh);
  form.addEventListener('input', refresh);

  if (window.jQuery && jQuery.fn.select2) {
    jQuery(select)
      .select2({
        placeholder: select.dataset.placeholder,
        width: '100%',
        // Products are searched on the server, a page at a time, so large catalogues stay fast.
        ajax: {
          url: select.dataset.searchUrl,
          dataType: 'json',
          delay: 250,
          data: params => ({ q: params.term || '', page: params.page || 1 })
        }
      })
      .on('change', refresh);
  }

  form.querySelector('[data-action="pdf"]').addEventListener('click', () => {
    if (hasProducts()) window.location = `${form.dataset.pdfUrl}?${query()}`;
  });

  form.querySelector('[data-action="print"]').addEventListener('click', () => {
    if (hasProducts()) window.open(`${form.dataset.previewUrl}?${query()}&print=1`, '_blank');
  });

  refresh();
});
