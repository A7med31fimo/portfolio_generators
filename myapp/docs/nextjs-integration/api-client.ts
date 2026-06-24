/**
 * Base API client for the Laravel backend.
 *
 * All requests to the Laravel API go through this client.
 * It handles:
 *   - Base URL prefixing
 *   - Authorization Bearer token injection
 *   - Consistent error handling
 *   - The standard { status, message, data, errors } envelope
 *
 * Usage:
 *   import { apiClient } from "@/services/api-client";
 *   const data = await apiClient.post("/auth/login", { email, password });
 */

const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

// ─── Types ────────────────────────────────────────────────────────────────────

export type LaravelResponse<T = unknown> = {
  status: boolean;
  message: string;
  data: T | null;
  errors?: Record<string, string[]>;
};

export class ApiError extends Error {
  constructor(
    public readonly message: string,
    public readonly status: number,
    public readonly errors?: Record<string, string[]>
  ) {
    super(message);
    this.name = "ApiError";
  }
}

// ─── Token storage ────────────────────────────────────────────────────────────

const TOKEN_KEY = "nextdev_token";

export const tokenStorage = {
  get: (): string | null => {
    if (typeof window === "undefined") return null;
    return localStorage.getItem(TOKEN_KEY);
  },
  set: (token: string): void => {
    localStorage.setItem(TOKEN_KEY, token);
  },
  remove: (): void => {
    localStorage.removeItem(TOKEN_KEY);
  },
};

// ─── Core fetch wrapper ───────────────────────────────────────────────────────

type RequestOptions = {
  body?: unknown;
  headers?: Record<string, string>;
  locale?: string;
};

async function request<T>(
  method: string,
  path: string,
  options: RequestOptions = {}
): Promise<LaravelResponse<T>> {
  const token = tokenStorage.get();
  const locale =
    options.locale ??
    (typeof window !== "undefined"
      ? document.documentElement.lang ?? "en"
      : "en");

  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    Accept: "application/json",
    "X-Locale": locale,
    ...options.headers,
  };

  if (token) {
    headers["Authorization"] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE}${path}`, {
    method,
    headers,
    body: options.body ? JSON.stringify(options.body) : undefined,
  });

  const json: LaravelResponse<T> = await response.json();

  if (!response.ok) {
    throw new ApiError(
      json.message ?? "An unexpected error occurred.",
      response.status,
      json.errors
    );
  }

  return json;
}

// ─── HTTP methods ─────────────────────────────────────────────────────────────

export const apiClient = {
  get: <T>(path: string, options?: RequestOptions) =>
    request<T>("GET", path, options),

  post: <T>(path: string, body?: unknown, options?: RequestOptions) =>
    request<T>("POST", path, { ...options, body }),

  patch: <T>(path: string, body?: unknown, options?: RequestOptions) =>
    request<T>("PATCH", path, { ...options, body }),

  put: <T>(path: string, body?: unknown, options?: RequestOptions) =>
    request<T>("PUT", path, { ...options, body }),

  delete: <T>(path: string, options?: RequestOptions) =>
    request<T>("DELETE", path, options),
};
