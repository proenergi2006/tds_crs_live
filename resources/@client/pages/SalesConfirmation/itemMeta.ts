export function joinMeta(...parts: Array<string | null | undefined>): string {
  return parts
    .map(part => String(part ?? '').trim())
    .filter(part => part !== '' && part !== '.' && part !== '-')
    .join(' · ')
}
