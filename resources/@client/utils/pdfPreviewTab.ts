/**
 * Buka tab baru + tampilkan loading state. Panggil sinkron dari event klik
 * (bukan setelah await) supaya tidak diblokir popup blocker.
 */
export function openPdfLoadingTab(): Window | null {
  const tab = window.open('', '_blank')
  if (!tab) return null

  tab.document.title = 'Menyiapkan dokumen...'
  tab.document.head.insertAdjacentHTML('beforeend', `<style>
    html, body {
      margin: 0;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: #f8fafc;
      color: #475569;
    }
    .wrap { display: flex; flex-direction: column; align-items: center; gap: 12px; }
    .spinner {
      width: 32px;
      height: 32px;
      border: 3px solid #cbd5e1;
      border-top-color: #2563eb;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>`)
  tab.document.body.innerHTML = `<div class="wrap">
    <div class="spinner"></div>
    <div>Menyiapkan dokumen PDF...</div>
  </div>`

  return tab
}
