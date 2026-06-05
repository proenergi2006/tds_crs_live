import axios from "axios";

const http = axios.create({
  baseURL: "/api",
  headers: {
    Accept: "application/json",
  },
});

export function createResourceApi(endpoint: string) {
  return {
    getAll(params?: Record<string, any>) {
      return http.get(endpoint, { params });
    },

    getById(id: number | string) {
      return http.get(`${endpoint}/${id}`);
    },

    store(payload: any) {
      return http.post(endpoint, payload);
    },

    update(id: number | string, payload: any) {
      return http.put(`${endpoint}/${id}`, payload);
    },

    destroy(id: number | string) {
      return http.delete(`${endpoint}/${id}`);
    },
  };
}
