/**
 * `navigator.clipboard` (Clipboard API) hanya tersedia di secure context
 * (HTTPS atau localhost). Project ini kadang diakses lewat domain .test
 * via HTTP biasa (bukan HTTPS), jadi navigator.clipboard bisa undefined
 * dan writeText() akan throw. Fallback ke document.execCommand('copy')
 * lewat textarea tersembunyi supaya tetap berfungsi di insecure context.
 */
async function copyToClipboard(text: string): Promise<boolean> {
  if (navigator.clipboard && window.isSecureContext) {
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch {
      // lanjut ke fallback di bawah
    }
  }

  const textarea = document.createElement("textarea");
  textarea.value = text;
  textarea.style.position = "fixed";
  textarea.style.left = "-9999px";
  textarea.style.top = "0";
  document.body.appendChild(textarea);
  textarea.focus();
  textarea.select();

  let succeeded = false;
  try {
    succeeded = document.execCommand("copy");
  } catch {
    succeeded = false;
  } finally {
    document.body.removeChild(textarea);
  }

  return succeeded;
}

export { copyToClipboard };
