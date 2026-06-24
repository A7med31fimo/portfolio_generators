/**
 * Next.js ↔ Laravel Integration Examples
 * =========================================
 * These are ready-to-use examples for every auth endpoint.
 * Copy the relevant sections into your Next.js components.
 *
 * Prerequisites:
 *   1. Copy api-client.ts and auth.service.ts to services/
 *   2. Add to .env.local:
 *      NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
 */

// ─── EXAMPLE 1: REGISTER ──────────────────────────────────────────────────────
//
// components/auth/register-form.tsx
// ----------------------------------

/*
"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useRouter } from "next/navigation";
import { register } from "@/services/auth.service";
import { ApiError } from "@/services/api-client";

const schema = z.object({
  name:                  z.string().min(2).max(60),
  username:              z.string().min(3).max(30).regex(/^[a-z0-9-]+$/),
  email:                 z.string().email(),
  password:              z.string().min(8).regex(/[A-Z]/).regex(/[0-9]/),
  password_confirmation: z.string(),
}).refine(d => d.password === d.password_confirmation, {
  message: "Passwords do not match",
  path: ["password_confirmation"],
});

type FormData = z.infer<typeof schema>;

export function RegisterForm() {
  const router = useRouter();
  const { register: field, handleSubmit, setError, formState: { errors, isSubmitting } } =
    useForm<FormData>({ resolver: zodResolver(schema) });

  const onSubmit = async (data: FormData) => {
    try {
      const result = await register(data);
      // Token is stored automatically in localStorage
      console.log("Registered:", result.user.username);
      router.push("/en/dashboard");
    } catch (error) {
      if (error instanceof ApiError && error.errors) {
        // Map Laravel field errors to react-hook-form
        Object.entries(error.errors).forEach(([field, messages]) => {
          setError(field as keyof FormData, { message: messages[0] });
        });
      }
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <input {...field("name")}     placeholder="Full name" />
      {errors.name && <span>{errors.name.message}</span>}

      <input {...field("username")} placeholder="username" />
      {errors.username && <span>{errors.username.message}</span>}

      <input {...field("email")}    type="email" placeholder="Email" />
      {errors.email && <span>{errors.email.message}</span>}

      <input {...field("password")} type="password" placeholder="Password" />
      {errors.password && <span>{errors.password.message}</span>}

      <input {...field("password_confirmation")} type="password" placeholder="Confirm" />
      {errors.password_confirmation && <span>{errors.password_confirmation.message}</span>}

      <button type="submit" disabled={isSubmitting}>
        {isSubmitting ? "Creating account…" : "Create account"}
      </button>
    </form>
  );
}
*/

// ─── EXAMPLE 2: LOGIN ─────────────────────────────────────────────────────────
//
// components/auth/login-form.tsx
// --------------------------------

/*
"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { login } from "@/services/auth.service";
import { ApiError } from "@/services/api-client";

export function LoginForm() {
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    const formData = new FormData(e.currentTarget);

    try {
      const result = await login({
        email:       formData.get("email") as string,
        password:    formData.get("password") as string,
        remember:    formData.get("remember") === "on",
        device_name: "Next.js Web",
      });

      console.log("Logged in as:", result.user.username);
      // Token is stored in localStorage automatically
      router.push("/en/dashboard");
      router.refresh(); // flush Next.js RSC cache
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message); // "These credentials do not match our records."
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {error && <div role="alert">{error}</div>}
      <input name="email"    type="email"    required placeholder="Email" />
      <input name="password" type="password" required placeholder="Password" />
      <label>
        <input name="remember" type="checkbox" />
        Remember me
      </label>
      <button type="submit" disabled={loading}>
        {loading ? "Signing in…" : "Sign in"}
      </button>
    </form>
  );
}
*/

// ─── EXAMPLE 3: LOGOUT ───────────────────────────────────────────────────────
//
// Standalone logout — use in a button or nav component
// --------------------------------------------------------

/*
"use client";

import { useRouter } from "next/navigation";
import { logout } from "@/services/auth.service";

export function LogoutButton() {
  const router = useRouter();

  const handleLogout = async () => {
    await logout();                  // revokes current device token
    // await logout(true);           // revokes ALL device tokens
    router.push("/en/login");
    router.refresh();
  };

  return <button onClick={handleLogout}>Sign out</button>;
}
*/

// ─── EXAMPLE 4: GET AUTHENTICATED USER (SERVER COMPONENT) ────────────────────
//
// app/(dashboard)/layout.tsx
// ----------------------------
// In a Server Component, read the token from cookies or the HTTP header.
// Below is the recommended pattern using a server-side fetch helper.

/*
// lib/get-current-user.server.ts
import { cookies } from "next/headers";

const API_BASE = process.env.API_URL ?? "http://localhost:8000/api/v1";

export async function getCurrentUserServer() {
  const cookieStore = await cookies();
  // If you store the token in a cookie (httpOnly) rather than localStorage:
  const token = cookieStore.get("nextdev_token")?.value;

  if (!token) return null;

  const res = await fetch(`${API_BASE}/auth/me`, {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept:        "application/json",
    },
    cache: "no-store",
  });

  if (!res.ok) return null;

  const json = await res.json();
  return json.data?.user ?? null;
}

// app/(dashboard)/layout.tsx
import { redirect } from "next/navigation";
import { getCurrentUserServer } from "@/lib/get-current-user.server";

export default async function DashboardLayout({ children }) {
  const user = await getCurrentUserServer();

  if (!user) {
    redirect("/en/login?callbackUrl=/en/dashboard");
  }

  return (
    <div>
      <p>Welcome, {user.username}</p>
      {children}
    </div>
  );
}
*/

// ─── EXAMPLE 5: GET ME (CLIENT COMPONENT with useEffect) ─────────────────────

/*
"use client";

import { useEffect, useState } from "react";
import { getMe, AuthUser } from "@/services/auth.service";

export function UserAvatar() {
  const [user, setUser] = useState<AuthUser | null>(null);

  useEffect(() => {
    getMe().then(setUser);
  }, []);

  if (!user) return <div>Loading…</div>;
  return <div>{user.name} (@{user.username})</div>;
}
*/

// ─── EXAMPLE 6: FORGOT PASSWORD ──────────────────────────────────────────────

/*
"use client";

import { useState } from "react";
import { forgotPassword } from "@/services/auth.service";

export function ForgotPasswordForm() {
  const [sent, setSent] = useState(false);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const email = (e.currentTarget.elements.namedItem("email") as HTMLInputElement).value;
    await forgotPassword(email);  // always resolves — never reveals if email exists
    setSent(true);
  };

  if (sent) {
    return <p>If that email is registered, a reset link has been sent.</p>;
  }

  return (
    <form onSubmit={handleSubmit}>
      <input name="email" type="email" required placeholder="your@email.com" />
      <button type="submit">Send reset link</button>
    </form>
  );
}
*/

// ─── EXAMPLE 7: RESET PASSWORD ───────────────────────────────────────────────

/*
"use client";

import { useSearchParams, useRouter } from "next/navigation";
import { resetPassword } from "@/services/auth.service";
import { ApiError } from "@/services/api-client";

export function ResetPasswordForm() {
  const params = useSearchParams();
  const router = useRouter();
  const token = params.get("token") ?? "";
  const email = params.get("email") ?? "";
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);

    try {
      await resetPassword({
        token,
        email,
        password:              fd.get("password") as string,
        password_confirmation: fd.get("password_confirmation") as string,
      });
      router.push("/en/login?reset=success");
    } catch (err) {
      if (err instanceof ApiError) setError(err.message);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {error && <div>{error}</div>}
      <input name="password"              type="password" placeholder="New password" />
      <input name="password_confirmation" type="password" placeholder="Confirm" />
      <button type="submit">Reset password</button>
    </form>
  );
}
*/

// ─── EXAMPLE 8: USERNAME AVAILABILITY CHECK ───────────────────────────────────

/*
"use client";

import { useEffect, useState } from "react";
import { checkUsername } from "@/services/auth.service";

export function UsernameField() {
  const [username, setUsername] = useState("");
  const [status, setStatus] = useState<"idle"|"checking"|"available"|"taken">("idle");

  useEffect(() => {
    if (username.length < 3) { setStatus("idle"); return; }

    setStatus("checking");
    const timer = setTimeout(async () => {
      const result = await checkUsername(username);
      setStatus(result.available ? "available" : "taken");
    }, 400);

    return () => clearTimeout(timer);
  }, [username]);

  return (
    <div>
      <input
        value={username}
        onChange={e => setUsername(e.target.value.toLowerCase())}
        placeholder="username"
      />
      {status === "checking"  && <span>Checking…</span>}
      {status === "available" && <span style={{color:"green"}}>✓ Available</span>}
      {status === "taken"     && <span style={{color:"red"}}>✗ Taken</span>}
      {username && <small>nextdev.io/{username}</small>}
    </div>
  );
}
*/
