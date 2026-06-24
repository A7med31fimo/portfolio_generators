/**
 * Auth service for the Next.js frontend.
 * All calls go to the Laravel API via apiClient.
 *
 * Copy this file to: services/auth.service.ts in your Next.js project.
 * Update NEXT_PUBLIC_API_URL in .env.local to point to your Laravel server.
 */

import { apiClient, tokenStorage, ApiError } from "./api-client";

// ─── Types matching Laravel UserResource ──────────────────────────────────────

export type UserProfile = {
  bio: string | null;
  headline: string | null;
  avatar: string | null;
  location: string | null;
  phone: string | null;
  locale: string;
  theme: string;
  accent_color: string;
  is_published: boolean;
};

export type AuthUser = {
  id: number;
  name: string;
  username: string;
  email: string;
  email_verified: boolean;
  email_verified_at: string | null;
  created_at: string;
  profile: UserProfile | null;
};

export type AuthResult = {
  user: AuthUser;
  token: string;
  expires_at: string | null;
};

// ─── Register ────────────────────────────────────────────────────────────────

export type RegisterData = {
  name: string;
  username: string;
  email: string;
  password: string;
  password_confirmation: string;
  locale?: "en" | "ar";
};

/**
 * Create a new account and receive a token immediately.
 *
 * On success: stores the token and returns the user.
 * On failure: throws ApiError with .errors for field-level messages.
 */
export async function register(data: RegisterData): Promise<AuthResult> {
  const response = await apiClient.post<AuthResult>("/auth/register", data);

  if (response.data?.token) {
    tokenStorage.set(response.data.token);
  }

  return response.data!;
}

// ─── Login ───────────────────────────────────────────────────────────────────

export type LoginData = {
  email: string;
  password: string;
  remember?: boolean;
  device_name?: string;
};

/**
 * Authenticate with credentials.
 *
 * On success: stores the token and returns the user.
 * On 401: throws ApiError("These credentials do not match our records.")
 */
export async function login(data: LoginData): Promise<AuthResult> {
  const response = await apiClient.post<AuthResult>("/auth/login", {
    ...data,
    device_name: data.device_name ?? "Next.js Web",
  });

  if (response.data?.token) {
    tokenStorage.set(response.data.token);
  }

  return response.data!;
}

// ─── Logout ──────────────────────────────────────────────────────────────────

/**
 * Revoke the current token.
 * Pass allDevices: true to sign out from every device simultaneously.
 */
export async function logout(allDevices = false): Promise<void> {
  try {
    await apiClient.post("/auth/logout", { all_devices: allDevices });
  } finally {
    // Always clear the local token — even if the API request fails
    tokenStorage.remove();
  }
}

// ─── Get current user ─────────────────────────────────────────────────────────

/**
 * Fetch the current authenticated user with their profile.
 * Returns null if the token is missing or expired.
 */
export async function getMe(): Promise<AuthUser | null> {
  const token = tokenStorage.get();
  if (!token) return null;

  try {
    const response = await apiClient.get<{ user: AuthUser }>("/auth/me");
    return response.data?.user ?? null;
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      tokenStorage.remove(); // Token expired — clean up
      return null;
    }
    throw error;
  }
}

// ─── Forgot password ─────────────────────────────────────────────────────────

/**
 * Request a password reset link.
 * Always returns true — the API doesn't reveal if the email exists.
 */
export async function forgotPassword(email: string): Promise<boolean> {
  await apiClient.post("/auth/forgot-password", { email });
  return true;
}

// ─── Reset password ───────────────────────────────────────────────────────────

export type ResetPasswordData = {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
};

/**
 * Reset the password using a token from the reset email.
 * On success, the API revokes all existing tokens — user must log in again.
 */
export async function resetPassword(data: ResetPasswordData): Promise<void> {
  await apiClient.post("/auth/reset-password", data);
}

// ─── Username check ───────────────────────────────────────────────────────────

type UsernameCheckResult = {
  available: boolean;
  reason: "taken" | "reserved" | "too_short" | "too_long" | "invalid_chars" | "invalid_format" | null;
};

/**
 * Check if a username is available.
 * Debounce calls on the component side before calling this.
 */
export async function checkUsername(username: string): Promise<UsernameCheckResult> {
  const response = await apiClient.get<UsernameCheckResult>(
    `/auth/check-username?username=${encodeURIComponent(username)}`
  );
  return response.data ?? { available: false, reason: null };
}
