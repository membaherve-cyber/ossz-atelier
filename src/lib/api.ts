import { NextResponse } from "next/server";
import { ZodError, type ZodType } from "zod";

/** Typed error that API routes can throw; converted to a JSON response by withApi(). */
export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public details?: unknown
  ) {
    super(message);
  }
}

export function json(data: unknown, status = 200) {
  return NextResponse.json(data as object, { status });
}

/** Parse & validate a JSON request body with a zod schema. */
export async function parseBody<T>(req: Request, schema: ZodType<T>): Promise<T> {
  let raw: unknown;
  try {
    raw = await req.json();
  } catch {
    throw new ApiError(400, "Invalid JSON body");
  }
  const result = schema.safeParse(raw);
  if (!result.success) {
    throw new ApiError(400, "Validation failed", {
      issues: result.error.issues.map((i) => ({
        path: i.path.join("."),
        message: i.message,
      })),
    });
  }
  return result.data;
}

/** Parse & validate URL search params with a zod schema. */
export function parseQuery<T>(req: Request, schema: ZodType<T>): T {
  const params: Record<string, string> = {};
  new URL(req.url).searchParams.forEach((value, key) => {
    if (value !== "") params[key] = value;
  });
  const result = schema.safeParse(params);
  if (!result.success) {
    throw new ApiError(400, "Invalid query parameters", {
      issues: result.error.issues.map((i) => ({
        path: i.path.join("."),
        message: i.message,
      })),
    });
  }
  return result.data;
}

/** Wrap a route handler with uniform error handling. */
export function withApi<Args extends unknown[]>(
  handler: (req: Request, ...args: Args) => Promise<Response>
) {
  return async (req: Request, ...args: Args): Promise<Response> => {
    try {
      return await handler(req, ...args);
    } catch (err) {
      if (err instanceof ApiError) {
        return NextResponse.json(
          { error: err.message, details: err.details ?? null },
          { status: err.status }
        );
      }
      if (err instanceof ZodError) {
        return NextResponse.json({ error: "Validation failed" }, { status: 400 });
      }
      // Missing table/column (Prisma P2021/P2022) — happens transiently while
      // the database schema is being (re)created at startup. Report it as a
      // retryable state instead of a scary 500.
      if (
        err instanceof Error &&
        "code" in err &&
        ((err as { code?: string }).code === "P2021" ||
          (err as { code?: string }).code === "P2022")
      ) {
        return NextResponse.json(
          { error: "The database is being initialised. Please retry in a few seconds." },
          { status: 503 }
        );
      }
      console.error("[api]", err);
      return NextResponse.json({ error: "Internal server error" }, { status: 500 });
    }
  };
}
