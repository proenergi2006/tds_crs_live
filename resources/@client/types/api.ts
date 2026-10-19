export interface ApiResponse<T> {
  success: true
  message: string
  data: T
}

export interface ApiPaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface ApiPaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  links: { url: string | null; label: string; active: boolean }[]
  path: string
  per_page: number
  to: number | null
  total: number
}

export interface ApiPaginatedResponse<T> extends ApiResponse<T[]> {
  links: ApiPaginationLinks
  meta: ApiPaginationMeta
}
